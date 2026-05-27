<?php

namespace bymayo\points\conditions\rules\commerce;

use bymayo\points\conditions\BaseConditionRule;
use bymayo\points\conditions\RuleEvaluationContext;
use Craft;
use craft\helpers\Cp;
use craft\helpers\Html;

class OrderTotalConditionRule extends BaseConditionRule
{
    public function handle(): string { return 'commerce.orderTotal'; }
    public function label(): string { return 'Total'; }
    public function group(): string { return 'Commerce'; }
    public function appliesToSubjects(): ?array { return ['order']; }

    public function isInline(): bool { return true; }

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

    public function renderConfigUi(int $index, array $config): string
    {
        $prefix = "conditions[{$index}]";
        $operatorOptions = [
            ['label' => Craft::t('points', 'equals'), 'value' => '='],
            ['label' => Craft::t('points', 'does not equal'), 'value' => '!='],
            ['label' => Craft::t('points', 'is less than'), 'value' => '<'],
            ['label' => Craft::t('points', 'is less than or equals'), 'value' => '<='],
            ['label' => Craft::t('points', 'is greater than'), 'value' => '>'],
            ['label' => Craft::t('points', 'is greater than or equals'), 'value' => '>='],
            ['label' => Craft::t('points', 'is between…'), 'value' => 'between'],
        ];

        return Cp::selectHtml([
                'name' => $prefix . '[operator]',
                'options' => $operatorOptions,
                'value' => $config['operator'] ?? '>=',
                'class' => 'op-select',
            ])
            . Html::tag('span', Cp::textHtml([
                'name' => $prefix . '[value]',
                'type' => 'number',
                'value' => (string) ($config['value'] ?? ''),
            ]), ['class' => 'op-value'])
            . Html::tag('span', Craft::t('points', 'and'), ['class' => 'op-and cnd-suffix'])
            . Html::tag('span', Cp::textHtml([
                'name' => $prefix . '[value2]',
                'type' => 'number',
                'value' => (string) ($config['value2'] ?? ''),
            ]), ['class' => 'op-value2']);
    }
}
