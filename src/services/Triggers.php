<?php

namespace bymayo\points\services;

use bymayo\points\events\RegisterTriggersEvent;
use bymayo\points\Points;
use bymayo\points\triggers\AssetDeletedTrigger;
use bymayo\points\triggers\AssetUploadedTrigger;
use bymayo\points\triggers\CategoryCreatedTrigger;
use bymayo\points\triggers\CategoryDeletedTrigger;
use bymayo\points\triggers\CategoryUpdatedTrigger;
use bymayo\points\triggers\EntryCreatedTrigger;
use bymayo\points\triggers\EntryDeletedTrigger;
use bymayo\points\triggers\EntryUpdatedTrigger;
use bymayo\points\triggers\TriggerInterface;
use bymayo\points\triggers\UserLoggedInTrigger;
use bymayo\points\triggers\UserRegisteredTrigger;
use bymayo\points\triggers\UserUpdatedTrigger;
use Craft;
use yii\base\Component;
use yii\base\Event;

class Triggers extends Component
{
    public const EVENT_REGISTER_TRIGGERS = 'registerTriggers';

    /** @var string[] Trigger class names, after the register event has fired. */
    private array $_triggers = [];

    /** @var array<string, string> Map of trigger handle → class. */
    private array $_byHandle = [];

    public function init(): void
    {
        parent::init();
        $this->registerTriggers();
        $this->attachListeners();
    }

    /** @return string[] All registered trigger class names. */
    public function getAllTriggers(): array
    {
        return $this->_triggers;
    }

    public function getTriggerClassByHandle(string $handle): ?string
    {
        return $this->_byHandle[$handle] ?? null;
    }

    /**
     * Returns trigger classes that support scoping — useful for the CP edit form.
     *
     * @return string[]
     */
    public function getScopedTriggers(): array
    {
        return array_values(array_filter(
            $this->_triggers,
            fn(string $class) => $class::scopedTo() !== null
        ));
    }

    /**
     * Returns options array for a Craft select field — grouped by trigger group.
     */
    public function getSelectOptions(): array
    {
        $options = [
            ['label' => Craft::t('points', 'Manual (Twig only)'), 'value' => ''],
        ];

        $byGroup = [];
        foreach ($this->_triggers as $class) {
            $byGroup[$class::group()][] = $class;
        }

        foreach ($byGroup as $group => $classes) {
            $options[] = ['optgroup' => $group];
            foreach ($classes as $class) {
                $options[] = [
                    'label' => $class::label(),
                    'value' => $class::handle(),
                ];
            }
        }

        return $options;
    }

    private function registerTriggers(): void
    {
        $defaults = [
            EntryCreatedTrigger::class,
            EntryUpdatedTrigger::class,
            EntryDeletedTrigger::class,
            CategoryCreatedTrigger::class,
            CategoryUpdatedTrigger::class,
            CategoryDeletedTrigger::class,
            UserRegisteredTrigger::class,
            UserUpdatedTrigger::class,
            UserLoggedInTrigger::class,
            AssetUploadedTrigger::class,
            AssetDeletedTrigger::class,
        ];

        $event = new RegisterTriggersEvent(['triggers' => $defaults]);
        $this->trigger(self::EVENT_REGISTER_TRIGGERS, $event);

        $this->_triggers = array_values(array_unique($event->triggers));

        foreach ($this->_triggers as $class) {
            if (!is_subclass_of($class, TriggerInterface::class)) {
                continue;
            }
            $this->_byHandle[$class::handle()] = $class;
        }
    }

    private function attachListeners(): void
    {
        // Group triggers by (eventClass, eventName) so we attach one listener per unique source event.
        $grouped = [];
        foreach ($this->_triggers as $class) {
            $key = $class::eventClass() . '::' . $class::eventName();
            $grouped[$key][] = $class;
        }

        foreach ($grouped as $triggerClasses) {
            $first = $triggerClasses[0];
            Event::on(
                $first::eventClass(),
                $first::eventName(),
                function($yiiEvent) use ($triggerClasses) {
                    foreach ($triggerClasses as $triggerClass) {
                        $this->dispatch($triggerClass, $yiiEvent);
                    }
                }
            );
        }
    }

    private function dispatch(string $triggerClass, $yiiEvent): void
    {
        if (!$triggerClass::appliesToEvent($yiiEvent)) {
            return;
        }

        $pointsEvents = Points::getInstance()->events->getEventsByTrigger($triggerClass::handle());
        if (empty($pointsEvents)) {
            return;
        }

        $userId = $triggerClass::getUserIdFromEvent($yiiEvent);
        if (!$userId) {
            return;
        }

        $scopeId = $triggerClass::scopedTo() ? $triggerClass::scopeIdForEvent($yiiEvent) : null;

        foreach ($pointsEvents as $pointsEvent) {
            if ($triggerClass::scopedTo()) {
                $scopeIds = $pointsEvent->triggerConfig['scopeIds'] ?? [];
                if (!empty($scopeIds)) {
                    if (!$scopeId || !in_array($scopeId, array_map('intval', $scopeIds), true)) {
                        continue;
                    }
                }
            }

            Points::getInstance()->entries->addEntry($userId, $pointsEvent->handle);
        }
    }
}
