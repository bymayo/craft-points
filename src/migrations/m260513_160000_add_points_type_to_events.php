<?php

namespace bymayo\points\migrations;

use craft\db\Migration;

class m260513_160000_add_points_type_to_events extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%points_events}}', 'pointsType')) {
            $this->addColumn(
                '{{%points_events}}',
                'pointsType',
                $this->string(20)->notNull()->defaultValue('flat')->after('points')
            );
        }
        return true;
    }

    public function safeDown(): bool
    {
        if ($this->db->columnExists('{{%points_events}}', 'pointsType')) {
            $this->dropColumn('{{%points_events}}', 'pointsType');
        }
        return true;
    }
}
