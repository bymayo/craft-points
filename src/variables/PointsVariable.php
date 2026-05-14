<?php

namespace bymayo\points\variables;

use bymayo\points\elements\PointAward;
use bymayo\points\models\Level;
use bymayo\points\models\Rule;
use bymayo\points\Points;
use Craft;
use craft\elements\User;

/**
 * Twig API for Points — exposed as `craft.points.*`.
 */
class PointsVariable
{
    /**
     * @return PointAward[]
     */
    public function awards(): array
    {
        return PointAward::find()
            ->orderBy(['dateCreated' => SORT_DESC])
            ->all();
    }

    /**
     * @return Rule[]
     */
    public function rules(): array
    {
        return Points::getInstance()->rules->getAllRules();
    }

    public function user(int $userId): ?User
    {
        return Craft::$app->getUsers()->getUserById($userId);
    }

    public function rule(string $handle): ?Rule
    {
        return Points::getInstance()->rules->getRuleByHandle($handle);
    }

    public function ruleById(int $id): ?Rule
    {
        return Points::getInstance()->rules->getRuleById($id);
    }

    public function ruleByHandle(string $handle): ?Rule
    {
        return Points::getInstance()->rules->getRuleByHandle($handle);
    }

    public function awardById(int $id): ?PointAward
    {
        return Points::getInstance()->awards->getAwardById($id);
    }

    /**
     * @return PointAward[]
     */
    public function awardsByUser(?int $userId = null): array
    {
        $userId = $userId ?? $this->currentUserId();
        return $userId
            ? Points::getInstance()->awards->getAwardsForUser($userId)
            : [];
    }

    /** Total points for a user (defaults to current user). */
    public function sumForUser(?int $userId = null): int
    {
        $userId = $userId ?? $this->currentUserId();
        return $userId
            ? Points::getInstance()->awards->sumForUser($userId)
            : 0;
    }

    /** Count of awards for a user (defaults to current user). */
    public function countForUser(?int $userId = null): int
    {
        $userId = $userId ?? $this->currentUserId();
        return $userId
            ? Points::getInstance()->awards->countForUser($userId)
            : 0;
    }

    /**
     * @return Level[]
     */
    public function levels(): array
    {
        return Points::getInstance()->levels->getAllLevels();
    }

    public function levelForUser(?int $userId = null): ?Level
    {
        $userId = $userId ?? $this->currentUserId();
        return $userId
            ? Points::getInstance()->levels->levelForUser($userId)
            : null;
    }

    public function levelForPoints(int $points): ?Level
    {
        return Points::getInstance()->levels->levelForPoints($points);
    }

    public function levelById(int $id): ?Level
    {
        return Points::getInstance()->levels->getLevelById($id);
    }

    public function levelByHandle(string $handle): ?Level
    {
        return Points::getInstance()->levels->getLevelByHandle($handle);
    }

    /**
     * @return array<int, array{user: User, points: int, level: ?Level}>
     */
    public function leaderboard(int $limit = 10, int $offset = 0): array
    {
        return Points::getInstance()->awards->leaderboard($limit, $offset);
    }

    /** Plugin name from settings — used in CP nav and breadcrumbs. */
    public function pluginName(): string
    {
        return Points::getInstance()->getSettings()->pluginName;
    }

    /** Singular currency label from plugin settings (e.g. "Coin"). */
    public function currency(): string
    {
        return Points::getInstance()->getSettings()->currencyName;
    }

    /** Plural currency label from plugin settings (e.g. "Coins"). */
    public function currencyPlural(): string
    {
        return Points::getInstance()->getSettings()->currencyNamePlural;
    }

    /**
     * Currency symbol derived from the Commerce primary store's currency
     * (e.g. "£", "$", "€"). Returns an empty string outside of Pro+Commerce
     * — currency/money helpers are gated to that combination.
     */
    public function symbol(): string
    {
        if (!$this->isProCommerce()) return '';
        return Points::getInstance()->getStoreCurrencySymbol() ?? '';
    }

    /** 3-letter ISO 4217 currency code from the Commerce primary store. Null outside Pro+Commerce. */
    public function currencyCode(): ?string
    {
        if (!$this->isProCommerce()) return null;
        return Points::getInstance()->getStoreCurrencyCode();
    }

    /** True if the plugin is running on Pro edition. */
    public function isPro(): bool
    {
        return Points::getInstance()->is(Points::EDITION_PRO);
    }

    /**
     * Currently-applied points redemption for an order, or null. Pro+Commerce only.
     *
     * @return \bymayo\points\models\OrderRedemption|null
     */
    public function orderRedemption(int $orderId)
    {
        if (!$this->isProCommerce()) return null;
        return Points::getInstance()->orderRedemptions->getForOrder($orderId);
    }

    /** Number of points currently applied to the order. 0 outside Pro+Commerce. */
    public function appliedToOrder(int $orderId): int
    {
        if (!$this->isProCommerce()) return 0;
        $r = Points::getInstance()->orderRedemptions->getForOrder($orderId);
        return $r ? $r->points : 0;
    }

    /**
     * Outputs an inline `<script>` defining the cache-safe JS API:
     *   window.Points.addAward(ruleHandle)
     *   window.Points.removeAward(ruleHandle)
     *
     * Cache-safe because:
     *   - The script defines functions, no CSRF token in the rendered HTML
     *   - Token is fetched at runtime via a separate uncached AJAX call
     *   - Works inside Blitz / static cache / {% cache %} blocks
     */
    public function script(): \Twig\Markup
    {
        $fireUrl = \craft\helpers\UrlHelper::actionUrl('points/awards/add');
        $removeUrl = \craft\helpers\UrlHelper::actionUrl('points/awards/remove');
        $tokenUrl = \craft\helpers\UrlHelper::actionUrl('points/awards/token');

        $fireJson = json_encode($fireUrl);
        $removeJson = json_encode($removeUrl);
        $tokenJson = json_encode($tokenUrl);

        $js = <<<JS
(function () {
    if (window.Points && window.Points._loaded) return;
    window.Points = window.Points || {};
    window.Points._loaded = true;
    var _token = null;

    function getToken() {
        if (_token) return Promise.resolve(_token);
        return fetch({$tokenJson}, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        }).then(function (r) { return r.json(); })
          .then(function (data) {
            _token = data && data.token ? data.token : null;
            return _token;
        });
    }

    function post(url, ruleHandle) {
        return getToken().then(function (token) {
            var body = new URLSearchParams();
            body.set('CRAFT_CSRF_TOKEN', token || '');
            body.set('ruleHandle', ruleHandle);
            return fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: body
            }).then(function (r) { return r.json(); });
        });
    }

    window.Points.addAward = function (ruleHandle) {
        return post({$fireJson}, ruleHandle);
    };
    window.Points.removeAward = function (ruleHandle) {
        return post({$removeJson}, ruleHandle);
    };
})();
JS;

        return \craft\helpers\Template::raw('<script>' . $js . '</script>');
    }

    /**
     * Convert a point balance into its monetary value.
     *
     * Uses the configured `conversionPointsCount`:`conversionCurrencyUnits`
     * ratio. With the defaults (100:1), 250 points → 2.5.
     *
     * Returns null outside Pro+Commerce — money helpers need a real store
     * currency, which we only resolve when both are present.
     */
    public function toMoney(?int $points = null): ?float
    {
        if (!$this->isProCommerce()) {
            return null;
        }
        $plugin = Points::getInstance();
        $points = $points ?? $this->sumForUser();
        $settings = $plugin->getSettings();
        $pointsCount = max(1, $settings->conversionPointsCount);
        return $points * $settings->conversionCurrencyUnits / $pointsCount;
    }

    /**
     * Same as toMoney() but locale-formatted using the Commerce primary store's
     * currency (e.g. "£2.50", "$2.50", "2,50 €"). Empty string outside Pro+Commerce.
     */
    public function formatMoney(?int $points = null): string
    {
        $value = $this->toMoney($points);
        if ($value === null) {
            return '';
        }
        return Points::getInstance()->formatStoreMoney($value);
    }

    private function isProCommerce(): bool
    {
        $plugin = Points::getInstance();
        return $plugin->is(Points::EDITION_PRO) && $plugin->hasCommerce();
    }

    private function currentUserId(): ?int
    {
        $user = Craft::$app->getUser()->getIdentity();
        return $user?->id;
    }
}
