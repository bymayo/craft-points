<?php

namespace bymayo\points\events;

use bymayo\points\models\Level;
use yii\base\Event;

class LevelChangedEvent extends Event
{
    public int $userId;
    public ?Level $previousLevel = null;
    public ?Level $currentLevel = null;
    public int $currentPoints = 0;
}
