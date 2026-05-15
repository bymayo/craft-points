<?php

namespace bymayo\points\elements;

use bymayo\points\elements\db\PointAwardQuery;
use bymayo\points\models\Rule;
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
    public ?int $ruleId = null;
    public ?int $userId = null;
    public ?int $orderId = null;
    public int $pointsSnapshot = 0;

    private ?Rule $_rule = null;
    private ?User $_user = null;
    private mixed $_order = null;

    public static function displayName(): string
    {
        // "Rule" is used by Craft's element-source service as the title column
        // label (it's hardcoded to displayName(), with no override hook). The
        // row content is the rule name (see getUiLabel) so labelling the column
        // "Rule" is what users expect. Plural / lower variants still use the
        // configured currency name ("{Coin} Awards", etc.) for page titles.
        return Craft::t('points', 'Rule');
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

        $rules = Points::getInstance()->rules->getAllRules();

        if (!empty($rules)) {
            $sources[] = ['heading' => Craft::t('points', 'Rules')];
            foreach ($rules as $rule) {
                $sources[] = [
                    'key' => 'rule:' . $rule->id,
                    'label' => $rule->name,
                    'criteria' => ['ruleId' => $rule->id],
                    'defaultSort' => ['dateCreated', 'desc'],
                ];
            }
        }

        return $sources;
    }

    protected static function defineTableAttributes(): array
    {
        // 'title' label comes from displayName() (we override that to "Rule").
        // The remaining columns are declared here.
        $attrs = [
            'user' => ['label' => Craft::t('points', 'User')],
            'pointsSnapshot' => ['label' => Points::getInstance()->getSettings()->currencyNamePlural],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
        ];
        // Order column is only meaningful when Pro+Commerce is in play.
        $plugin = Points::getInstance();
        if ($plugin->is(Points::EDITION_PRO) && $plugin->hasCommerce()) {
            $attrs['order'] = ['label' => Craft::t('points', 'Order')];
        }
        return $attrs;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        // 'order' is opt-in via column settings.
        return ['user', 'pointsSnapshot', 'dateCreated'];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'dateCreated' => [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
            ],
            'pointsSnapshot' => [
                'label' => Points::getInstance()->getSettings()->currencyNamePlural,
                'orderBy' => 'points_awards.pointsSnapshot',
                'attribute' => 'pointsSnapshot',
            ],
        ];
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['userId', 'ruleId'];
    }

    public function getRule(): ?Rule
    {
        if ($this->_rule === null && $this->ruleId) {
            $this->_rule = Points::getInstance()->rules->getRuleById($this->ruleId);
        }
        return $this->_rule;
    }

    public function getUser(): ?User
    {
        if ($this->_user === null && $this->userId) {
            $this->_user = Craft::$app->getUsers()->getUserById($this->userId);
        }
        return $this->_user;
    }

    /**
     * Resolve the Commerce order this award came from, if any. Returns null
     * outside Pro+Commerce, when there's no `orderId`, or when the order has
     * been deleted.
     */
    public function getOrder(): mixed
    {
        if ($this->_order !== null) {
            return $this->_order ?: null;
        }

        $plugin = Points::getInstance();
        if (!$this->orderId || !$plugin->is(Points::EDITION_PRO) || !$plugin->hasCommerce()) {
            return null;
        }

        $this->_order = \craft\commerce\elements\Order::find()
            ->id($this->orderId)
            ->status(null)
            ->one() ?? false;

        return $this->_order ?: null;
    }

    public function canView(\craft\elements\User $user): bool
    {
        return $user->can('points-viewAwards');
    }

    public function canSave(\craft\elements\User $user): bool
    {
        return $user->can($this->id ? 'points-editAwards' : 'points-createAwards');
    }

    public function canDelete(\craft\elements\User $user): bool
    {
        return $user->can('points-deleteAwards');
    }

    public function getCpEditUrl(): ?string
    {
        return $this->id ? UrlHelper::cpUrl('points/awards/' . $this->id) : null;
    }

    public function getUiLabel(): string
    {
        $rule = $this->getRule();
        return $rule?->name ?: Craft::t('points', 'Award #{id}', ['id' => $this->id ?? '?']);
    }

    public function __toString(): string
    {
        return $this->getUiLabel();
    }

    protected function attributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'user':
                $user = $this->getUser();
                return $user ? Cp::elementChipHtml($user) : '';
            case 'rule':
                $rule = $this->getRule();
                if (!$rule) {
                    return '';
                }
                return Html::a(Html::encode($rule->name), $rule->getCpEditUrl());
            case 'pointsSnapshot':
                return (string)$this->pointsSnapshot;
            case 'order':
                $order = $this->getOrder();
                return $order ? Cp::elementChipHtml($order) : '';
        }

        return parent::attributeHtml($attribute);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['ruleId', 'userId'], 'required'];
        $rules[] = [['ruleId', 'userId', 'pointsSnapshot', 'orderId'], 'integer'];
        return $rules;
    }

    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            $data = [
                'ruleId' => $this->ruleId,
                'userId' => $this->userId,
                'orderId' => $this->orderId,
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
