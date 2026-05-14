<?php

namespace bymayo\points\events;

use yii\base\Event;

class RegisterRewardsEvent extends Event
{
    /** @var string[] List of RewardInterface class names. */
    public array $rewards = [];
}
