<?php

namespace bymayo\points\conditions\rules\commerce;

use bymayo\points\conditions\BaseConditionRule;
use bymayo\points\conditions\RuleEvaluationContext;

class OrderItemCountConditionRule extends BaseConditionRule
{
    public static function handle(): string { return 'commerce.orderItemCount'; }
    public static function label(): string { return 'Item count'; }
    public static function group(): string { return 'Commerce'; }
    public static function appliesToSubjects(): ?array { return ['order']; }

    public static function evaluate(array $config, RuleEvaluationContext $ctx): bool
    {
        $order = $ctx->triggerEvent?->sender ?? null;
        if (!$order || !method_exists($order, 'getLineItems')) {
            return false;
        }

        $count = 0;
        foreach ($order->getLineItems() as $item) {
            $count += (int)($item->qty ?? 0);
        }

        $operator = $config['operator'] ?? '>=';
        $value = (int)($config['value'] ?? 0);
        $value2 = (int)($config['value2'] ?? 0);
        return match($operator) {
            '>'  => $count > $value,
            '>=' => $count >= $value,
            '<'  => $count < $value,
            '<=' => $count <= $value,
            '='  => $count == $value,
            '!=' => $count != $value,
            'between' => $count >= min($value, $value2) && $count <= max($value, $value2),
            default => false,
        };
    }
}
