---
title: Server-Side Reporting
---

# Server-Side Reporting

Server-side reporting queries the [Google Analytics Data API](https://developers.google.com/analytics/devguides/reporting/data/v1) with an OAuth token supplied by the base [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) package. This side of the package requires the base — without it, every reporting call throws [`BaseNotInstalledException`](API-Reference-Exceptions).

## Building blocks

| Class | Role |
|---|---|
| [`Ga4DataClient`](Server-Side-Reporting-GA4-Data-Client) | Executes `runReport` calls against the configured property. |
| [`ReportRequest`](Server-Side-Reporting-Report-Request) | Immutable value object describing a query. |
| [`ReportResponse`](Server-Side-Reporting-Report-Request) | Parses the raw JSON body into `rows()` / `totalFor()`. |
| [`DateRange`](Server-Side-Reporting-Date-Ranges) | Immutable start/end date pair with `lastDays()` and `between()` factories. |
| [`GaOverviewFetcher`](API-Reference-GA-Overview-Fetcher) | Fetches sessions / users / page views / avg engagement + daily trend. |
| [`GaTopContentFetcher`](API-Reference-GA-Top-Content-Fetcher) | Fetches top pages by views and top events by count. |

The two fetchers back the Livewire components and the HTTP endpoints, so the Livewire, React, and Vue reporting surfaces all see identical numbers for the same input.

## Scope registration

When both this package and the base are installed, this package contributes the `analytics.readonly` scope to the base's `ScopeRegistry` via the `ap.google.scopes` filter hook:

```php
addFilter( 'ap.google.scopes', static function ( array $scopes ) use ( $config ): array {
    $ours = (array) $config->get( 'analytics-google.scopes', [] );

    return array_values( array_unique( array_merge( $scopes, array_map( 'strval', $ours ) ) ) );
} );
```

Users see one consent screen covering GA4 alongside every other Google service the app has enabled.

## A minimal query

```php
use ArtisanPackUI\AnalyticsGoogle\Reporting\DateRange;
use ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient;
use ArtisanPackUI\AnalyticsGoogle\Reporting\ReportRequest;

$client = app( Ga4DataClient::class );

$response = $client->runReport(
    ReportRequest::make(
        range: DateRange::lastDays( 30 ),
        metrics: [ 'sessions', 'totalUsers' ],
        dimensions: [ 'date' ],
    ),
    $googleConnection,
);

foreach ( $response->rows() as $row ) {
    echo $row['date'] . ': ' . $row['sessions'] . PHP_EOL;
}
```

## The two ready-to-use fetchers

If you don't want to hand-write metrics/dimensions lists, use `GaOverviewFetcher` and `GaTopContentFetcher`:

```php
use ArtisanPackUI\AnalyticsGoogle\Reporting\DateRange;
use ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient;
use ArtisanPackUI\AnalyticsGoogle\Reporting\GaOverviewFetcher;

$client   = app( Ga4DataClient::class );
$fetcher  = new GaOverviewFetcher( $client );
$overview = $fetcher->fetch( $googleConnection, DateRange::lastDays( 30 ) );

$overview->totals; // ['sessions' => 1234, 'users' => 800, 'page_views' => 4567, 'avg_engagement_seconds' => 92.4]
$overview->trend;  // list<{date, sessions, users, page_views}>
```

The overview and top-content components render these shapes verbatim.

## Deeper topics

- [GA4 Data Client — raw `runReport` calls](Server-Side-Reporting-GA4-Data-Client)
- [Report request and response DTOs](Server-Side-Reporting-Report-Request)
- [Date ranges](Server-Side-Reporting-Date-Ranges)
- [Caching](Server-Side-Reporting-Caching)
- [Graceful degradation when the base is missing](Server-Side-Reporting-Graceful-Degradation)
