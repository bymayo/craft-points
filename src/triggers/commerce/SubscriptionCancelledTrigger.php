<?php

namespace bymayo\points\triggers\commerce;

use bymayo\points\triggers\BaseTrigger;
use craft\commerce\services\Subscriptions;

class SubscriptionCancelledTrigger extends BaseTrigger
{
    public static function handle(): string { return 'commerce.subscriptionCancelled'; }
    public static function label(): string { return 'Subscription cancelled'; }
    public static function group(): string { return 'Commerce'; }
    public static function subject(): string { return 'subscription'; }
    public static function actionLabel(): string { return 'Cancelled'; }
    public static function eventClass(): string { return Subscriptions::class; }
    public static function eventName(): string { return Subscriptions::EVENT_AFTER_CANCEL_SUBSCRIPTION; }

    public static function getUserIdFromEvent($event): ?int
    {
        /** @var \craft\commerce\events\CancelSubscriptionEvent $event */
        $subscription = $event->subscription ?? null;
        return $subscription?->userId ?: null;
    }
}
