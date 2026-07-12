---
title: GaOverviewData
---

# `GaOverviewData`

`ArtisanPackUI\AnalyticsGoogle\Reporting\GaOverviewData` — immutable, readonly `final` DTO backing the GA overview surface.

## Constructor

```php
public function __construct(
    public readonly array $totals,      // {sessions, users, page_views, avg_engagement_seconds}
    public readonly array $trend,       // list<{date, sessions, users, page_views}>
    public readonly DateRange $range,
)
```

- **`totals`** — `array{sessions: float, users: float, page_views: float, avg_engagement_seconds: float}`.
- **`trend`** — `list<array{date: string, sessions: float, users: float, page_views: float}>` sorted by `date` ascending. `date` is `YYYY-MM-DD`.
- **`range`** — the same `DateRange` passed to `GaOverviewFetcher::fetch()`.

## Methods

### `toArray(): array<string, mixed>`

JSON shape returned by the `/analytics-google/overview` endpoint:

```php
[
    'range'  => [ 'startDate' => '29daysAgo', 'endDate' => 'today' ],
    'totals' => [ 'sessions' => 1234.0, 'users' => 800.0, 'page_views' => 4567.0, 'avg_engagement_seconds' => 92.4 ],
    'trend'  => [ [ 'date' => '2026-06-12', 'sessions' => 45.0, 'users' => 30.0, 'page_views' => 180.0 ], ... ],
]
```

## Related

- [`GaOverviewFetcher`](API-Reference-GA-Overview-Fetcher)
- [GA Overview component](Components-GA-Overview)
- [HTTP Endpoints](HTTP-Endpoints)
