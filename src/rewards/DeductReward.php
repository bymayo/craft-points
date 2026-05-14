<?php

namespace bymayo\points\rewards;

use bymayo\points\conditions\RuleEvaluationContext;
use bymayo\points\Points;

/**
 * Deducts a flat number of points from the user's balance.
 * Config: { points: 100 } — always interpreted as a positive amount, returned negative.
 */
class DeductReward extends BaseReward
{
    public static function handle(): string { return 'deduct'; }

    public static function label(): string
    {
        return 'Deduct ' . Points::getInstance()->getSettings()->currencyNamePlural;
    }

    public static function calculate(array $config, RuleEvaluationContext $ctx): int
    {
        $points = max(0, (int) ($config['points'] ?? 0));
        return -$points;
    }
}
