<?php

namespace bymayo\points\triggers;

use craft\web\User;
use DateTime;

/**
 * Fires on a user's next login when today is the month/day of their
 * account creation, at least one year after registration.
 */
class UserAnniversaryTrigger extends BaseTrigger
{
    public static function handle(): string { return 'user.anniversary'; }
    public static function label(): string { return 'User anniversary'; }
    public static function group(): string { return 'Users'; }
    public static function actionLabel(): string { return 'Anniversary'; }
    public static function eventClass(): string { return User::class; }
    public static function eventName(): string { return User::EVENT_AFTER_LOGIN; }

    public static function appliesToEvent($event): bool
    {
        /** @var \yii\web\UserEvent $event */
        $identity = $event->identity ?? null;
        if (!$identity || !$identity->dateCreated) {
            return false;
        }

        $today = new DateTime();
        $created = $identity->dateCreated;

        if ($today->format('m-d') !== $created->format('m-d')) {
            return false;
        }
        return (int) $today->format('Y') > (int) $created->format('Y');
    }

    public static function getUserIdFromEvent($event): ?int
    {
        return $event->identity?->getId();
    }
}
