<?php

namespace bymayo\points\events;

use bymayo\points\elements\PointEntry;
use bymayo\points\models\Event as PointsEvent;
use yii\base\Event;

class EntryEvent extends Event
{
    /** The user receiving (or losing) points. */
    public int $userId;

    /** The Points Event triggering this. */
    public ?PointsEvent $event = null;

    /**
     * The saved PointEntry. Populated on AFTER events; null on BEFORE events
     * for adds (and for removes, points at the entry about to be deleted).
     */
    public ?PointEntry $entry = null;

    /**
     * Points being awarded (add flow) or removed (remove flow).
     *
     * On BEFORE_ADD_ENTRY, listeners can modify this to change the amount —
     * the new value will be used for the entry's pointsSnapshot.
     */
    public int $pointsToAward = 0;

    /**
     * Set to false in a BEFORE handler to cancel the operation.
     */
    public bool $isValid = true;
}
