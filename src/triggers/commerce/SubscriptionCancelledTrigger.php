<?php

namespace bymayo\points\triggers\commerce;

use bymayo\points\triggers\BaseTrigger;
use bymayo\points\triggers\TriggerContext;
use craft\commerce\services\Subscriptions;
use yii\base\Event;

class SubscriptionCancelledTrigger extends BaseTrigger
{
    public function handle(): string { return 'commerce.subscriptionCancelled'; }
    public function label(): string { return 'Subscription cancelled'; }
    public function group(): string { return 'Commerce'; }
    public function subject(): string { return 'subscription'; }
    public function actionLabel(): string { return 'Cancelled'; }

    public function events(): array
    {
        return [[Subscriptions::class, Subscriptions::EVENT_AFTER_CANCEL_SUBSCRIPTION]];
    }

    public function handleEvent(Event $event): ?TriggerContext
    {
        /** @var \craft\commerce\events\CancelSubscriptionEvent $event */
        $subscription = $event->subscription ?? null;
        $userId = $subscription?->userId ?: null;
        if (!$userId) {
            return null;
        }
        return new TriggerContext(userId: (int) $userId);
    }
}
