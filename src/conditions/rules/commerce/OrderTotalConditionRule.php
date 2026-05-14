<?php

namespace bymayo\points\conditions\rules\commerce;

use bymayo\points\conditions\BaseConditionRule;
use bymayo\points\conditions\RuleEvaluationContext;

class OrderTotalConditionRule extends BaseConditionRule
{
    public static function handle(): string { return 'commerce.orderTotal'; }
    public static function label(): string { return 'Total'; }
    public static function group(): string { return 'Commerce'; }
    public static function appliesToSubjects(): ?array { return ['order']; }

    public static function evaluate(array $config, RuleEvaluationContext $ctx): bool
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
