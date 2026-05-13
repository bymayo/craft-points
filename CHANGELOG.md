# Release Notes for Points

## Unreleased

### Breaking
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
