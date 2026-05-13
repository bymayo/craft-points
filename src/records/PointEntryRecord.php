<?php

namespace bymayo\points\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property int $eventId
 * @property int $userId
 * @property int $pointsSnapshot
 */
class PointEntryRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%points_entries}}';
    }
}
