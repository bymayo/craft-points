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

    /** @var array<string, ConditionRuleInterface> Handle → condition. First registration wins. */
    private array $_byHandle = [];

    /** @var ConditionRuleInterface[] Buffered registrations made before init() ran. */
    private array $_pending = [];

    private bool $_initialized = false;

    public function init(): void
    {
        parent::init();
        $this->registerRules();
        $this->_initialized = true;
    }

    /** @return array<string, ConditionRuleInterface> */
    public function getAll(): array
    {
        return $this->_byHandle;
    }

    public function getByHandle(string $handle): ?ConditionRuleInterface
    {
        return $this->_byHandle[$handle] ?? null;
    }

    /**
     * Sugar API for third-party plugins. Call from your plugin's init():
     *
     *     Points::getInstance()->conditions->register(new MyCondition());
     *
     * Custom triggers can also bundle their own conditions by returning
     * them from TriggerInterface::conditions() — the Triggers service
     * forwards those here automatically.
     */
    public function register(ConditionRuleInterface $condition): void
    {
        if (!$this->_initialized) {
            $this->_pending[] = $condition;
            return;
        }
        $this->addCondition($condition);
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
        foreach ($this->_byHandle as $handle => $condition) {
            $meta[$handle] = [
                'subjects' => $condition->appliesToSubjects(),
            ];
        }
        return $meta;
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
            $condition = $this->_byHandle[$type] ?? null;
            if (!$condition) continue;
            if (!$condition->evaluate($spec, $ctx)) {
                return false;
            }
        }
        return true;
    }

    private function registerRules(): void
    {
        $defaults = [
            new SectionConditionRule(),
            new UserGroupConditionRule(),
        ];

        // Formie - require Formie installed.
        if (\Craft::$app->getPlugins()->isPluginEnabled('formie')) {
            $defaults[] = new FormieFormConditionRule();
        }

        // Freeform - require Freeform installed.
        if (\Craft::$app->getPlugins()->isPluginEnabled('freeform')) {
            $defaults[] = new FreeformFormConditionRule();
        }

        // Pro-only: Commerce conditions.
        $isPro = Points::getInstance()->is(Points::EDITION_PRO);
        if ($isPro && \Craft::$app->getPlugins()->isPluginEnabled('commerce')) {
            $defaults[] = new OrderTotalConditionRule();
            $defaults[] = new OrderItemCountConditionRule();
            $defaults[] = new OrderHasCouponConditionRule();
            $defaults[] = new OrderContainsProductConditionRule();
        }

        $event = new RegisterConditionRulesEvent(['conditionRules' => $defaults]);
        $this->trigger(self::EVENT_REGISTER_CONDITION_RULES, $event);

        foreach ($event->conditionRules as $condition) {
            if ($condition instanceof ConditionRuleInterface) {
                $this->addCondition($condition);
            }
        }
        foreach ($this->_pending as $condition) {
            $this->addCondition($condition);
        }
        $this->_pending = [];
    }

    private function addCondition(ConditionRuleInterface $condition): void
    {
        $handle = $condition->handle();
        if (isset($this->_byHandle[$handle])) {
            return; // first registration wins
        }
        $this->_byHandle[$handle] = $condition;
    }
}
