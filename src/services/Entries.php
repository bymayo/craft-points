<?php

namespace bymayo\points\services;

use bymayo\points\elements\PointEntry;
use bymayo\points\events\EntryEvent;
use bymayo\points\events\LevelChangedEvent;
use bymayo\points\Points;
use Craft;
use craft\db\Query;
use craft\elements\User;
use yii\base\Component;

class Entries extends Component
{
    public const EVENT_BEFORE_ADD_ENTRY = 'beforeAddEntry';
    public const EVENT_AFTER_ADD_ENTRY = 'afterAddEntry';
    public const EVENT_BEFORE_REMOVE_ENTRY = 'beforeRemoveEntry';
    public const EVENT_AFTER_REMOVE_ENTRY = 'afterRemoveEntry';

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

    public function addEntry(int $userId, string $eventHandle, ?int $pointsOverride = null): ?PointEntry
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

        $pointsToAward = $pointsOverride ?? $event->points;

        if ($this->hasEventHandlers(self::EVENT_BEFORE_ADD_ENTRY)) {
            $beforeEvent = new EntryEvent([
                'userId' => $userId,
                'event' => $event,
                'pointsToAward' => $pointsToAward,
            ]);
            $this->trigger(self::EVENT_BEFORE_ADD_ENTRY, $beforeEvent);
            if (!$beforeEvent->isValid) {
                return null;
            }
            // Allow handlers to alter the points value.
            $pointsToAward = $beforeEvent->pointsToAward;
        }

        $beforeLevel = Points::getInstance()->levels->levelForUser($userId);

        $entry = new PointEntry();
        $entry->userId = $userId;
        $entry->eventId = $event->id;
        $entry->pointsSnapshot = $pointsToAward;

        if (!Craft::$app->getElements()->saveElement($entry)) {
            return null;
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_ADD_ENTRY)) {
            $this->trigger(self::EVENT_AFTER_ADD_ENTRY, new EntryEvent([
                'userId' => $userId,
                'event' => $event,
                'entry' => $entry,
                'pointsToAward' => $pointsToAward,
            ]));
        }

        $this->fireLevelChangedIfChanged($userId, $beforeLevel);

        return $entry;
    }

    /**
     * Returns the top N users by total points.
     *
     * Each row: ['user' => User, 'points' => int, 'level' => Level|null]
     *
     * @return array<int, array{user: User, points: int, level: ?\bymayo\points\models\Level}>
     */
    public function leaderboard(int $limit = 10, int $offset = 0): array
    {
        $rows = (new Query())
            ->select(['userId' => 'e.userId', 'total' => 'SUM([[e.pointsSnapshot]])'])
            ->from(['e' => '{{%points_entries}}'])
            ->innerJoin(['el' => '{{%elements}}'], '[[el.id]] = [[e.id]]')
            ->where(['el.dateDeleted' => null])
            ->groupBy(['e.userId'])
            ->orderBy(['total' => SORT_DESC, 'e.userId' => SORT_ASC])
            ->limit($limit)
            ->offset($offset)
            ->all();

        if (empty($rows)) {
            return [];
        }

        $userIds = array_column($rows, 'userId');
        /** @var User[] $users */
        $users = User::find()->id($userIds)->indexBy('id')->all();

        $results = [];
        foreach ($rows as $row) {
            $user = $users[$row['userId']] ?? null;
            if (!$user) {
                continue;
            }
            $points = (int)$row['total'];
            $results[] = [
                'user' => $user,
                'points' => $points,
                'level' => Points::getInstance()->levels->levelForPoints($points),
            ];
        }

        return $results;
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

        if ($this->hasEventHandlers(self::EVENT_BEFORE_REMOVE_ENTRY)) {
            $beforeEvent = new EntryEvent([
                'userId' => $userId,
                'event' => $event,
                'entry' => $entry,
                'pointsToAward' => $entry->pointsSnapshot,
            ]);
            $this->trigger(self::EVENT_BEFORE_REMOVE_ENTRY, $beforeEvent);
            if (!$beforeEvent->isValid) {
                return false;
            }
        }

        $beforeLevel = Points::getInstance()->levels->levelForUser($userId);

        if (!Craft::$app->getElements()->deleteElement($entry)) {
            return false;
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_REMOVE_ENTRY)) {
            $this->trigger(self::EVENT_AFTER_REMOVE_ENTRY, new EntryEvent([
                'userId' => $userId,
                'event' => $event,
                'entry' => $entry,
                'pointsToAward' => $entry->pointsSnapshot,
            ]));
        }

        $this->fireLevelChangedIfChanged($userId, $beforeLevel);

        return true;
    }

    private function fireLevelChangedIfChanged(int $userId, ?\bymayo\points\models\Level $beforeLevel): void
    {
        $levelsService = Points::getInstance()->levels;
        if (!$levelsService->hasEventHandlers(\bymayo\points\services\Levels::EVENT_LEVEL_CHANGED)) {
            return;
        }

        $afterLevel = $levelsService->levelForUser($userId);
        $beforeId = $beforeLevel?->id;
        $afterId = $afterLevel?->id;

        if ($beforeId === $afterId) {
            return;
        }

        $levelsService->trigger(\bymayo\points\services\Levels::EVENT_LEVEL_CHANGED, new LevelChangedEvent([
            'userId' => $userId,
            'previousLevel' => $beforeLevel,
            'currentLevel' => $afterLevel,
            'currentPoints' => $this->sumForUser($userId),
        ]));
    }
}
