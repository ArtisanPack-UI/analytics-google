---
title: Report Request and Response
---

# `ReportRequest` and `ReportResponse`

Two value objects wrap the input and output of a single `runReport` call.

## `ReportRequest`

`ArtisanPackUI\AnalyticsGoogle\Reporting\ReportRequest` is an immutable, readonly description of a query. It maps 1:1 onto the GA4 Data API's `runReport` POST body via `toApiPayload()`.

### Construction

Use `ReportRequest::make()` for the common single-date-range case:

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

```php
use ArtisanPackUI\AnalyticsGoogle\Reporting\DateRange;
use ArtisanPackUI\AnalyticsGoogle\Reporting\ReportRequest;

$request = ReportRequest::make(
    range: DateRange::lastDays( 30 ),
    metrics: [ 'sessions', 'totalUsers' ],
    dimensions: [ 'date', 'country' ],
    orderBys: [ [ 'metric' => 'sessions', 'desc' => true ] ],
    limit: 100,
    offset: 0,
);
```

For multi-range queries (typically for period-over-period comparisons), use the constructor directly:

```php
new ReportRequest(
    dateRanges: [
        DateRange::lastDays( 7 ),
        DateRange::between( '2026-01-01', '2026-01-07' ),
    ],
    metrics: [ 'sessions' ],
);
```

### Fields

- **`dateRanges`** (`list<DateRange>`) — one or more ranges. GA4 returns rows for each range, tagged with a `dateRange` dimension automatically.
- **`metrics`** (`list<string>`) — GA4 metric API names (e.g. `sessions`, `totalUsers`, `screenPageViews`, `eventCount`).
- **`dimensions`** (`list<string>`) — GA4 dimension API names (e.g. `date`, `country`, `pagePath`, `eventName`).
- **`orderBys`** — a list of `[ 'metric' => string, 'desc' => bool ]` or `[ 'dimension' => string, 'desc' => bool ]`. Entries missing both keys are silently dropped when serializing.
- **`limit`** — max rows returned. GA4's own hard cap is `100,000`.
- **`offset`** — row offset for pagination.

### `toApiPayload()`

Returns the JSON-serializable POST body. Empty optional fields (`dimensions`, `orderBys`, `limit`, `offset`) are omitted so requests stay minimal.

## `ReportResponse`

`ArtisanPackUI\AnalyticsGoogle\Reporting\ReportResponse` wraps the raw JSON body returned by the API and exposes convenience accessors.

### Construction

Constructed by `Ga4DataClient` from the parsed JSON body — you rarely instantiate directly. Field `raw` is a `readonly array<string, mixed>` you can walk yourself if the accessors below are not enough.

### `dimensionHeaders(): list<string>`

Ordered list of dimension names as they appear in each row.

### `metricHeaders(): list<string>`

Ordered list of metric names as they appear in each row.

### `rows(): list<array<string, string>>`

Iterate the response rows as `[headerName => value]` associative arrays. Dimension headers and metric headers merge into one flat map per row.

```php
foreach ( $response->rows() as $row ) {
    $row['date'];      // dimension
    $row['sessions'];  // metric, as a string — always cast for arithmetic
}
```

Rows with missing header positions collapse to empty strings rather than throwing — the shape is stable even if GA4 returns partial data.

### `totalFor( string $metric ): float`

Sum a single metric across every row. Handy for pulling top-level totals out of a dimensioned report without walking `rows()`:

```php
$totalSessions = $response->totalFor( 'sessions' );
```

Non-numeric values are skipped. Missing metric name returns `0.0`.

## Related

- [`Ga4DataClient`](Server-Side-Reporting-GA4-Data-Client)
- [`DateRange`](Server-Side-Reporting-Date-Ranges)
- [API Reference — `ReportRequest`](API-Reference-Report-Request)
- [API Reference — `ReportResponse`](API-Reference-Report-Response)
