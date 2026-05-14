<?php

namespace bymayo\points\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property int $ruleId
 * @property int $userId
 * @property int $pointsSnapshot
 * @property int|null $orderId
 */
class PointAwardRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%points_awards}}';
    }
}
