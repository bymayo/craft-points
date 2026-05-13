<?php

namespace bymayo\points\widgets;

use bymayo\points\Points;
use Craft;
use craft\base\Widget;

class LeaderboardWidget extends Widget
{
    public int $limit = 10;

    public static function displayName(): string
    {
        return Craft::t('points', 'Points Leaderboard');
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
        return Craft::t('points', 'Leaderboard');
    }

    public function getBodyHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('points/_widgets/leaderboard', [
            'rows' => Points::getInstance()->entries->leaderboard($this->limit),
        ]);
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('points/_widgets/leaderboard-settings', [
            'widget' => $this,
        ]);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['limit'], 'integer', 'min' => 1, 'max' => 100];
        return $rules;
    }
}
