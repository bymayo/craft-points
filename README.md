<img src="https://raw.githubusercontent.com/bymayo/craft-points/craft-5/src/icon.svg" width="70">

# Points for Craft CMS 5.x

Award points to users for actions they perform, build leaderboards, and unlock tiered loyalty programmes — all from inside Craft.

## Contents

- [Editions](#editions)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Concepts](#concepts)
  - [Settings storage (admin vs developer)](#settings-storage-admin-vs-developer)
  - [Permissions](#permissions)
- [Usage](#usage)
  - [Building a rule](#building-a-rule)
  - [Triggers shipped](#triggers-shipped)
  - [Firing Manual rules](#firing-manual-rules)
  - [Awarding from PHP](#awarding-from-php-modules-controllers-console-commands)
  - [Using with Vue / React / SPAs](#using-with-vue--react--spas)
  - [Reading points](#reading-points)
  - [Levels](#levels)
  - [Leaderboard](#leaderboard)
  - [Awards for a user](#awards-for-a-user)
  - [Spending points at checkout (Pro + Commerce)](#spending-points-at-checkout-pro--commerce)
  - [Dashboard widgets](#dashboard-widgets)
  - [Users element index columns](#users-element-index-columns)
- [Examples](#examples)
- [Plugin events](#plugin-events)
  - [Registering custom triggers / conditions / limits / rewards](#registering-custom-triggers--conditions--limits--rewards)
- [GraphQL](#graphql)
- [Twig reference](#twig-reference)
- [Element queries](#element-queries)
- [Security note](#security-note)
- [License](#license)

## Editions

| | Lite (free) | Pro |
|---|---|---|
| Rules, Awards, Levels, Leaderboard | ✅ | ✅ |
| Dashboard widgets | ✅ | ✅ |
| Built-in triggers (Entry / User / Asset) | ✅ | ✅ |
| Twig & GraphQL APIs | ✅ | ✅ |
| Plugin events | ✅ | ✅ |
| Renameable plugin & currency labels | ✅ | ✅ |
| Users index columns: balance + level | ✅ | ✅ |
| **Craft Commerce triggers** (Order completed / paid / refunded, Subscriptions) | — | ✅ |
| **Percentage-of-order-total** point awards | — | ✅ |
| **Commerce conditions** (Order total, item count, contains product, coupon) | — | ✅ |
| **Order redemptions** (customers spend points at checkout, gateway-agnostic) | — | ✅ |
| Users index columns: available spend + lifetime redeemed | — | ✅ |

Switch edition during development via `config/project/project.yaml`:

```yaml
plugins.points.edition: pro
```

## Features

- **Rules** — a builder for "When X happens, If Y is true, Then award Z" with limits and an active schedule
- **Automatic triggers** — fire on Entry create/update, User register/login/birthday/anniversary, Asset create, plus Commerce events on Pro
- **Conditions** — narrow rules with "Entry is in section", "User is in group", or "Order total / item count / contains product / has coupon" (Pro)
- **Limits** — cap how often a rule can fire (max count, reset period, and cooldown — composable on one rule)
- **Awards** — entry-style element index of every points award, with all the standard Craft sources, search, sort, and bulk actions
- **Levels** — tier users by accumulated points (Bronze / Silver / Gold style) with colour
- **Leaderboard** — CP page and dashboard widget showing top users by total points
- **Users index columns** — opt-in columns on Craft's built-in Users table for points balance, level, available spend (Pro), and lifetime redeemed (Pro)
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
- **Conversion rate** — how many points equal how many units of money. Currency itself comes from Craft Commerce's primary store automatically — there's no symbol setting.

All in **Points → Settings**. Plugin handle, URLs and database don't change.

### Settings storage (admin vs developer)

Plugin settings (currency labels, plugin name, conversion rate, redemption rules, …) are stored in the plugin's own `{{%points_settings}}` table **— not in Project Config**. This is a deliberate split:

- **Admins can change branding & operational settings on production** without their changes being clobbered by the next deploy from staging.
- **Settings don't sync across environments** via `project.yaml`. If you want a dev override (e.g. different currency symbol on staging), set it in `config/points.php` — values there take precedence over the DB row.

```php
// config/points.php
return [
    '*' => [
        'conversionPointsCount' => 100,   // 100 points
        'conversionCurrencyUnits' => 1,   // = 1 unit of the Commerce primary store's currency
        'redemptionMaxOrderPercent' => 50,
    ],
];
```

> Currency itself is **not configurable** — it always tracks your Craft Commerce primary store's currency. Money helpers (`toMoney()`, `formatMoney()`, Available Spend / Redeemed columns, Order redemptions) require Commerce to be installed. On Lite or Pro-without-Commerce, `formatMoney()` returns an empty string and the money columns are hidden.

### Permissions

The plugin ships granular user permissions so you can give different roles different levels of access. They appear under a **Points** heading on each user group's permissions page.

| Permission | What it does |
|---|---|
| `View awards` | Read access to the Awards CP page |
| ↳ `Create awards` | Manually create new award records |
| ↳ `Edit awards` | Modify existing awards |
| ↳ `Delete awards` | Delete awards |
| `View rules` | Read access to the Rules CP page (open the rule builder, see the rules table) |
| ↳ `Create rules` | Save a new rule |
| ↳ `Edit rules` | Save changes to an existing rule |
| ↳ `Delete rules` | Delete rules |
| `View levels` | Read access to the Levels CP page |
| ↳ `Create levels` | Create new levels |
| ↳ `Edit levels` | Modify existing levels |
| ↳ `Delete levels` | Delete levels |
| `View leaderboard` | Read access to the Leaderboard CP page |
| `Manage settings` | Access the Settings page and save changes |

Nesting works the standard Craft way: a child permission can only be granted once its parent is granted. So "Create awards", "Edit awards", "Delete awards" are only enable-able once "View awards" is checked. **The Points sidebar item, and each sub-page within it, is hidden entirely if the user has no relevant permissions.**

Example role setups:

- **Customer-success agent** — `View awards`, `View leaderboard`. Can investigate user balances and see top customers, can't change anything.
- **Loyalty manager** — Everything except `Delete rules`, `Delete levels`, `Manage settings`. Can build the programme but can't drop existing rules/levels (preserves audit trail) or change branding.
- **Admin** — All permissions.

> Frontend endpoints (`points/awards/fire`, `points/awards/remove`, GraphQL `pointsAddAward`) don't use these CP permissions — they only check that the user is logged in and that the rule is Manual. CP permissions only gate the CP UI.

## Usage

### Building a rule

Navigate to **Points → Rules → New rule**. You'll see a 5-section builder:

- **Name / Handle / Enabled** — top-level metadata
- **When** — pick the trigger (or leave as Manual)
- **If** — optional conditions (all must pass). Only shows conditions relevant to the chosen trigger
- **Then** — what to award: Add a flat amount, Add a percentage (Pro), or Deduct
- **Limit** — Once per user, or Max per user (any combination of: max N, reset every period, and/or min N seconds cooldown between fires)
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

### Firing Manual rules

A *Manual* rule is one with no trigger — it only fires when your code asks it to. Use this for actions Craft can't see on its own: newsletter signups, button clicks, "share" links, profile completion, etc.

There are **three** frontend ways to fire one, depending on how your site is built. All three share the same server-side security model (covered below).

#### 1. HTML form (the default — simplest, server-rendered pages)

Drop a form into your template. CSRF is handled by Craft's `csrfInput()`, the user is whoever's logged in, and you get a flash message back.

```twig
<form method="post">
    {{ csrfInput() }}
    {{ actionInput('points/awards/fire') }}
    {{ redirectInput('account/thanks') }}
    <input type="hidden" name="ruleHandle" value="signedUpForNewsletter">
    <button type="submit">Subscribe</button>
</form>
```

To reverse an award (e.g. an "unshare" button), post to `points/awards/remove` with the same shape.

> ⚠ Forms don't work inside Blitz / `{% cache %}` blocks — the embedded CSRF token will be stale. Use the JS API instead on cached pages.

#### 2. JS API (cache-safe — for buttons, SPAs-on-Craft, anything Blitz)

Drop the helper once in your layout:

```twig
{{ craft.points.script() }}
```

That outputs an inline script defining `window.Points.addAward(ruleHandle)` and `window.Points.removeAward(ruleHandle)`. The CSRF token is fetched at runtime through a separate uncached AJAX call, so nothing about it is embedded into the cached HTML — works inside Blitz / `{% cache %}` / static cache.

```html
<button id="share-btn">Share</button>
<script>
    document.getElementById('share-btn').addEventListener('click', function () {
        Points.addAward('shared').then(function (res) {
            if (res.success) {
                console.log('Earned ' + res.points + ' ' + res.currency);
            }
        });
    });
</script>
```

#### 3. GraphQL mutation (headless / decoupled SPAs)

For frontends that live outside Craft's templates (Next.js, Nuxt, native app, …) — see [Using with Vue / React / SPAs](#using-with-vue--react--spas).

```graphql
mutation FireRule($handle: String!) {
    pointsAddAward(ruleHandle: $handle) {
        success error points currency awardId
    }
}
```

#### Security model — applies to all three patterns

- **Login required** — anonymous requests are rejected
- **CSRF protected** — `csrfInput()` for forms, runtime token fetch for JS, Craft session cookie for GraphQL
- **Current-user only** — `userId` is never accepted as input; points always go to the authenticated user
- **Manual rules only** — rules with a trigger fire on their own system events and can't be triggered manually
- **Limits enforced** — "Once per user" / "Max N per period" / cooldown all apply
- **Schedule honoured** — rules outside their active date range are rejected

**Inherent limitation:** the client is trusted to ask. A determined user could call `Points.addAward('shared')` from devtools without sharing anything. Mitigations: rule Limits, and the principle of "small rewards only for Manual rules". For anything high-value, use server-side triggers (Order paid, Entry created, …) which can't be faked from the client.

### Awarding from PHP (modules, controllers, console commands)

If you're already running on the server — inside a module, a custom controller action, or a console command — go through the service directly. This is the **only** way to award points to a `userId` other than the currently-authenticated user.

```php
use bymayo\points\Points;

Points::getInstance()->awards->addAward($userId, 'profileCompleted');
Points::getInstance()->awards->removeAward($userId, 'profileCompleted');
```

Respects rule Limits. Skips Conditions (those need a trigger context).

### Using with Vue / React / SPAs

The right pattern depends on whether Craft is rendering your HTML or not.

#### Sprinkled Vue / React on Craft-rendered pages

If your component is being hydrated into HTML that Craft generates (Craft is still your view layer), use `{{ craft.points.script() }}` in your layout — that defines `window.Points.addAward(ruleHandle)` as a global. Your component can call it directly.

```jsx
function ShareButton() {
    const [earned, setEarned] = useState(null);

    return (
        <button onClick={async () => {
            const res = await window.Points.addAward('shared');
            if (res.success) setEarned(res.points);
        }}>
            {earned ? `+${earned} earned!` : 'Share'}
        </button>
    );
}
```

```vue
<script setup>
import { ref } from 'vue';
const earned = ref(null);
async function share() {
    const res = await window.Points.addAward('shared');
    if (res.success) earned.value = res.points;
}
</script>

<template>
    <button @click="share">{{ earned ? `+${earned} earned!` : 'Share' }}</button>
</template>
```

Same setup works with Alpine, Stimulus, htmx, vanilla JS — anything that can call a global function.

#### Decoupled / headless (Next.js, Nuxt, Astro, native apps)

When your frontend is served from a separate origin and never touches Craft's templates, use the GraphQL mutation. You'll already have a GraphQL client set up for reads.

```js
import { gql, useMutation } from '@apollo/client';

const ADD_AWARD = gql`
    mutation AddAward($handle: String!) {
        pointsAddAward(ruleHandle: $handle) {
            success
            error
            points
            currency
        }
    }
`;

function ShareButton() {
    const [addAward, { data }] = useMutation(ADD_AWARD);
    return (
        <button onClick={() => addAward({ variables: { handle: 'shared' } })}>
            {data?.pointsAddAward?.success
                ? `+${data.pointsAddAward.points} earned!`
                : 'Share'}
        </button>
    );
}
```

**Auth setup:**

- **Same-site (e.g. `app.example.com` calling `cms.example.com`)** — set `credentials: 'include'` on your fetch / Apollo client and configure CORS to allow your frontend origin. The user logs into Craft and the session cookie flows through.
- **Fully external (different root domain, native app)** — implement bearer token auth. Easiest path is the [`craftcms/jwt-auth`](https://github.com/craftcms/cms) pattern or roll your own login mutation that issues a JWT.

The same security model applies as for the form / JS API: must be authenticated, only Manual rules, points always go to the authenticated user (the mutation has no `userId` arg).

### Reading points

```twig
{{ craft.points.sumForUser() }}        {# total for current user #}
{{ craft.points.sumForUser(5) }}        {# total for user 5 #}
{{ craft.points.countForUser() }}       {# award count #}

{# Money conversion (uses Points per currency unit + Currency symbol settings) #}
{{ craft.points.toMoney() }}            {# e.g. 2.5 #}
{{ craft.points.formatMoney() }}        {# e.g. "£2.50" or "" if Commerce isn't installed #}
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

**Configure:** the conversion rate (X points : Y currency units), min points per redemption, max % of order, and refund behaviour all live in **Points → Settings → Money**. Currency itself tracks your Commerce primary store automatically.

### Dashboard widgets

Add via the Craft dashboard → + New widget. Both widgets are grouped under your configured plugin name (e.g. `Rewards - Leaderboard`, `Rewards - Latest Awards`), so they cluster together in the picker like Commerce's widgets do.

| Widget | What it shows |
|---|---|
| **{Plugin} - Leaderboard** | Table of top N users by total balance. Columns: Customer (avatar + name) · Level (coloured dot + name) · Total {currency plural}. |
| **{Plugin} - Latest Awards** | Table of the N most recent awards across all users. Columns: Customer · {currency plural} (linked to the award edit page) · Date. |

Both widgets respect your renaming: column headers and titles automatically follow the configured **Plugin name** and **Reward unit (plural)** values. Each user row uses Craft's standard avatar (uploaded image or monogram fallback) and links to the user's CP edit page.

Limit on either widget can be tuned via the widget's settings cog.

### Users element index columns

The plugin adds optional columns to Craft's built-in **Users** element index so admins can see each user's loyalty state at a glance, alongside their email, last login, groups, etc. None are enabled by default — opt in via the **Customize column** settings on the Users page.

| Column | Edition | What it shows |
|---|---|---|
| `{Currency Plural}` (e.g. *Points*, *Credits*) | Lite | Total points balance, formatted with thousands separators |
| `Level` | Lite | Current level with a small coloured dot (matches the Leaderboard styling) |
| `Available Spend` | Pro + Commerce | Monetary value of the user's *current* balance, formatted in the Commerce primary store's currency |
| `Redeemed` | Pro | Monetary value of the user's *lifetime* redemptions through Commerce checkout |

The column labels follow your plugin settings — set **Currency name (plural)** to "Coins" and the first column is "Coins"; set **Currency symbol** to `$` and the money-valued columns format as `$2.50`.

**Heads-up on performance:** each row fetches the user's balance independently, so on very large Users indexes (thousands+ per page) you'll see one extra query per row. Fine for typical CP pagination (50 / page).

## Examples

A grab-bag of real-world setups to get you thinking. Each one lists the **Rule config** (what to set in the CP) and the **Frontend** (only when one's needed — automatic triggers don't need any frontend code).

### General / community

**Newsletter signup**

- *Trigger:* Manual
- *Limit:* Once per user
- *Reward:* Flat — 50 points
- *Frontend:* form posting to `points/awards/fire` after the actual form submission succeeds, or directly bound to the signup button if you don't have a separate confirmation step

```twig
<form method="post">
    {{ csrfInput() }}
    {{ actionInput('points/awards/fire') }}
    <input type="hidden" name="ruleHandle" value="signedUpForNewsletter">
    <input type="email" name="email" required>
    <button>Subscribe</button>
</form>
```

(In a real setup the form posts to your newsletter handler, which on success forwards to a thank-you page that calls `Points.addAward('signedUpForNewsletter')` via the JS API. That way users can't grab the rule just by hitting the form once.)

**Share a page**

- *Trigger:* Manual
- *Limit:* Max per user — Max 1, Reset every day (so you can only earn it once per day)
- *Reward:* Flat — 5 points

```html
<button onclick="Points.addAward('shared')">Share</button>
```

**Daily login bonus**

- *Trigger:* User logged in
- *Limit:* Max per user — Max 1, Reset every day
- *Reward:* Flat — 10 points
- *Frontend:* none — fires automatically

**Profile completed**

- *Trigger:* User updated *(if you use a [custom event](#registering-custom-triggers--conditions--limits--rewards))* or Manual fired from your "save profile" controller
- *Limit:* Once per user
- *Reward:* Flat — 100 points

**Birthday gift**

- *Trigger:* User birthday
- *Limit:* Max per user — Max 1, Reset every year
- *Reward:* Flat — 250 points
- *Setup:* set a Date field on the user layout, then pop its handle into **Points → Settings → Birthday field handle**

**Loyal customer anniversary**

- *Trigger:* User anniversary
- *Reward:* Flat — 500 points

**Commented on a post**

- *Trigger:* Entry created
- *Condition:* Entry is in section *Comments*
- *Limit:* Max per user — Max 5, Reset every day
- *Reward:* Flat — 5 points

### Commerce (Pro)

**1 point per £1 spent**

- *Trigger:* Order paid
- *Reward:* Percentage — 100% of order total (i.e. order total in pence/cents → 1 point per minor unit). Or use the conversion rate in **Settings → Money** and let the percent reward do the math
- *Frontend:* none

**Welcome bonus on first order**

- *Trigger:* First order
- *Reward:* Flat — 500 points

**Big spender bonus**

- *Trigger:* Order paid
- *Condition:* Order total > £100
- *Reward:* Flat — 200 points (on top of any per-£ rule)

**Buy a featured product**

- *Trigger:* Order paid
- *Condition:* Order contains product *(picked from a Product field)*
- *Reward:* Flat — 100 points

**Subscriber loyalty**

- *Trigger:* Subscription renewed
- *Reward:* Flat — 50 points per renewal

**Don't reward coupon users**

Often you want to give bonus points only to customers paying full price.

- *Trigger:* Order paid
- *Condition:* Order has coupon = *No*
- *Reward:* Flat — 50 points

**Customer redeems points at checkout**

See [Spending points at checkout](#spending-points-at-checkout-pro--commerce). No rule config needed — the redemption form posts to `points/redeem/apply` and the plugin handles deduction on `Order::EVENT_AFTER_ORDER_PAID`.

### Tying it together — leaderboards & levels

Once you've got several rules awarding points, the **Leaderboard** widget and **Levels** kick in for free:

```twig
{% set me = craft.points.levelForUser() %}
{% if me %}
    You're a <span style="color: {{ me.colour }}">{{ me.name }}</span> member.
{% endif %}

<h3>Top customers this month</h3>
{% for row in craft.points.leaderboard(10) %}
    <p>{{ loop.index }}. {{ row.user.name }} — {{ row.points }}
        {% if row.level %}<small>({{ row.level.name }})</small>{% endif %}</p>
{% endfor %}
```

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

### Mutations

| Mutation | Args | Returns |
|---|---|---|
| `pointsAddAward` | `ruleHandle: String!` | `PointsAddAwardResult` |

`PointsAddAwardResult` is `{ success: Boolean, error: String, points: Int, currency: String, awardId: Int }`. Requires an authenticated session; same Manual-only / current-user-only / Limits-enforced guarantees as the form and JS API.

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
| `craft.points.script()` | `Markup` — inline `<script>` defining the cache-safe `window.Points.addAward()` JS API |

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

Manual rule firing from the frontend goes through one of three CSRF-protected, login-required, current-user-only endpoints — `<form>` POST, JS API, or GraphQL mutation. The plugin **does not expose `addAward` / `removeAward` as Twig variables**, because Twig calls bypass CSRF and run at render time (a disaster with caching). For server-side awarding (modules, controllers, console commands), call `Points::getInstance()->awards->addAward($userId, $handle)` directly in PHP — that's the only API that lets you target an arbitrary `userId`.

All three frontend patterns enforce rule **Limits** (Once per user, Max N per period, cooldown) and the **active schedule** server-side.

## License

[Craft License](https://craftcms.github.io/license/)
