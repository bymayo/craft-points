<?php

namespace bymayo\points\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\StringHelper;

class m260514_000000_add_order_redemptions extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%points_order_redemptions}}')) {
            $this->createTable('{{%points_order_redemptions}}', [
                'id' => $this->primaryKey(),
                'orderId' => $this->integer()->notNull(),
                'userId' => $this->integer()->notNull(),
                'points' => $this->integer()->notNull(),
                'discountAmount' => $this->decimal(14, 4)->notNull(),
                // Set when the points are actually deducted (i.e. order paid). Null while pending.
                'awardId' => $this->integer(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            // One redemption per order — re-applying replaces it.
            $this->createIndex(null, '{{%points_order_redemptions}}', ['orderId'], true);
            $this->createIndex(null, '{{%points_order_redemptions}}', ['userId']);

            // FK to users (always safe). Skip FK to commerce_orders since Commerce
            // is an optional dependency at this layer — we filter in code.
            $this->addForeignKey(null, '{{%points_order_redemptions}}', ['userId'], '{{%users}}', ['id'], 'CASCADE');
            $this->addForeignKey(null, '{{%points_order_redemptions}}', ['awardId'], '{{%points_awards}}', ['id'], 'SET NULL');
        }

        // Ensure the internal "Points redemption" rule exists so award deductions
        // have a valid ruleId. Created with handle '__redemption' and no trigger.
        $exists = (new Query())
            ->from('{{%points_rules}}')
            ->where(['handle' => '__redemption'])
            ->exists();

        if (!$exists) {
            $this->insert('{{%points_rules}}', [
                'name' => 'Points redemption',
                'handle' => '__redemption',
                'trigger' => null,
                'conditions' => null,
                'limits' => null,
                'reward' => '{"type":"flat","points":0}',
                'enabled' => true,
                'dateCreated' => \DateTime::createFromFormat('U', (string) time())->format('Y-m-d H:i:s'),
                'dateUpdated' => \DateTime::createFromFormat('U', (string) time())->format('Y-m-d H:i:s'),
                'uid' => StringHelper::UUID(),
            ]);
        }

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%points_order_redemptions}}');
        $this->delete('{{%points_rules}}', ['handle' => '__redemption']);
        return true;
    }
}
