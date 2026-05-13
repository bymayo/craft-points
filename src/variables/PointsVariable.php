<?php

namespace bymayo\points\variables;

use bymayo\points\elements\PointAward;
use bymayo\points\models\Event;
use bymayo\points\models\Level;
use bymayo\points\Points;
use Craft;
use craft\elements\User;

/**
 * Twig API for Points — exposed as `craft.points.*`.
 */
class PointsVariable
{
    /**
     * @return PointAward[]
     */
    public function awards(): array
    {
        return PointAward::find()
            ->orderBy(['dateCreated' => SORT_DESC])
            ->all();
    }

    /**
     * @return Event[]
     */
    public function events(): array
    {
        return Points::getInstance()->events->getAllEvents();
    }

    public function user(int $userId): ?User
    {
        return Craft::$app->getUsers()->getUserById($userId);
    }

    /**
     * Returns an options array suitable for a select field, keyed by event handle.
     */
    public function eventOptions(): array
    {
        $options = [['label' => '----', 'value' => '']];
        foreach ($this->events() as $event) {
            $options[] = ['label' => $event->name, 'value' => $event->handle];
        }
        return $options;
    }

    public function event(string $handle): ?Event
    {
        return Points::getInstance()->events->getEventByHandle($handle);
    }

    public function eventById(int $id): ?Event
    {
        return Points::getInstance()->events->getEventById($id);
    }

    public function eventByHandle(string $handle): ?Event
    {
        return Points::getInstance()->events->getEventByHandle($handle);
    }

    /**
     * Create an event on the fly if one with this handle doesn't already exist.
     *
     * Usage: {{ craft.points.addEvent({ event: 'Signed Up', eventHandle: 'signedUp', points: 20, multiple: false }) }}
     */
    public function addEvent(array $options): ?Event
    {
        if (empty($options['eventHandle'])) {
            return null;
        }

        $existing = $this->eventByHandle($options['eventHandle']);
        if ($existing) {
            return $existing;
        }

        $event = new Event();
        $event->name = $options['event'] ?? '';
        $event->handle = $options['eventHandle'];
        $event->points = (int)($options['points'] ?? 0);
        $event->pointsType = (string)($options['pointsType'] ?? 'flat');
        $event->multiple = (bool)($options['multiple'] ?? false);

        return Points::getInstance()->events->saveEvent($event) ? $event : null;
    }

    public function awardById(int $id): ?PointAward
    {
        return Points::getInstance()->awards->getAwardById($id);
    }

    /**
     * @return PointAward[]
     */
    public function awardsByUser(?int $userId = null): array
    {
        $userId = $userId ?? $this->currentUserId();
        return $userId
            ? Points::getInstance()->awards->getAwardsForUser($userId)
            : [];
    }

    /**
     * Award points to a user.
     *
     * Usage:
     *   {{ craft.points.addAward({ eventHandle: 'signedUp' }) }}             — current user
     *   {{ craft.points.addAward({ userId: 5, eventHandle: 'signedUp' }) }}  — specific user
     */
    public function addAward(array $options): ?PointAward
    {
        $userId = $options['userId'] ?? $this->currentUserId();
        $eventHandle = $options['eventHandle'] ?? null;

        if (!$userId || !$eventHandle) {
            return null;
        }

        return Points::getInstance()->awards->addAward((int)$userId, $eventHandle);
    }

    /**
     * Remove the oldest award for this user + event. Only removes one instance.
     */
    public function removeAward(array $options): bool
    {
        $userId = $options['userId'] ?? $this->currentUserId();
        $eventHandle = $options['eventHandle'] ?? null;

        if (!$userId || !$eventHandle) {
            return false;
        }

        return Points::getInstance()->awards->removeAward((int)$userId, $eventHandle);
    }

    /** Total points for a user (defaults to current user). */
    public function sumForUser(?int $userId = null): int
    {
        $userId = $userId ?? $this->currentUserId();
        return $userId
            ? Points::getInstance()->awards->sumForUser($userId)
            : 0;
    }

    /** Count of awards for a user (defaults to current user). */
    public function countForUser(?int $userId = null): int
    {
        $userId = $userId ?? $this->currentUserId();
        return $userId
            ? Points::getInstance()->awards->countForUser($userId)
            : 0;
    }

    /**
     * @return Level[]
     */
    public function levels(): array
    {
        return Points::getInstance()->levels->getAllLevels();
    }

    public function levelForUser(?int $userId = null): ?Level
    {
        $userId = $userId ?? $this->currentUserId();
        return $userId
            ? Points::getInstance()->levels->levelForUser($userId)
            : null;
    }

    public function levelForPoints(int $points): ?Level
    {
        return Points::getInstance()->levels->levelForPoints($points);
    }

    public function levelById(int $id): ?Level
    {
        return Points::getInstance()->levels->getLevelById($id);
    }

    public function levelByHandle(string $handle): ?Level
    {
        return Points::getInstance()->levels->getLevelByHandle($handle);
    }

    /**
     * @return array<int, array{user: User, points: int, level: ?Level}>
     */
    public function leaderboard(int $limit = 10, int $offset = 0): array
    {
        return Points::getInstance()->awards->leaderboard($limit, $offset);
    }

    private function currentUserId(): ?int
    {
        $user = Craft::$app->getUser()->getIdentity();
        return $user?->id;
    }
}
