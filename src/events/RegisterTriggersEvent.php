<?php

namespace bymayo\points\events;

use bymayo\points\triggers\TriggerInterface;
use yii\base\Event;

/**
 * Other plugins can register additional trigger instances here.
 *
 * Prefer the sugar API when you have an instance handy:
 *   Points::getInstance()->triggers->register(new MyTrigger());
 *
 * Use this event when you need to react to the registration moment itself
 * (e.g. to conditionally swap one trigger for another). Each entry in
 * $triggers must be an instance of \bymayo\points\triggers\TriggerInterface.
 *
 * @property TriggerInterface[] $triggers
 */
class RegisterTriggersEvent extends Event
{
    /** @var TriggerInterface[] */
    public array $triggers = [];
}
