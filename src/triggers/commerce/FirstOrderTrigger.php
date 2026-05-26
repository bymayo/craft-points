<?php

namespace bymayo\points\triggers\commerce;

use bymayo\points\triggers\BaseTrigger;
use bymayo\points\triggers\TriggerContext;
use craft\commerce\elements\Order;
use yii\base\Event;

/**
 * Fires when a customer's first-ever order is completed.
 * Detection: count of completed orders for the customer ≤ 1 (the current one).
 */
class FirstOrderTrigger extends BaseTrigger
{
    public function handle(): string { return 'commerce.firstOrder'; }
    public function label(): string { return 'First order ever'; }
    public function group(): string { return 'Commerce'; }
    public function subject(): string { return 'order'; }
    public function actionLabel(): string { return 'First ever'; }

    public function events(): array
    {
        return [[Order::class, Order::EVENT_AFTER_COMPLETE_ORDER]];
    }

    public function handleEvent(Event $event): ?TriggerContext
    {
        /** @var Order $order */
        $order = $event->sender;
        if (!$order->customerId) {
            return null;
        }

        $count = (int) Order::find()
            ->customerId($order->customerId)
            ->isCompleted(true)
            ->count();

        // The current order is already counted as completed.
        if ($count > 1) {
            return null;
        }

        return new TriggerContext(
            userId: (int) $order->customerId,
            amount: (float) $order->getTotalPrice(),
            orderId: $order->id ?: null,
        );
    }
}
