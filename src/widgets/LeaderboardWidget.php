<?php

namespace bymayo\points\widgets;

use bymayo\points\Points;
use Craft;
use craft\base\Widget;

class LeaderboardWidget extends Widget
{
    public int $limit = 5;

    public static function displayName(): string
    {
        return Points::getInstance()->getSettings()->pluginName . ' - Leaderboard';
    }

    public static function icon(): ?string
    {
        $path = Craft::getAlias('@bymayo/points/icon-outline.svg');
        return is_string($path) && file_exists($path) ? $path : null;
    }

    public static function isSelectable(): bool
    {
        // Only offer the widget in the picker if the current user can view
        // the corresponding CP page. Existing widgets on someone's dashboard
        // continue to render even if they lose the permission later — they
        // just can't add new ones.
        $user = Craft::$app->getUser()->getIdentity();
        return $user && $user->can('points-viewLeaderboard');
    }

    public function getTitle(): ?string
    {
        return Points::getInstance()->getSettings()->pluginName . ' ' . Craft::t('points', 'Leaderboard');
    }

    public function getBodyHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('points/_widgets/leaderboard', [
            'rows' => Points::getInstance()->awards->leaderboard($this->limit),
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
