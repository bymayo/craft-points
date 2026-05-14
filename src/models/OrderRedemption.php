<?php

namespace bymayo\points\models;

use craft\base\Model;

class OrderRedemption extends Model
{
    public ?int $id = null;
    public int $orderId = 0;
    public int $userId = 0;
    public int $points = 0;
    public float $discountAmount = 0.0;
    public ?int $awardId = null;
    public ?string $uid = null;

    public function isDeducted(): bool
    {
        return $this->awardId !== null;
    }
}
