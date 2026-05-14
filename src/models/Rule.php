<?php

namespace bymayo\points\models;

use bymayo\points\records\RuleRecord;
use craft\base\Model;
use craft\enums\Color;
use craft\helpers\Cp;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

class Rule extends Model
{
    public ?int $id = null;
    public string $name = '';
    public string $handle = '';
    public ?string $trigger = null;

    /** @var array<int, array<string, mixed>> */
    public array $conditions = [];

    /** @var array<int, array<string, mixed>> */
    public array $limits = [];

    /** @var array<string, mixed> */
    public array $reward = ['type' => 'flat', 'points' => 0];

    public bool $enabled = true;
    public ?string $activeFrom = null;
    public ?string $activeTo = null;
    public ?string $uid = null;

    public function defineRules(): array
    {
        return [
            [['name', 'handle'], 'required'],
            [['enabled'], 'boolean'],
            [['handle'], HandleValidator::class],
            [
                ['handle'],
                UniqueValidator::class,
                'targetClass' => RuleRecord::class,
                'filter' => function($query) {
                    if ($this->id) {
                        $query->andWhere(['not', ['id' => $this->id]]);
                    }
                },
            ],
        ];
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('points/rules/' . $this->id);
    }

    /**
     * Status pill matching Craft's native UI (same component used by the
     * Lightswitch field). Renders via `Cp::statusLabelHtml`.
     */
    public function getStatusLabelHtml(): \Twig\Markup
    {
        return Template::raw((string) Cp::statusLabelHtml([
            'color' => $this->enabled ? Color::Teal : Color::Gray,
            'label' => $this->enabled ? 'Enabled' : 'Disabled',
            'icon' => $this->enabled ? 'check' : 'xmark',
        ]));
    }

    /**
     * Human-readable trigger label, e.g. "Order completed" — for table display.
     * Falls back to "Manual" when no trigger is set, or the raw handle if the
     * trigger class can't be resolved (e.g. it was registered by a 3rd-party
     * plugin that's since been uninstalled).
     */
    public function getTriggerLabel(): string
    {
        if (!$this->trigger) {
            return 'Manual';
        }
        $class = \bymayo\points\Points::getInstance()->triggers->getTriggerClassByHandle($this->trigger);
        return $class ? $class::label() : $this->trigger;
    }

    /** Compact summary of the rule's limit config, e.g. "Once per user", "Max 5 / day". */
    public function getLimitSummary(): string
    {
        if (empty($this->limits)) {
            return 'No limit';
        }
        $limit = $this->limits[0] ?? null;
        if (!is_array($limit) || empty($limit['type'])) {
            return 'No limit';
        }

        return match ($limit['type']) {
            'oncePerUser' => 'Once per user',
            'maxPerUser' => (function () use ($limit) {
                $max = (int) ($limit['max'] ?? 0);
                $period = (string) ($limit['period'] ?? 'ever');
                return $period === 'ever'
                    ? "Max {$max}"
                    : "Max {$max} / {$period}";
            })(),
            'cooldown' => sprintf('%ds cooldown', (int) ($limit['seconds'] ?? 0)),
            default => $limit['type'],
        };
    }

    /**
     * Compact summary of the active schedule, e.g. "Always", "From 13/02/2026", "13/02/2026 → 31/03/2026".
     * Uses Craft's formatter so dates respect the site's configured format.
     */
    public function getScheduleSummary(): string
    {
        if (!$this->activeFrom && !$this->activeTo) {
            return 'Always';
        }
        try {
            $from = $this->activeFrom ? new \DateTime($this->activeFrom) : null;
            $to = $this->activeTo ? new \DateTime($this->activeTo) : null;
        } catch (\Throwable) {
            return 'Always';
        }

        $fmt = \Craft::$app->getFormatter();
        if ($from && !$to) return 'From ' . $fmt->asDate($from, 'short');
        if (!$from && $to) return 'Until ' . $fmt->asDate($to, 'short');
        return $fmt->asDate($from, 'short') . ' → ' . $fmt->asDate($to, 'short');
    }

    /** Best-effort one-line description of the reward for table display. */
    public function getRewardSummary(): string
    {
        $type = $this->reward['type'] ?? 'flat';
        $points = (int) ($this->reward['points'] ?? 0);
        $percent = $this->reward['percent'] ?? 0;
        $label = strtolower(\bymayo\points\Points::getInstance()->getSettings()->currencyNamePlural);

        return match($type) {
            'flat' => '+' . $points . ' ' . $label,
            'percent' => '+' . $percent . '%',
            'deduct' => '-' . $points . ' ' . $label,
            default => $type,
        };
    }
}
