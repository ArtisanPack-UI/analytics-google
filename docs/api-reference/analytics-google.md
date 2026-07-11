---
title: AnalyticsGoogle
---

# `AnalyticsGoogle`

`ArtisanPackUI\AnalyticsGoogle\AnalyticsGoogle` is the aggregator class the facade and helper point at. It exposes the three service singletons the rest of the package sits on top of.

## Constructor

```php
public function __construct(
    protected Gtag $gtag,
    protected Ga4Provider $provider,
    protected ?Ga4DataClient $dataClient = null,
)
```

Instantiated once by the service provider. `$dataClient` is `null` when the base [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) package is missing.

## Methods

### `gtag(): Gtag`

The client-side gtag.js snippet renderer. Always available.

```php
AnalyticsGoogle::gtag()->render();       // '<script async …>…</script>' or ''
AnalyticsGoogle::gtag()->isConfigured(); // bool
AnalyticsGoogle::gtag()->measurementId();// ?string
```

### `provider(): Ga4Provider`

The provider descriptor for the analytics parent. Always available.

```php
AnalyticsGoogle::provider()->getName();       // 'google-ga4' by default
AnalyticsGoogle::provider()->isEnabled();     // bool
AnalyticsGoogle::provider()->trackerScript(); // string
AnalyticsGoogle::provider()->getConfig();     // array
```

### `dataClient(): ?Ga4DataClient`

The GA4 Data API client. **Returns `null` when the base package is missing** so callers can feature-detect without catching an exception.

```php
$client = AnalyticsGoogle::dataClient();

if ( null !== $client ) {
    $response = $client->runReport( $request, $connection );
}
```

Internally checks `hasReporting()` so a partial install (base loadable but service binding missing) also returns `null`.

### `hasReporting(): bool`

Whether the reporting side is usable in the current environment.

```php
if ( AnalyticsGoogle::hasReporting() ) {
    // Show reporting UI.
}
```

Equivalent to `null !== $this->dataClient && BaseInstalled::check()`.

## The facade

`ArtisanPackUI\AnalyticsGoogle\Facades\AnalyticsGoogle` extends `Illuminate\Support\Facades\Facade` and returns `'analytics-google'` from `getFacadeAccessor()`. That resolves to a singleton binding of `AnalyticsGoogle`. Every facade call goes through this instance.

## The helper

```php
function analyticsGoogle(): AnalyticsGoogle
{
    return app( 'analytics-google' );
}
```

Same singleton as the facade. Pick whichever style your codebase prefers.

## `GoogleConnectionResolver` (related class)

`ArtisanPackUI\AnalyticsGoogle\Support\GoogleConnectionResolver` is the single source of truth for the `GoogleConnection` the reporting Livewire components and HTTP controllers use.

```php
public function forUser( ?Authenticatable $user ): ?GoogleConnection
```

Returns the most recently updated *connected* account for the user, or `null` when the user is unauthenticated, the base is missing, or the user has no active connection.

Rebind in your app's service provider for multi-tenant or multi-property setups:

```php
$this->app->bind( \ArtisanPackUI\AnalyticsGoogle\Support\GoogleConnectionResolver::class, TenantAwareResolver::class );
```

Both the Livewire components and the HTTP controllers resolve through this class, so an override affects every reporting surface consistently.

## Related

- [[API Reference/GA4 Data Client|`Ga4DataClient`]]
- [[API Reference/GA4 Provider|`Ga4Provider`]]
- [[API Reference/Helpers|Helpers and the `Gtag` service]]
