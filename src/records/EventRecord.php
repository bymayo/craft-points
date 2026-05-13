<?php

namespace bymayo\points\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string $handle
 * @property int $points
 * @property string $pointsType
 * @property bool $multiple
 * @property string|null $trigger
 * @property string|null $triggerConfig
 * @property string $uid
 */
class EventRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%points_events}}';
    }
}
