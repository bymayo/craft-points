<?php

namespace bymayo\points\conditions\rules\freeform;

use bymayo\points\conditions\BaseConditionRule;
use bymayo\points\conditions\RuleEvaluationContext;

/**
 * Restricts a Freeform "Form submitted" trigger to one or more specific
 * forms.
 *
 * Config shape: `{ type: 'freeform.form', ids: [1, 2, 3] }`. Empty
 * `ids` = no constraint (passes for forward-compat).
 */
class FreeformFormConditionRule extends BaseConditionRule
{
    public static function handle(): string { return 'freeform.form'; }
    public static function label(): string { return 'Form is'; }
    public static function group(): string { return 'Freeform'; }
    public static function appliesToSubjects(): ?array { return ['freeformForm']; }

    public static function evaluate(array $config, RuleEvaluationContext $ctx): bool
    {
        $ids = array_map('intval', $config['ids'] ?? []);
        if (empty($ids)) {
            return true;
        }

        $submission = $ctx->triggerEvent->submission ?? null;
        $formId = $submission?->formId ?? null;
        return $formId !== null && in_array((int) $formId, $ids, true);
    }
}
