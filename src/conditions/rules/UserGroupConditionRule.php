<?php

namespace bymayo\points\conditions\rules;

use bymayo\points\conditions\BaseConditionRule;
use bymayo\points\conditions\RuleEvaluationContext;
use Craft;

class UserGroupConditionRule extends BaseConditionRule
{
    public function handle(): string { return 'userGroup'; }
    public function label(): string { return 'Is in group'; }
    public function group(): string { return 'User'; }
    public function appliesToSubjects(): ?array { return ['user']; }

    public function evaluate(array $config, RuleEvaluationContext $ctx): bool
    {
        $groupIds = array_map('intval', $config['groupIds'] ?? []);
        if (empty($groupIds)) {
            return true;
        }

        $user = Craft::$app->getUsers()->getUserById($ctx->userId);
        if (!$user) {
            return false;
        }

        foreach ($user->getGroups() as $group) {
            if (in_array((int)$group->id, $groupIds, true)) {
                return true;
            }
        }
        return false;
    }

    public function renderConfigUi(int $index, array $config): string
    {
        $options = [];
        foreach (Craft::$app->getUserGroups()->getAllGroups() as $group) {
            $options[] = ['label' => $group->name, 'value' => (string) $group->id];
        }

        return $this->renderFormTemplate('_includes/forms/checkboxSelect.twig', [
            'name' => "conditions[{$index}][groupIds]",
            'options' => $options,
            'values' => $config['groupIds'] ?? [],
            'showAllOption' => false,
        ]);
    }
}
