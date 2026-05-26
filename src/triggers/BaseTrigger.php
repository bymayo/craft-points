<?php

namespace bymayo\points\triggers;

use yii\base\Event;

/**
 * Default implementations for the boilerplate metadata so a custom trigger
 * only has to define handle(), label(), group(), events(), and handleEvent().
 */
abstract class BaseTrigger implements TriggerInterface
{
    public function group(): string
    {
        return 'General';
    }

    public function subject(): string
    {
        $parts = explode('.', $this->handle(), 2);
        return $parts[0];
    }

    public function subjectLabel(): string
    {
        return ucfirst($this->subject());
    }

    public function actionLabel(): string
    {
        $label = $this->label();
        $subjectLabel = $this->subjectLabel();
        if (str_starts_with($label, $subjectLabel . ' ')) {
            return ucfirst(substr($label, strlen($subjectLabel) + 1));
        }
        return $label;
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function conditions(): array
    {
        return [];
    }

    /**
     * Default no-op. Override to return a TriggerContext (or null to skip).
     * Returning null here means a misconfigured subclass won't accidentally
     * award points — it'll silently no-op until it's overridden.
     */
    public function handleEvent(Event $event): ?TriggerContext
    {
        return null;
    }
}
