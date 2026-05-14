<?php

namespace bymayo\points\adjusters;

use bymayo\points\Points;
use craft\commerce\base\AdjusterInterface;
use craft\commerce\elements\Order;
use craft\commerce\models\OrderAdjustment;
use craft\commerce\Plugin as Commerce;

/**
 * Adds a negative line item to the order representing the points the user
 * chose to redeem against it. The actual deduction from the user's balance
 * happens on `Order::EVENT_AFTER_ORDER_PAID` in `OrderRedemptions::processPaidOrder`.
 */
class PointsAdjuster implements AdjusterInterface
{
    /**
     * @return OrderAdjustment[]
     */
    public function adjust(Order $order): array
    {
        if (!$order->id) {
            return [];
        }

        $redemption = Points::getInstance()->orderRedemptions->getForOrder($order->id);
        if (!$redemption || $redemption->points <= 0) {
            return [];
        }

        $settings = Points::getInstance()->getSettings();
        $currency = strtolower($settings->currencyNamePlural);

        $adjustment = new OrderAdjustment();
        $adjustment->type = 'discount';
        $adjustment->name = sprintf('%s redemption', $settings->pluginName);
        $adjustment->description = sprintf('%d %s', $redemption->points, $currency);
        $adjustment->amount = -1 * $redemption->discountAmount;
        $adjustment->setOrder($order);
        $adjustment->sourceSnapshot = [
            'source' => 'points',
            'points' => $redemption->points,
        ];

        return [$adjustment];
    }
}
