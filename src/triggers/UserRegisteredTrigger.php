<?php

namespace bymayo\points\triggers;

use craft\base\Element;
use craft\elements\User;
use craft\events\ModelEvent;

class UserRegisteredTrigger extends BaseTrigger
{
    public static function handle(): string { return 'user.registered'; }
    public static function label(): string { return 'User registered'; }
    public static function group(): string { return 'Users'; }
    public static function eventClass(): string { return User::class; }
    public static function eventName(): string { return Element::EVENT_AFTER_SAVE; }

    public static function appliesToEvent($event): bool
    {
        /** @var ModelEvent $event */
        return (bool)($event->isNew ?? false);
    }

    public static function getUserIdFromEvent($event): ?int
    {
        return $event->sender->id;
    }
}
