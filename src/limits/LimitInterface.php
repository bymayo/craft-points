<?php

namespace bymayo\points\limits;

use bymayo\points\conditions\RuleEvaluationContext;

interface LimitInterface
{
    public static function handle(): string;
    public static function label(): string;

    /**
     * Return true if the rule is still ALLOWED to fire (limit not exhausted).
     * Return false to block this dispatch.
     */
    public static function check(array $config, RuleEvaluationContext $ctx): bool;
}
