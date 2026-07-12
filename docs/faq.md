---
title: FAQ
---

# FAQ

## Setup

### Do I need the base `artisanpack-ui/google` package?

Only for **server-side reporting**. Client-side tracking (`@ga4Snippet`, React/Vue `<Ga4Snippet />`) works standalone. Install the base once you need dashboards or programmatic Data API queries. See [Installation](Installation) for the full install matrix.

### Do I need `artisanpack-ui/analytics`?

No — this package works standalone or with just the base. Installing the analytics parent lets you route tracking through its consent gate and register `google-ga4` alongside other providers. See [Analytics Parent Integration](Analytics-Parent-Integration).

### What's the difference between measurement ID and property ID?

- **Measurement ID** (`G-XXXXXXX`) — for client-side tracking. Lives in `GA4_MEASUREMENT_ID` / `analytics-google.tracking.measurement_id`.
- **Property ID** (numeric, e.g. `123456789`) — for server-side reporting. Lives in `GA4_PROPERTY_ID` / `analytics-google.reporting.property_id`.

Mixing them up is the single most common config bug. See [Google Cloud setup](Installation-Google-Cloud-Setup) for how to find each.

## Tracking

### Why doesn't tracking fire in the browser?

Most likely causes, in order:

1. `GA4_MEASUREMENT_ID` is empty → the directive emits nothing. Check `dd( app( \ArtisanPackUI\AnalyticsGoogle\Tracking\Gtag::class )->isConfigured() )`.
2. `GA4_TRACKING_ENABLED=false` → tracking is master-off.
3. `tracking.respect_consent` is `true` (default) and `window.__apAnalyticsConsent.analytics !== true` → the snippet loaded but Google is honoring the `analytics_storage: denied` default. See [Consent Integration](Client-Side-Tracking-Consent-Integration).
4. An ad blocker is stripping the `gtag.js` script — check DevTools → Network for the `googletagmanager.com` request.

### Can I use this alongside another GA4 tracker?

Yes, but you'll double-count events. The safest path is to disable one — usually the other tracker, since this one composes with the analytics parent's consent gate. If you must run both, set different measurement IDs on each, or set `GA4_TRACKING_ENABLED=false` here and route tracking through your other tool.

### The snippet has `gtag('consent', 'default', {analytics_storage: 'denied'})` — is that a bug?

No — that's the intentional consent guard. Set `analytics-google.tracking.respect_consent` to `false` to bypass it, or write `window.__apAnalyticsConsent = { analytics: true }` after user consent (the analytics parent does this for you). See [Consent Integration](Client-Side-Tracking-Consent-Integration).

## Reporting

### Why do I get "Reconnect your Google account to continue viewing analytics."?

The OAuth token is unusable — either revoked in the user's Google account, or the refresh token itself has expired. The base package's `TokenManager` flips the connection to `disconnected` when this happens; the user should hit the base's `/google/auth/connect` again.

### Why do I get `503 Service Unavailable` or `429 Too Many Requests`?

You're hitting the GA4 Data API's quota. See [Google's quota documentation](https://developers.google.com/analytics/devguides/reporting/data/v1/quotas). Options:

- Increase `cache_ttl` so more requests hit the cache.
- Rate-limit `wire:poll` on the Livewire components.
- Apply for higher quota on the Cloud project.

### Can I query a different property per user?

Yes. Pass the property ID explicitly:

```php
$client->runReport( $request, $connection, $customPropertyId );
```

The Livewire components and HTTP controllers both accept a `propertyId` parameter that passes through to the fetcher.

### Can I query multiple properties in parallel?

Not directly. Each `runReport` call is one property. Loop and dispatch them yourself; the cache keys include property so parallel dashboards on the same page don't collide.

### The React/Vue components show "install the base package". What now?

`GET /analytics-google/overview` returned `501 base_not_installed`. Install the base:

```bash
composer require artisanpack-ui/google
```

See [Graceful Degradation](Server-Side-Reporting-Graceful-Degradation) for the full error-to-fix table.

## Components

### Can I customize the range picker options?

Publish the JS sources:

```bash
php artisan vendor:publish --tag=analytics-google-js
```

Edit the `RANGE_OPTIONS = [7, 14, 30, 90]` constant at the top of `resources/js/vendor/analytics-google/react/GaOverview.tsx` (and the Vue equivalent).

For the Livewire component, publish the views and edit `resources/views/vendor/analytics-google/livewire/ga-overview.blade.php`.

### Can I move the HTTP endpoints under a different prefix?

Yes:

```php
'routes' => [
    'prefix' => 'admin/analytics',
],
```

Then pass `baseUrl="/admin/analytics/overview"` on the React and Vue components. Livewire components are unaffected — they call the fetchers directly.

### Can the components render for guests?

Not out of the box — the default middleware is `[ 'web', 'auth' ]` and the controllers resolve `$request->user()`. Loosen the middleware, then override `GoogleConnectionResolver` in your container binding to look up a connection by tenant/domain instead of by user.

## Related

- [Getting Started](Getting-Started)
- [Installation](Installation)
- [Graceful Degradation](Server-Side-Reporting-Graceful-Degradation)
