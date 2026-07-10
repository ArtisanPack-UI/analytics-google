# ArtisanPack UI Analytics Google

Google Analytics (GA4) provider for the ArtisanPack UI [`analytics`](https://github.com/ArtisanPack-UI/analytics) package. Uses the shared [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) base package for OAuth2 authentication, token storage/refresh, and scope management, and calls the GA4 Data API to power the analytics dashboard and reporting surfaces.

## Installation

Install the package via Composer:

```bash
composer require artisanpack-ui/analytics-google
```

The service provider and `AnalyticsGoogle` facade are auto-discovered by Laravel. The base [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) package is pulled in automatically.

## Usage

Resolve the AnalyticsGoogle service from the container using the `analyticsGoogle()` helper or the `AnalyticsGoogle` facade:

```php
use ArtisanPackUI\AnalyticsGoogle\Facades\AnalyticsGoogle;

$provider = analyticsGoogle();
// or
$provider = AnalyticsGoogle::getFacadeRoot();
```

Provider registration with the shared analytics package and GA4 Data API reporting methods will be documented here as they ship.

## Contributing

Please [read through the contributing guidelines](CONTRIBUTING.md) to learn more about how you can contribute to this project.
