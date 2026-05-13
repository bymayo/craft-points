<?php

namespace bymayo\points\widgets;

use bymayo\points\elements\PointEntry;
use Craft;
use craft\base\Widget;

class LatestEntriesWidget extends Widget
{
    public int $limit = 10;

    public static function displayName(): string
    {
        return Craft::t('points', 'Latest Points Entries');
    }

    public static function icon(): ?string
    {
        $path = Craft::getAlias('@bymayo/points/icon-outline.svg');
        return is_string($path) && file_exists($path) ? $path : null;
    }

    public static function isSelectable(): bool
    {
        return true;
    }

    public function getTitle(): ?string
    {
        return Craft::t('points', 'Latest Entries');
    }

    public function getBodyHtml(): ?string
    {
        $entries = PointEntry::find()
            ->orderBy(['dateCreated' => SORT_DESC])
            ->limit($this->limit)
            ->all();

        return Craft::$app->getView()->renderTemplate('points/_widgets/latest-entries', [
            'entries' => $entries,
        ]);
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('points/_widgets/latest-entries-settings', [
            'widget' => $this,
        ]);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['limit'], 'integer', 'min' => 1, 'max' => 50];
        return $rules;
    }
}
