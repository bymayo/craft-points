<?php

namespace bymayo\points\triggers;

abstract class BaseTrigger implements TriggerInterface
{
    public static function group(): string
    {
        return 'General';
    }

    public static function subject(): string
    {
        $parts = explode('.', static::handle(), 2);
        return $parts[0];
    }

    public static function subjectLabel(): string
    {
        return ucfirst(static::subject());
    }

    public static function actionLabel(): string
    {
        $label = static::label();
        $subjectLabel = static::subjectLabel();
        if (str_starts_with($label, $subjectLabel . ' ')) {
            return ucfirst(substr($label, strlen($subjectLabel) + 1));
        }
        return $label;
    }

    public static function appliesToEvent($event): bool
    {
        return true;
    }

    public static function getAmountForEvent($event): ?float
    {
        return null;
    }

    public static function isAvailable(): bool
    {
        return true;
    }
}
