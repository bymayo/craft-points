<?php

namespace bymayo\points\events;

use yii\base\Event;

/**
 * Other plugins can register additional trigger classes here.
 *
 * Each class in $triggers must implement \bymayo\points\triggers\TriggerInterface.
 */
class RegisterTriggersEvent extends Event
{
    /** @var string[] List of trigger class names. */
    public array $triggers = [];
}
