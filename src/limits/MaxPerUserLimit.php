<?php

namespace bymayo\points\limits;

use bymayo\points\conditions\RuleEvaluationContext;
use craft\db\Query;

class MaxPerUserLimit extends BaseLimit
{
    public static function handle(): string { return 'maxPerUser'; }
    public static function label(): string { return 'Max per user'; }

    public static function check(array $config, RuleEvaluationContext $ctx): bool
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
}
