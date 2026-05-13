<?php

namespace bymayo\points\triggers;

use craft\base\Element;
use craft\elements\Asset;

class AssetDeletedTrigger extends BaseTrigger
{
    public static function handle(): string { return 'asset.deleted'; }
    public static function label(): string { return 'Asset deleted'; }
    public static function group(): string { return 'Assets'; }
    public static function eventClass(): string { return Asset::class; }
    public static function eventName(): string { return Element::EVENT_AFTER_DELETE; }
    public static function scopedTo(): ?string { return 'volumes'; }

    public static function getUserIdFromEvent($event): ?int
    {
        return $event->sender->uploaderId;
    }

    public static function scopeIdForEvent($event): ?int
    {
        return $event->sender->volumeId;
    }
}
