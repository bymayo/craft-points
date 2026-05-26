<?php

namespace bymayo\points\events;

use bymayo\points\conditions\ConditionRuleInterface;
use yii\base\Event;

/**
 * Other plugins can register additional condition rule instances here.
 *
 * Prefer the sugar API when you have an instance handy:
 *   Points::getInstance()->conditions->register(new MyCondition());
 *
 * Or, for conditions that belong to a custom trigger, return them from
 * TriggerInterface::conditions() — they'll be auto-registered.
 *
 * @property ConditionRuleInterface[] $conditionRules
 */
class RegisterConditionRulesEvent extends Event
{
    /** @var ConditionRuleInterface[] */
    public array $conditionRules = [];
}
