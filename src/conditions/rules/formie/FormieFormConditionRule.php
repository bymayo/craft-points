<?php

namespace bymayo\points\conditions\rules\formie;

use bymayo\points\conditions\BaseConditionRule;
use bymayo\points\conditions\RuleEvaluationContext;

/**
 * Restricts a Form-submitted trigger to one or more specific Formie forms.
 *
 * Config shape: `{ type: 'formie.form', ids: [1, 2, 3] }`.
 * Empty `ids` = no constraint (matches any form), which doesn't really
 * make sense as a condition but is treated as "pass" for forward-compat.
 */
class FormieFormConditionRule extends BaseConditionRule
{
    public static function handle(): string { return 'formie.form'; }
    public static function label(): string { return 'Form is'; }
    public static function group(): string { return 'Formie'; }
    public static function appliesToSubjects(): ?array { return ['formieForm']; }

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
