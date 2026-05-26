<?php

namespace bymayo\points\conditions\rules;

use bymayo\points\conditions\BaseConditionRule;
use bymayo\points\conditions\RuleEvaluationContext;

class SectionConditionRule extends BaseConditionRule
{
    public function handle(): string { return 'section'; }
    public function label(): string { return 'Is in section'; }
    public function group(): string { return 'Element'; }
    public function appliesToSubjects(): ?array { return ['entry']; }

    public function evaluate(array $config, RuleEvaluationContext $ctx): bool
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
