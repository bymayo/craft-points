<?php

namespace bymayo\points\services;

use bymayo\points\elements\PointAward;
use bymayo\points\models\OrderRedemption;
use bymayo\points\Points;
use bymayo\points\records\OrderRedemptionRecord;
use Craft;
use craft\commerce\elements\Order;
use craft\commerce\models\Transaction;
use yii\base\Component;

/**
 * Manages "spend N points off this order" intents and processes them at the
 * appropriate points in the Commerce order lifecycle.
 *
 * Storage shape: one row per order in `points_order_redemptions`.
 * - While the order is unpaid, the row is "pending" (awardId is null).
 * - When the order is paid, points are deducted and the resulting PointAward
 *   id is stored in awardId.
 * - On refund, the deduction may be reversed depending on the configured
 *   refund behaviour setting.
 */
class OrderRedemptions extends Component
{
    public const REDEMPTION_RULE_HANDLE = '__redemption';

    /**
     * Apply (or update) a redemption for an order.
     *
     * @return array{success: bool, error: ?string, redemption: ?OrderRedemption}
     */
    public function apply(int $orderId, int $userId, int $points): array
    {
        $plugin = Points::getInstance();
        if (!$plugin->is(Points::EDITION_PRO) || !$plugin->hasCommerce()) {
            return ['success' => false, 'error' => 'Order redemptions require Pro + Craft Commerce.', 'redemption' => null];
        }

        $settings = $plugin->getSettings();
        $points = max(0, $points);

        if ($points === 0) {
            // Treat zero as a removal.
            $this->remove($orderId);
            return ['success' => true, 'error' => null, 'redemption' => null];
        }

        if ($points < $settings->redemptionMinPoints) {
            return [
                'success' => false,
                'error' => "Minimum {$settings->redemptionMinPoints} {$settings->currencyNamePlural} to redeem.",
                'redemption' => null,
            ];
        }

        $balance = Points::getInstance()->awards->sumForUser($userId);
        // Subtract any existing redemption (we'll replace it) to compute "available balance".
        $existing = $this->getForOrder($orderId);
        if ($existing && !$existing->isDeducted()) {
            // Pending — its points are still in the user's balance.
        }

        if ($points > $balance) {
            return [
                'success' => false,
                'error' => "Not enough {$settings->currencyNamePlural}.",
                'redemption' => null,
            ];
        }

        $order = $this->resolveOrder($orderId);
        if (!$order) {
            return ['success' => false, 'error' => 'Order not found.', 'redemption' => null];
        }

        // Apply the X:Y points → currency conversion. Defensive against zero.
        $pointsCount = max(1, $settings->conversionPointsCount);
        $discount = round($points * $settings->conversionCurrencyUnits / $pointsCount, 2);

        // Cap at max % of the order's *gross* total (before our adjustment).
        $orderTotalBeforeUs = (float) $order->getTotalPrice()
            + ($existing ? (float) $existing->discountAmount : 0.0);
        $maxDiscount = round($orderTotalBeforeUs * ($settings->redemptionMaxOrderPercent / 100), 2);

        if ($discount > $maxDiscount) {
            return [
                'success' => false,
                'error' => "Redemption can't exceed {$settings->redemptionMaxOrderPercent}% of the order total.",
                'redemption' => null,
            ];
        }

        $record = OrderRedemptionRecord::findOne(['orderId' => $orderId]);
        if (!$record) {
            $record = new OrderRedemptionRecord();
            $record->orderId = $orderId;
        }
        // If a row already exists and has been deducted (awardId set), don't allow overwrite.
        if ($record->awardId) {
            return [
                'success' => false,
                'error' => 'This order has already had points deducted and can no longer be changed.',
                'redemption' => null,
            ];
        }

        $record->userId = $userId;
        $record->points = $points;
        $record->discountAmount = (string) $discount;
        $record->save(false);

        $order->recalculate();
        Craft::$app->getElements()->saveElement($order, false);

        return [
            'success' => true,
            'error' => null,
            'redemption' => $this->modelFromRecord($record),
        ];
    }

    public function remove(int $orderId): bool
    {
        $plugin = Points::getInstance();
        if (!$plugin->is(Points::EDITION_PRO) || !$plugin->hasCommerce()) {
            return false;
        }

        $record = OrderRedemptionRecord::findOne(['orderId' => $orderId]);
        if (!$record) return false;
        if ($record->awardId) {
            // Already deducted — can't simply remove.
            return false;
        }
        $record->delete();

        $order = $this->resolveOrder($orderId);
        if ($order) {
            $order->recalculate();
            Craft::$app->getElements()->saveElement($order, false);
        }
        return true;
    }

    public function getForOrder(int $orderId): ?OrderRedemption
    {
        $record = OrderRedemptionRecord::findOne(['orderId' => $orderId]);
        return $record ? $this->modelFromRecord($record) : null;
    }

    /**
     * Called when an order is paid. Deducts the redeemed points from the user's
     * balance and creates a negative PointAward for the audit trail.
     */
    public function processPaidOrder(Order $order): void
    {
        $record = OrderRedemptionRecord::findOne(['orderId' => $order->id]);
        if (!$record || $record->awardId) {
            return;
        }

        $points = (int) $record->points;
        if ($points <= 0) return;

        // Re-check balance — user may have spent points elsewhere since they applied.
        $balance = Points::getInstance()->awards->sumForUser($record->userId);
        $deduction = min($points, $balance);
        if ($deduction <= 0) return;

        $award = $this->createDeductionAward($record->userId, -$deduction);
        if ($award) {
            $record->awardId = $award->id;
            $record->save(false);
        }
    }

    /**
     * Called after a successful refund transaction. Optionally restores points
     * based on the configured behaviour.
     */
    public function processRefund(Transaction $tx): void
    {
        $order = $tx->order;
        if (!$order) return;

        $record = OrderRedemptionRecord::findOne(['orderId' => $order->id]);
        if (!$record || !$record->awardId) return;

        $behaviour = Points::getInstance()->getSettings()->redemptionRefundBehaviour;
        if ($behaviour === 'none') return;

        $refundAmount = (float) $tx->amount;
        // Use the order's *original* total (paid amount + redemption discount).
        $orderTotal = (float) $order->getTotalPrice() + (float) $record->discountAmount;
        if ($orderTotal <= 0) return;

        $proportion = min(1.0, $refundAmount / $orderTotal);

        if ($behaviour === 'restoreFullOnly' && $proportion < 0.99) {
            return;
        }

        $pointsToRestore = (int) floor($record->points * $proportion);
        if ($pointsToRestore <= 0) return;

        $this->createDeductionAward($record->userId, $pointsToRestore);
    }

    /** Bypasses Awards::addAward's rule lookup since we need a guaranteed-positive insert. */
    private function createDeductionAward(int $userId, int $points): ?PointAward
    {
        $rule = Points::getInstance()->rules->getRuleByHandle(self::REDEMPTION_RULE_HANDLE);
        if (!$rule || !$rule->id) {
            return null;
        }
        $award = new PointAward();
        $award->userId = $userId;
        $award->ruleId = $rule->id;
        $award->pointsSnapshot = $points;
        if (!Craft::$app->getElements()->saveElement($award, false)) {
            return null;
        }
        return $award;
    }

    private function resolveOrder(int $orderId): ?Order
    {
        if (!class_exists(Order::class)) return null;
        return Order::find()->id($orderId)->one();
    }

    private function modelFromRecord(OrderRedemptionRecord $r): OrderRedemption
    {
        $m = new OrderRedemption();
        $m->id = (int) $r->id;
        $m->orderId = (int) $r->orderId;
        $m->userId = (int) $r->userId;
        $m->points = (int) $r->points;
        $m->discountAmount = (float) $r->discountAmount;
        $m->awardId = $r->awardId !== null ? (int) $r->awardId : null;
        $m->uid = $r->uid;
        return $m;
    }
}
