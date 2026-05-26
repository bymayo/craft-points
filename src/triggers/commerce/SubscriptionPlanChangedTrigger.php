<?php

namespace bymayo\points\triggers\commerce;

use bymayo\points\triggers\BaseTrigger;
use bymayo\points\triggers\TriggerContext;
use craft\commerce\services\Subscriptions;
use yii\base\Event;

/**
 * Fires whenever the subscription's plan changes (upgrade or downgrade).
 * Distinguish upgrade-vs-downgrade in a condition if needed.
 */
class SubscriptionPlanChangedTrigger extends BaseTrigger
{
    public function handle(): string { return 'commerce.subscriptionPlanChanged'; }
    public function label(): string { return 'Subscription plan changed'; }
    public function group(): string { return 'Commerce'; }
    public function subject(): string { return 'subscription'; }
    public function actionLabel(): string { return 'Plan changed'; }

    public function events(): array
    {
        return [[Subscriptions::class, Subscriptions::EVENT_AFTER_SWITCH_SUBSCRIPTION_PLAN]];
    }

    public function handleEvent(Event $event): ?TriggerContext
    {
        /** @var \craft\commerce\events\SubscriptionSwitchPlansEvent $event */
        $subscription = $event->subscription ?? null;
        $userId = $subscription?->userId ?: null;
        if (!$userId) {
            return null;
        }
        return new TriggerContext(userId: (int) $userId);
    }
}
