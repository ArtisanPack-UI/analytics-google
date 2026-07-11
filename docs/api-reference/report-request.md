---
title: ReportRequest
---

# `ReportRequest`

`ArtisanPackUI\AnalyticsGoogle\Reporting\ReportRequest` — immutable, readonly `final` value object describing a single GA4 `runReport` query.

Full narrative usage: [[Server-Side Reporting/Report Request|Report Request and Response]]. This page is the mechanical reference.

## Constructor

```php
public function __construct(
    public readonly array $dateRanges,   // list<DateRange>
    public readonly array $metrics,      // list<string>
    public readonly array $dimensions = [],
    public readonly array $orderBys = [],
    public readonly ?int $limit = null,
    public readonly ?int $offset = null,
)
```

### Field shapes

- `dateRanges` — `list<DateRange>`. Non-empty.
- `metrics` — `list<string>`. GA4 metric API names.
- `dimensions` — `list<string>`. GA4 dimension API names.
- `orderBys` — `list<array{metric?: string, dimension?: string, desc?: bool}>`. Entries missing both `metric` and `dimension` are dropped by `toApiPayload()`.
- `limit` — GA4 hard cap is `100,000`.
- `offset` — pagination offset.

## Factories

### `make()`

```php
public static function make(
    DateRange $range,
    array $metrics,
    array $dimensions = [],
    array $orderBys = [],
    ?int $limit = null,
    ?int $offset = null,
): self
```

Single-range convenience factory. Wraps `$range` in a one-element `dateRanges` list.

## Methods

### `toApiPayload(): array<string, mixed>`

Serialize to the shape the GA4 Data API's `runReport` endpoint expects. Empty `dimensions`, `orderBys`, and `null` `limit`/`offset` are omitted from the output.

## Related

- [[API Reference/Report Response|`ReportResponse`]]
- [[API Reference/Date Range|`DateRange`]]
- [[API Reference/GA4 Data Client|`Ga4DataClient`]]
