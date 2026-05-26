<?php

namespace bymayo\points\conditions;

abstract class BaseConditionRule implements ConditionRuleInterface
{
    public function group(): string
    {
        return 'General';
    }

    public function appliesToSubjects(): ?array
    {
        return null; // applies to all subjects, including Manual
    }
}
