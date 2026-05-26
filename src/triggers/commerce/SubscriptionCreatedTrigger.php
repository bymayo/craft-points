<?php

namespace bymayo\points\triggers\commerce;

use bymayo\points\triggers\BaseTrigger;
use bymayo\points\triggers\TriggerContext;
use craft\base\Element;
use craft\commerce\elements\Subscription;
use craft\events\ModelEvent;
use yii\base\Event;

class SubscriptionCreatedTrigger extends BaseTrigger
{
    public function handle(): string { return 'commerce.subscriptionCreated'; }
    public function label(): string { return 'Subscription created'; }
    public function group(): string { return 'Commerce'; }
    public function subject(): string { return 'subscription'; }

    public function events(): array
    {
        return [[Subscription::class, Element::EVENT_AFTER_SAVE]];
    }

    public function handleEvent(Event $event): ?TriggerContext
    {
        /** @var ModelEvent $event */
        if (!($event->isNew ?? false)) {
            return null;
        }
        /** @var Subscription $subscription */
        $subscription = $event->sender;
        $userId = $subscription->userId ?: null;
        if (!$userId) {
            return null;
        }
        return new TriggerContext(userId: (int) $userId);
    }
}
