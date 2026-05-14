<?php

namespace bymayo\points\migrations;

use craft\db\Migration;

class m260513_180000_rename_events_to_rules extends Migration
{
    public function safeUp(): bool
    {
        if (
            $this->db->tableExists('{{%points_events}}')
            && !$this->db->tableExists('{{%points_rules}}')
        ) {
            $this->renameTable('{{%points_events}}', '{{%points_rules}}');
        }

        if (
            $this->db->columnExists('{{%points_awards}}', 'eventId')
            && !$this->db->columnExists('{{%points_awards}}', 'ruleId')
        ) {
            $this->renameColumn('{{%points_awards}}', 'eventId', 'ruleId');
        }

        return true;
    }

    public function safeDown(): bool
    {
        if (
            $this->db->columnExists('{{%points_awards}}', 'ruleId')
            && !$this->db->columnExists('{{%points_awards}}', 'eventId')
        ) {
            $this->renameColumn('{{%points_awards}}', 'ruleId', 'eventId');
        }

        if (
            $this->db->tableExists('{{%points_rules}}')
            && !$this->db->tableExists('{{%points_events}}')
        ) {
            $this->renameTable('{{%points_rules}}', '{{%points_events}}');
        }

        return true;
    }
}
