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

    /**
     * Award points to a user.
     *
     * Usage:
     *   {{ craft.points.addAward({ ruleHandle: 'signedUp' }) }}             — current user
     *   {{ craft.points.addAward({ userId: 5, ruleHandle: 'signedUp' }) }}  — specific user
     */
    public function addAward(array $options): ?PointAward
    {
        $userId = $options['userId'] ?? $this->currentUserId();
        $ruleHandle = $options['ruleHandle'] ?? null;

        if (!$userId || !$ruleHandle) {
            return null;
        }

        return Points::getInstance()->awards->addAward((int)$userId, $ruleHandle);
    }

    /**
     * Remove the oldest award for this user + rule. Only removes one instance.
     */
    public function removeAward(array $options): bool
    {
        $userId = $options['userId'] ?? $this->currentUserId();
        $ruleHandle = $options['ruleHandle'] ?? null;

        if (!$userId || !$ruleHandle) {
            return false;
        }

        return Points::getInstance()->awards->removeAward((int)$userId, $ruleHandle);
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

    /** Currency symbol from plugin settings (e.g. "£", "$"). */
    public function symbol(): string
    {
        return Points::getInstance()->getSettings()->currencySymbol;
    }

    /** True if the plugin is running on Pro edition. */
    public function isPro(): bool
    {
        return Points::getInstance()->is(Points::EDITION_PRO);
    }

    /**
     * Currently-applied points redemption for an order, or null if none.
     *
     * @return \bymayo\points\models\OrderRedemption|null
     */
    public function orderRedemption(int $orderId)
    {
        return Points::getInstance()->orderRedemptions->getForOrder($orderId);
    }

    /** Number of points currently applied to the order, or 0. */
    public function appliedToOrder(int $orderId): int
    {
        $r = Points::getInstance()->orderRedemptions->getForOrder($orderId);
        return $r ? $r->points : 0;
    }

    /**
     * Outputs an inline `<script>` defining `window.Points.addAward(ruleHandle)`
     * — a cache-safe way to fire Manual rules from frontend pages.
     *
     * Cache-safe because:
     *   - The script defines a function, no CSRF token in the rendered HTML
     *   - Token is fetched at runtime via a separate uncached AJAX call
     *   - Works inside Blitz / static cache / {% cache %} blocks
     */
    public function script(): \Twig\Markup
    {
        $fireUrl = \craft\helpers\UrlHelper::actionUrl('points/awards/fire');
        $tokenUrl = \craft\helpers\UrlHelper::actionUrl('points/awards/token');

        // JSON-encode for safe injection into JS string literals.
        $fireJson = json_encode($fireUrl);
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

    window.Points.addAward = function (ruleHandle) {
        return getToken().then(function (token) {
            var body = new URLSearchParams();
            body.set('CRAFT_CSRF_TOKEN', token || '');
            body.set('ruleHandle', ruleHandle);
            return fetch({$fireJson}, {
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
    };
})();
JS;

        return \craft\helpers\Template::raw('<script>' . $js . '</script>');
    }

    /**
     * Convert a point balance into its monetary value.
     *
     * Uses the `pointsPerCurrencyUnit` setting. With the default of 100, 250 points → 2.50.
     */
    public function toMoney(?int $points = null): float
    {
        $points = $points ?? $this->sumForUser();
        $rate = max(1, Points::getInstance()->getSettings()->pointsPerCurrencyUnit);
        return $points / $rate;
    }

    /**
     * Same as toMoney() but pre-formatted with the configured currency symbol
     * and two decimal places. e.g. "£2.50".
     */
    public function formatMoney(?int $points = null): string
    {
        $symbol = Points::getInstance()->getSettings()->currencySymbol;
        return $symbol . number_format($this->toMoney($points), 2);
    }

    private function currentUserId(): ?int
    {
        $user = Craft::$app->getUser()->getIdentity();
        return $user?->id;
    }
}
