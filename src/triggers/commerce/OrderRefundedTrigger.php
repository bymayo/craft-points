<?php

namespace bymayo\points\triggers\commerce;

use bymayo\points\triggers\BaseTrigger;
use bymayo\points\triggers\TriggerContext;
use craft\commerce\services\Transactions;
use yii\base\Event;

/**
 * Fires when a successful refund transaction is saved.
 *
 * The transaction service fires for every transaction (purchase, authorize, capture, refund),
 * so we filter on type+status before returning a context.
 *
 * The TriggerContext's amount is the refunded amount (not the original order total),
 * so percent rewards calculate against what was actually refunded.
 *
 * Pair this with a "Deduct points" reward to subtract from the user's balance.
 */
class OrderRefundedTrigger extends BaseTrigger
{
    public function handle(): string { return 'commerce.orderRefunded'; }
    public function label(): string { return 'Order refunded'; }
    public function group(): string { return 'Commerce'; }
    public function subject(): string { return 'order'; }
    public function actionLabel(): string { return 'Refunded'; }

    public function events(): array
    {
        return [[Transactions::class, Transactions::EVENT_AFTER_SAVE_TRANSACTION]];
    }

    public function handleEvent(Event $event): ?TriggerContext
    {
        /** @var \craft\commerce\events\TransactionEvent $event */
        $tx = $event->transaction ?? null;
        if (!$tx) {
            return null;
        }
        if ($tx->type !== 'refund' || $tx->status !== 'success') {
            return null;
        }

        $order = $tx->order ?? null;
        $userId = $order?->customerId ?: null;
        if (!$userId) {
            return null;
        }

        return new TriggerContext(
            userId: (int) $userId,
            amount: (float) $tx->amount,
            orderId: $order?->id ?: null,
        );
    }
}
