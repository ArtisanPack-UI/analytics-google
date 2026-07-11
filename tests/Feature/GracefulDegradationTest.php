<?php

declare( strict_types=1 );

use ArtisanPackUI\AnalyticsGoogle\Exceptions\BaseNotInstalledException;
use ArtisanPackUI\AnalyticsGoogle\Reporting\DateRange;
use ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient;
use ArtisanPackUI\AnalyticsGoogle\Reporting\ReportRequest;
use ArtisanPackUI\AnalyticsGoogle\Support\BaseInstalled;
use ArtisanPackUI\Google\Models\GoogleConnection;
use Illuminate\Http\Client\Factory as HttpFactory;

/*
 * Covers issue #5 — the package must boot and expose the tracking side
 * when the base google package is absent, and reporting must throw a
 * well-typed exception rather than a class-not-found fatal.
 */

it( 'boots and exposes tracking even when the base is reported absent', function (): void {
    BaseInstalled::setForTesting( false );

    config()->set( 'analytics-google.tracking.measurement_id', 'G-STANDALONE' );
    config()->set( 'analytics-google.tracking.respect_consent', false );

    $snippet = app( ArtisanPackUI\AnalyticsGoogle\Tracking\Gtag::class )->render();

    expect( $snippet )->toContain( 'G-STANDALONE' );
} );

it( 'reports the reporting side unavailable when the base is absent', function (): void {
    BaseInstalled::setForTesting( false );

    $client = new Ga4DataClient(
        app( 'config' ),
        app( HttpFactory::class ),
        null,
    );

    expect( $client->isAvailable() )->toBeFalse();
} );

it( 'throws a BaseNotInstalledException rather than a class-not-found fatal', function (): void {
    BaseInstalled::setForTesting( false );

    $client = new Ga4DataClient(
        app( 'config' ),
        app( HttpFactory::class ),
        null,
    );

    // Give the client something valid to serialise so its failure surfaces
    // from the base check, not from missing config.
    config()->set( 'analytics-google.reporting.property_id', '1' );

    $client->runReport(
        ReportRequest::make( range: DateRange::lastDays( 1 ), metrics: [ 'sessions' ] ),
        new GoogleConnection(),
    );
} )->throws( BaseNotInstalledException::class );

it( 'BaseInstalled::check reports true in this test environment', function (): void {
    BaseInstalled::reset();

    expect( BaseInstalled::check() )->toBeTrue();
} );
