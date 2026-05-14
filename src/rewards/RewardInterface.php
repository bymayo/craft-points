<?php

namespace bymayo\points\rewards;

use bymayo\points\conditions\RuleEvaluationContext;

interface RewardInterface
{
    public static function handle(): string;
    public static function label(): string;

    /** Calculate the integer points value to award. Return 0 to skip. */
    public static function calculate(array $config, RuleEvaluationContext $ctx): int;
}
