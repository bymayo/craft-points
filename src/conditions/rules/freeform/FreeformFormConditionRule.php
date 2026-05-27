<?php

namespace bymayo\points\conditions\rules\freeform;

use bymayo\points\conditions\BaseConditionRule;
use bymayo\points\conditions\RuleEvaluationContext;
use Craft;

/**
 * Restricts a Freeform "Form submitted" trigger to one or more specific
 * forms.
 *
 * Config shape: `{ type: 'freeform.form', ids: [1, 2, 3] }`. Empty
 * `ids` = no constraint (passes for forward-compat).
 */
class FreeformFormConditionRule extends BaseConditionRule
{
    public function handle(): string { return 'freeform.form'; }
    public function label(): string { return 'Form is'; }
    public function group(): string { return 'Freeform'; }
    public function appliesToSubjects(): ?array { return ['freeformForm']; }

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
            !Craft::$app->getPlugins()->isPluginEnabled('freeform')
            || !class_exists('Solspace\\Freeform\\Freeform')
        ) {
            return [];
        }
        try {
            $forms = \Solspace\Freeform\Freeform::getInstance()->forms->getAllForms();
        } catch (\Throwable) {
            return [];
        }
        $options = [];
        foreach ($forms as $form) {
            $options[] = [
                'label' => (string) $form->getName(),
                'value' => (string) $form->getId(),
            ];
        }
        return $options;
    }
}
