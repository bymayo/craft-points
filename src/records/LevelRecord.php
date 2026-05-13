<?php

namespace bymayo\points\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string $handle
 * @property int $threshold
 * @property string|null $colour
 * @property string|null $icon
 * @property string $uid
 */
class LevelRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%points_levels}}';
    }
}
