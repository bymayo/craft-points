<?php

namespace bymayo\points\triggers;

use craft\base\Element;
use craft\elements\Entry;
use craft\events\ModelEvent;

class EntryUpdatedTrigger extends BaseTrigger
{
    public static function handle(): string { return 'entry.updated'; }
    public static function label(): string { return 'Entry updated'; }
    public static function group(): string { return 'Entries'; }
    public static function eventClass(): string { return Entry::class; }
    public static function eventName(): string { return Element::EVENT_AFTER_SAVE; }
    public static function scopedTo(): ?string { return 'sections'; }

    public static function appliesToEvent($event): bool
    {
        /** @var ModelEvent $event */
        return !($event->isNew ?? false);
    }

    public static function getUserIdFromEvent($event): ?int
    {
        return $event->sender->getAuthorId();
    }

    public static function scopeIdForEvent($event): ?int
    {
        return $event->sender->sectionId;
    }
}
