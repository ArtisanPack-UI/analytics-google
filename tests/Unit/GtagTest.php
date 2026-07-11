<?php

declare( strict_types=1 );

use ArtisanPackUI\AnalyticsGoogle\Tracking\Gtag;

it( 'renders nothing when no measurement ID is configured', function (): void {
    config()->set( 'analytics-google.tracking.measurement_id', null );

    expect( app( Gtag::class )->render() )->toBe( '' );
} );

it( 'renders nothing when tracking is disabled', function (): void {
    config()->set( 'analytics-google.tracking.measurement_id', 'G-ABCDE12345' );
    config()->set( 'analytics-google.tracking.enabled', false );

    expect( app( Gtag::class )->render() )->toBe( '' );
} );

it( 'renders the gtag.js snippet with the configured measurement ID', function (): void {
    config()->set( 'analytics-google.tracking.measurement_id', 'G-ABCDE12345' );
    config()->set( 'analytics-google.tracking.enabled', true );
    config()->set( 'analytics-google.tracking.respect_consent', false );

    $snippet = app( Gtag::class )->render();

    expect( $snippet )->toContain( 'https://www.googletagmanager.com/gtag/js?id=G-ABCDE12345' )
        ->and( $snippet )->toContain( "gtag('config','G-ABCDE12345'" );
} );

it( 'emits a consent-default guard when consent is respected', function (): void {
    config()->set( 'analytics-google.tracking.measurement_id', 'G-CONSENT01' );
    config()->set( 'analytics-google.tracking.respect_consent', true );

    $snippet = app( Gtag::class )->render();

    expect( $snippet )->toContain( "gtag('consent','default'" )
        ->and( $snippet )->toContain( 'analytics_storage' )
        ->and( $snippet )->toContain( 'denied' );
} );

it( 'omits the consent guard when consent is not respected', function (): void {
    config()->set( 'analytics-google.tracking.measurement_id', 'G-NOCONSENT' );
    config()->set( 'analytics-google.tracking.respect_consent', false );

    $snippet = app( Gtag::class )->render();

    expect( $snippet )->not->toContain( "gtag('consent'" );
} );

it( 'escapes the measurement ID against script tag injection', function (): void {
    config()->set( 'analytics-google.tracking.measurement_id', 'G-EVIL</script><script>alert(1)</script>' );
    config()->set( 'analytics-google.tracking.respect_consent', false );

    $snippet = app( Gtag::class )->render();

    expect( $snippet )->not->toContain( '</script><script>alert(1)</script>' )
        ->and( $snippet )->toContain( '\\u003C' );
} );

it( 'serialises config options into the gtag call', function (): void {
    config()->set( 'analytics-google.tracking.measurement_id', 'G-OPT' );
    config()->set( 'analytics-google.tracking.respect_consent', false );
    config()->set( 'analytics-google.tracking.config', [
        'anonymize_ip'   => true,
        'send_page_view' => false,
    ] );

    $snippet = app( Gtag::class )->render();

    expect( $snippet )->toContain( '"anonymize_ip":true' )
        ->and( $snippet )->toContain( '"send_page_view":false' );
} );

it( 'exposes isConfigured() only when both enabled and ID are set', function (): void {
    $gtag = app( Gtag::class );

    config()->set( 'analytics-google.tracking.enabled', true );
    config()->set( 'analytics-google.tracking.measurement_id', null );
    expect( $gtag->isConfigured() )->toBeFalse();

    config()->set( 'analytics-google.tracking.measurement_id', 'G-YES' );
    expect( $gtag->isConfigured() )->toBeTrue();

    config()->set( 'analytics-google.tracking.enabled', false );
    expect( $gtag->isConfigured() )->toBeFalse();
} );
