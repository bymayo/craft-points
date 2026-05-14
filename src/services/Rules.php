<?php

namespace bymayo\points\services;

use bymayo\points\models\Rule;
use bymayo\points\records\RuleRecord;
use yii\base\Component;
use yii\base\Exception;

class Rules extends Component
{
    /**
     * @return Rule[]
     */
    public function getAllRules(): array
    {
        $records = RuleRecord::find()->orderBy(['name' => SORT_ASC])->all();
        return array_map(fn(RuleRecord $r) => $this->createRuleFromRecord($r), $records);
    }

    public function getRuleById(int $id): ?Rule
    {
        $record = RuleRecord::findOne(['id' => $id]);
        return $record ? $this->createRuleFromRecord($record) : null;
    }

    public function getRuleByHandle(string $handle): ?Rule
    {
        $record = RuleRecord::findOne(['handle' => $handle]);
        return $record ? $this->createRuleFromRecord($record) : null;
    }

    /**
     * @return Rule[]
     */
    public function getRulesByTrigger(string $triggerHandle): array
    {
        $records = RuleRecord::find()->where(['trigger' => $triggerHandle])->all();
        return array_map(fn(RuleRecord $r) => $this->createRuleFromRecord($r), $records);
    }

    public function saveRule(Rule $rule, bool $runValidation = true): bool
    {
        if ($runValidation && !$rule->validate()) {
            return false;
        }

        if ($rule->id) {
            $record = RuleRecord::findOne(['id' => $rule->id]);
            if (!$record) {
                throw new Exception("No rule exists with the ID {$rule->id}.");
            }
        } else {
            $record = new RuleRecord();
        }

        $record->name = $rule->name;
        $record->handle = $rule->handle;
        $record->trigger = $rule->trigger ?: null;
        $record->conditions = !empty($rule->conditions) ? json_encode($rule->conditions) : null;
        $record->limits = !empty($rule->limits) ? json_encode($rule->limits) : null;
        $record->reward = json_encode($rule->reward);
        $record->enabled = $rule->enabled;
        $record->activeFrom = $rule->activeFrom ?: null;
        $record->activeTo = $rule->activeTo ?: null;

        if (!$record->save(false)) {
            return false;
        }

        $rule->id = $record->id;
        $rule->uid = $record->uid;
        return true;
    }

    public function deleteRuleById(int $id): bool
    {
        $record = RuleRecord::findOne(['id' => $id]);
        if (!$record) {
            return false;
        }
        return (bool) $record->delete();
    }

    private function createRuleFromRecord(RuleRecord $r): Rule
    {
        $rule = new Rule();
        $rule->id = (int) $r->id;
        $rule->name = (string) $r->name;
        $rule->handle = (string) $r->handle;
        $rule->trigger = $r->trigger ?: null;
        $rule->conditions = $r->conditions ? (json_decode($r->conditions, true) ?: []) : [];
        $rule->limits = $r->limits ? (json_decode($r->limits, true) ?: []) : [];
        $rule->reward = $r->reward
            ? (json_decode($r->reward, true) ?: ['type' => 'flat', 'points' => 0])
            : ['type' => 'flat', 'points' => 0];
        $rule->enabled = (bool) $r->enabled;
        $rule->activeFrom = $r->activeFrom ?: null;
        $rule->activeTo = $r->activeTo ?: null;
        $rule->uid = $r->uid;
        return $rule;
    }
}
