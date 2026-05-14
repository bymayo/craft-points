<?php

namespace bymayo\points\migrations;

use craft\db\Migration;

/**
 * Adds a single-row settings store so the plugin's settings live in the DB
 * (admin-managed, per-environment) rather than in project config.
 *
 * Devs can still override any field per-environment via `config/points.php`.
 */
class m260514_120000_create_points_settings_table extends Migration
{
    public function safeUp(): bool
    {
        if ($this->db->tableExists('{{%points_settings}}')) {
            return true;
        }

        $this->createTable('{{%points_settings}}', [
            'id' => $this->primaryKey(),
            'settings' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%points_settings}}');
        return true;
    }
}
