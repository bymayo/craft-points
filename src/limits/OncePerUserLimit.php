<?php

namespace bymayo\points\limits;

use bymayo\points\conditions\RuleEvaluationContext;
use bymayo\points\elements\PointAward;

class OncePerUserLimit extends BaseLimit
{
    public static function handle(): string { return 'oncePerUser'; }
    public static function label(): string { return 'Once per user'; }

    public static function check(array $config, RuleEvaluationContext $ctx): bool
    {
        $exists = PointAward::find()
            ->userId($ctx->userId)
            ->ruleId($ctx->rule->id)
            ->exists();
        return !$exists;
    }
}
