# Release Notes for Points

## Unreleased

### Added
- **Up to four new columns available on the Users element index** — register via the Users index column settings:
  - `{Currency Plural}` (e.g. *Points*, *Credits*) — total points balance, formatted with thousands separators *(Lite)*
  - `Level` — current level with a coloured dot *(Lite)*
  - `Available Spend` — monetary value of their *current* balance, formatted with the configured currency symbol *(Pro)*
  - `Redeemed` — monetary value of their *lifetime* redemptions *(Pro)*
- Leaderboard CP page and Leaderboard dashboard widget now render levels as a coloured dot + name (matching the Users element index) instead of the old solid-coloured pill.
- Leaderboard CP page now shows `Available Spend` and `Redeemed` columns on Pro — matches the Users element index so the leaderboard doubles as a quick-view of value per top earner.
- New service method: `Awards::getRedeemedPointsForUser($userId)` — returns lifetime redemption total as a positive int.
- **Awards element index now uses the rule name as the row title** instead of the auto-generated "Currency Award {id}" string — much more scannable. The standalone Rule column is removed from default attributes (still available via column settings).
- **Three ways to fire a Manual rule from the frontend**, all sharing the same login-required, CSRF-protected, current-user-only, Manual-rules-only, Limits-enforcing security model:
  1. **HTML form** — POST to `points/awards/fire` (or `points/awards/remove`) with `csrfInput()`, `actionInput()`, `redirectInput()` and a `ruleHandle`. Sets a flash and redirects. The default pattern for server-rendered pages.
  2. **JS API** — `{{ craft.points.script() }}` defines `window.Points.addAward(ruleHandle)` and `window.Points.removeAward(ruleHandle)`. Cache-safe (CSRF token fetched at runtime, never embedded into cached HTML) — works inside Blitz, `{% cache %}`, and any static cache.
  3. **GraphQL mutation** — `pointsAddAward(ruleHandle: String!): PointsAddAwardResult` for headless / decoupled SPAs. Available in any schema; same security boundary as the REST endpoint.
- `AwardsController::actionFire` is content-negotiated — JSON for AJAX callers (the JS API), `redirectToPostedUrl()` + flash for form posts.
- New `AwardsController::actionRemove` — same security model as `actionFire`, removes the user's oldest matching award.
- New `AwardsController::actionToken` — returns the current request's CSRF token; used by the JS API to keep cache-safety.
- New GQL type `PointsAddAwardResult` (`success`, `error`, `points`, `currency`, `awardId`).
- README has new **Examples** section (newsletter signup, share button, daily login bonus, birthday gift, 1 pt per £1, big-spender bonus, subscription loyalty, no-coupon-bonus, …) and **Using with Vue / React / SPAs** section showing both the sprinkled `window.Points` pattern and the headless `pointsAddAward` GraphQL mutation pattern.
- **Cooldown field on the Max per user limit** — set alongside Max + Reset period to combine "max 10 per day" with "at least 30s between each fire". All three fields are optional and stack. Replaces the standalone Cooldown limit type that was briefly added.
- **Order redemptions** (Pro + Commerce) — logged-in customers can spend points against Commerce orders. Posts to `points/redeem/apply` with `orderId` and `points`; appears as an order adjuster (like a coupon line). Points deduct from the user's balance on `Order::EVENT_AFTER_ORDER_PAID`. Refunds can optionally restore points (proportional / full-only / none).
- New settings: **Minimum to redeem**, **Max % of order**, **On refund** (restore behaviour).
- New service: `OrderRedemptions` — `apply($orderId, $userId, $points)`, `remove($orderId)`, `getForOrder($orderId)`, `processPaidOrder($order)`, `processRefund($tx)`.
- New Twig helpers: `craft.points.orderRedemption(orderId)`, `craft.points.appliedToOrder(orderId)`.
- New internal "Points redemption" rule (handle `__redemption`) — created on migration so deduction Awards have a valid `ruleId`. Don't delete it via the CP UI.

### Fixed
- `Awards::addAward` was silently behaving as "once per user" for every rule because it still referenced the long-removed `$rule->multiple` and `$rule->points` properties. Now reads from `$rule->reward['points']` and properly evaluates the rule's Limits when called from Twig (`addAward({ ruleHandle })`).
- Awards CP edit form (`/admin/points/awards/new`) errored when listing rules — it called the long-removed `rule.pointsType` / `rule.points`. Now uses `rule.rewardSummary` for the option labels.
- `AwardsController::actionSave` was reading `$rule->points` (also stale) when snapshotting points for manual awards. Now reads from the new reward config (flat → positive, deduct → negative, percent → 0 since manual awards have no order amount context).
- `SubscriptionPlanChangedTrigger` read `$event->newSubscription` which doesn't exist on Commerce's `SubscriptionSwitchPlansEvent`. Switched to `$event->subscription`.
- `EntryCreatedTrigger` / `EntryUpdatedTrigger` now skip drafts, revisions, and propagating saves — previously they could fire many times for a single user save action.

### Removed
- **`craft.points.addAward()` and `craft.points.removeAward()` Twig variable methods.** These bypassed CSRF (Twig calls aren't form submissions), ran at render time (one award per cache build, then never again on cached views), and accepted arbitrary `userId`s — all bad. They're replaced by:
  - **Form POST** to `points/awards/fire` / `points/awards/remove` for server-rendered pages (CSRF enforced by `csrfInput()`)
  - **JS API** (`Points.addAward` / `Points.removeAward`) for cache-safe button clicks
  - **GraphQL mutation** (`pointsAddAward`) for headless SPAs
  - **PHP service** (`Points::getInstance()->awards->addAward($userId, $handle)`) for modules, controllers, console commands — the only API that can target an arbitrary `userId`
- Dead code from earlier iterations:
  - Trigger "scope" system (`scopedTo`, `scopeIdForEvent`, `getScopeOptions`) — superseded by the Conditions engine. Removed from `TriggerInterface`, `BaseTrigger`, `EntryCreatedTrigger`, `EntryUpdatedTrigger`, and from `Triggers::dispatch` / `RuleEvaluationContext::$scopeId`.
  - Two-step trigger picker helpers (`Triggers::getSubjectOptions`, `getActionOptions`, `getSubjectForTrigger`, `getScopedTriggers`, `getScopedTriggersForTemplate`) — the UI was reverted to a single optgroup'd select.

### Added (continued)
- **Plugin editions** — Lite (free) and Pro. Lite is the full gamification feature set (events, awards, levels, leaderboard, widgets, triggers for Entry/Category/User/Asset, Twig + GraphQL APIs, plugin events). Pro adds:
  - Commerce triggers (Order completed, Subscription created)
  - Percentage-of-order-total point awards
- **Configurable currency name** — plugin settings let you rename "Points" to anything (Coins, Credits, Stars, Tokens…). The label appears in the CP nav, element type names, widget titles, page breadcrumbs, and frontend Twig via `craft.points.currency` / `craft.points.currencyPlural`. Plugin handle, URLs, table names, and Twig API namespaces are unchanged — only display labels are affected.
- `craft.points.isPro` Twig helper for conditional templating

### Added (continued)
- **New rule editor UI** — replaces the old Points/Multiple/Trigger form with a Zapier-style builder: WHEN (trigger) → IF (repeatable conditions) → LIMITS (repeatable frequency caps) → REWARD (type-specific points calculation) → ACTIVE PERIOD (optional date range). Vanilla JS, no Vue/React. Each row's irrelevant config fields are auto-disabled so only the active variant's values get submitted.
- **Conditions / Limits / Rewards engine** — Rules now compose a trigger with:
  - **Conditions** (predicates evaluated when the trigger fires) — pluggable, registered via `Conditions::EVENT_REGISTER_CONDITION_RULES`. v1 ships: Section, CategoryGroup, Volume, UserGroup, UserLevel, DayOfWeek. Pro adds: OrderTotal, OrderItemCount.
  - **Limits** (frequency caps) — pluggable, registered via `Limits::EVENT_REGISTER_LIMITS`. v1 ships: OncePerUser, MaxPerUser, PerPeriod (hour/day/week/month/year), Cooldown.
  - **Rewards** (how points are calculated) — pluggable, registered via `Rewards::EVENT_REGISTER_REWARDS`. v1 ships: Flat. Pro adds: Percent (% of trigger amount), PointsPerUnit (1 pt per £X spent).
- Rule model gains `enabled` flag and `activeFrom`/`activeTo` dates for campaign-style scheduling.
- Existing rule data is auto-migrated to the new shape on upgrade: `multiple=false` → `[oncePerUser]` limit; `pointsType=flat` → flat reward; `pointsType=percent` → percent reward; `triggerConfig.scopeIds` → section/categoryGroup/volume condition (chosen by trigger family).
- Stage 2 of the v3 redesign — the **CP UI for building rules** is Stage 3 (next). Existing rules continue to work; new rules created via Stage 3 UI will use the full conditions/limits/rewards engine.

### Breaking
- **Legacy rule columns dropped** — `points`, `pointsType`, `multiple`, `triggerConfig`. Rules now store all configuration in the `conditions` / `limits` / `reward` JSON columns. If you'd been testing with old-shape data, you'll need to re-create rules in the new editor.
- **"Event" renamed to "Rule" throughout** — the word "event" was overloaded with Craft's PHP `Event` concept and with system events that triggers listen for. "Rule" matches the industry vocabulary (Smile, LoyaltyLion, Yotpo all use "earning rules"). This affects:
  - DB table: `points_events` → `points_rules`; column `points_awards.eventId` → `ruleId` (migration handles both)
  - Classes: `Event` model → `Rule`, `EventRecord` → `RuleRecord`, `Events` service → `Rules`, `EventsController` → `RulesController`, `EventType` (GQL) → `RuleType`
  - Service property: `Points::getInstance()->events` → `->rules`
  - Element: `PointAward::$eventId` → `ruleId`, `getEvent()` → `getRule()`, `award.event` (Twig) → `award.rule`
  - Twig API: `craft.points.events` → `craft.points.rules`, `eventById/eventByHandle/event(handle)` → `ruleById/ruleByHandle/rule(handle)`; `addAward({eventHandle:})` → `addAward({ruleHandle:})`; `removeAward({eventHandle:})` → `removeAward({ruleHandle:})`. Method `craft.points.eventOptions` removed.
  - Plugin event class: `AwardEvent::$event` (the Rule reference) → `AwardEvent::$rule`
  - GraphQL: query `pointsEvents` → `pointsRules`, query `pointsEvent` → `pointsRule`, type `PointsEvent` → `PointsRule`, `PointsAward.eventId/event` → `ruleId/rule`, `pointsAwards` query arg `eventId` → `ruleId`
  - CP URLs: `/admin/points/events/*` → `/admin/points/rules/*`
  - Permission: `points-manageEvents` → `points-manageRules`
  - CP subnav: "Events" → "Rules"
- **Removed `craft.points.addEvent`** — rules are now CMS-managed only. Creating rules from Twig caused sprawl, bypassed admin curation, and didn't sync via project config. To award points per-content, use a single rule (e.g. `articleViewed`) with `multiple: true` and let `craft.points.addAward({ruleHandle:'articleViewed'})` be called from your template.
- **"Entry" renamed to "Award" throughout** to avoid clashing with Craft's own Entry element. This affects:
  - DB table: `points_entries` → `points_awards` (versioned migration handles the rename, data preserved)
  - Element class: `PointEntry` → `PointAward`
  - Service: `Entries` → `Awards` (`Points::getInstance()->entries` → `->awards`)
  - Twig API: `craft.points.addEntry` → `addAward`, `removeEntry` → `removeAward`, `entries` → `awards`, `entriesByUser` → `awardsByUser`, `entryById` → `awardById`, `sumEntries` → `sumForUser`, `totalEntries` → `countForUser`
  - GraphQL: `pointsEntries` query → `pointsAwards`, type `PointsEntry` → `PointsAward`, `pointsTotalForUser` → `pointsCountForUser`
  - Plugin events: `EVENT_*_ADD_ENTRY` → `EVENT_*_ADD_AWARD`, `EVENT_*_REMOVE_ENTRY` → `EVENT_*_REMOVE_AWARD`
  - Permission: `points-manageEntries` → `points-manageAwards` (any existing user assignments need to be re-granted)
  - CP URL: `/admin/points/entries/*` → `/admin/points/awards/*`

### Added
- Complete rewrite for Craft CMS 5
- `PointEntry` element type — entries are first-class elements with index, search, sort, and per-event source filtering
- `pointsSnapshot` field on entries — captures the event's point value at award time so editing an event later doesn't retroactively change historical totals
- `Levels` — tiered loyalty thresholds with optional colour and icon (Bronze/Silver/Gold style)
- **Leaderboard** — CP page (Points → Leaderboard) and dashboard widget showing top users by accumulated points, with level badges
- **Latest Entries** dashboard widget — shows the most recent points awarded with user, event, and relative timestamp (Craft 2 parity)
- **Automatic triggers** — Events can now fire automatically on system events: Entry created/updated/deleted, Category created/updated/deleted, User registered/updated/logged in, Asset uploaded/deleted
- **Craft Commerce triggers** (optional dep) — Order completed and Subscription created. Registered automatically when Commerce is enabled. Points go to the order's customer / subscription's user.
- **Percentage-of-amount points** — Events have a new `pointsType` field (`flat` or `percent`). When set to `percent` and fired from a trigger that provides an amount (currently `Order completed`), points awarded = `floor(amount * value / 100)`. Percent on a non-amount trigger silently skips.
- **Plugin events** for extensibility:
  - `Entries::EVENT_BEFORE_ADD_ENTRY` — cancellable; handlers can modify `pointsToAward`
  - `Entries::EVENT_AFTER_ADD_ENTRY`
  - `Entries::EVENT_BEFORE_REMOVE_ENTRY` — cancellable
  - `Entries::EVENT_AFTER_REMOVE_ENTRY`
  - `Levels::EVENT_LEVEL_CHANGED` — fires when adding or removing an entry causes a user to cross a level threshold
- CP sidebar subnav is now filtered by permissions — users only see the sections they can access; the whole Points nav item is hidden for users with no Points permissions
- **GraphQL support** — new queries: `pointsEvents`, `pointsEvent`, `pointsLevels`, `pointsLevelForUser`, `pointsEntries`, `pointsSumForUser`, `pointsTotalForUser`, `pointsLeaderboard`. Object types: `PointsEvent`, `PointsLevel`, `PointsEntry`, `PointsLeaderboardRow`.
- Per-trigger scope filtering — limit "Entry updated" to specific sections, "Asset uploaded" to specific volumes, etc.
- `Triggers::EVENT_REGISTER_TRIGGERS` plugin event — other plugins (e.g. Commerce, Formie) can register their own trigger classes that show up in the same picker
- User permissions: `points-manageEvents`, `points-manageEntries`, `points-manageLevels`
- Outline SVG icon for the CP sidebar, separate from the marketing icon
- Twig API back-compatible with the Craft 2 plugin: `craft.points.addEntry`, `removeEntry`, `sumEntries`, `totalEntries`, `addEvent`, `entriesByUser`, `eventOptions`, etc.
- New Twig methods: `craft.points.levels`, `levelForUser`, `levelForPoints`, `levelById`, `levelByHandle`, `leaderboard`

### Changed
- Minimum requirements: Craft 5.6, PHP 8.2
- Entries reference events by `eventId` (FK) rather than by handle string — renaming an event no longer orphans entries
- Namespace: `Craft\PointsPlugin` → `bymayo\points\Points`

## 1.0.3 - 2016

Final Craft 2 release. See the [`craft-2`](https://github.com/bymayo/craft-points/tree/craft-2) branch.
