---
title: ReportResponse
---

# `ReportResponse`

`ArtisanPackUI\AnalyticsGoogle\Reporting\ReportResponse` — immutable, readonly `final` wrapper over the raw JSON body returned by the GA4 Data API.

Full narrative usage: [[Server-Side Reporting/Report Request|Report Request and Response]]. This page is the mechanical reference.

## Constructor

```php
public function __construct(
    public readonly array $raw,   // array<string, mixed>
)
```

Constructed by `Ga4DataClient` from the parsed JSON body. The `raw` field is publicly accessible for callers that need to walk fields the accessors below do not cover.

## Methods

### `dimensionHeaders(): list<string>`

Ordered dimension names as they appear in each row. Empty when the raw payload has no `dimensionHeaders` key.

### `metricHeaders(): list<string>`

Ordered metric names as they appear in each row. Empty when the raw payload has no `metricHeaders` key.

### `rows(): list<array<string, string>>`

Iterate rows as `[headerName => value]` associative arrays. Dimension and metric headers merge into one flat map per row. Missing positions become empty strings rather than throwing.

Example row:

```php
[
    'date'       => '20260612',
    'country'    => 'US',
    'sessions'   => '42',
    'totalUsers' => '30',
]
```

### `totalFor( string $metric ): float`

Sum a single metric across every row. Non-numeric values are skipped; a metric absent from the response returns `0.0`.

## Related

- [[API Reference/Report Request|`ReportRequest`]]
- [[API Reference/GA4 Data Client|`Ga4DataClient`]]
