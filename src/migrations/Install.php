<?php

namespace bymayo\points\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp(): bool
    {
        $this->createTable('{{%points_events}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'points' => $this->integer()->notNull()->defaultValue(0),
            'multiple' => $this->boolean()->notNull()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%points_events}}', ['handle'], true);

        $this->createTable('{{%points_entries}}', [
            'id' => $this->integer()->notNull(),
            'eventId' => $this->integer()->notNull(),
            'userId' => $this->integer()->notNull(),
            'pointsSnapshot' => $this->integer()->notNull()->defaultValue(0),
            'PRIMARY KEY([[id]])',
        ]);

        $this->createIndex(null, '{{%points_entries}}', ['eventId']);
        $this->createIndex(null, '{{%points_entries}}', ['userId']);

        $this->addForeignKey(null, '{{%points_entries}}', ['id'], '{{%elements}}', ['id'], 'CASCADE');
        $this->addForeignKey(null, '{{%points_entries}}', ['eventId'], '{{%points_events}}', ['id'], 'CASCADE');
        $this->addForeignKey(null, '{{%points_entries}}', ['userId'], '{{%users}}', ['id'], 'CASCADE');

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
        $this->dropTableIfExists('{{%points_entries}}');
        $this->dropTableIfExists('{{%points_events}}');
        return true;
    }
}
