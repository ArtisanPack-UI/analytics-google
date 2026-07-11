---
title: Date Ranges
---

# `DateRange`

`ArtisanPackUI\AnalyticsGoogle\Reporting\DateRange` is an immutable value object wrapping a `startDate` / `endDate` pair in the format the GA4 Data API expects: `YYYY-MM-DD` or a GA4 alias like `today`, `yesterday`, or `NdaysAgo`.

## Factories

### `DateRange::lastDays()`

```php
public static function lastDays( int $days ): self
```

Build a range covering the last `N` days ending today. Uses GA4's `NdaysAgo` alias so no local clock skew is possible.

```php
DateRange::lastDays( 30 );
// startDate: "29daysAgo", endDate: "today"
```

Throws `InvalidArgumentException` when `$days < 1`. Clamps to `DateRange::MAX_DAYS` (`730`, roughly two years — the standard GA4 retention window) on the high end.

### `DateRange::between()`

```php
public static function between( DateTimeInterface|string $start, DateTimeInterface|string $end ): self
```

Build a range from explicit start and end dates. `DateTimeInterface` inputs get formatted with `Y-m-d`; strings pass through, so aliases work too.

```php
DateRange::between( now()->subMonth(), now() );
DateRange::between( '2026-01-01', '2026-01-31' );
DateRange::between( 'yesterday', 'today' );
```

### Direct construction

```php
new DateRange( startDate: '2026-01-01', endDate: '2026-01-31' );
new DateRange( startDate: '7daysAgo', endDate: 'yesterday' );
```

The constructor guards both values against the accepted formats and throws `InvalidArgumentException` at construction time on bad input — you get a stack trace at the caller, not deep inside a Data API response parser.

## Accepted formats

- **Calendar dates**: `YYYY-MM-DD`. Parsed via `Illuminate\Support\Carbon::parse()` so any Carbon-parseable string works, but stick to `YYYY-MM-DD` for portability.
- **GA4 relative aliases**: `today`, `yesterday`, or `NdaysAgo` where `N` is a positive integer. Case-insensitive.

Any other input throws `InvalidArgumentException`.

## `toArray()`

```php
public function toArray(): array
```

Serializes to the shape the `runReport` `dateRanges` array expects:

```php
DateRange::lastDays( 7 )->toArray();
// [ 'startDate' => '6daysAgo', 'endDate' => 'today' ]
```

## Constants

- **`DateRange::MAX_DAYS = 730`** — the upper bound `lastDays()` clamps to. Also used by the Livewire components and HTTP controllers to clamp caller-supplied `days` parameters so a user can't force multi-decade lookups on every `wire:poll`.

## Related

- [[Server-Side Reporting/Report Request|`ReportRequest` and `ReportResponse`]]
- [[Server-Side Reporting/GA4 Data Client|`Ga4DataClient`]]
- [[API Reference/Date Range|API Reference — `DateRange`]]
