<?php

namespace bymayo\points\triggers\commerce;

use bymayo\points\triggers\BaseTrigger;
use craft\commerce\elements\Order;

class OrderCompletedTrigger extends BaseTrigger
{
    public static function handle(): string { return 'commerce.orderCompleted'; }
    public static function label(): string { return 'Order completed'; }
    public static function group(): string { return 'Commerce'; }
    public static function subject(): string { return 'order'; }
    public static function eventClass(): string { return Order::class; }
    public static function eventName(): string { return Order::EVENT_AFTER_COMPLETE_ORDER; }

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
        return (float)$order->getTotalPrice();
    }

    public static function getOrderIdFromEvent($event): ?int
    {
        /** @var Order $order */
        $order = $event->sender;
        return $order->id ?: null;
    }
}
