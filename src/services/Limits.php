<?php

namespace bymayo\points\services;

use bymayo\points\conditions\RuleEvaluationContext;
use bymayo\points\events\RegisterLimitsEvent;
use bymayo\points\limits\LimitInterface;
use bymayo\points\limits\MaxPerUserLimit;
use bymayo\points\limits\OncePerUserLimit;
use yii\base\Component;

class Limits extends Component
{
    public const EVENT_REGISTER_LIMITS = 'registerLimits';

    /** @var array<string, string> Map of handle → class. */
    private array $_byHandle = [];

    public function init(): void
    {
        parent::init();
        $this->registerLimits();
    }

    /** @return array<string, string> */
    public function getAll(): array
    {
        return $this->_byHandle;
    }

    public function getByHandle(string $handle): ?string
    {
        return $this->_byHandle[$handle] ?? null;
    }

    /**
     * Returns true if every limit in the spec list allows the rule to fire.
     */
    public function checkAll(array $specs, RuleEvaluationContext $ctx): bool
    {
        foreach ($specs as $spec) {
            $type = $spec['type'] ?? null;
            if (!$type) continue;
            $class = $this->_byHandle[$type] ?? null;
            if (!$class) continue;
            /** @var class-string<LimitInterface> $class */
            if (!$class::check($spec, $ctx)) {
                return false;
            }
        }
        return true;
    }

    private function registerLimits(): void
    {
        $defaults = [
            OncePerUserLimit::class,
            MaxPerUserLimit::class,
        ];

        $event = new RegisterLimitsEvent(['limits' => $defaults]);
        $this->trigger(self::EVENT_REGISTER_LIMITS, $event);

        foreach ($event->limits as $class) {
            if (!is_subclass_of($class, LimitInterface::class)) {
                continue;
            }
            $this->_byHandle[$class::handle()] = $class;
        }
    }
}
