<img src="https://raw.githubusercontent.com/bymayo/craft-points/craft-5/src/icon.svg" width="70">

# Points for Craft CMS

A friendly points-and-rewards engine for Craft. Give users points when they sign up, log in, buy something, share a page - anything you like. Build leaderboards. Tier them into Bronze / Silver / Gold. Let customers spend their points at checkout. All from inside the CP.

<img src="https://raw.githubusercontent.com/bymayo/craft-points/craft-5/resources/screenshot.png" width="850">

## Features

- Visual rule builder: *When X happens, if Y is true, then award Z, max once per day*
- Automatic triggers for Entries, Users, Assets - plus Commerce Orders & Subscriptions on Pro
- Conditions (Order total, Order contains product, Entry in section, etc.) to keep rules precise
- Per-rule limits (Once per user, Max N per period, cooldowns)
- Tiered Levels with custom names, colours, and badge icons
- Leaderboard CP page + dashboard widget, with avatars and live filtering
- "Latest awards" dashboard widget
- Customers can spend points at checkout (Pro + Commerce, any payment gateway)
- Fully renameable: plugin name, currency labels, all from settings
- Twig API, GraphQL queries, and a GraphQL mutation for headless apps
- Cache-safe JS helper for Manual rules - works inside Blitz and `{% cache %}`
- Granular permissions (view / create / edit / delete per resource)
- Extension events for triggers, conditions, limits, and rewards

## Contents

- [Editions](#editions)
- [Install](#install)
- [Requirements](#requirements)
- [How it works (3 minutes)](#how-it-works)
- [Building your first rule](#building-your-first-rule)
- [Firing a rule from your site](#firing-a-rule-from-your-site)
- [Reading points in Twig](#reading-points-in-twig)
- [Levels & leaderboard](#levels--leaderboard)
- [Spending points at checkout](#spending-points-at-checkout) (Pro + Commerce)
- [Dashboard widgets](#dashboard-widgets)
- [Examples](#examples)
- [Settings](#settings)
- [Permissions](#permissions)
- [GraphQL](#graphql)
- [Twig reference](#twig-reference)
- [Extending the plugin](#extending-the-plugin)

## Editions

| | Lite (free) | Pro |
|---|---|---|
| Rules, Awards, Levels, Leaderboard | ✅ | ✅ |
| Dashboard widgets | ✅ | ✅ |
| Triggers for Entries, Users, Assets | ✅ | ✅ |
| Twig + GraphQL APIs | ✅ | ✅ |
| Renameable plugin & currency labels | ✅ | ✅ |
| **Commerce triggers** (orders, subscriptions) | - | ✅ |
| **Percentage-of-order** rewards | - | ✅ |
| **Commerce conditions** (order total, contains product, …) | - | ✅ |
| **Customers spend points at checkout** | - | ✅ |

## Install

- Install with Composer via `composer require bymayo/craft-points` from your project directory
- Enable / Install the plugin in the Craft Control Panel under `Settings > Plugins`

You can also install the plugin via the Plugin Store in the Craft Admin CP by searching for `Points`.

## Requirements

- Craft CMS 5.6+
- PHP 8.2+
- Craft Commerce 5.x (only for Pro Commerce features - orders, subscriptions, redemptions)

## How it works

Three concepts cover everything:

- **Rule** - a "When this happens, give this many points" sentence. e.g. *"When a customer pays for an order over £50, give them 100 points, max once per day"*.
- **Award** - a single payout of a rule to a user. The amount is snapshotted at the time, so editing the rule later doesn't rewrite history.
- **Level** - an optional tier (Bronze / Silver / Gold) a user reaches once their balance crosses a threshold.

Defaults call everything "Points" - but you can rename. The plugin can show up in the sidebar as "Rewards" or "VIP Club", the unit can be "Coins" or "Stars". Set it all under **Points → Settings → General**.

## Building your first rule

Go to **Points → Rules → New rule**. Five sections, each optional except the first two:

| Section | What it is |
|---|---|
| **Name** | What you call this rule |
| **When** | The trigger - *Entry created*, *Order paid*, etc. Or "Manual" if your own code will fire it. |
| **If** | Optional conditions - e.g. *only when the entry is in the "Reviews" section*. |
| **Then** | The reward - flat amount, percentage of order total (Pro), or a deduction. |
| **Limit** | How often it can fire per user - Once, Max N per day/week/month, optional cooldown. |
| **Active period** | Optional date range - handy for campaigns. |

### Triggers that come with the plugin

| Subject | Triggers | Available on |
|---|---|---|
| Manual | (fired by your own code, see [below](#firing-a-rule-from-your-site)) | Lite |
| Entry | Created · Updated | Lite |
| Asset | Created | Lite |
| User | Registered · Logged in · Birthday · Anniversary | Lite |
| Order | Paid · Completed · Refunded · First ever | Pro |
| Subscription | Created · Renewed · Cancelled · Plan changed | Pro |

Each non-Manual trigger awards the right person automatically: the entry's author, the order's customer, the asset's uploader, etc. Manual rules don't fire on their own - your code (a form, JS button, or GraphQL mutation) decides when, as explained in [Firing a rule from your site](#firing-a-rule-from-your-site).

> **Birthday trigger**: needs a Date field on the user layout. Set its handle in **Settings → Triggers**. The trigger fires on the user's next login after their birthday - it'll only appear in the rule picker once you've configured it.

## Firing a rule from your site

For "Manual" rules (no trigger), pick whichever fits your setup:

### 1. A regular form - server-rendered pages

The simplest path. Drop a form into your template, post to the plugin:

```twig
<form method="post">
    {{ csrfInput() }}
    {{ actionInput('points/awards/add') }}
    {{ redirectInput('account/thanks') }}
    <input type="hidden" name="ruleHandle" value="signedUpForNewsletter">
    <button>Subscribe</button>
</form>
```

To reverse an award (e.g. an "unshare" button), post to `points/awards/remove` with the same shape.

> Forms don't play nicely inside Blitz / `{% cache %}` blocks - the embedded CSRF token gets stale. Use the JS helper on cached pages.

### 2. The JS helper - for buttons & cached pages

Drop this once in your layout:

```twig
{{ craft.points.script() }}
```

Now you have `window.Points.addAward(handle)` and `window.Points.removeAward(handle)` everywhere. Cache-safe - works inside Blitz, `{% cache %}`, anything:

```html
<button onclick="Points.addAward('shared')">Share</button>
```

It returns a Promise:

```js
const res = await Points.addAward('signedUpForNewsletter');
if (res.success) {
    alert(`You earned ${res.points} ${res.currency}!`);
}
```

Works with React, Vue, Alpine, Stimulus, htmx - anything that can call a global function.

### 3. A GraphQL mutation - for headless / decoupled apps

For Next.js, Nuxt, native apps, or anything where your frontend lives outside Craft's templates:

```graphql
mutation FireRule($handle: String!) {
    pointsAddAward(ruleHandle: $handle) {
        success error points currency awardId
    }
}
```

Same security rules as the form and JS API - the user must be logged in, the rule must be Manual, and points always go to the authenticated user.

### Behind the scenes (the boring but important bit)

All three options share the same safety net: login required, CSRF protected, only Manual rules, current-user-only (no `userId` ever gets accepted from the client), Limits enforced, and the rule's active dates honoured.

That said, the client is *trusted to ask politely*. Someone could open devtools and call `Points.addAward('shared')` without actually sharing. Keep Manual rules low-value, and use trigger-based rules (Order paid, Entry created, …) for anything worth gaming.

### Server-side awards

From a module, controller, or console command:

```php
use bymayo\points\Points;

Points::getInstance()->awards->addAward($userId, 'profileCompleted');
```

This is the only API that can target a user other than the one currently logged in.

## Reading points in Twig

```twig
{{ craft.points.sumForUser() }}        {# total for current user #}
{{ craft.points.sumForUser(5) }}        {# total for user 5 #}
{{ craft.points.countForUser() }}       {# how many awards they have #}

{{ craft.points.formatMoney() }}        {# their balance as money - needs Commerce #}
```

Loop through someone's awards:

```twig
{% for award in craft.points.awardsByUser() %}
    {{ award.rule.name }} - {{ award.pointsSnapshot }} ({{ award.dateCreated|datetime }})
{% endfor %}
```

## Levels & leaderboard

Levels are tiers your users earn by accumulating points. Create them in **Points → Levels** with a threshold, colour, and optional badge icon.

```twig
{% set level = craft.points.levelForUser() %}
{% if level %}
    You're a <span style="color: {{ level.colour }}">{{ level.name }}</span> member.
{% endif %}
```

Leaderboard:

```twig
{% for row in craft.points.leaderboard(10) %}
    <p>{{ loop.index }}. {{ row.user.name }} - {{ row.points }}
        {% if row.level %}<small>({{ row.level.name }})</small>{% endif %}
    </p>
{% endfor %}
```

There's also a paginated **Points → Leaderboard** page in the CP and a matching dashboard widget.

## Spending points at checkout

Available on **Pro + Craft Commerce**. Customers apply points against an order - it shows up like a coupon discount, works with any payment gateway.

```twig
{% set cart = craft.commerce.carts.cart %}
{% set balance = craft.points.sumForUser() %}
{% set applied = craft.points.appliedToOrder(cart.id) %}

<p>You have {{ balance }} {{ craft.points.currencyPlural|lower }}
   ({{ craft.points.formatMoney() }}).</p>

{% if applied %}
    <p>{{ applied }} applied to this order.</p>
    <form method="post">
        {{ csrfInput() }}{{ actionInput('points/redeem/remove') }}
        <input type="hidden" name="orderId" value="{{ cart.id }}">
        <button>Remove</button>
    </form>
{% else %}
    <form method="post">
        {{ csrfInput() }}{{ actionInput('points/redeem/apply') }}
        <input type="hidden" name="orderId" value="{{ cart.id }}">
        <input type="number" name="points" min="1" max="{{ balance }}">
        <button>Apply points</button>
    </form>
{% endif %}
```

What happens behind the scenes:

1. Customer applies points → the plugin validates and records the intent.
2. Commerce shows it as a discount line on the order.
3. Customer pays the reduced total via any gateway.
4. On `Order::EVENT_AFTER_ORDER_PAID`, the points are taken from their balance (recorded as a negative award for the audit trail).
5. On refund, points are restored according to your refund-behaviour setting.

Configure conversion rate, minimum redemption, max % of order, and refund behaviour in **Settings → Commerce**.

## Dashboard widgets

Add via the Craft dashboard → **+ New widget**. Both are grouped under your plugin name (e.g. `Rewards - Leaderboard`):

| Widget | Shows |
|---|---|
| **Leaderboard** | Top N users by balance. Columns: Customer · Level · Total. |
| **Latest Awards** | The N most recent awards across all users. Columns: Customer · {currency} · Date. |

Each widget respects your renames - column headers follow whatever you set in Settings.

## Examples

A small grab-bag to get the wheels turning.

### General

**Welcome bonus on signup**
- Trigger: *User registered* · Reward: 100 points · Limit: Once per user.

**Daily login bonus**
- Trigger: *User logged in* · Reward: 10 points · Limit: Max 1 / day.

**Birthday gift**
- Trigger: *User birthday* · Reward: 250 points · Limit: Max 1 / year.

**Share button** (Manual)
- Trigger: Manual · Reward: 5 points · Limit: Max 1 / day per user.

  ```html
  <button onclick="Points.addAward('shared')">Share</button>
  ```

**Commented on a post**
- Trigger: *Entry created* · Condition: in section "Comments" · Limit: Max 5 / day · Reward: 5 points.

### Commerce (Pro)

**1 point per £1 spent**
- Trigger: *Order paid* · Reward: Percentage of order total.

**First-order bonus**
- Trigger: *First order* · Reward: 500 points.

**Big spender bonus** (stacks with above)
- Trigger: *Order paid* · Condition: Total > £100 · Reward: 200 points.

**Subscriber loyalty**
- Trigger: *Subscription renewed* · Reward: 50 points per renewal.

**Don't reward coupon orders**
- Trigger: *Order paid* · Condition: Order has coupon = No · Reward: 50 points.

## Settings

Renamable bits all live in **Points → Settings**:

- **General** - plugin name (sidebar label), reward unit singular/plural, edition info.
- **Commerce** (Pro) - conversion rate (e.g. 100 points = 1 unit of store currency), minimum redemption, max % of order, refund behaviour.
- **Triggers** - birthday field handle.

Settings save to the plugin's own DB table - not Project Config. That means admins can rename things on production without a deploy from staging clobbering them. Developers can still pin per-environment values in `config/points.php`:

```php
return [
    '*' => [
        'conversionPointsCount' => 100,
        'conversionCurrencyUnits' => 1,
        'redemptionMaxOrderPercent' => 50,
    ],
];
```

The store currency itself isn't a setting - it tracks your Craft Commerce primary store automatically. Money helpers (`toMoney`, `formatMoney`, the Available Spend / Redeemed columns) only work when Commerce is installed.

## Permissions

The plugin ships granular permissions under a **Points** heading on each user group's permissions page:

```
Points
  ▸ View awards          ↳ Create / Edit / Delete awards
  ▸ View rules           ↳ Create / Edit / Delete rules
  ▸ View levels          ↳ Create / Edit / Delete levels
  ☐ View leaderboard
  ☐ Manage settings
```

A child permission can only be granted once its parent is granted (standard Craft pattern). The Points sidebar and sub-pages are hidden entirely if the user has none.

A few role recipes:

- **Customer-success agent** - View awards + View leaderboard. Can look things up, can't change anything.
- **Loyalty manager** - Everything except Delete \* and Manage settings. Can build the programme, can't drop existing rules/levels.
- **Admin** - All permissions.

Frontend endpoints (form, JS, GraphQL) don't use CP permissions. They only check login + the rule is Manual.

## GraphQL

```graphql
query Player($userId: Int!) {
    points: pointsSumForUser(userId: $userId)
    level:  pointsLevelForUser(userId: $userId) { name colour }
}

query Top10 {
    pointsLeaderboard(limit: 10) {
        userId userName points
        level { name colour }
    }
}
```

| Query | Args | Returns |
|---|---|---|
| `pointsRules` / `pointsRule` | (handle) | `[PointsRule]` / `PointsRule` |
| `pointsLevels` / `pointsLevelForUser` | (userId) | `[PointsLevel]` / `PointsLevel` |
| `pointsAwards` | userId, ruleId, limit, offset | `[PointsAward]` |
| `pointsSumForUser` / `pointsCountForUser` | userId | `Int` |
| `pointsLeaderboard` | limit, offset | `[PointsLeaderboardRow]` |

Mutation for firing Manual rules from a headless app:

| Mutation | Args | Returns |
|---|---|---|
| `pointsAddAward` | `ruleHandle: String!` | `PointsAddAwardResult { success, error, points, currency, awardId }` |

## Twig reference

| Call | Returns |
|---|---|
| `craft.points.sumForUser(id?)` | `int` - total points |
| `craft.points.countForUser(id?)` | `int` - number of awards |
| `craft.points.awardsByUser(id?)` | `PointAward[]` |
| `craft.points.levelForUser(id?)` | `Level` or null |
| `craft.points.levelForPoints(n)` | `Level` or null |
| `craft.points.leaderboard(limit?, offset?)` | rows of `{ user, points, level }` |
| `craft.points.toMoney(points?)` | `float` - needs Commerce |
| `craft.points.formatMoney(points?)` | `string` - e.g. "£2.50" - needs Commerce |
| `craft.points.appliedToOrder(orderId)` | `int` - points currently applied to that order |
| `craft.points.script()` | inline `<script>` defining `window.Points.addAward()` |
| `craft.points.pluginName` / `currency` / `currencyPlural` / `symbol` | configured labels |
| `craft.points.rules` / `levels` / `awards` | full lists |
| `craft.points.rule(handle)` / `ruleById(id)` / `awardById(id)` / `levelById(id)` / `levelByHandle(h)` | single lookups |
| `craft.points.isPro` | true on Pro |

Awards are a first-class element, so you can use element queries too:

```twig
{% set recent = craft.app.elements
    .createElementQuery('bymayo\\points\\elements\\PointAward')
    .userId(currentUser.id)
    .limit(20)
    .all() %}
```

## Extending the plugin

Need to react to things, or add your own trigger / condition / limit / reward type?

```php
use bymayo\points\events\AwardEvent;
use bymayo\points\services\Awards;
use yii\base\Event;

// Block an award (e.g. fraud check)
Event::on(Awards::class, Awards::EVENT_BEFORE_ADD_AWARD, function(AwardEvent $e) {
    if (suspiciousActivity($e->userId)) {
        $e->isValid = false;
    }
});

// Send a "you earned points!" email
Event::on(Awards::class, Awards::EVENT_AFTER_ADD_AWARD, function(AwardEvent $e) {
    sendThankYouEmail($e->userId, $e->rule, $e->award);
});
```

Events available:

| Event | When |
|---|---|
| `Awards::EVENT_BEFORE_ADD_AWARD` | Before an award is saved (cancellable; can change points) |
| `Awards::EVENT_AFTER_ADD_AWARD` | After an award is saved |
| `Awards::EVENT_BEFORE_REMOVE_AWARD` | Before deletion (cancellable) |
| `Awards::EVENT_AFTER_REMOVE_AWARD` | After deletion |
| `Levels::EVENT_LEVEL_CHANGED` | When a user crosses a level threshold |

Each subsystem can be extended with your own classes:

```php
Event::on(Triggers::class, Triggers::EVENT_REGISTER_TRIGGERS,
    fn($e) => $e->triggers[] = MyTrigger::class);
```

Same pattern for `Conditions`, `Limits`, and `Rewards`. Extend the matching base class (`BaseTrigger`, etc.).

## Support

If you have any issues (surely not!) then I'll aim to reply to these as soon as possible. If it's a site-breaking-oh-no-what-has-happened moment, then hit me up on the Craft CMS Discord - `@bymayo`.

## License

[Craft License](https://craftcms.github.io/license/)
