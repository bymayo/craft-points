<?php

namespace bymayo\points\triggers;

use bymayo\points\Points;
use Craft;
use craft\elements\User as UserElement;
use craft\web\User;
use DateTime;
use yii\base\Event;

/**
 * Fires on a user's next login when today matches the date stored in the
 * configured "birthday" custom field on the user. Set the field handle
 * in the plugin settings.
 */
class UserBirthdayTrigger extends BaseTrigger
{
    public function handle(): string { return 'user.birthday'; }
    public function label(): string { return 'User birthday'; }
    public function group(): string { return 'Users'; }
    public function actionLabel(): string { return 'Birthday'; }

    public function events(): array
    {
        return [[User::class, User::EVENT_AFTER_LOGIN]];
    }

    public function handleEvent(Event $event): ?TriggerContext
    {
        /** @var \yii\web\UserEvent $event */
        $identity = $event->identity ?? null;
        if (!$identity) {
            return null;
        }

        $fieldHandle = Points::getInstance()->getSettings()->birthdayFieldHandle;
        if (!$fieldHandle) {
            return null;
        }

        $birthday = $identity->{$fieldHandle} ?? null;
        if (!$birthday instanceof DateTime) {
            return null;
        }

        if ((new DateTime())->format('m-d') !== $birthday->format('m-d')) {
            return null;
        }

        $userId = $identity->getId();
        if (!$userId) {
            return null;
        }
        return new TriggerContext(userId: (int) $userId);
    }

    /**
     * Only show in the rule builder if the admin has configured a
     * `birthdayFieldHandle` AND a matching Date field actually exists on the
     * User field layout. If either is missing the trigger can never fire, so
     * there's no point letting anyone select it.
     */
    public function isAvailable(): bool
    {
        $handle = trim((string) Points::getInstance()->getSettings()->birthdayFieldHandle);
        if ($handle === '') {
            return false;
        }
        $layout = Craft::$app->getFields()->getLayoutByType(UserElement::class);
        return $layout->getFieldByHandle($handle) !== null;
    }
}
