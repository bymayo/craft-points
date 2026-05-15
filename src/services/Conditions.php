<?php

namespace bymayo\points\services;

use bymayo\points\conditions\ConditionRuleInterface;
use bymayo\points\conditions\rules\commerce\OrderContainsProductConditionRule;
use bymayo\points\conditions\rules\commerce\OrderHasCouponConditionRule;
use bymayo\points\conditions\rules\commerce\OrderItemCountConditionRule;
use bymayo\points\conditions\rules\commerce\OrderTotalConditionRule;
use bymayo\points\conditions\rules\formie\FormieFormConditionRule;
use bymayo\points\conditions\rules\freeform\FreeformFormConditionRule;
use bymayo\points\conditions\rules\SectionConditionRule;
use bymayo\points\conditions\rules\UserGroupConditionRule;
use bymayo\points\conditions\RuleEvaluationContext;
use bymayo\points\events\RegisterConditionRulesEvent;
use bymayo\points\Points;
use yii\base\Component;

class Conditions extends Component
{
    public const EVENT_REGISTER_CONDITION_RULES = 'registerConditionRules';

    /** @var array<string, string> Map of handle → class. */
    private array $_byHandle = [];

    public function init(): void
    {
        parent::init();
        $this->registerRules();
    }

    /** @return array<string, string> */
    public function getAll(): array
    {
        return $this->_byHandle;
    }

    /**
     * Subject metadata for each condition, used by the CP UI to filter
     * the condition picker based on the chosen trigger subject.
     *
     * @return array<string, array{subjects: ?array<int,string>}>
     */
    public function getMetaForJs(): array
    {
        $meta = [];
        foreach ($this->_byHandle as $handle => $class) {
            $meta[$handle] = [
                'subjects' => $class::appliesToSubjects(),
            ];
        }
        return $meta;
    }

    public function getByHandle(string $handle): ?string
    {
        return $this->_byHandle[$handle] ?? null;
    }

    /**
     * Returns true if every condition in the spec list passes.
     *
     * Conditions whose type isn't registered (e.g. a Pro condition on a Lite install)
     * are silently skipped — they don't cause the rule to fail.
     */
    public function evaluateAll(array $specs, RuleEvaluationContext $ctx): bool
    {
        foreach ($specs as $spec) {
            $type = $spec['type'] ?? null;
            if (!$type) continue;
            $class = $this->_byHandle[$type] ?? null;
            if (!$class) continue;
            /** @var class-string<ConditionRuleInterface> $class */
            if (!$class::evaluate($spec, $ctx)) {
                return false;
            }
        }
        return true;
    }

    private function registerRules(): void
    {
        $defaults = [
            SectionConditionRule::class,
            UserGroupConditionRule::class,
        ];

        // Formie - require Formie installed.
        if (\Craft::$app->getPlugins()->isPluginEnabled('formie')) {
            $defaults[] = FormieFormConditionRule::class;
        }

        // Freeform - require Freeform installed.
        if (\Craft::$app->getPlugins()->isPluginEnabled('freeform')) {
            $defaults[] = FreeformFormConditionRule::class;
        }

        // Pro-only: Commerce conditions.
        $isPro = Points::getInstance()->is(Points::EDITION_PRO);
        if ($isPro && \Craft::$app->getPlugins()->isPluginEnabled('commerce')) {
            $defaults[] = OrderTotalConditionRule::class;
            $defaults[] = OrderItemCountConditionRule::class;
            $defaults[] = OrderHasCouponConditionRule::class;
            $defaults[] = OrderContainsProductConditionRule::class;
        }

        $event = new RegisterConditionRulesEvent(['conditionRules' => $defaults]);
        $this->trigger(self::EVENT_REGISTER_CONDITION_RULES, $event);

        foreach ($event->conditionRules as $class) {
            if (!is_subclass_of($class, ConditionRuleInterface::class)) {
                continue;
            }
            $this->_byHandle[$class::handle()] = $class;
        }
    }
}
