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
}
