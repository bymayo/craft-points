<?php

namespace bymayo\points\triggers\commerce;

use bymayo\points\triggers\BaseTrigger;
use craft\commerce\elements\Subscription;
use craft\commerce\services\Subscriptions;

class SubscriptionRenewedTrigger extends BaseTrigger
{
    public static function handle(): string { return 'commerce.subscriptionRenewed'; }
    public static function label(): string { return 'Subscription renewed'; }
    public static function group(): string { return 'Commerce'; }
    public static function subject(): string { return 'subscription'; }
    public static function actionLabel(): string { return 'Renewed'; }
    public static function eventClass(): string { return Subscriptions::class; }
    public static function eventName(): string { return Subscriptions::EVENT_RECEIVE_SUBSCRIPTION_PAYMENT; }

    public static function getUserIdFromEvent($event): ?int
    {
        /** @var \craft\commerce\events\SubscriptionPaymentEvent $event */
        $subscription = $event->subscription ?? null;
        return $subscription?->userId ?: null;
    }
}
