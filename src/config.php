<?php

/**
 * Points config — example file.
 *
 * Copy this to `config/points.php` in your Craft project to pin Points'
 * settings per-environment. Anything you set here takes precedence over
 * the values stored in the plugin's DB row (which admins edit at
 * **Points → Settings**), so use this file when you want a value to be
 * locked down per-environment rather than CP-editable.
 *
 * Every key is optional — omitted keys fall back to the DB row, which
 * falls back to Points' built-in defaults.
 *
 * Per-environment overrides work the same as any Craft config file: the
 * `*` block applies everywhere, then a key matching `Craft::$app->env`
 * (or your `CRAFT_ENVIRONMENT` env var) is merged on top.
 */

return [
    '*' => [
        // CP labels — what the plugin and its currency are called.
        'pluginName' => 'Points',
        'currencyName' => 'Point',
        'currencyNamePlural' => 'Points',

        // Handle of a Date field on the user layout that drives the
        // "User birthday" trigger. Leave empty to hide the trigger.
        'birthdayFieldHandle' => '',

        // Commerce redemption (Pro + Craft Commerce).
        // Points-to-money conversion: by default 100 points = 1 unit of
        // store currency.
        'conversionPointsCount' => 100,
        'conversionCurrencyUnits' => 1,

        // Fewest points a customer can apply in one redemption.
        'redemptionMinPoints' => 1,

        // Most of an order's total a customer can pay with points (1-100).
        'redemptionMaxOrderPercent' => 100,

        // What to do with redeemed points on refund:
        //   'restoreProportional' — restore points pro-rata with the refunded amount (default)
        //   'restoreFullOnly'     — only restore when the order is fully refunded
        //   'none'                — never restore
        'redemptionRefundBehaviour' => 'restoreProportional',
    ],

    // Example per-environment overrides — uncomment as needed.
    // 'staging' => [
    //     'redemptionMaxOrderPercent' => 100,
    // ],
    //
    // 'dev' => [
    //     'pluginName' => 'Points (dev)',
    // ],
];
