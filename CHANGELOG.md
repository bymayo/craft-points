# Release Notes for Points

## 5.0.1 - 2026-05-15

### Added
- **`PercentReward` ("% of order total") is now filtered by trigger subject** in the rule builder - it only shows up when an Order trigger is selected. Manual / Entry / User triggers don't expose it (without an `amount` on the trigger the calculation would always be 0). Implemented via a new `RewardInterface::appliesToSubjects()` static method (default `null` in `BaseReward` = applies to all subjects), matching the pattern already used by Conditions.

### Changed
- **Twig helper `formatMoney()` renamed to `spendForUser($id?)`** for consistency with `sumForUser` / `countForUser` / `levelForUser` and the Users index "Available Spend" column. `toMoney(points)` stays as the lower-level float helper.
- README rewritten to be more concise and friendlier - examples consolidated, jargon trimmed, full settings table added, Twig reference split out per-method, Vue/React section folded into the main "Firing a rule" flow.
- Awards element index "Point Award" title column header now reads "Rule" (matches the row content). Implemented via a `displayName()` override.
- Awards element index Points and Date Created columns are now click-to-sort.
- Latest Awards / Leaderboard dashboard widgets now use Craft's native `tableview` styling with avatars, configurable column headers that follow the plugin / currency name settings, and a "View all" footer button gated on the matching view permission.
- LICENSE.md copyright line updated to `Jason Mayo (ByMayo)` to match other ByMayo paid plugins.

### Fixed
- `craft.points.appliedToOrder($orderId)` and `orderRedemption($orderId)` now accept `null` instead of `TypeError`-ing. Fixes the case where a logged-out visitor lands on a page that passes `cart.id` (which can be null) into these helpers.
- All single-element Twig lookups (`user`, `rule`, `ruleById`, `ruleByHandle`, `awardById`, `levelForPoints`, `levelById`, `levelByHandle`) are now defensively nullable - return null when given null instead of throwing.

## 5.0.0 - 2026-05-14

First Craft 5 release - complete rewrite from the Craft 2 line (last released at 1.0.3 in 2016). Version jumped to align with Craft 5 itself, the way Craft Commerce and other ecosystem plugins do.

### Added
- **Plugin editions** - Lite (free) and Pro. Lite includes the full gamification feature set (rules, awards, levels, leaderboard, widgets, Entry/Asset/User triggers, Twig + GraphQL APIs). Pro adds Commerce triggers (Orders + Subscriptions), percentage-of-order rewards, Commerce conditions, and order redemptions.
- **Visual rule builder** (CP) - Zapier-style: WHEN (trigger) → IF (repeatable conditions) → THEN (reward type) → LIMITS (frequency caps) → ACTIVE PERIOD (optional date range).
- **Conditions / Limits / Rewards engine** - pluggable subsystems, each with a register event so other plugins can ship their own classes.
  - Conditions ship: Section, CategoryGroup, Volume, UserGroup, UserLevel, DayOfWeek. Pro adds: OrderTotal, OrderItemCount, OrderHasCoupon, OrderContainsProduct.
  - Limits ship: OncePerUser, MaxPerUser (with optional cooldown field).
  - Rewards ship: Flat, Deduct. Pro adds: Percent (% of trigger amount). Reward types can declare which trigger subjects they apply to via `appliesToSubjects()` - Percent is scoped to `order` so it only appears in the rule builder when an Order trigger is selected.
- **Triggers** - Entry created/updated, Asset created, User registered/logged in/birthday/anniversary on Lite; Order paid/completed/refunded/first-ever and Subscription created/renewed/cancelled/plan-changed on Pro.
- **`PointAward` element type** - awards are first-class elements with a full index, search, source filters, and bulk actions. Row title is the rule name; title column header reads "Rule" (via `displayName()` override) so it matches the content. Points and Date Created columns are click-to-sort. Points snapshotted at award time so editing a rule later doesn't rewrite history.
- **Levels** - tiered loyalty thresholds with optional colour and badge icon (Bronze/Silver/Gold style).
- **Leaderboard** - CP page (paginated) and dashboard widget showing top users by total balance.
- **Latest Awards** dashboard widget - the N most recent awards across all users, with user avatars.
- **Three ways to fire a Manual rule from the frontend**, all sharing the same login-required, CSRF-protected, current-user-only, Manual-only, Limits-enforcing security model:
  1. **HTML form** - POST to `points/awards/add` (or `points/awards/remove`) with `csrfInput()`, `actionInput()`, `redirectInput()`, and a `ruleHandle`.
  2. **JS API** - `{{ craft.points.script() }}` defines `window.Points.addAward(handle)` and `removeAward(handle)`. Cache-safe (CSRF token fetched at runtime), works inside Blitz / `{% cache %}` / static cache.
  3. **GraphQL mutation** - `pointsAddAward(ruleHandle: String!): PointsAddAwardResult` for headless / decoupled SPAs.
- **Order redemptions** (Pro + Commerce) - customers spend points against a Commerce order. The `PointsAdjuster` shows it like a coupon discount. Points deduct on `Order::EVENT_AFTER_ORDER_PAID`; refunds restore points per the configured behaviour (proportional / full-only / none).
- **Awards stamp the originating order** (Pro + Commerce) - awards from `OrderPaidTrigger`, `OrderCompletedTrigger`, `OrderRefundedTrigger`, `FirstOrderTrigger` carry an `orderId` back-reference. Optional `Order` column on the Awards index renders the order as a Craft chip linking to the order edit URL.
- **Up to four optional columns on the Users element index** - `{Currency Plural}` (balance) and `Level` on Lite; `Available Spend` and `Redeemed` on Pro+Commerce. Opt-in via column settings.
- **Renameable** - plugin name (sidebar / breadcrumbs - e.g. "Rewards System") and Reward unit labels (singular / plural - e.g. "Coin" / "Coins") configurable in **Points → Settings → General**. Plugin handle, URLs, table names, and Twig API namespaces unchanged - display labels only.
- **Conversion rate** stored as a paired `conversionPointsCount` + `conversionCurrencyUnits` setting so admins can express ratios like "5 points = 1 unit". Currency itself tracks the Commerce primary store automatically (no symbol setting).
- **Plugin settings live in their own DB table**, not Project Config - admins can rename things on production without staging deploys clobbering them. Devs can pin per-environment values via `config/points.php`.
- **Tabbed Settings page** extending `_layouts/cp` with native Craft tabs - General, Commerce (Pro), Rules.
- **Settings link in the CP sidebar**, gated on `points-manageSettings`. Craft's gear-menu route redirects to the same page.
- **Granular permissions** - View + Create + Edit + Delete per resource (Awards, Rules, Levels), plus `points-viewLeaderboard` (read-only) and `points-manageSettings`. Child permissions only grantable once the parent is granted. The Points sidebar item and each sub-page hide entirely when the user has no relevant permissions.
- **Dashboard widget visibility** - widgets check the user's `view-*` permission before appearing in the widget picker.
- **Plugin events** for extensibility:
  - `Awards::EVENT_BEFORE_ADD_AWARD` (cancellable; handlers can modify points)
  - `Awards::EVENT_AFTER_ADD_AWARD`
  - `Awards::EVENT_BEFORE_REMOVE_AWARD` (cancellable)
  - `Awards::EVENT_AFTER_REMOVE_AWARD`
  - `Levels::EVENT_LEVEL_CHANGED`
- **Pluggable subsystem events**: `Triggers::EVENT_REGISTER_TRIGGERS`, `Conditions::EVENT_REGISTER_CONDITION_RULES`, `Limits::EVENT_REGISTER_LIMITS`, `Rewards::EVENT_REGISTER_REWARDS`.
- **GraphQL** - queries `pointsRules`, `pointsRule`, `pointsLevels`, `pointsLevelForUser`, `pointsAwards`, `pointsSumForUser`, `pointsCountForUser`, `pointsLeaderboard`. Mutation `pointsAddAward`. Types: `PointsRule`, `PointsLevel`, `PointsAward`, `PointsLeaderboardRow`, `PointsAddAwardResult`.
- **Twig API** - `craft.points.*` with helpers for rules, awards, levels, leaderboard, money conversion, and the cache-safe `script()` helper. Plus `currency`, `currencyPlural`, `symbol`, `currencyCode`, `pluginName`, `isPro`. Per-user money helper renamed `formatMoney()` → `spendForUser($id?)` for consistency with `sumForUser` / `countForUser` / `levelForUser` (and the Users index "Available Spend" column). All single-element lookups (`user`, `rule`, `ruleById`, `ruleByHandle`, `awardById`, `levelForPoints`, `levelById`, `levelByHandle`, `orderRedemption`, `appliedToOrder`, `spendForUser`) accept null and return null / 0 / '' gracefully, so templates can pass `currentUser.id`, `cart.id`, `award.ruleId` etc. without guarding against logged-out or cartless visitors.
- **Plugin / service helpers**: `Points::hasCommerce()`, `getStoreCurrencyCode()`, `getStoreCurrencySymbol()`, `formatStoreMoney()`, static `pointsToMoney()`. `Awards::getRedeemedPointsForUser()`. `OrderRedemptions` service (`apply`, `remove`, `getForOrder`, `processPaidOrder`, `processRefund`).
- **Trigger API**: `TriggerInterface::isAvailable()` (hide triggers from the picker until their dependencies are met - e.g. `UserBirthdayTrigger` waits until a Date field handle is set on the user layout) and `TriggerInterface::getOrderIdFromEvent()` (lets order triggers stamp their award with the source order ID).

### Changed (breaking from the Craft 2 plugin)
- **"Event" renamed to "Rule" throughout** - the word "event" was overloaded with Craft's PHP `Event` concept. Affects DB tables, class names, service properties, Twig/GraphQL APIs, CP URLs, and permissions.
- **"Entry" renamed to "Award" throughout** to avoid clashing with Craft's own Entry element. Affects DB tables, element class (`PointEntry` → `PointAward`), service (`Entries` → `Awards`), Twig/GraphQL APIs, plugin events, permissions, and CP URLs.
- **Currency is no longer configured in the plugin** - tracks Craft Commerce's primary store automatically.
- **Money helpers are Pro + Commerce only** - `craft.points.toMoney()`, `spendForUser()`, `symbol`, `currencyCode`, `orderRedemption`, `appliedToOrder`, the `Available Spend` / `Redeemed` columns, and the order-redemption flow all require Commerce installed.
- **Twig `craft.points.addAward()` / `removeAward()` removed** - they bypassed CSRF and accepted arbitrary `userId`s. Use the form / JS API / GraphQL mutation from public pages, or `Points::getInstance()->awards->addAward($userId, $handle)` from server-side PHP.
- **Twig `craft.points.addEvent` removed** - rules are CMS-managed only now.
- **Permissions overhauled** - the Craft 2 plugin's single `points-manageEvents` / `points-manageEntries` / `points-manageLevels` tier is replaced with View + Create + Edit + Delete per resource, plus `points-viewLeaderboard` and `points-manageSettings`.
- **Minimum requirements** - Craft 5.6+, PHP 8.2+.
- **Namespace** - `Craft\PointsPlugin` → `bymayo\points\Points`.

## 1.0.3 - 2016

Final Craft 2 release. See the [`craft-2`](https://github.com/bymayo/craft-points/tree/craft-2) branch.
