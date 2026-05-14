<?php

namespace bymayo\points\rewards;

use bymayo\points\conditions\RuleEvaluationContext;
use bymayo\points\Points;

class FlatReward extends BaseReward
{
    public static function handle(): string { return 'flat'; }

    public static function label(): string
    {
        return 'Add ' . Points::getInstance()->getSettings()->currencyNamePlural;
    }

    public static function calculate(array $config, RuleEvaluationContext $ctx): int
    {
        return max(0, (int)($config['points'] ?? 0));
    }
}
