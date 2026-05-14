<?php

namespace bymayo\points\triggers;

use bymayo\points\Points;
use Craft;
use craft\elements\User as UserElement;
use craft\web\User;
use DateTime;

/**
 * Fires on a user's next login when today matches the date stored in the
 * configured "birthday" custom field on the user. Set the field handle
 * in the plugin settings.
 */
class UserBirthdayTrigger extends BaseTrigger
{
    public static function handle(): string { return 'user.birthday'; }
    public static function label(): string { return 'User birthday'; }
    public static function group(): string { return 'Users'; }
    public static function actionLabel(): string { return 'Birthday'; }
    public static function eventClass(): string { return User::class; }
    public static function eventName(): string { return User::EVENT_AFTER_LOGIN; }

    public static function appliesToEvent($event): bool
    {
        /** @var \yii\web\UserEvent $event */
        $identity = $event->identity ?? null;
        if (!$identity) {
            return false;
        }

        $fieldHandle = Points::getInstance()->getSettings()->birthdayFieldHandle;
        if (!$fieldHandle) {
            return false;
        }

        $birthday = $identity->{$fieldHandle} ?? null;
        if (!$birthday instanceof DateTime) {
            return false;
        }

        return (new DateTime())->format('m-d') === $birthday->format('m-d');
    }

    public static function getUserIdFromEvent($event): ?int
    {
        return $event->identity?->getId();
    }

    /**
     * Only show in the rule builder if the admin has configured a
     * `birthdayFieldHandle` AND a matching Date field actually exists on the
     * User field layout. If either is missing the trigger can never fire, so
     * there's no point letting anyone select it.
     */
    public static function isAvailable(): bool
    {
        $handle = trim((string) Points::getInstance()->getSettings()->birthdayFieldHandle);
        if ($handle === '') {
            return false;
        }
        $layout = Craft::$app->getFields()->getLayoutByType(UserElement::class);
        return $layout->getFieldByHandle($handle) !== null;
    }
}
