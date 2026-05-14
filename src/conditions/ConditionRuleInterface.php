<?php

namespace bymayo\points\conditions;

interface ConditionRuleInterface
{
    /** Unique handle stored in the rule's `conditions` JSON, e.g. 'section'. */
    public static function handle(): string;

    /** Human-readable label for the rule picker. */
    public static function label(): string;

    /** Group label for organising rules in the picker (e.g. 'Element', 'Commerce'). */
    public static function group(): string;

    /**
     * Trigger subject handles this condition applies to (e.g. ['entry'], ['order']).
     * Return null or empty to apply to all subjects (and to Manual rules).
     *
     * @return string[]|null
     */
    public static function appliesToSubjects(): ?array;

    /**
     * Evaluate this condition against the trigger context.
     *
     * @param array $config The condition's stored config (e.g. ['ids' => [1, 2]]).
     */
    public static function evaluate(array $config, RuleEvaluationContext $ctx): bool;
}
