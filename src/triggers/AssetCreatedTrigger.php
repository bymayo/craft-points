<?php

namespace bymayo\points\triggers;

use craft\base\Element;
use craft\elements\Asset;
use craft\events\ModelEvent;
use yii\base\Event;

class AssetCreatedTrigger extends BaseTrigger
{
    public function handle(): string { return 'asset.created'; }
    public function label(): string { return 'Asset created'; }
    public function group(): string { return 'Assets'; }
    public function actionLabel(): string { return 'Created'; }

    public function events(): array
    {
        return [[Asset::class, Element::EVENT_AFTER_SAVE]];
    }

    public function handleEvent(Event $event): ?TriggerContext
    {
        /** @var ModelEvent $event */
        if (!($event->isNew ?? false)) {
            return null;
        }
        /** @var Asset $asset */
        $asset = $event->sender;
        $userId = $asset->uploaderId ?: null;
        if (!$userId) {
            return null;
        }
        return new TriggerContext(userId: $userId);
    }
}
