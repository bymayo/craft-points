<?php

namespace bymayo\points\limits;

use bymayo\points\conditions\RuleEvaluationContext;
use craft\db\Query;

/**
 * Composite per-user limit. Three optional, independently-applied constraints:
 *   - `max` + `period`: count cap (e.g. "max 10 per day"). `period: ever` = lifetime.
 *   - `cooldown`: minimum seconds between two awards of the same rule.
 *
 * Each field, when set, must pass. Unset fields are ignored.
 */
class MaxPerUserLimit extends BaseLimit
{
    public static function handle(): string { return 'maxPerUser'; }
    public static function label(): string { return 'Max per user'; }

    public static function check(array $config, RuleEvaluationContext $ctx): bool
    {
        return self::checkCount($config, $ctx)
            && self::checkCooldown($config, $ctx);
    }

    private static function checkCount(array $config, RuleEvaluationContext $ctx): bool
    {
        $max = (int) ($config['max'] ?? 0);
        if ($max <= 0) {
            return true;
        }

        $period = (string) ($config['period'] ?? 'ever');

        $query = (new Query())
            ->from(['a' => '{{%points_awards}}'])
            ->innerJoin(['el' => '{{%elements}}'], '[[el.id]] = [[a.id]]')
            ->where([
                'a.userId' => $ctx->userId,
                'a.ruleId' => $ctx->rule->id,
                'el.dateDeleted' => null,
            ]);

        if ($period !== 'ever') {
            $cutoff = match ($period) {
                'hour'  => '-1 hour',
                'day'   => '-1 day',
                'week'  => '-1 week',
                'month' => '-1 month',
                'year'  => '-1 year',
                default => null,
            };
            if ($cutoff !== null) {
                $since = (new \DateTime($cutoff))->format('Y-m-d H:i:s');
                $query->andWhere(['>=', 'el.dateCreated', $since]);
            }
        }

        return (int) $query->count() < $max;
    }

    private static function checkCooldown(array $config, RuleEvaluationContext $ctx): bool
    {
        $seconds = (int) ($config['cooldown'] ?? 0);
        if ($seconds <= 0) {
            return true;
        }

        $row = (new Query())
            ->select(['el.dateCreated'])
            ->from(['a' => '{{%points_awards}}'])
            ->innerJoin(['el' => '{{%elements}}'], '[[el.id]] = [[a.id]]')
            ->where([
                'a.userId' => $ctx->userId,
                'a.ruleId' => $ctx->rule->id,
                'el.dateDeleted' => null,
            ])
            ->orderBy(['el.dateCreated' => SORT_DESC])
            ->limit(1)
            ->one();

        if (!$row) {
            return true;
        }

        $last = strtotime($row['dateCreated']);
        return (time() - $last) >= $seconds;
    }
}
