<?php

namespace bymayo\points\triggers\commerce;

use bymayo\points\triggers\BaseTrigger;
use craft\commerce\services\Subscriptions;

/**
 * Fires whenever the subscription's plan changes (upgrade or downgrade).
 * Distinguish upgrade-vs-downgrade in a condition if needed.
 */
class SubscriptionPlanChangedTrigger extends BaseTrigger
{
    public static function handle(): string { return 'commerce.subscriptionPlanChanged'; }
    public static function label(): string { return 'Subscription plan changed'; }
    public static function group(): string { return 'Commerce'; }
    public static function subject(): string { return 'subscription'; }
    public static function actionLabel(): string { return 'Plan changed'; }
    public static function eventClass(): string { return Subscriptions::class; }
    public static function eventName(): string { return Subscriptions::EVENT_AFTER_SWITCH_SUBSCRIPTION_PLAN; }

    public static function getUserIdFromEvent($event): ?int
    {
        /** @var \craft\commerce\events\SubscriptionSwitchPlansEvent $event */
        $subscription = $event->newSubscription ?? $event->subscription ?? null;
        return $subscription?->userId ?: null;
    }
}
