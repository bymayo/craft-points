<?php

namespace bymayo\points\triggers;

use craft\base\Element;
use craft\elements\User;
use craft\events\ModelEvent;
use yii\base\Event;

class UserRegisteredTrigger extends BaseTrigger
{
    public function handle(): string { return 'user.registered'; }
    public function label(): string { return 'User registered'; }
    public function group(): string { return 'Users'; }

    public function events(): array
    {
        return [[User::class, Element::EVENT_AFTER_SAVE]];
    }

    public function handleEvent(Event $event): ?TriggerContext
    {
        /** @var ModelEvent $event */
        if (!($event->isNew ?? false)) {
            return null;
        }
        $userId = $event->sender->id ?? null;
        if (!$userId) {
            return null;
        }
        return new TriggerContext(userId: $userId);
    }
}
