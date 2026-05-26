<?php

namespace bymayo\points\triggers\commerce;

use bymayo\points\triggers\BaseTrigger;
use bymayo\points\triggers\TriggerContext;
use craft\commerce\elements\Order;
use yii\base\Event;

class OrderPaidTrigger extends BaseTrigger
{
    public function handle(): string { return 'commerce.orderPaid'; }
    public function label(): string { return 'Order paid'; }
    public function group(): string { return 'Commerce'; }
    public function subject(): string { return 'order'; }
    public function actionLabel(): string { return 'Paid'; }

    public function events(): array
    {
        return [[Order::class, Order::EVENT_AFTER_ORDER_PAID]];
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
