<?php

namespace bymayo\points\conditions;

use bymayo\points\models\Rule;

/**
 * Value object passed to conditions, limits, and rewards when a Rule is evaluated.
 */
class RuleEvaluationContext
{
    public int $userId;
    public Rule $rule;
    public string $triggerHandle;

    /** The underlying Yii event the trigger fired on (e.g. Order, Entry, ModelEvent). */
    public mixed $triggerEvent = null;

    /** Monetary amount provided by the trigger, if any (e.g. order total). */
    public ?float $amount = null;

    /** The ID of the scope-relevant element (e.g. sectionId for entry triggers). */
    public ?int $scopeId = null;

    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}
