# ArtisanPack UI Analytics Google

Google Analytics 4 (GA4) integration for Laravel applications. Two independent surfaces:

- **Client-side tracking** — a `@ga4Snippet` Blade directive (plus React and Vue equivalents) that renders the standard `gtag.js` snippet. Works standalone; no OAuth required.
- **Server-side reporting** — a `Ga4DataClient` that queries the GA4 Data API using an OAuth token from the [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) base package. Ships with a Livewire, React, and Vue *Overview* component that renders sessions, users, page views, and average engagement time over a configurable date range.

The package registers itself with the [`artisanpack-ui/analytics`](https://github.com/ArtisanPack-UI/analytics) parent when it is installed, so tracking runs through the parent's consent gate rather than firing unconditionally.

## Installation

```bash
composer require artisanpack-ui/analytics-google
```

The service provider and `AnalyticsGoogle` facade are auto-discovered. For server-side reporting, also install the base:

```bash
composer require artisanpack-ui/google
```

The package boots without the base — the client-side tracking side stays available, and the reporting side gracefully degrades with a well-typed `BaseNotInstalledException` if you call it.

Publish the config:

```bash
php artisan vendor:publish --tag=analytics-google-config
```

Then set the two env vars you need:

```dotenv
GA4_MEASUREMENT_ID=G-XXXXXXX
GA4_PROPERTY_ID=123456789
```

## Client-side tracking

Drop the Blade directive into your layout:

```blade
<head>
    …
    @ga4Snippet
</head>
```

React:

```tsx
import { Ga4Snippet } from '@artisanpack-ui/analytics-google-js/react'

<Ga4Snippet measurementId="G-XXXXXXX" />
```

Vue:

```vue
<script setup>
import { Ga4Snippet } from '@artisanpack-ui/analytics-google-js/vue'
</script>

<template>
    <Ga4Snippet measurement-id="G-XXXXXXX" />
</template>
```

By default the snippet defers page views until the analytics parent's consent banner grants the `analytics` category — configurable via `analytics-google.tracking.respect_consent`.

## Server-side reporting

Reporting requires the [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) base package and a connected Google account for the current user.

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

### GA Overview component

```blade
<livewire:analytics-google::ga-overview :days="30" />
```

For React and Vue, publish the component sources and point them at the built-in HTTP endpoint:

```bash
php artisan vendor:publish --tag=analytics-google-js
```

```tsx
import { GaOverview } from './vendor/analytics-google/react'

<GaOverview initialDays={30} />
```

## Contributing

Please [read through the contributing guidelines](CONTRIBUTING.md) to learn more about how you can contribute to this project.
