<?php

namespace bymayo\points\services;

use bymayo\points\elements\PointEntry;
use bymayo\points\Points;
use Craft;
use yii\base\Component;

class Entries extends Component
{
    public function getEntryById(int $id): ?PointEntry
    {
        /** @var PointEntry|null $entry */
        $entry = PointEntry::find()->id($id)->one();
        return $entry;
    }

    /**
     * @return PointEntry[]
     */
    public function getEntriesForUser(int $userId): array
    {
        /** @var PointEntry[] $entries */
        $entries = PointEntry::find()
            ->userId($userId)
            ->orderBy(['dateCreated' => SORT_DESC])
            ->all();
        return $entries;
    }

    public function sumForUser(int $userId): int
    {
        $sum = PointEntry::find()
            ->userId($userId)
            ->sum('points_entries.pointsSnapshot');
        return (int)$sum;
    }

    public function totalForUser(int $userId): int
    {
        return (int)PointEntry::find()->userId($userId)->count();
    }

    public function addEntry(int $userId, string $eventHandle): ?PointEntry
    {
        $event = Points::getInstance()->events->getEventByHandle($eventHandle);
        if (!$event) {
            return null;
        }

        if (!$event->multiple) {
            $exists = PointEntry::find()
                ->userId($userId)
                ->eventId($event->id)
                ->exists();
            if ($exists) {
                return null;
            }
        }

        $entry = new PointEntry();
        $entry->userId = $userId;
        $entry->eventId = $event->id;
        $entry->pointsSnapshot = $event->points;

        if (!Craft::$app->getElements()->saveElement($entry)) {
            return null;
        }

        return $entry;
    }

    public function removeEntry(int $userId, string $eventHandle): bool
    {
        $event = Points::getInstance()->events->getEventByHandle($eventHandle);
        if (!$event) {
            return false;
        }

        /** @var PointEntry|null $entry */
        $entry = PointEntry::find()
            ->userId($userId)
            ->eventId($event->id)
            ->orderBy(['dateCreated' => SORT_ASC])
            ->one();

        if (!$entry) {
            return false;
        }

        return Craft::$app->getElements()->deleteElement($entry);
    }
}
