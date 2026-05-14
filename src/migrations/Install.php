<?php

namespace bymayo\points\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp(): bool
    {
        $this->createTable('{{%points_rules}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'trigger' => $this->string(),
            'conditions' => $this->text(),
            'limits' => $this->text(),
            'reward' => $this->text(),
            'enabled' => $this->boolean()->notNull()->defaultValue(true),
            'activeFrom' => $this->dateTime(),
            'activeTo' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%points_rules}}', ['handle'], true);

        $this->createTable('{{%points_awards}}', [
            'id' => $this->integer()->notNull(),
            'ruleId' => $this->integer()->notNull(),
            'userId' => $this->integer()->notNull(),
            'pointsSnapshot' => $this->integer()->notNull()->defaultValue(0),
            'PRIMARY KEY([[id]])',
        ]);

        $this->createIndex(null, '{{%points_awards}}', ['ruleId']);
        $this->createIndex(null, '{{%points_awards}}', ['userId']);

        $this->addForeignKey(null, '{{%points_awards}}', ['id'], '{{%elements}}', ['id'], 'CASCADE');
        $this->addForeignKey(null, '{{%points_awards}}', ['ruleId'], '{{%points_rules}}', ['id'], 'CASCADE');
        $this->addForeignKey(null, '{{%points_awards}}', ['userId'], '{{%users}}', ['id'], 'CASCADE');

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
        $this->dropTableIfExists('{{%points_awards}}');
        $this->dropTableIfExists('{{%points_rules}}');
        return true;
    }
}
