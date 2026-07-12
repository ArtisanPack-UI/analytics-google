---
title: GaTopContentData
---

# `GaTopContentData`

`ArtisanPackUI\AnalyticsGoogle\Reporting\GaTopContentData` — immutable, readonly `final` DTO backing the top-content surface.

## Constructor

```php
public function __construct(
    public readonly array $topPages,    // list<{path, title, views, users}>
    public readonly array $topEvents,   // list<{event, count, users}>
    public readonly DateRange $range,
)
```

- **`topPages`** — `list<array{path: string, title: string, views: float, users: float}>` sorted by `views` descending.
- **`topEvents`** — `list<array{event: string, count: float, users: float}>` sorted by `count` descending.
- **`range`** — the same `DateRange` passed to `GaTopContentFetcher::fetch()`.

## Methods

### `toArray(): array<string, mixed>`

JSON shape returned by the `/analytics-google/top-content` endpoint:

```php
[
    'range'      => [ 'startDate' => '29daysAgo', 'endDate' => 'today' ],
    'top_pages'  => [ [ 'path' => '/', 'title' => 'Home', 'views' => 1234.0, 'users' => 800.0 ], ... ],
    'top_events' => [ [ 'event' => 'page_view', 'count' => 4567.0, 'users' => 800.0 ], ... ],
]
```

## Related

- [`GaTopContentFetcher`](API-Reference-GA-Top-Content-Fetcher)
- [GA Top Content component](Components-GA-Top-Content)
- [HTTP Endpoints](HTTP-Endpoints)
