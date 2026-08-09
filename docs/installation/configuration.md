---
title: Configuration Reference
---

# Configuration Reference

Complete reference for `config/analytics-google.php`. Publish with:

```bash
php artisan vendor:publish --tag=analytics-google-config
```

The published file is the source of truth — this page mirrors it and explains each key.

## Sections

- [Client-side tracking](#client-side-tracking)
- [Server-side forwarding](#server-side-forwarding)
- [Server-side reporting](#server-side-reporting)
- [Provider registration](#provider-registration)
- [OAuth scopes](#oauth-scopes)
- [HTTP routes](#http-routes)

## Client-side tracking

```php
'tracking' => [
    'measurement_id'  => env( 'GA4_MEASUREMENT_ID' ),
    'enabled'         => env( 'GA4_TRACKING_ENABLED', true ),
    'config'          => [
        'anonymize_ip' => true,
    ],
    'respect_consent' => true,
],
```

- **`measurement_id`** — The GA4 measurement ID (`G-XXXXXXX`). Leave empty to disable the `@ga4Snippet` directive entirely; it emits nothing when this is missing so a shared layout is safe across environments.
- **`enabled`** — Master switch for the tracker. When `false`, `Gtag::render()` returns an empty string regardless of measurement ID. Handy for staging or CI.
- **`config`** — Extra `gtag('config', ...)` options serialized to JSON in the emitted snippet. Keep values to primitives (strings, bools, numbers) — the encoder uses `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` so nothing can break out of the surrounding `<script>` tag, but complex objects still make the resulting snippet noisy.
- **`respect_consent`** — When `true` (default), the emitted snippet sets `gtag('consent', 'default', { analytics_storage: 'denied' })` unless `window.__apAnalyticsConsent.analytics === true`. Set to `false` to fire tracking unconditionally on every page load.

Full details on the emitted markup and the consent flag: [Client-Side Tracking](Client-Side-Tracking) and [Consent Integration](Client-Side-Tracking-Consent-Integration).

## Server-side forwarding

Added in 1.1.0. These keys live under the same `tracking` section and control the Measurement Protocol client that forwards the analytics parent's page views and events to GA4 from the server.

```php
'tracking' => [
    'api_secret'         => env( 'GA4_API_SECRET' ),
    'server_side'        => env( 'GA4_SERVER_SIDE_TRACKING', true ),
    'page_location_base' => env( 'GA4_PAGE_LOCATION_BASE' ),
    'timeout'            => 3,
    'debug'              => env( 'GA4_MEASUREMENT_PROTOCOL_DEBUG', false ),
],
```

- **`api_secret`** — Measurement Protocol API secret, created in GA4 under Admin → Data Streams → Measurement Protocol API secrets. Forwarding stays off until this and `measurement_id` are both set.
- **`server_side`** — Switch for forwarding, independent of the secret. Set to `false` to keep the secret configured while sending nothing.
- **`page_location_base`** — Base URL used to build the absolute `page_location` GA4 expects. Defaults to `app.url`. A bare path leaves GA4's hostname and page-path reporting empty while the hit still succeeds.
- **`timeout`** — Request timeout in seconds. Deliberately short: forwarding runs on the ingest request path with a visitor waiting on a beacon response, so a GA4 outage degrades to lost hits rather than a slow host application.
- **`debug`** — Targets GA4's validation endpoint and logs the `validationMessages` it returns. The live endpoint accepts malformed payloads silently, so this is the only way to see what GA4 would reject.

Client-side `gtag.js` and server-side forwarding both observing the same event counts it twice. Read [Analytics Parent Integration](Analytics-Parent-Integration) for the double-counting, attribution, and session-boundary caveats before enabling this.

## Server-side reporting

```php
'reporting' => [
    'property_id' => env( 'GA4_PROPERTY_ID' ),
    'api_base'    => 'https://analyticsdata.googleapis.com/v1beta',
    'timeout'     => 30,
    'cache_ttl'   => 300,
],
```

- **`property_id`** — The GA4 numeric property ID Data API queries target. Overridable per call via the third argument to `Ga4DataClient::runReport()`. Missing when a query runs → [`ReportingException::missingConfiguration()`](API-Reference-Exceptions).
- **`api_base`** — Base URL for the GA4 Data API. Rarely needs to change; kept configurable so tests can point at a fake server.
- **`timeout`** — Request timeout for a single `runReport` call, in seconds. Trips a [`ReportingException::transportFailure()`](API-Reference-Exceptions) on `ConnectionException`.
- **`cache_ttl`** — Cache TTL in seconds. Set to `0` to disable caching. Cache keys are hashed from `(connection identity, property ID, request payload)` so distinct users cannot see each other's cached rows.

## Provider registration

```php
'provider_name' => 'google-ga4',
```

- **`provider_name`** — The name this package registers under with the [`artisanpack-ui/analytics`](https://github.com/ArtisanPack-UI/analytics) parent's `extend()` API. Add this name to `artisanpack.analytics.active_providers` on the parent to route tracking through its consent gate. See [Analytics Parent Integration](Analytics-Parent-Integration).

## OAuth scopes

```php
'scopes' => [
    'https://www.googleapis.com/auth/analytics.readonly',
],
```

- **`scopes`** — The Google OAuth scopes this package needs. Contributed to the base package's `ScopeRegistry` via the `ap.google.scopes` filter hook when both are installed. Only affects the base's consent screen — this key does nothing when the base is missing.

## HTTP routes

```php
'routes' => [
    'enabled'    => true,
    'prefix'     => 'analytics-google',
    'middleware' => [ 'web', 'auth' ],
],
```

- **`enabled`** — When `false`, the two reporting routes are not registered. Correct choice for headless / API-only apps. The Livewire components still work — they call the fetchers directly.
- **`prefix`** — Prefix mounted under `/`. Full route paths become `<prefix>/overview` and `<prefix>/top-content`.
- **`middleware`** — Middleware stack applied to the two routes. `web` mounts them under the session guard the auth middleware needs; `auth` requires an authenticated user so the controllers can resolve a `GoogleConnection`.

Full endpoint reference: [HTTP Endpoints](HTTP-Endpoints).

## Related pages

- [Environment variables](Installation-Environment-Variables) — every env var this package reads.
- [Configuration](Configuration) — the same content organized by task rather than by section.
- [HTTP Endpoints](HTTP-Endpoints) — response shapes and error codes for the two reporting routes.
