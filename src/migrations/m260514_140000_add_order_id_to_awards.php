<?php

namespace bymayo\points\migrations;

use craft\db\Migration;

/**
 * Adds a nullable `orderId` column to `points_awards` so awards generated
 * from Commerce Order triggers (Order paid, Order completed, Order refunded,
 * First order) carry a reference back to the originating order. Lets admins
 * see "which order earned these points" from the Awards element index and
 * lets future reporting features (e.g. points-per-order) hang off the same
 * column.
 */
class m260514_140000_add_order_id_to_awards extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%points_awards}}', 'orderId')) {
            $this->addColumn('{{%points_awards}}', 'orderId', $this->integer()->null());
            $this->createIndex(null, '{{%points_awards}}', ['orderId']);
        }
        return true;
    }

    public function safeDown(): bool
    {
        if ($this->db->columnExists('{{%points_awards}}', 'orderId')) {
            $this->dropColumn('{{%points_awards}}', 'orderId');
        }
        return true;
    }
}
