<?php

namespace bymayo\points\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property int $orderId
 * @property int $userId
 * @property int $points
 * @property string $discountAmount
 * @property int|null $awardId
 * @property string $uid
 */
class OrderRedemptionRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%points_order_redemptions}}';
    }
}
