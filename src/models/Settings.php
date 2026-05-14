<?php

namespace bymayo\points\models;

use craft\base\Model;

class Settings extends Model
{
    /**
     * Name shown for the plugin in the CP sidebar and breadcrumbs.
     * Independent of the currency name — e.g. "Rewards System" with currency "Coins".
     */
    public string $pluginName = 'Points';

    /** Singular form of the currency name, e.g. "Point", "Coin", "Credit". */
    public string $currencyName = 'Point';

    /** Plural form, e.g. "Points", "Coins", "Credits". */
    public string $currencyNamePlural = 'Points';

    /**
     * Handle of the custom user field that stores each user's birthday.
     * Required for the "User birthday" trigger to fire. Must be a Date field on the user layout.
     */
    public string $birthdayFieldHandle = 'birthday';

    /**
     * How many points equal one unit of real currency. Used by `craft.points.toMoney()`.
     * e.g. 100 means "100 points = £1". e.g. 1 means "1 point = £1".
     */
    public int $pointsPerCurrencyUnit = 100;

    /** Display symbol for the points→money conversion helper. */
    public string $currencySymbol = '£';

    /** Minimum points a user can redeem against an order in a single redemption. */
    public int $redemptionMinPoints = 1;

    /** Maximum percentage of the order total that points can cover (0–100). */
    public int $redemptionMaxOrderPercent = 100;

    /**
     * How to handle refunds when an order had a points redemption applied.
     * `restoreProportional` — partial refund restores a proportional share.
     * `restoreFullOnly` — only full refunds restore points.
     * `none` — refunds never restore points (admin must do manually).
     */
    public string $redemptionRefundBehaviour = 'restoreProportional';

    public function defineRules(): array
    {
        return [
            [['pluginName', 'currencyName', 'currencyNamePlural'], 'required'],
            [['pluginName', 'currencyName', 'currencyNamePlural'], 'string', 'max' => 50],
            [['birthdayFieldHandle', 'currencySymbol'], 'string', 'max' => 100],
            [['pointsPerCurrencyUnit'], 'integer', 'min' => 1],
            [['redemptionMinPoints'], 'integer', 'min' => 1],
            [['redemptionMaxOrderPercent'], 'integer', 'min' => 1, 'max' => 100],
            [['redemptionRefundBehaviour'], 'in', 'range' => ['restoreProportional', 'restoreFullOnly', 'none']],
        ];
    }
}
