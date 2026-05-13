<?php

namespace bymayo\points\migrations;

use craft\db\Migration;

class m260513_150000_add_trigger_columns_to_events extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%points_events}}', 'trigger')) {
            $this->addColumn('{{%points_events}}', 'trigger', $this->string()->null()->after('multiple'));
        }
        if (!$this->db->columnExists('{{%points_events}}', 'triggerConfig')) {
            $this->addColumn('{{%points_events}}', 'triggerConfig', $this->text()->null()->after('trigger'));
        }
        return true;
    }

    public function safeDown(): bool
    {
        if ($this->db->columnExists('{{%points_events}}', 'triggerConfig')) {
            $this->dropColumn('{{%points_events}}', 'triggerConfig');
        }
        if ($this->db->columnExists('{{%points_events}}', 'trigger')) {
            $this->dropColumn('{{%points_events}}', 'trigger');
        }
        return true;
    }
}
