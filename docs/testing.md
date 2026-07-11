---
title: Testing
---

# Testing

The package ships with a Pest test suite in `tests/`. This page collects patterns for testing your own code against `artisanpack-ui/analytics-google`.

## Test bootstrap

Use Orchestra Testbench with the service provider:

```php
use ArtisanPackUI\AnalyticsGoogle\AnalyticsGoogleServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders( $app ): array
    {
        return [ AnalyticsGoogleServiceProvider::class ];
    }
}
```

If you're testing the reporting side, also register the base:

```php
protected function getPackageProviders( $app ): array
{
    return [
        \ArtisanPackUI\Google\GoogleServiceProvider::class,
        AnalyticsGoogleServiceProvider::class,
    ];
}
```

## Disabling the base for a test

The `BaseInstalled` cache is reset via a static helper:

```php
use ArtisanPackUI\AnalyticsGoogle\Support\BaseInstalled;

BaseInstalled::setForTesting( false );
// exercise the "install the base" branches
BaseInstalled::reset();
```

Remember to reset in `tearDown` so later tests get honest autoload-based detection.

## Faking the GA4 Data API

Use Laravel's HTTP fake:

```php
use Illuminate\Support\Facades\Http;

Http::fake( [
    'analyticsdata.googleapis.com/*' => Http::response( [
        'dimensionHeaders' => [ [ 'name' => 'date' ] ],
        'metricHeaders'    => [ [ 'name' => 'sessions' ] ],
        'rows'             => [
            [
                'dimensionValues' => [ [ 'value' => '20260101' ] ],
                'metricValues'    => [ [ 'value' => '42' ] ],
            ],
        ],
    ] ),
] );

$response = $client->runReport( $request, $connection );

expect( $response->totalFor( 'sessions' ) )->toBe( 42.0 );
```

## Disable caching in tests

`Ga4DataClient` caches successful responses. In tests, either set the TTL to `0`:

```php
config()->set( 'analytics-google.reporting.cache_ttl', 0 );
```

Or use the array cache driver so entries evaporate at end-of-test:

```php
config()->set( 'cache.default', 'array' );
```

## Testing the Blade directive

```php
use function Pest\Laravel\view;

config()->set( 'analytics-google.tracking.measurement_id', 'G-TEST' );

$rendered = (string) view( 'welcome' );

expect( $rendered )->toContain( 'gtag/js?id=G-TEST' );
```

Or resolve `Gtag` directly and assert on its `render()`:

```php
$snippet = app( \ArtisanPackUI\AnalyticsGoogle\Tracking\Gtag::class )->render();
```

## Testing the Livewire reporting components

```php
use ArtisanPackUI\AnalyticsGoogle\Livewire\GaOverview;
use Livewire\Livewire;

Livewire::test( GaOverview::class, [ 'days' => 30 ] )
    ->assertSet( 'days', 30 )
    ->assertSet( 'baseInstalled', true )
    ->assertHasNoErrors();
```

For the base-missing branch, use `BaseInstalled::setForTesting( false )` before instantiating.

## Testing the HTTP endpoints

```php
use function Pest\Laravel\{actingAs, getJson};

actingAs( $user );

getJson( '/analytics-google/overview?days=7' )
    ->assertOk()
    ->assertJsonStructure( [ 'range' => [ 'startDate', 'endDate' ], 'totals', 'trend' ] );
```

For the `501 base_not_installed` branch:

```php
BaseInstalled::setForTesting( false );

getJson( '/analytics-google/overview' )
    ->assertStatus( 501 )
    ->assertJson( [ 'error' => 'base_not_installed', 'baseInstalled' => false ] );
```

## Related

- [[HTTP Endpoints]]
- [[Server-Side Reporting/GA4 Data Client|`Ga4DataClient`]]
- [[Server-Side Reporting/Graceful Degradation|Graceful Degradation]]
