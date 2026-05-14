<?php

namespace bymayo\points\triggers\commerce;

use bymayo\points\triggers\BaseTrigger;
use craft\commerce\elements\Order;

/**
 * Fires when a customer's first-ever order is completed.
 * Detection: count of completed orders for the customer ≤ 1 (the current one).
 */
class FirstOrderTrigger extends BaseTrigger
{
    public static function handle(): string { return 'commerce.firstOrder'; }
    public static function label(): string { return 'First order ever'; }
    public static function group(): string { return 'Commerce'; }
    public static function subject(): string { return 'order'; }
    public static function actionLabel(): string { return 'First ever'; }
    public static function eventClass(): string { return Order::class; }
    public static function eventName(): string { return Order::EVENT_AFTER_COMPLETE_ORDER; }

    public static function appliesToEvent($event): bool
    {
        /** @var Order $order */
        $order = $event->sender;
        if (!$order->customerId) {
            return false;
        }

        $count = (int) Order::find()
            ->customerId($order->customerId)
            ->isCompleted(true)
            ->count();

        // The current order is already counted as completed.
        return $count <= 1;
    }

    public static function getUserIdFromEvent($event): ?int
    {
        /** @var Order $order */
        $order = $event->sender;
        return $order->customerId ?: null;
    }

    public static function getAmountForEvent($event): ?float
    {
        /** @var Order $order */
        $order = $event->sender;
        return (float) $order->getTotalPrice();
    }

    public static function getOrderIdFromEvent($event): ?int
    {
        /** @var Order $order */
        $order = $event->sender;
        return $order->id ?: null;
    }
}
