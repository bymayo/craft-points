<?php

namespace bymayo\points\events;

use yii\base\Event;

class RegisterLimitsEvent extends Event
{
    /** @var string[] List of LimitInterface class names. */
    public array $limits = [];
}
