<?php

namespace bymayo\points\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string $handle
 * @property string|null $trigger
 * @property string|null $conditions
 * @property string|null $limits
 * @property string|null $reward
 * @property bool $enabled
 * @property string|null $activeFrom
 * @property string|null $activeTo
 * @property string $uid
 */
class RuleRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%points_rules}}';
    }
}
