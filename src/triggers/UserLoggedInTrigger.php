<?php

namespace bymayo\points\triggers;

use craft\web\User;

class UserLoggedInTrigger extends BaseTrigger
{
    public static function handle(): string { return 'user.loggedIn'; }
    public static function label(): string { return 'User logged in'; }
    public static function group(): string { return 'Users'; }
    public static function eventClass(): string { return User::class; }
    public static function eventName(): string { return User::EVENT_AFTER_LOGIN; }

    public static function getUserIdFromEvent($event): ?int
    {
        /** @var \yii\web\UserEvent $event */
        return $event->identity?->getId();
    }
}
