<?php

namespace bymayo\points\conditions\rules\formie;

use bymayo\points\conditions\BaseConditionRule;
use bymayo\points\conditions\RuleEvaluationContext;
use Craft;

/**
 * Restricts a Form-submitted trigger to one or more specific Formie forms.
 *
 * Config shape: `{ type: 'formie.form', ids: [1, 2, 3] }`.
 * Empty `ids` = no constraint (matches any form), which doesn't really
 * make sense as a condition but is treated as "pass" for forward-compat.
 */
class FormieFormConditionRule extends BaseConditionRule
{
    public function handle(): string { return 'formie.form'; }
    public function label(): string { return 'Form is'; }
    public function group(): string { return 'Formie'; }
    public function appliesToSubjects(): ?array { return ['formieForm']; }

    public function evaluate(array $config, RuleEvaluationContext $ctx): bool
    {
        $ids = array_map('intval', $config['ids'] ?? []);
        if (empty($ids)) {
            return true;
        }

        $submission = $ctx->triggerEvent->submission ?? null;
        $formId = $submission?->formId ?? null;
        return $formId !== null && in_array((int) $formId, $ids, true);
    }

    public function renderConfigUi(int $index, array $config): string
    {
        return $this->renderFormTemplate('_includes/forms/checkboxSelect.twig', [
            'name' => "conditions[{$index}][ids]",
            'options' => $this->formOptions(),
            'values' => $config['ids'] ?? [],
            'showAllOption' => false,
        ]);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function formOptions(): array
    {
        if (
            !Craft::$app->getPlugins()->isPluginEnabled('formie')
            || !class_exists('verbb\\formie\\elements\\Form')
        ) {
            return [];
        }
        $options = [];
        foreach (\verbb\formie\elements\Form::find()->all() as $form) {
            $options[] = ['label' => (string) $form->title, 'value' => (string) $form->id];
        }
        return $options;
    }
}
