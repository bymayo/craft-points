<?php

namespace bymayo\points\conditions;

abstract class BaseConditionRule implements ConditionRuleInterface
{
    public static function group(): string
    {
        return 'General';
    }

    public static function appliesToSubjects(): ?array
    {
        return null; // applies to all subjects, including Manual
    }
}
