<?php

namespace bymayo\points\triggers;

use craft\base\Element;
use craft\elements\Asset;
use craft\events\ModelEvent;

class AssetCreatedTrigger extends BaseTrigger
{
    public static function handle(): string { return 'asset.created'; }
    public static function label(): string { return 'Asset created'; }
    public static function group(): string { return 'Assets'; }
    public static function actionLabel(): string { return 'Created'; }
    public static function eventClass(): string { return Asset::class; }
    public static function eventName(): string { return Element::EVENT_AFTER_SAVE; }

    public static function appliesToEvent($event): bool
    {
        /** @var ModelEvent $event */
        return (bool) ($event->isNew ?? false);
    }

    public static function getUserIdFromEvent($event): ?int
    {
        /** @var Asset $asset */
        $asset = $event->sender;
        return $asset->uploaderId ?: null;
    }
}
