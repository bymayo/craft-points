<?php

namespace bymayo\points\triggers\commerce;

use bymayo\points\triggers\BaseTrigger;
use craft\commerce\services\Transactions;

/**
 * Fires when a successful refund transaction is saved.
 *
 * The transaction service fires for every transaction (purchase, authorize, capture, refund),
 * so we filter on type+status in appliesToEvent.
 *
 * `getAmountForEvent` returns the refunded amount (not the original order total),
 * so percent rewards calculate against what was actually refunded.
 *
 * Pair this with a "Deduct points" reward to subtract from the user's balance.
 */
class OrderRefundedTrigger extends BaseTrigger
{
    public static function handle(): string { return 'commerce.orderRefunded'; }
    public static function label(): string { return 'Order refunded'; }
    public static function group(): string { return 'Commerce'; }
    public static function subject(): string { return 'order'; }
    public static function actionLabel(): string { return 'Refunded'; }
    public static function eventClass(): string { return Transactions::class; }
    public static function eventName(): string { return Transactions::EVENT_AFTER_SAVE_TRANSACTION; }

    public static function appliesToEvent($event): bool
    {
        /** @var \craft\commerce\events\TransactionEvent $event */
        $tx = $event->transaction ?? null;
        if (!$tx) {
            return false;
        }
        return $tx->type === 'refund' && $tx->status === 'success';
    }

    public static function getUserIdFromEvent($event): ?int
    {
        $tx = $event->transaction ?? null;
        $order = $tx?->order;
        return $order?->customerId ?: null;
    }

    public static function getAmountForEvent($event): ?float
    {
        $tx = $event->transaction ?? null;
        return $tx ? (float) $tx->amount : null;
    }

    public static function getOrderIdFromEvent($event): ?int
    {
        $tx = $event->transaction ?? null;
        $order = $tx?->order;
        return $order?->id ?: null;
    }
}
