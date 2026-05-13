<?php

namespace bymayo\points\triggers;

use craft\base\Element;
use craft\elements\Entry;

class EntryDeletedTrigger extends BaseTrigger
{
    public static function handle(): string { return 'entry.deleted'; }
    public static function label(): string { return 'Entry deleted'; }
    public static function group(): string { return 'Entries'; }
    public static function eventClass(): string { return Entry::class; }
    public static function eventName(): string { return Element::EVENT_AFTER_DELETE; }
    public static function scopedTo(): ?string { return 'sections'; }

    public static function getUserIdFromEvent($event): ?int
    {
        return $event->sender->getAuthorId();
    }

    public static function scopeIdForEvent($event): ?int
    {
        return $event->sender->sectionId;
    }
}
