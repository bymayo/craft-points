<?php

namespace bymayo\points\models;

use bymayo\points\records\RuleRecord;
use craft\base\Model;
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
