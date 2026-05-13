<?php

namespace bymayo\points\models;

use bymayo\points\records\EventRecord;
use craft\base\Model;
use craft\helpers\UrlHelper;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

class Event extends Model
{
    public ?int $id = null;
    public string $name = '';
    public string $handle = '';
    public int $points = 0;
    public string $pointsType = 'flat';
    public bool $multiple = false;
    public ?string $trigger = null;
    public ?array $triggerConfig = null;
    public ?string $uid = null;

    public function defineRules(): array
    {
        return [
            [['name', 'handle'], 'required'],
            [['points'], 'integer'],
            [['pointsType'], 'in', 'range' => ['flat', 'percent']],
            [['multiple'], 'boolean'],
            [['handle'], HandleValidator::class],
            [
                ['handle'],
                UniqueValidator::class,
                'targetClass' => EventRecord::class,
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
        return UrlHelper::cpUrl('points/events/' . $this->id);
    }
}
