<?php

namespace bymayo\points\events;

use bymayo\points\elements\PointAward;
use bymayo\points\models\Event as PointsEvent;
use yii\base\Event;

class AwardEvent extends Event
{
    /** The user receiving (or losing) points. */
    public int $userId;

    /** The Points Event triggering this. */
    public ?PointsEvent $event = null;

    /**
     * The PointAward. Populated on AFTER events; null on BEFORE_ADD events.
     * For remove events, set to the award about to be deleted.
     */
    public ?PointAward $award = null;

    /**
     * Points being awarded (add flow) or removed (remove flow).
     *
     * On EVENT_BEFORE_ADD_AWARD, listeners can modify this to change the amount —
     * the new value will be used for the award's pointsSnapshot.
     */
    public int $pointsToAward = 0;

    /** Set to false in a BEFORE handler to cancel the operation. */
    public bool $isValid = true;
}
