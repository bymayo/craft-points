<?php

namespace bymayo\points\models;

use craft\base\Model;

class Settings extends Model
{
    /** Singular form of the currency name, e.g. "Point", "Coin", "Credit". */
    public string $currencyName = 'Point';

    /** Plural form, e.g. "Points", "Coins", "Credits". */
    public string $currencyNamePlural = 'Points';

    public function defineRules(): array
    {
        return [
            [['currencyName', 'currencyNamePlural'], 'required'],
            [['currencyName', 'currencyNamePlural'], 'string', 'max' => 50],
        ];
    }
}
