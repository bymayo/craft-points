<?php

namespace bymayo\points\rewards;

use bymayo\points\conditions\RuleEvaluationContext;

interface RewardInterface
{
    public static function handle(): string;
    public static function label(): string;

    /** Calculate the integer points value to award. Return 0 to skip. */
    public static function calculate(array $config, RuleEvaluationContext $ctx): int;

    /**
     * Trigger subjects this reward type applies to. Null or empty array = all
     * subjects (and Manual rules). Use to hide rewards that need a specific
     * context: e.g. percent-of-order-total only makes sense for `order`.
     *
     * @return string[]|null
     */
    public static function appliesToSubjects(): ?array;
}
