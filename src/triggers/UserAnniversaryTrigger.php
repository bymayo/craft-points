<?php

namespace bymayo\points\triggers;

use craft\web\User;
use DateTime;
use yii\base\Event;

/**
 * Fires on a user's next login when today is the month/day of their
 * account creation, at least one year after registration.
 */
class UserAnniversaryTrigger extends BaseTrigger
{
    public function handle(): string { return 'user.anniversary'; }
    public function label(): string { return 'User anniversary'; }
    public function group(): string { return 'Users'; }
    public function actionLabel(): string { return 'Anniversary'; }

    public function events(): array
    {
        return [[User::class, User::EVENT_AFTER_LOGIN]];
    }

    public function handleEvent(Event $event): ?TriggerContext
    {
        /** @var \yii\web\UserEvent $event */
        $identity = $event->identity ?? null;
        if (!$identity || !$identity->dateCreated) {
            return null;
        }

        $today = new DateTime();
        $created = $identity->dateCreated;

        if ($today->format('m-d') !== $created->format('m-d')) {
            return null;
        }
        if ((int) $today->format('Y') <= (int) $created->format('Y')) {
            return null;
        }

        $userId = $identity->getId();
        if (!$userId) {
            return null;
        }
        return new TriggerContext(userId: (int) $userId);
    }
}
