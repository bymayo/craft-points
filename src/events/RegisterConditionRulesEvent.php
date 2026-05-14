<?php

namespace bymayo\points\events;

use yii\base\Event;

class RegisterConditionRulesEvent extends Event
{
    /** @var string[] List of ConditionRuleInterface class names. */
    public array $conditionRules = [];
}
