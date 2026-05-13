<?php

namespace bymayo\points\services;

use bymayo\points\elements\PointAward;
use bymayo\points\events\AwardEvent;
use bymayo\points\events\LevelChangedEvent;
use bymayo\points\Points;
use Craft;
use craft\db\Query;
use craft\elements\User;
use yii\base\Component;

class Awards extends Component
{
    public const EVENT_BEFORE_ADD_AWARD = 'beforeAddAward';
    public const EVENT_AFTER_ADD_AWARD = 'afterAddAward';
    public const EVENT_BEFORE_REMOVE_AWARD = 'beforeRemoveAward';
    public const EVENT_AFTER_REMOVE_AWARD = 'afterRemoveAward';

    public function getAwardById(int $id): ?PointAward
    {
        /** @var PointAward|null $award */
        $award = PointAward::find()->id($id)->one();
        return $award;
    }

    /**
     * @return PointAward[]
     */
    public function getAwardsForUser(int $userId): array
    {
        /** @var PointAward[] $awards */
        $awards = PointAward::find()
            ->userId($userId)
            ->orderBy(['dateCreated' => SORT_DESC])
            ->all();
        return $awards;
    }

    public function sumForUser(int $userId): int
    {
        $sum = PointAward::find()
            ->userId($userId)
            ->sum('points_awards.pointsSnapshot');
        return (int)$sum;
    }

    public function countForUser(int $userId): int
    {
        return (int)PointAward::find()->userId($userId)->count();
    }

    public function addAward(int $userId, string $eventHandle, ?int $pointsOverride = null): ?PointAward
    {
        $event = Points::getInstance()->events->getEventByHandle($eventHandle);
        if (!$event) {
            return null;
        }

        if (!$event->multiple) {
            $exists = PointAward::find()
                ->userId($userId)
                ->eventId($event->id)
                ->exists();
            if ($exists) {
                return null;
            }
        }

        $pointsToAward = $pointsOverride ?? $event->points;

        if ($this->hasEventHandlers(self::EVENT_BEFORE_ADD_AWARD)) {
            $beforeEvent = new AwardEvent([
                'userId' => $userId,
                'event' => $event,
                'pointsToAward' => $pointsToAward,
            ]);
            $this->trigger(self::EVENT_BEFORE_ADD_AWARD, $beforeEvent);
            if (!$beforeEvent->isValid) {
                return null;
            }
            // Allow handlers to alter the points value.
            $pointsToAward = $beforeEvent->pointsToAward;
        }

        $beforeLevel = Points::getInstance()->levels->levelForUser($userId);

        $award = new PointAward();
        $award->userId = $userId;
        $award->eventId = $event->id;
        $award->pointsSnapshot = $pointsToAward;

        if (!Craft::$app->getElements()->saveElement($award)) {
            return null;
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_ADD_AWARD)) {
            $this->trigger(self::EVENT_AFTER_ADD_AWARD, new AwardEvent([
                'userId' => $userId,
                'event' => $event,
                'award' => $award,
                'pointsToAward' => $pointsToAward,
            ]));
        }

        $this->fireLevelChangedIfChanged($userId, $beforeLevel);

        return $award;
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
            ->select(['userId' => 'a.userId', 'total' => 'SUM([[a.pointsSnapshot]])'])
            ->from(['a' => '{{%points_awards}}'])
            ->innerJoin(['el' => '{{%elements}}'], '[[el.id]] = [[a.id]]')
            ->where(['el.dateDeleted' => null])
            ->groupBy(['a.userId'])
            ->orderBy(['total' => SORT_DESC, 'a.userId' => SORT_ASC])
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

    public function removeAward(int $userId, string $eventHandle): bool
    {
        $event = Points::getInstance()->events->getEventByHandle($eventHandle);
        if (!$event) {
            return false;
        }

        /** @var PointAward|null $award */
        $award = PointAward::find()
            ->userId($userId)
            ->eventId($event->id)
            ->orderBy(['dateCreated' => SORT_ASC])
            ->one();

        if (!$award) {
            return false;
        }

        if ($this->hasEventHandlers(self::EVENT_BEFORE_REMOVE_AWARD)) {
            $beforeEvent = new AwardEvent([
                'userId' => $userId,
                'event' => $event,
                'award' => $award,
                'pointsToAward' => $award->pointsSnapshot,
            ]);
            $this->trigger(self::EVENT_BEFORE_REMOVE_AWARD, $beforeEvent);
            if (!$beforeEvent->isValid) {
                return false;
            }
        }

        $beforeLevel = Points::getInstance()->levels->levelForUser($userId);

        if (!Craft::$app->getElements()->deleteElement($award)) {
            return false;
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_REMOVE_AWARD)) {
            $this->trigger(self::EVENT_AFTER_REMOVE_AWARD, new AwardEvent([
                'userId' => $userId,
                'event' => $event,
                'award' => $award,
                'pointsToAward' => $award->pointsSnapshot,
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
