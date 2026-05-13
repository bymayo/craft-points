<?php

namespace bymayo\points\services;

use bymayo\points\elements\PointEntry;
use bymayo\points\Points;
use Craft;
use craft\db\Query;
use craft\elements\User;
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

        return Craft::$app->getElements()->deleteElement($entry);
    }
}
