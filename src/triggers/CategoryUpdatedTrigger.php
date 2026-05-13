<?php

namespace bymayo\points\triggers;

use Craft;
use craft\base\Element;
use craft\elements\Category;
use craft\events\ModelEvent;

class CategoryUpdatedTrigger extends BaseTrigger
{
    public static function handle(): string { return 'category.updated'; }
    public static function label(): string { return 'Category updated'; }
    public static function group(): string { return 'Categories'; }
    public static function eventClass(): string { return Category::class; }
    public static function eventName(): string { return Element::EVENT_AFTER_SAVE; }
    public static function scopedTo(): ?string { return 'groups'; }

    public static function appliesToEvent($event): bool
    {
        /** @var ModelEvent $event */
        return !($event->isNew ?? false);
    }

    public static function getUserIdFromEvent($event): ?int
    {
        return Craft::$app->getUser()->getIdentity()?->id;
    }

    public static function scopeIdForEvent($event): ?int
    {
        return $event->sender->groupId;
    }
}
