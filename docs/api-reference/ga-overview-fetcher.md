---
title: GaOverviewFetcher
---

# `GaOverviewFetcher`

`ArtisanPackUI\AnalyticsGoogle\Reporting\GaOverviewFetcher` — pulls the four headline metrics and a daily trend chart for the GA overview surface. Shared by the Livewire component and the HTTP endpoint so all three frameworks see identical numbers.

## Constructor

```php
public function __construct(
    protected Ga4DataClient $client,
)
```

## Methods

### `fetch( GoogleConnection $connection, DateRange $range, ?string $propertyId = null ): GaOverviewData`

Runs two `runReport` calls behind the scenes:

1. **Totals** — metrics `sessions`, `totalUsers`, `screenPageViews`, `averageSessionDuration`. No dimensions.
2. **Trend** — metrics `sessions`, `totalUsers`, `screenPageViews`. Dimension: `date`.

Returns a [`GaOverviewData`](API-Reference-GA-Overview-Data) with totals normalized to `{sessions, users, page_views, avg_engagement_seconds}` (all floats) and the trend sorted by date ascending with `YYYY-MM-DD` normalization from GA4's `YYYYMMDD` format.

Impossible calendar dates (e.g. `20260230`) are preserved in the trend row unchanged rather than reshaped into invalid output.

**Throws** — every exception `Ga4DataClient::runReport()` throws. See [`Ga4DataClient`](API-Reference-GA4-Data-Client).

## Metric constants (private)

- `METRIC_SESSIONS = 'sessions'`
- `METRIC_USERS = 'totalUsers'`
- `METRIC_PAGE_VIEWS = 'screenPageViews'`
- `METRIC_AVG_ENGAGE = 'averageSessionDuration'`

These are private constants of the class; you cannot swap them without subclassing.

## Example

```php
use ArtisanPackUI\AnalyticsGoogle\Reporting\DateRange;
use ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient;
use ArtisanPackUI\AnalyticsGoogle\Reporting\GaOverviewFetcher;

$client   = app( Ga4DataClient::class );
$fetcher  = new GaOverviewFetcher( $client );
$overview = $fetcher->fetch( $connection, DateRange::lastDays( 30 ) );

$overview->totals['sessions'];
$overview->trend;
```

## Related

- [`GaOverviewData`](API-Reference-GA-Overview-Data)
- [GA Overview component](Components-GA-Overview)
