<?php

namespace bymayo\points\triggers\commerce;

use bymayo\points\triggers\BaseTrigger;
use bymayo\points\triggers\TriggerContext;
use craft\commerce\elements\Order;
use yii\base\Event;

class OrderCompletedTrigger extends BaseTrigger
{
    public function handle(): string { return 'commerce.orderCompleted'; }
    public function label(): string { return 'Order completed'; }
    public function group(): string { return 'Commerce'; }
    public function subject(): string { return 'order'; }

    public function events(): array
    {
        return [[Order::class, Order::EVENT_AFTER_COMPLETE_ORDER]];
    }

    public function handleEvent(Event $event): ?TriggerContext
    {
        /** @var Order $order */
        $order = $event->sender;
        $userId = $order->customerId ?: null;
        if (!$userId) {
            return null;
        }
        return new TriggerContext(
            userId: (int) $userId,
            amount: (float) $order->getTotalPrice(),
            orderId: $order->id ?: null,
        );
    }
}
