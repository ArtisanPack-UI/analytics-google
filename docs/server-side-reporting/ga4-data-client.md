---
title: GA4 Data Client
---

# `Ga4DataClient`

`ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient` is the low-level client that hits the GA4 Data API's `runReport` endpoint. Every server-side reporting call flows through it — fetchers, Livewire components, HTTP controllers — so caching, auth, and error handling are consistent across surfaces.

## Resolving the client

Always resolve from the container so the injected `TokenManager`, HTTP factory, and cache repository are wired for you:

```php
$client = app( \ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient::class );
```

## `runReport()`

```php
public function runReport(
    ReportRequest $request,
    GoogleConnection $connection,
    ?string $propertyId = null,
): ReportResponse
```

Executes a single `runReport` call against the GA4 Data API.

- **`$request`** — a [[Server-Side Reporting/Report Request|`ReportRequest`]] describing the metrics, dimensions, date range, ordering, limit, and offset.
- **`$connection`** — the [[../api-reference/ga4-provider|`GoogleConnection`]] whose access token to use. In practice, resolved via the [[API Reference/Analytics Google|`GoogleConnectionResolver`]].
- **`$propertyId`** — optional override. Defaults to `analytics-google.reporting.property_id`.

Returns a [[Server-Side Reporting/Report Request|`ReportResponse`]].

### Example

```php
use ArtisanPackUI\AnalyticsGoogle\Reporting\DateRange;
use ArtisanPackUI\AnalyticsGoogle\Reporting\ReportRequest;

$response = $client->runReport(
    ReportRequest::make(
        range: DateRange::lastDays( 7 ),
        metrics: [ 'sessions', 'totalUsers', 'screenPageViews' ],
        dimensions: [ 'date', 'country' ],
        orderBys: [ [ 'metric' => 'sessions', 'desc' => true ] ],
        limit: 100,
    ),
    $connection,
);

foreach ( $response->rows() as $row ) {
    // $row = ['date' => '20260101', 'country' => 'US', 'sessions' => '42', 'totalUsers' => '30', ...]
}
```

### Errors

- [[API Reference/Exceptions|`BaseNotInstalledException`]] — the base package is missing. Detected via `BaseInstalled::check()` before any HTTP work.
- [[API Reference/Exceptions|`ReportingException::missingConfiguration()`]] — `property_id` is unset and no `$propertyId` override was passed.
- [[API Reference/Exceptions|`ReportingException::authenticationFailed()`]] — the base's `TokenManager` threw `TokenRefreshException`. The user needs to reconnect.
- [[API Reference/Exceptions|`ReportingException::transportFailure()`]] — the request could not be delivered (DNS, connection refused, timeout).
- [[API Reference/Exceptions|`ReportingException::apiError()`]] — the API returned a non-2xx status. Body is preserved in the message.

## `isAvailable()`

```php
public function isAvailable(): bool
```

Returns `true` when both the base package is loadable and a `TokenManager` was injected. Callers should use this to hide reporting UI cleanly rather than catching `BaseNotInstalledException`:

```php
if ( $client->isAvailable() ) {
    // render the "run report" button
}
```

## Caching

`Ga4DataClient` writes successful responses to Laravel's default cache store when `analytics-google.reporting.cache_ttl` is greater than zero. Cache keys hash `(connection identity, property, request payload)` so users cannot see each other's cached rows. Full details: [[Server-Side Reporting/Caching|Caching]].

## Timeouts

`analytics-google.reporting.timeout` (default `30`) is the per-request timeout in seconds. Timeouts and connection failures surface as `ReportingException::transportFailure()` so callers can distinguish "GA4 said no" from "we could not reach GA4."

## Constructor

```php
public function __construct(
    ConfigRepository $config,
    HttpFactory $http,
    ?TokenManager $tokens = null,
    ?CacheRepository $cache = null,
)
```

The service provider constructs it once and registers it as a singleton:

```php
$this->app->singleton( Ga4DataClient::class, fn ( Application $app ) => new Ga4DataClient(
    $app[ 'config' ],
    $app->make( HttpFactory::class ),
    BaseInstalled::check() ? $app->make( TokenManager::class ) : null,
    $app->make( 'cache.store' ),
) );
```

Rebind if you want a different HTTP factory (for example, one with fake responses) or a scoped cache store.

## Related

- [[Server-Side Reporting/Report Request|`ReportRequest` and `ReportResponse`]]
- [[Server-Side Reporting/Date Ranges|`DateRange`]]
- [[Server-Side Reporting/Caching|Caching]]
- [[Server-Side Reporting/Graceful Degradation|Graceful degradation]]
- [[API Reference/GA4 Data Client|API Reference — `Ga4DataClient`]]
