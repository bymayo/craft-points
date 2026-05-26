<?php

namespace bymayo\points\conditions\rules\commerce;

use bymayo\points\conditions\BaseConditionRule;
use bymayo\points\conditions\RuleEvaluationContext;

/**
 * Passes if the order has a coupon code applied (when `hasCoupon` is true)
 * or no coupon (when `hasCoupon` is false).
 *
 * Useful for both "purchased after coupon use" and "purchased without coupon".
 */
class OrderHasCouponConditionRule extends BaseConditionRule
{
    public function handle(): string { return 'commerce.hasCoupon'; }
    public function label(): string { return 'Coupon'; }
    public function group(): string { return 'Commerce'; }
    public function appliesToSubjects(): ?array { return ['order']; }

    public function evaluate(array $config, RuleEvaluationContext $ctx): bool
    {
        // Pull the order from the trigger context. Transaction-based triggers
        // (refunds) have $event->transaction->order; order triggers have $event->sender.
        $sender = $ctx->triggerEvent?->sender ?? null;
        $order = $sender;
        if (!$order || !method_exists($order, 'getCouponCode')) {
            $order = $ctx->triggerEvent?->transaction?->order ?? null;
        }
        if (!$order) {
            return false;
        }

        $expected = (bool) ($config['hasCoupon'] ?? true);
        $hasCoupon = !empty($order->couponCode ?? null);
        return $hasCoupon === $expected;
    }
}
