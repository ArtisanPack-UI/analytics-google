---
title: Installation
---

# Installation

## Install matrix

Pick the row that matches what you want to do:

| Goal | `analytics-google` | `google` (base) | `analytics` (parent) | Livewire / React / Vue |
|---|---|---|---|---|
| Emit the gtag snippet from Blade | ✅ | — | — | — |
| Emit the gtag snippet from React or Vue | ✅ | — | — | React or Vue |
| Route gtag through the analytics parent's consent gate | ✅ | — | ✅ | — |
| Query the GA4 Data API from your own code | ✅ | ✅ | — | — |
| Render the GA overview component | ✅ | ✅ | — | Livewire, React, or Vue |
| Render the top pages + top events component | ✅ | ✅ | — | Livewire, React, or Vue |
| Drop the two components into the CMS framework admin dashboard | ✅ | ✅ | — | `artisanpack-ui/cms-framework` + Livewire |

## Install via Composer

```bash
composer require artisanpack-ui/analytics-google
```

The package auto-registers via Laravel's package discovery:

- **Service provider**: `ArtisanPackUI\AnalyticsGoogle\AnalyticsGoogleServiceProvider`
- **Facade alias**: `AnalyticsGoogle` (`ArtisanPackUI\AnalyticsGoogle\Facades\AnalyticsGoogle`)

For server-side reporting, also install the base:

```bash
composer require artisanpack-ui/google
```

The package is deliberately safe to install without the base — see [Graceful Degradation](Server-Side-Reporting-Graceful-Degradation).

## Publish the config

```bash
php artisan vendor:publish --tag=analytics-google-config
```

Copies `config/analytics-google.php` into your app. Every key is documented in [Configuration](Configuration).

## Publish the views (optional)

```bash
php artisan vendor:publish --tag=analytics-google-views
```

Copies the Livewire component views to `resources/views/vendor/analytics-google/`. Only needed if you want to customize the overview or top-content markup.

## Publish the JS components (optional)

```bash
php artisan vendor:publish --tag=analytics-google-js
```

Copies the React and Vue `Ga4Snippet`, `GaOverview`, and `GaTopContent` sources — plus the shared TypeScript clients under `shared/` — to `resources/js/vendor/analytics-google/`. Skip this step if you'd rather point your bundler at the package's `resources/js/` directly.

## Google Cloud setup

Before server-side reporting works, you need an OAuth 2.0 client on a Google Cloud project with the **Google Analytics Data API** enabled and the `https://www.googleapis.com/auth/analytics.readonly` scope authorized on the consent screen.

The base [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) package owns the OAuth setup end-to-end; this package contributes the required scope via the `ap.google.scopes` filter hook, so the base's single consent screen covers GA4 alongside every other Google service you've enabled.

Walkthrough with the extra GA4-specific steps: [Google Cloud setup](Installation-Google-Cloud-Setup).

## Set the environment variables

```env
# Client-side tracking
GA4_MEASUREMENT_ID=G-XXXXXXX

# Server-side reporting
GA4_PROPERTY_ID=123456789
```

Full env-var reference: [Environment variables](Installation-Environment-Variables).

## Apply route middleware (optional)

The package registers two web routes for the React and Vue reporting components. They mount under `analytics-google.routes.prefix` (default `analytics-google`) with `[ 'web', 'auth' ]` middleware. To disable them entirely for a headless / API-only app:

```php
// config/analytics-google.php
'routes' => [
    'enabled' => false,
],
```

Route reference: [HTTP Endpoints](HTTP-Endpoints).

## Verify the install

Run:

```bash
php artisan route:list --path=analytics-google
```

You should see (when the base is installed):

```
GET  analytics-google/overview      analytics-google.overview
GET  analytics-google/top-content   analytics-google.top-content
```

Without the base package, these routes are not registered — they would call into the OAuth token manager, which does not exist.

Confirm the Blade directive:

```bash
php artisan tinker
>>> app( \ArtisanPackUI\AnalyticsGoogle\Tracking\Gtag::class )->isConfigured();
```

Returns `true` once `GA4_MEASUREMENT_ID` is set.

## Deeper topics

- [Requirements](Installation-Requirements) — PHP, Laravel, peer-package versions.
- [Configuration](Installation-Configuration) — full `config/analytics-google.php` reference.
- [Environment variables](Installation-Environment-Variables) — every env var the package reads.
- [Google Cloud setup](Installation-Google-Cloud-Setup) — GA4 property, Data API, and the base package's Cloud Console walkthrough.

---
Continue to [Client-Side Tracking](Client-Side-Tracking) →
