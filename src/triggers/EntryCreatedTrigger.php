<?php

namespace bymayo\points\triggers;

use craft\base\Element;
use craft\elements\Entry;
use craft\events\ModelEvent;

class EntryCreatedTrigger extends BaseTrigger
{
    public static function handle(): string { return 'entry.created'; }
    public static function label(): string { return 'Entry created'; }
    public static function group(): string { return 'Entries'; }
    public static function eventClass(): string { return Entry::class; }
    public static function eventName(): string { return Element::EVENT_AFTER_SAVE; }
    public static function scopedTo(): ?string { return 'sections'; }

    public static function appliesToEvent($event): bool
    {
        /** @var ModelEvent $event */
        return (bool)($event->isNew ?? false);
    }

    public static function getUserIdFromEvent($event): ?int
    {
        /** @var Entry $entry */
        $entry = $event->sender;
        return $entry->getAuthorId();
    }

    public static function scopeIdForEvent($event): ?int
    {
        /** @var Entry $entry */
        $entry = $event->sender;
        return $entry->sectionId;
    }
}
