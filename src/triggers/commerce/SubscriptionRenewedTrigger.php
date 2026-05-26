<?php

namespace bymayo\points\triggers\commerce;

use bymayo\points\triggers\BaseTrigger;
use bymayo\points\triggers\TriggerContext;
use craft\commerce\services\Subscriptions;
use yii\base\Event;

class SubscriptionRenewedTrigger extends BaseTrigger
{
    public function handle(): string { return 'commerce.subscriptionRenewed'; }
    public function label(): string { return 'Subscription renewed'; }
    public function group(): string { return 'Commerce'; }
    public function subject(): string { return 'subscription'; }
    public function actionLabel(): string { return 'Renewed'; }

    public function events(): array
    {
        return [[Subscriptions::class, Subscriptions::EVENT_RECEIVE_SUBSCRIPTION_PAYMENT]];
    }

    public function handleEvent(Event $event): ?TriggerContext
    {
        /** @var \craft\commerce\events\SubscriptionPaymentEvent $event */
        $subscription = $event->subscription ?? null;
        $userId = $subscription?->userId ?: null;
        if (!$userId) {
            return null;
        }
        return new TriggerContext(userId: (int) $userId);
    }
}
