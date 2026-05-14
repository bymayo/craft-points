<?php

namespace bymayo\points\models;

use bymayo\points\records\LevelRecord;
use craft\base\Model;
use craft\helpers\UrlHelper;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

class Level extends Model
{
    public ?int $id = null;
    public string $name = '';
    public string $handle = '';
    public int $threshold = 0;
    public ?string $colour = null;
    public ?string $icon = null;
    public ?string $uid = null;

    public function defineRules(): array
    {
        return [
            [['name', 'handle'], 'required'],
            [['threshold'], 'integer', 'min' => 0],
            [['threshold'], 'required'],
            [['handle'], HandleValidator::class],
            [
                ['handle'],
                UniqueValidator::class,
                'targetClass' => LevelRecord::class,
                'filter' => function($query) {
                    if ($this->id) {
                        $query->andWhere(['not', ['id' => $this->id]]);
                    }
                },
            ],
            [['colour'], 'string', 'max' => 7],
            [['icon'], 'string'],
        ];
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('points/levels/' . $this->id);
    }

    /**
     * The stored colour normalised to a `#hex` string for safe use in CSS.
     * Returns null if no colour is set.
     */
    public function getColourHex(): ?string
    {
        if (!$this->colour) {
            return null;
        }
        return str_starts_with($this->colour, '#') ? $this->colour : '#' . $this->colour;
    }
}
