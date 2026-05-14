<?php

namespace bymayo\points\triggers\commerce;

use bymayo\points\triggers\BaseTrigger;
use craft\base\Element;
use craft\commerce\elements\Subscription;
use craft\events\ModelEvent;

class SubscriptionCreatedTrigger extends BaseTrigger
{
    public static function handle(): string { return 'commerce.subscriptionCreated'; }
    public static function label(): string { return 'Subscription created'; }
    public static function group(): string { return 'Commerce'; }
    public static function subject(): string { return 'subscription'; }
    public static function eventClass(): string { return Subscription::class; }
    public static function eventName(): string { return Element::EVENT_AFTER_SAVE; }

    public static function appliesToEvent($event): bool
    {
        /** @var ModelEvent $event */
        return (bool)($event->isNew ?? false);
    }

    public static function getUserIdFromEvent($event): ?int
    {
        /** @var Subscription $subscription */
        $subscription = $event->sender;
        return $subscription->userId ?: null;
    }
}
