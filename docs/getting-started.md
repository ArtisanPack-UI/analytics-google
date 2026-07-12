---
title: Getting Started
---

# Getting Started

Welcome to ArtisanPack UI Analytics Google. This guide walks through the shortest path from `composer require` to a working GA4 tracker on the frontend and a first server-side report.

See also: [Installation](Installation), [Client-Side Tracking](Client-Side-Tracking), [Server-Side Reporting](Server-Side-Reporting), and [Components](Components).

## Requirements

- PHP 8.2+
- Laravel 10.x, 11.x, 12.x, or 13.x
- A Google Analytics 4 property (measurement ID for tracking, numeric property ID for reporting)

For server-side reporting, additional dependencies apply — see [Requirements](Installation-Requirements).

## 1. Install

```bash
composer require artisanpack-ui/analytics-google
```

The service provider and `AnalyticsGoogle` facade are auto-discovered.

For server-side reporting, also install the base:

```bash
composer require artisanpack-ui/google
```

The package boots either way; the reporting classes throw a well-typed [`BaseNotInstalledException`](API-Reference-Exceptions) when the base is missing so you get a clear failure mode instead of a class-not-found fatal.

## 2. Publish the config

```bash
php artisan vendor:publish --tag=analytics-google-config
```

This copies `config/analytics-google.php` into your app. Full reference: [Configuration](Configuration).

## 3. Add your IDs to `.env`

```env
GA4_MEASUREMENT_ID=G-XXXXXXX
GA4_PROPERTY_ID=123456789
```

- `GA4_MEASUREMENT_ID` — for client-side tracking. The `G-XXXXXXX` value from GA4 → Admin → Data Streams.
- `GA4_PROPERTY_ID` — for server-side reporting. The numeric property ID from GA4 → Admin → Property Settings.

Only fill in what you need. Leaving `GA4_MEASUREMENT_ID` empty disables the tracker; leaving `GA4_PROPERTY_ID` empty causes reporting calls to throw a [`ReportingException::missingConfiguration()`](API-Reference-Exceptions).

## 4. Add the tracker to your layout

**Blade:**

```blade
<head>
    …
    @ga4Snippet
</head>
```

The directive emits nothing when the measurement ID is empty, so it's safe to leave in a shared layout across environments.

**React or Vue** — see [React](Client-Side-Tracking-React) and [Vue](Client-Side-Tracking-Vue).

## 5. Connect a Google account (reporting only)

Server-side reporting queries GA4 with an OAuth token supplied by the base package. Follow the base's [Getting Started](Getting-Started) guide to connect an account with the `analytics.readonly` scope — this package contributes that scope automatically via the `ap.google.scopes` [filter hook](Server-Side-Reporting).

## 6. Run a report

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

Full details: [Server-Side Reporting](Server-Side-Reporting).

## 7. Drop in a reporting component

If Livewire is installed:

```blade
<livewire:analytics-google::ga-overview :days="30" />
<livewire:analytics-google::ga-top-content :days="30" :limit="10" />
```

React and Vue equivalents ship as source under `resources/js/`. See [Components](Components) and [HTTP Endpoints](HTTP-Endpoints) for the JSON responses they consume.

## Next steps

- [Installation](Installation) — full install walkthrough, config publishing, and Google Cloud setup.
- [Client-Side Tracking](Client-Side-Tracking) — Blade, React, Vue, and consent integration.
- [Server-Side Reporting](Server-Side-Reporting) — `Ga4DataClient`, request/response DTOs, caching, and error handling.
- [Components](Components) — the three shared reporting surfaces.
- [Configuration](Configuration) — every key and env var this package reads.
- [API Reference](API-Reference) — the full public surface.

---
Continue to [Installation](Installation) →
