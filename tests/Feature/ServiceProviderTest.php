<?php

declare( strict_types=1 );

use ArtisanPackUI\AnalyticsGoogle\AnalyticsGoogle;
use ArtisanPackUI\AnalyticsGoogle\Providers\Ga4Provider;
use ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient;
use ArtisanPackUI\AnalyticsGoogle\Support\BaseInstalled;
use ArtisanPackUI\AnalyticsGoogle\Tracking\Gtag;

it( 'binds the AnalyticsGoogle service, Gtag, and provider', function (): void {
    expect( app( 'analytics-google' ) )->toBeInstanceOf( AnalyticsGoogle::class )
        ->and( app( Gtag::class ) )->toBeInstanceOf( Gtag::class )
        ->and( app( Ga4Provider::class ) )->toBeInstanceOf( Ga4Provider::class );
} );

it( 'exposes the reporting client via AnalyticsGoogle when the base is installed', function (): void {
    BaseInstalled::setForTesting( true );

    $svc = app( 'analytics-google' );

    expect( $svc->hasReporting() )->toBeTrue()
        ->and( $svc->dataClient() )->toBeInstanceOf( Ga4DataClient::class );
} );

it( 'hides the reporting client via AnalyticsGoogle when the base is not installed', function (): void {
    // Rebind AnalyticsGoogle without the client to simulate a bootstrapped
    // container where BaseInstalled::check() was false at register-time.
    $svc = new AnalyticsGoogle(
        app( Gtag::class ),
        app( Ga4Provider::class ),
        null,
    );

    expect( $svc->hasReporting() )->toBeFalse()
        ->and( $svc->dataClient() )->toBeNull();
} );

it( 'exposes the provider name from config', function (): void {
    config()->set( 'analytics-google.provider_name', 'my-google' );
    config()->set( 'analytics-google.tracking.measurement_id', 'G-YES' );

    $provider = app( Ga4Provider::class );

    expect( $provider->getName() )->toBe( 'my-google' )
        ->and( $provider->isEnabled() )->toBeTrue();
} );

it( 'contributes analytics.readonly to the ap.google.scopes hook when hooks are available', function (): void {
    if ( ! function_exists( 'applyFilters' ) ) {
        $this->markTestSkipped( 'hooks package not available' );
    }

    $union = applyFilters( 'ap.google.scopes', [] );

    expect( $union )->toContain( 'https://www.googleapis.com/auth/analytics.readonly' );
} );
