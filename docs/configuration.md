---
title: Configuration
---

# Configuration

This page collects every configurable knob in `config/analytics-google.php` organized by the task you're trying to accomplish. For a straight top-to-bottom mirror of the published config file, see [Configuration reference](Installation-Configuration). For env-var-specific detail, see [Environment variables](Installation-Environment-Variables).

## Publishing

```bash
php artisan vendor:publish --tag=analytics-google-config
```

Copies `config/analytics-google.php` into your app. The published file is the source of truth.

## Task-oriented reference

### I want to turn tracking on / off

- **Turn it off**: unset `GA4_MEASUREMENT_ID`, or set `GA4_TRACKING_ENABLED=false`.
- **Turn it on**: set both.

The `@ga4Snippet` directive and React/Vue components respect this switch. When off, they emit nothing at all.

### I want to forward the analytics parent's page views and events to GA4 from the server

Added in 1.1.0. Set `GA4_API_SECRET` alongside `GA4_MEASUREMENT_ID` — forwarding stays off until both are present.

```php
'tracking' => [
    'api_secret'  => env( 'GA4_API_SECRET' ),
    'server_side' => env( 'GA4_SERVER_SIDE_TRACKING', true ),
],
```

Turn it off without unsetting the secret via `GA4_SERVER_SIDE_TRACKING=false`. Anything both this and the client-side snippet observe is counted twice — see [Analytics Parent Integration](Analytics-Parent-Integration) for that and the attribution and session-boundary caveats.

### I want to debug a Measurement Protocol payload GA4 seems to be dropping

Set `GA4_MEASUREMENT_PROTOCOL_DEBUG=true`. Hits go to GA4's validation endpoint instead of the live one, and the `validationMessages` it returns are logged. The live endpoint answers `2xx` and discards non-conforming events silently, so this is the only way to see what it objects to. If GA4's hostname or page-path reporting is empty, set `GA4_PAGE_LOCATION_BASE` — it defaults to `app.url` and builds the absolute `page_location` GA4 expects.

### I want to change the GA4 property server-side reporting targets

Set `GA4_PROPERTY_ID`. Or set `analytics-google.reporting.property_id` directly. Overridable per call via `Ga4DataClient::runReport( $request, $connection, $propertyId )`.

### I want to disable the consent guard

```php
'tracking' => [
    'respect_consent' => false,
],
```

See [Consent Integration](Client-Side-Tracking-Consent-Integration) for the full picture.

### I want to customize the `gtag('config', …)` options

```php
'tracking' => [
    'config' => [
        'anonymize_ip'  => true,
        'send_page_view' => false,
    ],
],
```

Keep values to primitives — the emitted `<script>` uses JSON-escape flags to prevent breakout, but complex object graphs are still noisy.

### I want to change the cache TTL for reports

```php
'reporting' => [
    'cache_ttl' => 300,
],
```

Set to `0` to disable. See [Caching](Server-Side-Reporting-Caching) for the key shape and store-selection details.

### I want to change the request timeout for the Data API

```php
'reporting' => [
    'timeout' => 30,
],
```

Trips [`ReportingException::transportFailure()`](API-Reference-Exceptions) on timeout.

### I want to disable the HTTP endpoints (headless / API app)

```php
'routes' => [
    'enabled' => false,
],
```

Livewire components still work — they hit the fetchers directly and never touch the routes.

### I want to move the endpoints under a different prefix

```php
'routes' => [
    'prefix' => 'admin/analytics',
],
```

The React and Vue components accept a `baseUrl` prop so you can point them at the new prefix without editing the shipped source.

### I want to change route middleware

```php
'routes' => [
    'middleware' => [ 'web', 'auth', 'can:view-analytics' ],
],
```

Anything in the Laravel middleware pipeline works. The default `[ 'web', 'auth' ]` mounts the routes under the session guard needed for the controller's `$request->user()` calls.

### I want to rename the provider registered with the analytics parent

```php
'provider_name' => 'google-analytics',
```

Then add `google-analytics` to `artisanpack.analytics.active_providers` on the parent. See [Analytics Parent Integration](Analytics-Parent-Integration).

### I want to change the OAuth scopes contributed to the base

```php
'scopes' => [
    'https://www.googleapis.com/auth/analytics.readonly',
    'https://www.googleapis.com/auth/analytics.edit', // for example
],
```

Contributed to the base's `ScopeRegistry` via `ap.google.scopes`. Also add the new scope to the OAuth consent screen — see [Google Cloud setup](Installation-Google-Cloud-Setup).

## Related

- [Configuration reference](Installation-Configuration) — top-to-bottom mirror of the config file.
- [Environment variables](Installation-Environment-Variables) — every env var.
- [HTTP Endpoints](HTTP-Endpoints) — full route details.
