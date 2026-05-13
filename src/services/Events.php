<?php

namespace bymayo\points\services;

use bymayo\points\models\Event;
use bymayo\points\records\EventRecord;
use yii\base\Component;
use yii\base\Exception;

class Events extends Component
{
    /**
     * @return Event[]
     */
    public function getAllEvents(): array
    {
        $records = EventRecord::find()->orderBy(['name' => SORT_ASC])->all();
        return array_map(fn(EventRecord $r) => $this->createEventFromRecord($r), $records);
    }

    public function getEventById(int $id): ?Event
    {
        $record = EventRecord::findOne(['id' => $id]);
        return $record ? $this->createEventFromRecord($record) : null;
    }

    public function getEventByHandle(string $handle): ?Event
    {
        $record = EventRecord::findOne(['handle' => $handle]);
        return $record ? $this->createEventFromRecord($record) : null;
    }

    /**
     * @return Event[]
     */
    public function getEventsByTrigger(string $triggerHandle): array
    {
        $records = EventRecord::find()->where(['trigger' => $triggerHandle])->all();
        return array_map(fn(EventRecord $r) => $this->createEventFromRecord($r), $records);
    }

    public function saveEvent(Event $event, bool $runValidation = true): bool
    {
        if ($runValidation && !$event->validate()) {
            return false;
        }

        if ($event->id) {
            $record = EventRecord::findOne(['id' => $event->id]);
            if (!$record) {
                throw new Exception("No event exists with the ID {$event->id}.");
            }
        } else {
            $record = new EventRecord();
        }

        $record->name = $event->name;
        $record->handle = $event->handle;
        $record->points = $event->points;
        $record->multiple = $event->multiple;
        $record->trigger = $event->trigger ?: null;
        $record->triggerConfig = $event->triggerConfig
            ? json_encode($event->triggerConfig)
            : null;

        if (!$record->save(false)) {
            return false;
        }

        $event->id = $record->id;
        $event->uid = $record->uid;
        return true;
    }

    public function deleteEventById(int $id): bool
    {
        $record = EventRecord::findOne(['id' => $id]);
        if (!$record) {
            return false;
        }
        return (bool) $record->delete();
    }

    private function createEventFromRecord(EventRecord $r): Event
    {
        $event = new Event();
        $event->id = (int) $r->id;
        $event->name = (string) $r->name;
        $event->handle = (string) $r->handle;
        $event->points = (int) $r->points;
        $event->multiple = (bool) $r->multiple;
        $event->trigger = $r->trigger ?: null;
        $event->triggerConfig = $r->triggerConfig
            ? (json_decode($r->triggerConfig, true) ?: null)
            : null;
        $event->uid = $r->uid;
        return $event;
    }
}
