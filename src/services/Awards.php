<?php

namespace bymayo\points\services;

use bymayo\points\conditions\RuleEvaluationContext;
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

    /**
     * Total number of distinct users with at least one award.
     * Used for leaderboard pagination.
     */
    public function getDistinctRecipientCount(): int
    {
        return (int) (new Query())
            ->from(['a' => '{{%points_awards}}'])
            ->innerJoin(['el' => '{{%elements}}'], '[[el.id]] = [[a.id]]')
            ->where(['el.dateDeleted' => null])
            ->count('DISTINCT [[a.userId]]');
    }

    /**
     * Returns a map of ruleId → ['count' => N, 'lastAt' => \DateTime|null] for
     * all rules with at least one award. Used by the rules index table.
     *
     * @return array<int, array{count: int, lastAt: ?\DateTime}>
     */
    public function getStatsByRule(): array
    {
        $rows = (new Query())
            ->select([
                'ruleId' => 'a.ruleId',
                'cnt' => 'COUNT(*)',
                'lastAt' => 'MAX([[el.dateCreated]])',
            ])
            ->from(['a' => '{{%points_awards}}'])
            ->innerJoin(['el' => '{{%elements}}'], '[[el.id]] = [[a.id]]')
            ->where(['el.dateDeleted' => null])
            ->groupBy(['a.ruleId'])
            ->all();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['ruleId']] = [
                'count' => (int) $row['cnt'],
                'lastAt' => !empty($row['lastAt']) ? new \DateTime($row['lastAt']) : null,
            ];
        }
        return $map;
    }

    public function addAward(int $userId, string $ruleHandle, ?int $pointsOverride = null): ?PointAward
    {
        $rule = Points::getInstance()->rules->getRuleByHandle($ruleHandle);
        if (!$rule) {
            return null;
        }

        // Enforce rule limits even when called manually from Twig. Conditions can't
        // be enforced here since they need a trigger event for context — automatic
        // rule firings handle those upstream in Triggers::dispatch.
        if (!empty($rule->limits)) {
            $ctx = new RuleEvaluationContext([
                'userId' => $userId,
                'rule' => $rule,
                'triggerHandle' => 'manual',
                'triggerEvent' => null,
                'amount' => null,
            ]);
            if (!Points::getInstance()->limits->checkAll($rule->limits, $ctx)) {
                return null;
            }
        }

        // Default points fall back to the rule's reward config if no override given.
        $pointsToAward = $pointsOverride ?? (int) ($rule->reward['points'] ?? 0);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_ADD_AWARD)) {
            $beforeEvent = new AwardEvent([
                'userId' => $userId,
                'rule' => $rule,
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
        $award->ruleId = $rule->id;
        $award->pointsSnapshot = $pointsToAward;

        if (!Craft::$app->getElements()->saveElement($award)) {
            return null;
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_ADD_AWARD)) {
            $this->trigger(self::EVENT_AFTER_ADD_AWARD, new AwardEvent([
                'userId' => $userId,
                'rule' => $rule,
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

    public function removeAward(int $userId, string $ruleHandle): bool
    {
        $rule = Points::getInstance()->rules->getRuleByHandle($ruleHandle);
        if (!$rule) {
            return false;
        }

        /** @var PointAward|null $award */
        $award = PointAward::find()
            ->userId($userId)
            ->ruleId($rule->id)
            ->orderBy(['dateCreated' => SORT_ASC])
            ->one();

        if (!$award) {
            return false;
        }

        if ($this->hasEventHandlers(self::EVENT_BEFORE_REMOVE_AWARD)) {
            $beforeEvent = new AwardEvent([
                'userId' => $userId,
                'rule' => $rule,
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
                'rule' => $rule,
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
