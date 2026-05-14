<img src="https://raw.githubusercontent.com/bymayo/craft-points/craft-5/src/icon.svg" width="70">

# Points for Craft CMS 5.x

Award points to users for actions they perform, build leaderboards, and unlock tiered loyalty programmes — all from inside Craft.

## Editions

| | Lite (free) | Pro |
|---|---|---|
| Rules, Awards, Levels, Leaderboard | ✅ | ✅ |
| Dashboard widgets | ✅ | ✅ |
| Built-in triggers (Entry / User / Asset) | ✅ | ✅ |
| Twig & GraphQL APIs | ✅ | ✅ |
| Plugin events | ✅ | ✅ |
| Renameable plugin & currency labels | ✅ | ✅ |
| **Craft Commerce triggers** (Order completed / paid / refunded, Subscriptions) | — | ✅ |
| **Percentage-of-order-total** point awards | — | ✅ |
| **Commerce conditions** (Order total, item count, contains product, coupon) | — | ✅ |
| **Order redemptions** (customers spend points at checkout, gateway-agnostic) | — | ✅ |

Switch edition during development via `config/project/project.yaml`:

```yaml
plugins.points.edition: pro
```

## Features

- **Rules** — a builder for "When X happens, If Y is true, Then award Z" with limits and an active schedule
- **Automatic triggers** — fire on Entry create/update, User register/login/birthday/anniversary, Asset create, plus Commerce events on Pro
- **Conditions** — narrow rules with "Entry is in section", "User is in group", or "Order total / item count / contains product / has coupon" (Pro)
- **Limits** — cap how often a rule can fire ("Once per user", "Max N every [period]")
- **Awards** — entry-style element index of every points award, with all the standard Craft sources, search, sort, and bulk actions
- **Levels** — tier users by accumulated points (Bronze / Silver / Gold style) with colour
- **Leaderboard** — CP page and dashboard widget showing top users by total points
- **Renameable** — name the plugin in the sidebar ("Rewards System"), the currency ("Coins"), and your money symbol independently
- **Twig & GraphQL APIs** — read points, list awards, fetch the leaderboard
- **Extensible** — plugin events (`EVENT_BEFORE_ADD_AWARD`, `EVENT_LEVEL_CHANGED`, …), pluggable triggers / conditions / limits / rewards via PHP

## Requirements

- Craft CMS 5.6 or later
- PHP 8.2 or later

## Installation

```bash
composer require bymayo/craft-points
php craft plugin/install points
```

## Concepts

| Term | Meaning |
|---|---|
| **Rule** | A configured "When + If + Then + Limit" — e.g. "When Order completed, if total > £50, add 10 credits, max once per day" |
| **Trigger** | The event that fires a rule (e.g. *Entry created*, *Order completed*). "Manual" rules only fire from Twig. |
| **Award** | A record of a rule paying out to a user, at a specific time, with a points snapshot |
| **Level** | A named tier reached once a user's points sum crosses a threshold |

Defaults are "Points" everywhere. You can rename:

- **Plugin name** — what shows in the CP sidebar and breadcrumbs (e.g. *"Rewards System"*)
- **Currency name** — used in award labels, value suffixes etc. (singular & plural — e.g. *"Coin"* / *"Coins"*)
- **Currency symbol** — for money conversion display (e.g. `£`, `$`)

All in **Points → Settings**. Plugin handle, URLs and database don't change.

## Usage

### Building a rule

Navigate to **Points → Rules → New rule**. You'll see a 5-section builder:

- **Name / Handle / Enabled** — top-level metadata
- **When** — pick the trigger (or leave as Manual)
- **If** — optional conditions (all must pass). Only shows conditions relevant to the chosen trigger
- **Then** — what to award: Add a flat amount, Add a percentage (Pro), or Deduct
- **Limit** — Once per user, or Max N every (Hour / Day / Week / Month / Year / Never)
- **Active period** — optional date range

### Triggers shipped

| Subject | Triggers | Lite/Pro |
|---|---|---|
| Entry | Created · Updated | Lite |
| Asset | Created | Lite |
| User | Registered · Logged in · Birthday · Anniversary | Lite |
| Order | Completed · Paid · Refunded · First ever | Pro |
| Subscription | Created · Renewed · Cancelled · Plan changed | Pro |

**Recipients** default to:

| Trigger | Recipient |
|---|---|
| Entry created / updated | Entry author |
| Asset created | Asset uploader |
| User registered / logged in / birthday / anniversary | The user themselves |
| Order paid / completed / refunded / first ever | Order customer |
| Subscription * | The subscribing user |

For **User birthday** to fire, you need a Date field on the user layout. Set its handle in **Points → Settings → Birthday field handle**. The trigger fires on the user's next login after their birthday.

### Awarding from Twig

```twig
{# Award the current logged-in user #}
{{ craft.points.addAward({ ruleHandle: 'signedUp' }) }}

{# Award a specific user #}
{{ craft.points.addAward({ userId: 5, ruleHandle: 'signedUp' }) }}

{# Remove the oldest matching award #}
{{ craft.points.removeAward({ ruleHandle: 'signedUp' }) }}
```

Twig calls respect the rule's **Limits** (e.g. "Once per user" or "Max N per period"). They don't run **Conditions** because those need a trigger event for context — apply conditions only to rules with a non-Manual trigger.

### Reading points

```twig
{{ craft.points.sumForUser() }}        {# total for current user #}
{{ craft.points.sumForUser(5) }}        {# total for user 5 #}
{{ craft.points.countForUser() }}       {# award count #}

{# Money conversion (uses Points per currency unit + Currency symbol settings) #}
{{ craft.points.toMoney() }}            {# e.g. 2.5 #}
{{ craft.points.formatMoney() }}        {# e.g. "£2.50" #}
```

### Levels

```twig
{% set level = craft.points.levelForUser() %}
{% if level %}
    <span style="color: {{ level.colour }}">{{ level.name }}</span>
{% endif %}

{{ craft.points.levelForUser(5).name }}
{{ craft.points.levelForPoints(250).name }}

{% for level in craft.points.levels %}
    {{ level.name }} — {{ level.threshold }} pts
{% endfor %}
```

### Leaderboard

```twig
{% for row in craft.points.leaderboard(10) %}
    {{ loop.index }}. {{ row.user.name }} — {{ row.points }}
    {% if row.level %}({{ row.level.name }}){% endif %}
{% endfor %}
```

Each row is `{ user: User, points: int, level: Level|null }`. The CP page is at **Points → Leaderboard** with pagination, and there's a dashboard widget too.

### Awards for a user

```twig
{% for award in craft.points.awardsByUser() %}
    {{ award.rule.name }} — {{ award.pointsSnapshot }} ({{ award.dateCreated|datetime }})
{% endfor %}
```

### Spending points at checkout (Pro + Commerce)

Customers can apply points against an order — shows up like a coupon discount on the order summary, works with any gateway (Stripe, PayPal, manual).

**Cart template:**

```twig
{% set cart = craft.commerce.carts.cart %}
{% set balance = craft.points.sumForUser() %}
{% set applied = craft.points.appliedToOrder(cart.id) %}

<p>You have {{ balance }} {{ craft.points.currencyPlural|lower }} ({{ craft.points.formatMoney() }})</p>

{% if applied %}
    <p>{{ applied }} {{ craft.points.currencyPlural|lower }} applied to this order</p>
    <form method="post">
        {{ csrfInput() }}{{ actionInput('points/redeem/remove') }}
        <input type="hidden" name="orderId" value="{{ cart.id }}">
        <button type="submit">Remove</button>
    </form>
{% else %}
    <form method="post">
        {{ csrfInput() }}{{ actionInput('points/redeem/apply') }}
        <input type="hidden" name="orderId" value="{{ cart.id }}">
        <input type="number" name="points" min="1" max="{{ balance }}">
        <button type="submit">Apply points</button>
    </form>
{% endif %}
```

**How it works:**

1. User posts to `points/redeem/apply` with `orderId` and `points`
2. We validate (balance, min, max % of order), store the intent, and trigger Commerce to recalculate the order
3. The `PointsAdjuster` adds a negative line item (`-£X.XX (500 points)`) to the order
4. User pays the reduced total via any gateway
5. On `Order::EVENT_AFTER_ORDER_PAID`, the points are deducted from the user's balance — a negative `PointAward` is created (audit trail shows up in **Points → Awards**)
6. On refund, points are restored according to the **On refund** setting (`Restore proportionally` / `Restore only on full refund` / `Never restore`)

**Configure:** the conversion rate (`Points per £1`), min points per redemption, max % of order, and refund behaviour all live in **Points → Settings**.

### Dashboard widgets

Add via the Craft dashboard → + New widget:

- **Points Leaderboard** — top N users by total
- **Latest Points Awards** — most recent N awards across all users

## Plugin events

```php
use bymayo\points\events\AwardEvent;
use bymayo\points\events\LevelChangedEvent;
use bymayo\points\services\Awards;
use bymayo\points\services\Levels;
use yii\base\Event;

// Cancel an award (e.g. fraud check)
Event::on(Awards::class, Awards::EVENT_BEFORE_ADD_AWARD, function(AwardEvent $e) {
    if (suspiciousActivity($e->userId)) {
        $e->isValid = false;
    }
});

// Modify the points awarded
Event::on(Awards::class, Awards::EVENT_BEFORE_ADD_AWARD, function(AwardEvent $e) {
    if (isVip($e->userId)) {
        $e->pointsToAward *= 2;
    }
});

// React to an award
Event::on(Awards::class, Awards::EVENT_AFTER_ADD_AWARD, function(AwardEvent $e) {
    sendThankYouEmail($e->userId, $e->rule, $e->award);
});

// React when a user crosses a level threshold
Event::on(Levels::class, Levels::EVENT_LEVEL_CHANGED, function(LevelChangedEvent $e) {
    if ($e->currentLevel && $e->previousLevel?->threshold < $e->currentLevel->threshold) {
        congratulate($e->userId, $e->currentLevel);
    }
});
```

| Constant | Cancellable | When |
|---|---|---|
| `Awards::EVENT_BEFORE_ADD_AWARD` | Yes | Before an award is saved. Handler may modify `pointsToAward`. |
| `Awards::EVENT_AFTER_ADD_AWARD` | — | After the award is saved. |
| `Awards::EVENT_BEFORE_REMOVE_AWARD` | Yes | Before an award is deleted. |
| `Awards::EVENT_AFTER_REMOVE_AWARD` | — | After the award is deleted. |
| `Levels::EVENT_LEVEL_CHANGED` | — | When add/remove caused the user to change level. |

### Registering custom triggers / conditions / limits / rewards

Each subsystem has a register event:

```php
Event::on(Triggers::class, Triggers::EVENT_REGISTER_TRIGGERS, fn($e) => $e->triggers[] = MyTrigger::class);
Event::on(Conditions::class, Conditions::EVENT_REGISTER_CONDITION_RULES, fn($e) => $e->conditionRules[] = MyCondition::class);
Event::on(Limits::class, Limits::EVENT_REGISTER_LIMITS, fn($e) => $e->limits[] = MyLimit::class);
Event::on(Rewards::class, Rewards::EVENT_REGISTER_REWARDS, fn($e) => $e->rewards[] = MyReward::class);
```

Each class extends the matching base (`BaseTrigger`, `BaseConditionRule`, `BaseLimit`, `BaseReward`).

## GraphQL

```graphql
query Player($userId: Int!) {
  points: pointsSumForUser(userId: $userId)
  total:  pointsCountForUser(userId: $userId)
  level:  pointsLevelForUser(userId: $userId) {
    name handle threshold colour
  }
}

query Top10 {
  pointsLeaderboard(limit: 10) {
    userId userName points
    level { name colour }
  }
}

query Recent($userId: Int!) {
  pointsAwards(userId: $userId, limit: 20) {
    id pointsSnapshot dateCreated
    rule { name handle }
  }
}
```

| Query | Args | Returns |
|---|---|---|
| `pointsRules` | — | `[PointsRule]` |
| `pointsRule` | `handle: String!` | `PointsRule` |
| `pointsLevels` | — | `[PointsLevel]` |
| `pointsLevelForUser` | `userId: Int!` | `PointsLevel` |
| `pointsAwards` | `userId, ruleId, limit, offset` | `[PointsAward]` |
| `pointsSumForUser` | `userId: Int!` | `Int` |
| `pointsCountForUser` | `userId: Int!` | `Int` |
| `pointsLeaderboard` | `limit, offset` | `[PointsLeaderboardRow]` |

## Twig reference

| Call | Returns |
|---|---|
| `craft.points.awards` | `PointAward[]` — all awards, newest first |
| `craft.points.rules` | `Rule[]` |
| `craft.points.levels` | `Level[]` — ordered by threshold ascending |
| `craft.points.user(id)` | `User\|null` |
| `craft.points.rule(handle)` | `Rule\|null` |
| `craft.points.ruleById(id)` | `Rule\|null` |
| `craft.points.ruleByHandle(handle)` | `Rule\|null` |
| `craft.points.awardById(id)` | `PointAward\|null` |
| `craft.points.awardsByUser(id?)` | `PointAward[]` — defaults to current user |
| `craft.points.addAward(options)` | `PointAward\|null` |
| `craft.points.removeAward(options)` | `bool` |
| `craft.points.sumForUser(id?)` | `int` |
| `craft.points.countForUser(id?)` | `int` |
| `craft.points.levelForUser(id?)` | `Level\|null` |
| `craft.points.levelForPoints(points)` | `Level\|null` |
| `craft.points.levelById(id)` | `Level\|null` |
| `craft.points.levelByHandle(handle)` | `Level\|null` |
| `craft.points.leaderboard(limit?, offset?)` | `array` — rows of `{user, points, level}` |
| `craft.points.toMoney(points?)` | `float` — converts points to currency |
| `craft.points.formatMoney(points?)` | `string` — formatted with currency symbol |
| `craft.points.pluginName` | `string` — plugin name from settings |
| `craft.points.currency` | `string` — singular currency label |
| `craft.points.currencyPlural` | `string` — plural currency label |
| `craft.points.symbol` | `string` — currency symbol from settings |
| `craft.points.isPro` | `bool` — true on Pro edition |
| `craft.points.orderRedemption(orderId)` | `OrderRedemption\|null` — current redemption on the order |
| `craft.points.appliedToOrder(orderId)` | `int` — points currently applied to the order |

## Element queries

Awards are a first-class element type:

```twig
{% set awards = craft.app.elements.createElementQuery('bymayo\\points\\elements\\PointAward')
    .userId(currentUser.id)
    .ruleId(5)
    .orderBy({ dateCreated: SORT_DESC })
    .limit(20)
    .all() %}
```

## Security note

`craft.points.addAward` and `removeAward` are plain Twig calls — they bypass CSRF protection because they're template tags. Don't place them on publicly-accessible pages without thinking about abuse: a logged-out attacker hitting a page that awards points to a hardcoded user ID will succeed.

Safer patterns:

- Only award points behind a form post handler in your own controller
- Only allow awards from authenticated sessions (`craft.app.user.identity`)
- Apply rate limiting at the web server / CDN layer

Built-in protections that *do* apply: rule `Limit` settings (Once per user, Max N per period) and rule `Conditions` are evaluated server-side — Twig calls obey them just like automatic triggers.

## License

[Craft License](https://craftcms.github.io/license/)
