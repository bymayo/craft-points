<img src="https://raw.githubusercontent.com/bymayo/craft-points/craft-5/src/icon.svg" width="70">

# Points for Craft CMS 5.x

Award points to users for actions they perform, build leaderboards, and unlock tiered loyalty programmes — all from inside Craft.

## Features

- **Events** — define point-awarding actions (e.g. "Signed up to newsletter" = 20pts)
- **Automatic triggers** — fire events on Entry create/update/delete, Category save/delete, User register/login/update, Asset upload/delete — with per-section / per-group / per-volume scoping
- **Extensible** — other plugins can register their own triggers via `Triggers::EVENT_REGISTER_TRIGGERS`
- **Entries** — award those events to users from the CP or Twig
- **Levels** — tier users by accumulated points (Bronze/Silver/Gold style) with colour and icon
- **Leaderboard** — CP page and dashboard widget showing top users by total points, with their current level
- **Element index** — entries are a first-class element type with search, sort, filters, and bulk delete
- **Audit trail** — each entry stores the event's points value at the time it was awarded, so editing an event later doesn't retroactively rewrite history
- **Twig & GraphQL APIs** — drop-in Twig compatibility with the Craft 2 Points plugin, plus first-class GraphQL queries
- **Extensible** — plugin events (`EVENT_BEFORE_ADD_ENTRY`, `EVENT_LEVEL_CHANGED`, …) and per-user permissions

## Coming soon

- Full element-query GraphQL integration for entries (filter by section, search, eager loading)
- Plugin events for extensibility (`EVENT_AFTER_ADD_ENTRY`, `EVENT_LEVEL_CHANGED`, …)
- GraphQL types

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
| **Event** | A named action worth a fixed number of points, e.g. `signedUp` = 20 |
| **Entry** | A record of an event being awarded to a specific user, at a specific time |
| **Level** | A named tier reached once a user's point sum crosses a threshold |

## Usage

### Events

Navigate to **Points → Events** in the CP. Create an event with:

- **Name** — display label, e.g. "Signed up to newsletter"
- **Handle** — short identifier you'll use in Twig, e.g. `signedUp`
- **Points type** — Flat (fixed number of points) or Percent (percentage of order total — Commerce only)
- **Value** — the number of points (flat) or the percentage (percent)
- **Allow multiple** — when off, a user can only receive this event's points once; when on, the event is repeatable
- **Trigger** — when should this fire? "Manual" means only via Twig / CP. Pick a system event (Entry created, User logged in, Asset uploaded, …) to fire automatically.
- **Scope** (when applicable) — limit a trigger to specific sections, category groups, or volumes. Leave empty to apply to all.

### Automatic triggers

When you pick a trigger that's not "Manual", the plugin listens for that system event and awards points automatically. Recipients default to:

| Trigger | Recipient |
|---|---|
| Entry created/updated/deleted | Entry author |
| Category created/updated/deleted | Active CP user |
| User registered / updated / logged in | The user themselves |
| Asset uploaded / deleted | Asset uploader |
| Order completed (Commerce) | Order customer |
| Subscription created (Commerce) | Subscriber |

Commerce triggers only appear in the dropdown when `craftcms/commerce` is installed and enabled.

The Event's `multiple` flag still applies — so an `Entry updated` event with `multiple: false` only awards the first time a given user updates an entry.

### Adding triggers from another plugin

```php
use bymayo\points\events\RegisterTriggersEvent;
use bymayo\points\services\Triggers;
use yii\base\Event;

Event::on(Triggers::class, Triggers::EVENT_REGISTER_TRIGGERS, function(RegisterTriggersEvent $e) {
    $e->triggers[] = MyTrigger::class; // extends \bymayo\points\triggers\BaseTrigger
});
```

### Listening for entry / level events

```php
use bymayo\points\events\EntryEvent;
use bymayo\points\events\LevelChangedEvent;
use bymayo\points\services\Entries;
use bymayo\points\services\Levels;
use yii\base\Event;

// Cancel an entry award (e.g. fraud check)
Event::on(Entries::class, Entries::EVENT_BEFORE_ADD_ENTRY, function(EntryEvent $e) {
    if (suspiciousActivity($e->userId)) {
        $e->isValid = false;
    }
});

// Modify the points being awarded
Event::on(Entries::class, Entries::EVENT_BEFORE_ADD_ENTRY, function(EntryEvent $e) {
    if (isVip($e->userId)) {
        $e->pointsToAward = $e->pointsToAward * 2;
    }
});

// React after an entry is awarded
Event::on(Entries::class, Entries::EVENT_AFTER_ADD_ENTRY, function(EntryEvent $e) {
    sendThankYouEmail($e->userId, $e->event, $e->entry);
});

// React when a user crosses a level threshold (up or down)
Event::on(Levels::class, Levels::EVENT_LEVEL_CHANGED, function(LevelChangedEvent $e) {
    if ($e->currentLevel && $e->previousLevel?->threshold < $e->currentLevel->threshold) {
        congratulate($e->userId, $e->currentLevel);
    }
});
```

Available events:

| Constant | Cancellable | When |
|---|---|---|
| `Entries::EVENT_BEFORE_ADD_ENTRY` | Yes | Before an entry is saved. Handler may modify `pointsToAward`. |
| `Entries::EVENT_AFTER_ADD_ENTRY` | — | After the entry is saved. |
| `Entries::EVENT_BEFORE_REMOVE_ENTRY` | Yes | Before an entry is deleted. |
| `Entries::EVENT_AFTER_REMOVE_ENTRY` | — | After the entry is deleted. |
| `Levels::EVENT_LEVEL_CHANGED` | — | When add/remove caused the user to change level. |

## GraphQL

The plugin registers GraphQL queries automatically. Available in any GraphQL schema that's allowed to use them (admin schemas get them by default).

```graphql
# Get a user's total points and current level
query Player($userId: Int!) {
  points: pointsSumForUser(userId: $userId)
  total:  pointsTotalForUser(userId: $userId)
  level: pointsLevelForUser(userId: $userId) {
    name
    handle
    threshold
    colour
  }
}

# Leaderboard
query Top10 {
  pointsLeaderboard(limit: 10) {
    userId
    userName
    points
    level { name colour }
  }
}

# Recent entries for a user
query Recent($userId: Int!) {
  pointsEntries(userId: $userId, limit: 20) {
    id
    pointsSnapshot
    dateCreated
    event { name handle pointsType }
  }
}

# Look up an event by handle
query Event {
  pointsEvent(handle: "signedUp") {
    name
    points
    pointsType
    multiple
    trigger
  }
}

# List all defined levels
query Levels {
  pointsLevels {
    name
    threshold
    colour
  }
}
```

Available queries:

| Query | Args | Returns |
|---|---|---|
| `pointsEvents` | — | `[PointsEvent]` |
| `pointsEvent` | `handle: String!` | `PointsEvent` |
| `pointsLevels` | — | `[PointsLevel]` |
| `pointsLevelForUser` | `userId: Int!` | `PointsLevel` |
| `pointsEntries` | `userId, eventId, limit, offset` | `[PointsEntry]` |
| `pointsSumForUser` | `userId: Int!` | `Int` |
| `pointsTotalForUser` | `userId: Int!` | `Int` |
| `pointsLeaderboard` | `limit, offset` | `[PointsLeaderboardRow]` |

### Awarding points

From the CP — **Points → Entries → New entry** — or via Twig:

```twig
{# Award points to the current logged-in user #}
{{ craft.points.addEntry({ eventHandle: 'signedUp' }) }}

{# Award to a specific user #}
{{ craft.points.addEntry({ userId: 5, eventHandle: 'signedUp' }) }}
```

If the event has **Allow multiple** off and the user already has an entry for it, `addEntry` is a silent no-op.

### Removing points

```twig
{# Remove the oldest matching entry for the current user #}
{{ craft.points.removeEntry({ eventHandle: 'signedUp' }) }}

{# Or for a specific user #}
{{ craft.points.removeEntry({ userId: 5, eventHandle: 'signedUp' }) }}
```

`removeEntry` removes a single entry. To clear all of a user's entries for an event, call it in a loop.

### Reading totals

```twig
{# Current user's total points #}
{{ craft.points.sumEntries() }}

{# Specific user #}
{{ craft.points.sumEntries(5) }}

{# Entry count #}
{{ craft.points.totalEntries() }}
{{ craft.points.totalEntries(5) }}
```

Sums are based on each entry's `pointsSnapshot` (the event's value at award time), not the event's *current* value. This means editing an event's points value later doesn't retroactively change history.

### Levels

Navigate to **Points → Levels**. Each level has a name, handle, threshold (minimum points needed), optional colour, and optional icon.

A user's level is the highest one whose threshold is ≤ their current point sum. If two levels share a threshold, the most recently created wins.

```twig
{# Current user's level #}
{% set level = craft.points.levelForUser() %}
{% if level %}
    <span style="color: {{ level.colour }}">{{ level.name }}</span>
{% endif %}

{# Specific user's level #}
{{ craft.points.levelForUser(5).name }}

{# All levels (ordered by threshold ascending) #}
{% for level in craft.points.levels %}
    {{ level.name }} — {{ level.threshold }} pts
{% endfor %}

{# What level a hypothetical point total would reach #}
{{ craft.points.levelForPoints(250).name }}
```

### Leaderboard

```twig
{% for row in craft.points.leaderboard(10) %}
    {{ loop.index }}. {{ row.user.name }} — {{ row.points }} pts
    {% if row.level %}({{ row.level.name }}){% endif %}
{% endfor %}
```

Each row is `{ user: User, points: int, level: Level|null }`. The CP page lives at **Points → Leaderboard**, and there's a "Points Leaderboard" dashboard widget you can drop on the Craft dashboard.

### Dashboard widgets

Two widgets ship with the plugin (Dashboard → + New widget):

- **Points Leaderboard** — top N users by total points, with their level badges
- **Latest Points Entries** — most recent N entries, with user, event, points awarded, and relative time

### Dynamic events

Create events on the fly from Twig — returns the existing event if one with that handle already exists:

```twig
{{ craft.points.addEvent({
    event: 'Viewed ' ~ entry.title,
    eventHandle: 'viewed' ~ entry.title|camel,
    points: 5,
    multiple: false,
}) }}
```

### Entries for a user

```twig
{% for entry in craft.points.entriesByUser() %}
    {{ entry.event.name }} — {{ entry.pointsSnapshot }} pts ({{ entry.dateCreated|datetime }})
{% endfor %}
```

## Twig reference

| Call | Returns |
|---|---|
| `craft.points.entries` | `PointEntry[]` — all entries, newest first |
| `craft.points.events` | `Event[]` |
| `craft.points.levels` | `Level[]` — ordered by threshold ascending |
| `craft.points.user(id)` | `User\|null` |
| `craft.points.event(handle)` | `Event\|null` |
| `craft.points.eventById(id)` | `Event\|null` |
| `craft.points.eventByHandle(handle)` | `Event\|null` |
| `craft.points.eventOptions` | `array` — for select fields |
| `craft.points.entryById(id)` | `PointEntry\|null` |
| `craft.points.entriesByUser(id?)` | `PointEntry[]` — defaults to current user |
| `craft.points.addEntry(options)` | `PointEntry\|null` |
| `craft.points.removeEntry(options)` | `bool` |
| `craft.points.sumEntries(id?)` | `int` |
| `craft.points.totalEntries(id?)` | `int` |
| `craft.points.levelForUser(id?)` | `Level\|null` |
| `craft.points.levelForPoints(points)` | `Level\|null` |
| `craft.points.levelById(id)` | `Level\|null` |
| `craft.points.levelByHandle(handle)` | `Level\|null` |
| `craft.points.leaderboard(limit?, offset?)` | `array` — rows of `{user, points, level}` |
| `craft.points.addEvent(options)` | `Event\|null` |

## Element query

Entries are elements, so you can also use element queries directly:

```twig
{% set bigSpenders = craft.entries({
    section: null
}).elementType('bymayo\\points\\elements\\PointEntry').all() %}
```

Or import the type:

```twig
{% set PointEntry = 'bymayo\\points\\elements\\PointEntry' %}
{% set entries = PointEntry.find().userId(currentUser.id).all() %}
```

## Security note

`addEntry`, `removeEntry`, and `addEvent` carry over from Craft 2 and are unauthenticated Twig calls — they bypass CSRF protection because they're plain template tags. Don't place them on publicly-accessible pages without thinking about abuse: a logged-out attacker hitting a page that awards points to a hardcoded user ID will succeed.

Safer patterns:

- Only award points behind a form post handler in your own controller
- Only allow `addEntry` from authenticated sessions (`craft.app.user.identity`)
- Apply rate limiting at the web server / CDN layer for any URL that awards points

## Migrating from the Craft 2 plugin

The Twig API is back-compatible — your existing `craft.points.addEntry / removeEntry / sumEntries / totalEntries / addEvent` calls work unchanged.

The database schema is **not** back-compatible: Craft 2 stored entries with an `eventHandle` string and no audit trail. The Craft 5 version uses an `eventId` foreign key and a `pointsSnapshot` column. There is no automatic data migration — you'll need to re-create events and award points fresh.

## License

[Craft License](https://craftcms.github.io/license/)
