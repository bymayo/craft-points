<?php

namespace bymayo\points\models;

use craft\base\Model;

class Settings extends Model
{
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

    public function defineRules(): array
    {
        return [
            [['currencyName', 'currencyNamePlural'], 'required'],
            [['currencyName', 'currencyNamePlural'], 'string', 'max' => 50],
            [['birthdayFieldHandle', 'currencySymbol'], 'string', 'max' => 100],
            [['pointsPerCurrencyUnit'], 'integer', 'min' => 1],
        ];
    }
}
