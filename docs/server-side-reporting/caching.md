---
title: Caching
---

# Caching

`Ga4DataClient` caches successful `runReport` responses when `analytics-google.reporting.cache_ttl` is greater than zero. This page documents the caching contract in full.

## The setting

```php
'reporting' => [
    'cache_ttl' => 300,
],
```

- **`> 0`** — cache responses for that many seconds.
- **`0`** — disable caching entirely. Every call hits the API.

The default is `300` (five minutes) — a good balance for a dashboard that refreshes on a `wire:poll` while keeping the Data API quota comfortably under budget.

## Cache store

The client uses Laravel's default cache store — resolved via the container binding `cache.store`. Rebind if you want a scoped store:

```php
$this->app->when( \ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient::class )
    ->needs( \Illuminate\Contracts\Cache\Repository::class )
    ->give( fn ( $app ) => $app[ 'cache' ]->store( 'redis' ) );
```

## Cache keys

Cache keys have the form `analytics-google:runReport:<sha256>` where the SHA-256 hash covers `(property ID, connection identity, JSON-encoded request payload)`.

- **Connection identity** is the `GoogleConnection` primary key, falling back to `google_user_id`, falling back to the literal string `anon`. Two different users never see each other's cached rows.
- **Property ID** is included so a per-call `$propertyId` override lives in its own cache slot.
- **Request payload** is the exact JSON body sent to GA4, so a `dimensions: ['date']` query does not collide with a `dimensions: ['country']` one.

## What gets cached

Only successful `2xx` responses. Every error path — auth failure, transport failure, non-2xx status — bypasses the cache so a transient failure does not poison later requests.

## Invalidation

There is no explicit invalidation API. Options:

- Wait for the TTL to expire.
- Clear the cache store: `php artisan cache:clear` (nuclear).
- Rebind `Ga4DataClient` with `cache_ttl = 0` in the environment you want to bust for.
- Tag your custom cache store with `analytics-google` and flush the tag from a job.

If you need per-key invalidation, wrap `runReport` in an app-level service that keeps its own key registry.

## Testing

Force-disable in tests so mocked HTTP responses actually get called:

```php
// tests/Pest.php or per-test setUp
config()->set( 'analytics-google.reporting.cache_ttl', 0 );
```

Or use an array cache store (`config()->set('cache.default', 'array')`) so entries evaporate at end-of-test without touching the real store.

## Interaction with the components

The Livewire, React, and Vue reporting components hit `Ga4DataClient` via `GaOverviewFetcher` / `GaTopContentFetcher`, so the cache benefit is transitive — a `wire:poll` on the overview or a React `refetch` will not re-query the Data API within the TTL window.

## Related

- [[Server-Side Reporting/GA4 Data Client|`Ga4DataClient`]]
- [[Installation/Configuration|Configuration reference]] — the `cache_ttl` key in context.
- [[Testing]] — patterns for testing caching behavior.
