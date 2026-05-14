<?php

namespace bymayo\points\conditions\rules;

use bymayo\points\conditions\BaseConditionRule;
use bymayo\points\conditions\RuleEvaluationContext;

class SectionConditionRule extends BaseConditionRule
{
    public static function handle(): string { return 'section'; }
    public static function label(): string { return 'Is in section'; }
    public static function group(): string { return 'Element'; }
    public static function appliesToSubjects(): ?array { return ['entry']; }

    public static function evaluate(array $config, RuleEvaluationContext $ctx): bool
    {
        $ids = array_map('intval', $config['ids'] ?? []);
        if (empty($ids)) {
            return true;
        }

        $entry = $ctx->triggerEvent?->sender ?? null;
        $sectionId = $entry?->sectionId ?? null;
        return $sectionId !== null && in_array((int)$sectionId, $ids, true);
    }
}
