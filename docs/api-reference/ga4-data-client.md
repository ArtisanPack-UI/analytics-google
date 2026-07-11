---
title: Ga4DataClient
---

# `Ga4DataClient`

`ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient` — the low-level GA4 Data API client. Executes `runReport` calls with an OAuth token from the base package's `TokenManager`, caches responses, and normalizes errors into typed exceptions.

Full narrative usage: [[Server-Side Reporting/GA4 Data Client|GA4 Data Client]]. This page is the mechanical reference.

## Constructor

```php
public function __construct(
    protected ConfigRepository $config,
    protected HttpFactory $http,
    protected ?TokenManager $tokens = null,
    protected ?CacheRepository $cache = null,
)
```

Resolved from the container as a singleton — you rarely instantiate directly.

## Methods

### `runReport()`

```php
public function runReport(
    ReportRequest $request,
    GoogleConnection $connection,
    ?string $propertyId = null,
): ReportResponse
```

Executes a `runReport` request against `POST /properties/{propertyId}:runReport`.

- `$propertyId` overrides `analytics-google.reporting.property_id` when non-empty.
- Reads from cache when `analytics-google.reporting.cache_ttl > 0` and a cached response exists for the exact `(connection, property, payload)` tuple.
- Writes successful responses to cache with the same TTL.

**Throws** — in order of check:

- [[API Reference/Exceptions|`BaseNotInstalledException`]] via `ensureBaseInstalled()` when `BaseInstalled::check()` is false.
- [[API Reference/Exceptions|`ReportingException::missingConfiguration('analytics-google.reporting.property_id')`]] when no property is available.
- [[API Reference/Exceptions|`BaseNotInstalledException::forReporting()`]] when `$tokens` is null (partial install).
- [[API Reference/Exceptions|`ReportingException::authenticationFailed($e)`]] when `TokenManager::getValidAccessToken()` throws `TokenRefreshException`.
- [[API Reference/Exceptions|`ReportingException::transportFailure($e)`]] on `ConnectionException` from the HTTP client.
- [[API Reference/Exceptions|`ReportingException::apiError($status, $body)`]] on non-2xx HTTP status.

### `isAvailable()`

```php
public function isAvailable(): bool
```

Returns `true` when `BaseInstalled::check()` is `true` **and** `$tokens` is non-null.

Use this to hide reporting UI cleanly without catching exceptions.

## Protected helpers

### `cacheKey( GoogleConnection $connection, string $property, array $payload ): string`

Builds a `analytics-google:runReport:<sha256>` key. The hash covers `property | connectionId | json_encode($payload)`. Connection id falls back to `google_user_id`, then to `'anon'`.

### `ensureBaseInstalled(): void`

Delegates to `BaseInstalled::check()`. Kept as a private helper so subclasses can hook the pre-flight without duplicating the exception constructor.

## Cache semantics

See [[Server-Side Reporting/Caching|Caching]].

## Related

- [[Server-Side Reporting/GA4 Data Client|Narrative usage guide]]
- [[API Reference/Report Request|`ReportRequest`]]
- [[API Reference/Report Response|`ReportResponse`]]
- [[API Reference/Exceptions|Exceptions]]
