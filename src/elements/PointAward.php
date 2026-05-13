<?php

namespace bymayo\points\elements;

use bymayo\points\elements\db\PointAwardQuery;
use bymayo\points\models\Event;
use bymayo\points\Points;
use bymayo\points\records\PointAwardRecord;
use Craft;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\UrlHelper;

class PointAward extends Element
{
    public ?int $eventId = null;
    public ?int $userId = null;
    public int $pointsSnapshot = 0;

    private ?Event $_event = null;
    private ?User $_user = null;

    public static function displayName(): string
    {
        return Points::getInstance()->getSettings()->currencyName . ' Award';
    }

    public static function lowerDisplayName(): string
    {
        return strtolower(Points::getInstance()->getSettings()->currencyName) . ' award';
    }

    public static function pluralDisplayName(): string
    {
        return Points::getInstance()->getSettings()->currencyName . ' Awards';
    }

    public static function pluralLowerDisplayName(): string
    {
        return strtolower(Points::getInstance()->getSettings()->currencyName) . ' awards';
    }

    public static function refHandle(): ?string
    {
        return 'pointAward';
    }

    public static function hasContent(): bool
    {
        return false;
    }

    public static function hasTitles(): bool
    {
        return false;
    }

    public static function hasUris(): bool
    {
        return false;
    }

    public static function isLocalized(): bool
    {
        return false;
    }

    public static function hasStatuses(): bool
    {
        return false;
    }

    public static function find(): ElementQueryInterface
    {
        return new PointAwardQuery(static::class);
    }

    protected static function defineSources(?string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('points', 'All awards'),
                'criteria' => [],
                'defaultSort' => ['dateCreated', 'desc'],
            ],
        ];

        $events = Points::getInstance()->events->getAllEvents();

        if (!empty($events)) {
            $sources[] = ['heading' => Craft::t('points', 'Events')];
            foreach ($events as $event) {
                $sources[] = [
                    'key' => 'event:' . $event->id,
                    'label' => $event->name,
                    'criteria' => ['eventId' => $event->id],
                    'defaultSort' => ['dateCreated', 'desc'],
                ];
            }
        }

        return $sources;
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'user' => ['label' => Craft::t('points', 'User')],
            'event' => ['label' => Craft::t('points', 'Event')],
            'pointsSnapshot' => ['label' => Craft::t('points', 'Points')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return ['user', 'event', 'pointsSnapshot', 'dateCreated'];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'dateCreated' => Craft::t('app', 'Date Created'),
            'pointsSnapshot' => [
                'label' => Craft::t('points', 'Points'),
                'orderBy' => 'points_awards.pointsSnapshot',
            ],
        ];
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['userId', 'eventId'];
    }

    public function getEvent(): ?Event
    {
        if ($this->_event === null && $this->eventId) {
            $this->_event = Points::getInstance()->events->getEventById($this->eventId);
        }
        return $this->_event;
    }

    public function getUser(): ?User
    {
        if ($this->_user === null && $this->userId) {
            $this->_user = Craft::$app->getUsers()->getUserById($this->userId);
        }
        return $this->_user;
    }

    public function canView(\craft\elements\User $user): bool
    {
        return $user->can('points-manageAwards');
    }

    public function canSave(\craft\elements\User $user): bool
    {
        return $user->can('points-manageAwards');
    }

    public function canDelete(\craft\elements\User $user): bool
    {
        return $user->can('points-manageAwards');
    }

    public function getCpEditUrl(): ?string
    {
        return $this->id ? UrlHelper::cpUrl('points/awards/' . $this->id) : null;
    }

    protected function attributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'user':
                $user = $this->getUser();
                return $user ? Cp::elementChipHtml($user) : '';
            case 'event':
                $event = $this->getEvent();
                if (!$event) {
                    return '';
                }
                return Html::a(Html::encode($event->name), $event->getCpEditUrl());
            case 'pointsSnapshot':
                return (string)$this->pointsSnapshot;
        }

        return parent::attributeHtml($attribute);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['eventId', 'userId'], 'required'];
        $rules[] = [['eventId', 'userId', 'pointsSnapshot'], 'integer'];
        return $rules;
    }

    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            $data = [
                'eventId' => $this->eventId,
                'userId' => $this->userId,
                'pointsSnapshot' => $this->pointsSnapshot,
            ];

            if ($isNew) {
                Craft::$app->getDb()->createCommand()
                    ->insert(PointAwardRecord::tableName(), $data + ['id' => $this->id])
                    ->execute();
            } else {
                Craft::$app->getDb()->createCommand()
                    ->update(PointAwardRecord::tableName(), $data, ['id' => $this->id])
                    ->execute();
            }
        }

        parent::afterSave($isNew);
    }
}
