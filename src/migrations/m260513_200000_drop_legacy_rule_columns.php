<?php

namespace bymayo\points\migrations;

use craft\db\Migration;

class m260513_200000_drop_legacy_rule_columns extends Migration
{
    public function safeUp(): bool
    {
        foreach (['triggerConfig', 'multiple', 'pointsType', 'points'] as $col) {
            if ($this->db->columnExists('{{%points_rules}}', $col)) {
                $this->dropColumn('{{%points_rules}}', $col);
            }
        }
        return true;
    }

    public function safeDown(): bool
    {
        if (!$this->db->columnExists('{{%points_rules}}', 'points')) {
            $this->addColumn('{{%points_rules}}', 'points', $this->integer()->notNull()->defaultValue(0));
        }
        if (!$this->db->columnExists('{{%points_rules}}', 'pointsType')) {
            $this->addColumn('{{%points_rules}}', 'pointsType', $this->string(20)->notNull()->defaultValue('flat'));
        }
        if (!$this->db->columnExists('{{%points_rules}}', 'multiple')) {
            $this->addColumn('{{%points_rules}}', 'multiple', $this->boolean()->notNull()->defaultValue(false));
        }
        if (!$this->db->columnExists('{{%points_rules}}', 'triggerConfig')) {
            $this->addColumn('{{%points_rules}}', 'triggerConfig', $this->text());
        }
        return true;
    }
}
