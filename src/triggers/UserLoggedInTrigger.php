<?php

namespace bymayo\points\triggers;

use craft\web\User;
use yii\base\Event;

class UserLoggedInTrigger extends BaseTrigger
{
    public function handle(): string { return 'user.loggedIn'; }
    public function label(): string { return 'User logged in'; }
    public function group(): string { return 'Users'; }

    public function events(): array
    {
        return [[User::class, User::EVENT_AFTER_LOGIN]];
    }

    public function handleEvent(Event $event): ?TriggerContext
    {
        /** @var \yii\web\UserEvent $event */
        $userId = $event->identity?->getId();
        if (!$userId) {
            return null;
        }
        return new TriggerContext(userId: (int) $userId);
    }
}
