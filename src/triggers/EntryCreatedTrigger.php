<?php

namespace bymayo\points\triggers;

use craft\base\Element;
use craft\elements\Entry;
use craft\events\ModelEvent;
use yii\base\Event;

class EntryCreatedTrigger extends BaseTrigger
{
    public function handle(): string { return 'entry.created'; }
    public function label(): string { return 'Entry created'; }
    public function group(): string { return 'Entries'; }

    public function events(): array
    {
        return [[Entry::class, Element::EVENT_AFTER_SAVE]];
    }

    public function handleEvent(Event $event): ?TriggerContext
    {
        /** @var ModelEvent $event */
        if (!($event->isNew ?? false)) {
            return null;
        }
        /** @var Entry $entry */
        $entry = $event->sender;
        // Ignore drafts, revisions, and the propagating saves that fire after the canonical save.
        if ($entry->getIsDraft() || $entry->getIsRevision() || $entry->propagating) {
            return null;
        }

        $userId = $entry->getAuthorId();
        if (!$userId) {
            return null;
        }
        return new TriggerContext(userId: $userId);
    }
}
