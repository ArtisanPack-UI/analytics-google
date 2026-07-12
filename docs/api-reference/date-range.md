---
title: DateRange
---

# `DateRange`

`ArtisanPackUI\AnalyticsGoogle\Reporting\DateRange` — immutable, readonly `final` start/end date pair in the format the GA4 Data API expects.

Full narrative usage: [Date Ranges](Server-Side-Reporting-Date-Ranges). This page is the mechanical reference.

## Constants

- **`MAX_DAYS = 730`** — hard upper bound for `lastDays()` clamping and the components' `days` clamp.

## Constructor

```php
public function __construct(
    public readonly string $startDate,
    public readonly string $endDate,
)
```

Guards both values at construction. Throws `InvalidArgumentException` on empty strings or values that are neither a GA4 relative alias (`today`, `yesterday`, `NdaysAgo`) nor a Carbon-parseable date.

## Factories

### `lastDays( int $days ): self`

Build `[($days - 1)daysAgo, today]`.

- Throws `InvalidArgumentException` when `$days < 1`.
- Clamps `$days` to `MAX_DAYS` (730) silently on the high end.

### `between( DateTimeInterface|string $start, DateTimeInterface|string $end ): self`

Build a range from two dates. `DateTimeInterface` inputs get formatted with `Y-m-d`; strings pass through so relative aliases work too.

## Methods

### `toArray(): array{startDate: string, endDate: string}`

Serialize to the shape the `runReport` `dateRanges` array expects.

## Related

- [Date Ranges](Server-Side-Reporting-Date-Ranges)
- [`ReportRequest`](API-Reference-Report-Request)
