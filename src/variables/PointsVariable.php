<?php

namespace bymayo\points\variables;

use bymayo\points\elements\PointEntry;
use bymayo\points\models\Event;
use bymayo\points\Points;
use Craft;
use craft\elements\User;

/**
 * Twig API for Points — exposed as `craft.points.*`.
 *
 * Mirrors the Craft 2 plugin's API so existing templates keep working.
 */
class PointsVariable
{
    /**
     * @return PointEntry[]
     */
    public function entries(): array
    {
        return PointEntry::find()
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
        $event->multiple = (bool)($options['multiple'] ?? false);

        return Points::getInstance()->events->saveEvent($event) ? $event : null;
    }

    public function entryById(int $id): ?PointEntry
    {
        return Points::getInstance()->entries->getEntryById($id);
    }

    /**
     * @return PointEntry[]
     */
    public function entriesByUser(?int $userId = null): array
    {
        $userId = $userId ?? $this->currentUserId();
        return $userId
            ? Points::getInstance()->entries->getEntriesForUser($userId)
            : [];
    }

    /**
     * Award points to a user.
     *
     * Usage:
     *   {{ craft.points.addEntry({ eventHandle: 'signedUp' }) }}             — current user
     *   {{ craft.points.addEntry({ userId: 5, eventHandle: 'signedUp' }) }}  — specific user
     */
    public function addEntry(array $options): ?PointEntry
    {
        $userId = $options['userId'] ?? $this->currentUserId();
        $eventHandle = $options['eventHandle'] ?? null;

        if (!$userId || !$eventHandle) {
            return null;
        }

        return Points::getInstance()->entries->addEntry((int)$userId, $eventHandle);
    }

    /**
     * Remove the oldest entry for this user + event. Only removes one instance.
     */
    public function removeEntry(array $options): bool
    {
        $userId = $options['userId'] ?? $this->currentUserId();
        $eventHandle = $options['eventHandle'] ?? null;

        if (!$userId || !$eventHandle) {
            return false;
        }

        return Points::getInstance()->entries->removeEntry((int)$userId, $eventHandle);
    }

    public function sumEntries(?int $userId = null): int
    {
        $userId = $userId ?? $this->currentUserId();
        return $userId
            ? Points::getInstance()->entries->sumForUser($userId)
            : 0;
    }

    public function totalEntries(?int $userId = null): int
    {
        $userId = $userId ?? $this->currentUserId();
        return $userId
            ? Points::getInstance()->entries->totalForUser($userId)
            : 0;
    }

    private function currentUserId(): ?int
    {
        $user = Craft::$app->getUser()->getIdentity();
        return $user?->id;
    }
}
