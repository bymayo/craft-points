<?php

namespace bymayo\points\migrations;

use craft\db\Migration;
use craft\db\Query;

class m260513_190000_rules_conditions_engine extends Migration
{
    public function safeUp(): bool
    {
        // Add the new columns alongside the existing ones.
        if (!$this->db->columnExists('{{%points_rules}}', 'conditions')) {
            $this->addColumn('{{%points_rules}}', 'conditions', $this->text()->null()->after('triggerConfig'));
        }
        if (!$this->db->columnExists('{{%points_rules}}', 'limits')) {
            $this->addColumn('{{%points_rules}}', 'limits', $this->text()->null()->after('conditions'));
        }
        if (!$this->db->columnExists('{{%points_rules}}', 'reward')) {
            $this->addColumn('{{%points_rules}}', 'reward', $this->text()->null()->after('limits'));
        }
        if (!$this->db->columnExists('{{%points_rules}}', 'enabled')) {
            $this->addColumn('{{%points_rules}}', 'enabled', $this->boolean()->notNull()->defaultValue(true)->after('reward'));
        }
        if (!$this->db->columnExists('{{%points_rules}}', 'activeFrom')) {
            $this->addColumn('{{%points_rules}}', 'activeFrom', $this->dateTime()->null()->after('enabled'));
        }
        if (!$this->db->columnExists('{{%points_rules}}', 'activeTo')) {
            $this->addColumn('{{%points_rules}}', 'activeTo', $this->dateTime()->null()->after('activeFrom'));
        }

        // Migrate existing rules from old shape (multiple, pointsType, points, triggerConfig)
        // to the new shape (conditions, limits, reward).
        $rows = (new Query())->from('{{%points_rules}}')->all();
        foreach ($rows as $row) {
            // Skip rules already migrated.
            if (!empty($row['reward'])) {
                continue;
            }

            $limits = [];
            if (empty($row['multiple'])) {
                $limits[] = ['type' => 'oncePerUser'];
            }

            $reward = ($row['pointsType'] ?? 'flat') === 'percent'
                ? ['type' => 'percent', 'percent' => (int)($row['points'] ?? 0)]
                : ['type' => 'flat', 'points' => (int)($row['points'] ?? 0)];

            $conditions = [];
            if (!empty($row['triggerConfig'])) {
                $tc = json_decode((string)$row['triggerConfig'], true) ?: [];
                $scopeIds = $tc['scopeIds'] ?? [];
                if (!empty($scopeIds)) {
                    $triggerHandle = (string)($row['trigger'] ?? '');
                    $condType = match(true) {
                        str_starts_with($triggerHandle, 'entry.') => 'section',
                        str_starts_with($triggerHandle, 'category.') => 'categoryGroup',
                        str_starts_with($triggerHandle, 'asset.') => 'volume',
                        default => null,
                    };
                    if ($condType) {
                        $conditions[] = ['type' => $condType, 'ids' => array_map('intval', $scopeIds)];
                    }
                }
            }

            $this->update('{{%points_rules}}', [
                'limits' => json_encode($limits),
                'reward' => json_encode($reward),
                'conditions' => !empty($conditions) ? json_encode($conditions) : null,
                'enabled' => true,
            ], ['id' => $row['id']]);
        }

        return true;
    }

    public function safeDown(): bool
    {
        foreach (['activeTo', 'activeFrom', 'enabled', 'reward', 'limits', 'conditions'] as $col) {
            if ($this->db->columnExists('{{%points_rules}}', $col)) {
                $this->dropColumn('{{%points_rules}}', $col);
            }
        }
        return true;
    }
}
