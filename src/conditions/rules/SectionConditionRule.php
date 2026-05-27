<?php

namespace bymayo\points\conditions\rules;

use bymayo\points\conditions\BaseConditionRule;
use bymayo\points\conditions\RuleEvaluationContext;
use Craft;

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

    public function renderConfigUi(int $index, array $config): string
    {
        $options = [];
        foreach (Craft::$app->getEntries()->getAllSections() as $section) {
            $options[] = ['label' => $section->name, 'value' => (string) $section->id];
        }

        return $this->renderFormTemplate('_includes/forms/checkboxSelect.twig', [
            'name' => "conditions[{$index}][ids]",
            'options' => $options,
            'values' => $config['ids'] ?? [],
            'showAllOption' => false,
        ]);
    }
}
