<?php

namespace bymayo\points\migrations;

use craft\db\Migration;

class m260513_140000_create_points_levels_table extends Migration
{
    public function safeUp(): bool
    {
        if ($this->db->tableExists('{{%points_levels}}')) {
            return true;
        }

        $this->createTable('{{%points_levels}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'threshold' => $this->integer()->notNull()->defaultValue(0),
            'colour' => $this->string(7),
            'icon' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%points_levels}}', ['handle'], true);
        $this->createIndex(null, '{{%points_levels}}', ['threshold']);

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%points_levels}}');
        return true;
    }
}
