<?php

namespace bymayo\points\triggers;

use Craft;

abstract class BaseTrigger implements TriggerInterface
{
    public static function group(): string
    {
        return 'General';
    }

    public static function appliesToEvent($event): bool
    {
        return true;
    }

    public static function scopedTo(): ?string
    {
        return null;
    }

    public static function scopeIdForEvent($event): ?int
    {
        return null;
    }

    public static function getScopeOptions(): array
    {
        switch (static::scopedTo()) {
            case 'sections':
                $sections = method_exists(Craft::$app, 'getEntries')
                    ? Craft::$app->getEntries()->getAllSections()
                    : Craft::$app->getSections()->getAllSections();
                return array_map(
                    fn($s) => ['label' => $s->name, 'value' => (string)$s->id],
                    $sections
                );

            case 'groups':
                return array_map(
                    fn($g) => ['label' => $g->name, 'value' => (string)$g->id],
                    Craft::$app->getCategories()->getAllGroups()
                );

            case 'volumes':
                return array_map(
                    fn($v) => ['label' => $v->name, 'value' => (string)$v->id],
                    Craft::$app->getVolumes()->getAllVolumes()
                );
        }
        return [];
    }
}
