# ArtisanPack UI Analytics Google

Google Analytics 4 (GA4) integration for Laravel applications.

The package ships **two independent surfaces** that you can adopt separately:

| Surface | What it does | Requires the `artisanpack-ui/google` base package? |
|---|---|---|
| **Client-side tracking** | Emits the standard `gtag.js` snippet on your pages. | No |
| **Server-side reporting** | Queries the GA4 Data API for sessions, users, top pages, top events. | Yes (for the OAuth token) |

If you only need tracking, install this package alone. If you need dashboards, add the base too. The package boots either way — the reporting classes gracefully throw a well-typed [`BaseNotInstalledException`](src/Exceptions/BaseNotInstalledException.php) when the base is missing, and reporting components render an "install the base" prompt instead of blowing up.

When installed alongside the [`artisanpack-ui/analytics`](https://github.com/ArtisanPack-UI/analytics) parent, this package registers itself as the `google-ga4` provider so client-side tracking runs through the parent's consent gate rather than firing unconditionally.

## Install matrix

Pick the row that matches what you want to do:

| Goal | `artisanpack-ui/analytics-google` | `artisanpack-ui/google` | `artisanpack-ui/analytics` | Livewire / React / Vue |
|---|---|---|---|---|
| Emit the gtag snippet from Blade | ✅ | — | — | — |
| Emit the gtag snippet from React or Vue | ✅ | — | — | React or Vue |
| Route gtag through the analytics parent's consent gate | ✅ | — | ✅ | — |
| Query the GA4 Data API from your own code | ✅ | ✅ | — | — |
| Render the GA overview component | ✅ | ✅ | — | Livewire, React, or Vue |
| Render the Top pages + top events component | ✅ | ✅ | — | Livewire, React, or Vue |
| Drop the two components into the CMS framework admin dashboard | ✅ | ✅ | — | `artisanpack-ui/cms-framework` + Livewire |

## Installation

```bash
composer require artisanpack-ui/analytics-google
```

For server-side reporting, also install the base package:

```bash
composer require artisanpack-ui/google
```

The service provider and `AnalyticsGoogle` facade are auto-discovered.

Publish the config:

```bash
php artisan vendor:publish --tag=analytics-google-config
```

Set the environment variables you need — measurement ID for tracking, property ID for reporting:

```dotenv
GA4_MEASUREMENT_ID=G-XXXXXXX
GA4_PROPERTY_ID=123456789
```

To forward the analytics parent's page views and events to GA4 from the server (since 1.1.0), add a Measurement Protocol API secret:

| Variable | Description |
|---|---|
| `GA4_API_SECRET` | Measurement Protocol API secret (GA4 → Admin → Data Streams → Measurement Protocol API secrets). Forwarding stays off until this and `GA4_MEASUREMENT_ID` are both set. |
| `GA4_SERVER_SIDE_TRACKING` | Turns server-side forwarding off independently of the secret. Defaults to `true`. |
| `GA4_PAGE_LOCATION_BASE` | Base URL used to build the absolute `page_location` GA4 expects. Defaults to `app.url`. |
| `GA4_MEASUREMENT_PROTOCOL_DEBUG` | Sends hits to GA4's validation endpoint and logs the validation messages it returns. Defaults to `false`. |

Read [`docs/analytics-parent-integration.md`](docs/analytics-parent-integration.md) before enabling this — it covers the caveats around double-counting against client-side `gtag.js`, attribution, and session boundaries.

## Client-side tracking

### Blade

Drop the `@ga4Snippet` directive into your layout `<head>`:

```blade
<head>
    …
    @ga4Snippet
</head>
```

The directive emits nothing when `analytics-google.tracking.measurement_id` is empty, so it is safe to leave in a shared layout across environments.

### React

```tsx
import { Ga4Snippet } from '@artisanpack-ui/analytics-google-js/react'

<Ga4Snippet measurementId="G-XXXXXXX" />
```

### Vue

```vue
<script setup>
import { Ga4Snippet } from '@artisanpack-ui/analytics-google-js/vue'
</script>

<template>
    <Ga4Snippet measurement-id="G-XXXXXXX" />
</template>
```

By default the snippet defers `page_view` events until the analytics parent's consent banner grants the `analytics` category — configurable via `analytics-google.tracking.respect_consent`. When the parent is not installed, tracking fires immediately since there is no consent gate to defer to.

## Server-side reporting

Reporting requires the base package and a connected Google account for the current user. Under the hood the [`Ga4DataClient`](src/Reporting/Ga4DataClient.php) pulls an OAuth token from the base's `TokenManager` and POSTs against the GA4 Data API's `runReport` endpoint.

### Ga4DataClient — raw queries

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

### GA overview component

Four headline metrics — sessions, users, page views, average engagement time — plus a daily trend chart for a configurable date range.

**Livewire:**

```blade
<livewire:analytics-google::ga-overview :days="30" />
```

**React and Vue** — publish the component sources, then point them at the built-in HTTP endpoint at `/analytics-google/overview`:

```bash
php artisan vendor:publish --tag=analytics-google-js
```

```tsx
import { GaOverview } from './vendor/analytics-google/react'

<GaOverview initialDays={30} />
```

```vue
<script setup>
import { GaOverview } from './vendor/analytics-google/vue'
</script>

<template>
    <GaOverview :initial-days="30" />
</template>
```

### Top pages and events component

Top pages (by views) and top events (by count) for a configurable date range.

**Livewire:**

```blade
<livewire:analytics-google::ga-top-content :days="30" :limit="10" />
```

**React and Vue** — the endpoint lives at `/analytics-google/top-content`:

```tsx
import { GaTopContent } from './vendor/analytics-google/react'

<GaTopContent initialDays={30} limit={10} />
```

```vue
<script setup>
import { GaTopContent } from './vendor/analytics-google/vue'
</script>

<template>
    <GaTopContent :initial-days="30" :limit="10" />
</template>
```

### CMS framework admin widgets

When `artisanpack-ui/cms-framework` is installed, this package automatically registers both components as dashboard widgets:

- `analytics-google-overview` → the overview widget
- `analytics-google-top-content` → the top pages + events widget

Both are guarded behind the `view_analytics` capability. See the CMS framework's admin widgets documentation for how end users add them to their dashboard.

## Configuration reference

See [`config/analytics-google.php`](config/analytics-google.php). Highlights:

- `tracking.measurement_id` — GA4 measurement ID (`G-XXXXXXX`). Leave empty to disable the client-side snippet.
- `tracking.respect_consent` — whether to defer `page_view` until the analytics parent's consent gate grants the `analytics` category. Defaults to `true`.
- `tracking.api_secret` / `tracking.server_side` — Measurement Protocol API secret and the switch for server-side forwarding of the analytics parent's page views and events.
- `tracking.page_location_base` / `tracking.timeout` / `tracking.debug` — absolute-URL base, HTTP timeout, and validation-endpoint toggle for Measurement Protocol calls.
- `reporting.property_id` — GA4 numeric property ID for Data API queries.
- `reporting.cache_ttl` — how long (seconds) to cache Data API responses. Set to `0` to disable.
- `provider_name` — the name this package registers under with the `artisanpack-ui/analytics` parent. Defaults to `google-ga4`.
- `routes.enabled` / `routes.prefix` / `routes.middleware` — controls the HTTP endpoints that back the React and Vue components. Set `routes.enabled` to `false` for headless / API-only apps.

## Graceful degradation

The package is deliberately safe to install without the base:

- The service provider registers Blade directives, Livewire components, and the config regardless of whether the base is present.
- `Ga4DataClient::isAvailable()` returns `false` when the base is missing, so callers can hide reporting UI cleanly.
- Calling `Ga4DataClient::runReport()` without the base throws [`BaseNotInstalledException`](src/Exceptions/BaseNotInstalledException.php) rather than a class-not-found fatal.
- The Livewire and React/Vue reporting components render an "install the base" prompt instead of blowing up.
- The reporting HTTP endpoints return `501 base_not_installed` responses that the React/Vue components render via their error-code branch.

## Contributing

Please [read through the contributing guidelines](CONTRIBUTING.md) to learn more about how you can contribute to this project.

## License

ArtisanPack UI Analytics Google is open-source software licensed under the [MIT license](LICENSE).
