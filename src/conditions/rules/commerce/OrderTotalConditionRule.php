<?php

namespace bymayo\points\conditions\rules\commerce;

use bymayo\points\conditions\BaseConditionRule;
use bymayo\points\conditions\RuleEvaluationContext;

class OrderTotalConditionRule extends BaseConditionRule
{
    public function handle(): string { return 'commerce.orderTotal'; }
    public function label(): string { return 'Total'; }
    public function group(): string { return 'Commerce'; }
    public function appliesToSubjects(): ?array { return ['order']; }

    public function evaluate(array $config, RuleEvaluationContext $ctx): bool
    {
        if ($ctx->amount === null) {
            return false;
        }
        $operator = $config['operator'] ?? '>=';
        $value = (float)($config['value'] ?? 0);
        $value2 = (float)($config['value2'] ?? 0);
        return match($operator) {
            '>'  => $ctx->amount > $value,
            '>=' => $ctx->amount >= $value,
            '<'  => $ctx->amount < $value,
            '<=' => $ctx->amount <= $value,
            '='  => $ctx->amount == $value,
            '!=' => $ctx->amount != $value,
            'between' => $ctx->amount >= min($value, $value2) && $ctx->amount <= max($value, $value2),
            default => false,
        };
    }
}
