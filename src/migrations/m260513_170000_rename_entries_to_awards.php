<?php

namespace bymayo\points\migrations;

use craft\db\Migration;

class m260513_170000_rename_entries_to_awards extends Migration
{
    public function safeUp(): bool
    {
        if (
            $this->db->tableExists('{{%points_entries}}')
            && !$this->db->tableExists('{{%points_awards}}')
        ) {
            $this->renameTable('{{%points_entries}}', '{{%points_awards}}');
        }
        return true;
    }

    public function safeDown(): bool
    {
        if (
            $this->db->tableExists('{{%points_awards}}')
            && !$this->db->tableExists('{{%points_entries}}')
        ) {
            $this->renameTable('{{%points_awards}}', '{{%points_entries}}');
        }
        return true;
    }
}
