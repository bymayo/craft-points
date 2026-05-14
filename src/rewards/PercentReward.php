<?php

namespace bymayo\points\rewards;

use bymayo\points\conditions\RuleEvaluationContext;
use bymayo\points\Points;

class PercentReward extends BaseReward
{
    public static function handle(): string { return 'percent'; }

    public static function label(): string
    {
        return 'Add ' . Points::getInstance()->getSettings()->currencyNamePlural . ' (% of order total)';
    }

    public static function calculate(array $config, RuleEvaluationContext $ctx): int
    {
        if ($ctx->amount === null) {
            return 0;
        }
        $percent = (float)($config['percent'] ?? 0);
        return max(0, (int)floor($ctx->amount * $percent / 100));
    }
}
