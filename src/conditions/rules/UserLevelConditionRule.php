<?php

namespace bymayo\points\conditions\rules;

use bymayo\points\conditions\BaseConditionRule;
use bymayo\points\conditions\RuleEvaluationContext;
use bymayo\points\Points;

class UserLevelConditionRule extends BaseConditionRule
{
    public static function handle(): string { return 'userLevel'; }
    public static function label(): string { return 'Has reached level'; }
    public static function group(): string { return 'User'; }
    public static function appliesToSubjects(): ?array { return ['user']; }

    public static function evaluate(array $config, RuleEvaluationContext $ctx): bool
    {
        $handles = $config['levelHandles'] ?? [];
        if (empty($handles)) {
            return true;
        }
        $level = Points::getInstance()->levels->levelForUser($ctx->userId);
        return $level !== null && in_array($level->handle, $handles, true);
    }
}
