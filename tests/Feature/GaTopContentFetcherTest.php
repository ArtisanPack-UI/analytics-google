<?php

declare( strict_types=1 );

use ArtisanPackUI\AnalyticsGoogle\Reporting\DateRange;
use ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient;
use ArtisanPackUI\AnalyticsGoogle\Reporting\GaTopContentFetcher;
use ArtisanPackUI\AnalyticsGoogle\Support\BaseInstalled;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;

beforeEach( function (): void {
    BaseInstalled::setForTesting( true );
    config()->set( 'analytics-google.reporting.property_id', '999' );
} );

it( 'parses top pages and top events from two GA4 calls, sorted by their headline metric', function (): void {
    $pagesResponse = [
        'dimensionHeaders' => [
            [ 'name' => 'pagePath' ],
            [ 'name' => 'pageTitle' ],
        ],
        'metricHeaders' => [
            [ 'name' => 'screenPageViews' ],
            [ 'name' => 'totalUsers' ],
        ],
        'rows' => [
            [
                'dimensionValues' => [ [ 'value' => '/about' ], [ 'value' => 'About us' ] ],
                'metricValues'    => [ [ 'value' => '50' ], [ 'value' => '30' ] ],
            ],
            [
                'dimensionValues' => [ [ 'value' => '/' ], [ 'value' => 'Home' ] ],
                'metricValues'    => [ [ 'value' => '400' ], [ 'value' => '250' ] ],
            ],
        ],
    ];

    $eventsResponse = [
        'dimensionHeaders' => [ [ 'name' => 'eventName' ] ],
        'metricHeaders'    => [
            [ 'name' => 'eventCount' ],
            [ 'name' => 'totalUsers' ],
        ],
        'rows' => [
            [
                'dimensionValues' => [ [ 'value' => 'scroll' ] ],
                'metricValues'    => [ [ 'value' => '75' ], [ 'value' => '20' ] ],
            ],
            [
                'dimensionValues' => [ [ 'value' => 'page_view' ] ],
                'metricValues'    => [ [ 'value' => '600' ], [ 'value' => '300' ] ],
            ],
        ],
    ];

    Http::fakeSequence()
        ->push( $pagesResponse, 200 )
        ->push( $eventsResponse, 200 );

    $client = new Ga4DataClient(
        app( 'config' ),
        app( HttpFactory::class ),
        makeStubTokenManager( 'x' ),
    );

    $fetcher = new GaTopContentFetcher( $client );

    $data = $fetcher->fetch( makeStubConnection(), DateRange::lastDays( 30 ) );

    expect( $data->topPages )->toBe( [
        [ 'path' => '/', 'title' => 'Home', 'views' => 400.0, 'users' => 250.0 ],
        [ 'path' => '/about', 'title' => 'About us', 'views' => 50.0, 'users' => 30.0 ],
    ] );

    expect( $data->topEvents )->toBe( [
        [ 'event' => 'page_view', 'count' => 600.0, 'users' => 300.0 ],
        [ 'event' => 'scroll', 'count' => 75.0, 'users' => 20.0 ],
    ] );
} );

it( 'asks GA4 to sort by the headline metric so top-N is authoritative rather than local', function (): void {
    $empty = [
        'dimensionHeaders' => [ [ 'name' => 'pagePath' ], [ 'name' => 'pageTitle' ] ],
        'metricHeaders'    => [ [ 'name' => 'screenPageViews' ], [ 'name' => 'totalUsers' ] ],
        'rows'             => [],
    ];
    $emptyEvents = [
        'dimensionHeaders' => [ [ 'name' => 'eventName' ] ],
        'metricHeaders'    => [ [ 'name' => 'eventCount' ], [ 'name' => 'totalUsers' ] ],
        'rows'             => [],
    ];

    Http::fakeSequence()
        ->push( $empty, 200 )
        ->push( $emptyEvents, 200 );

    $client = new Ga4DataClient(
        app( 'config' ),
        app( HttpFactory::class ),
        makeStubTokenManager( 'x' ),
    );

    ( new GaTopContentFetcher( $client ) )->fetch( makeStubConnection(), DateRange::lastDays( 30 ) );

    $seenMetrics = [];
    Http::assertSent( function ( $request ) use ( &$seenMetrics ): bool {
        $orderBys = $request->data()['orderBys'] ?? [];
        foreach ( $orderBys as $orderBy ) {
            $metric = $orderBy['metric']['metricName'] ?? null;
            $desc   = $orderBy['desc'] ?? null;
            if ( null !== $metric && true === $desc ) {
                $seenMetrics[] = $metric;
            }
        }

        return true;
    } );

    expect( $seenMetrics )->toContain( 'screenPageViews' )
        ->and( $seenMetrics )->toContain( 'eventCount' );
} );

it( 'clamps caller-supplied limits into a sane range before hitting the Data API', function (): void {
    $empty = [
        'dimensionHeaders' => [ [ 'name' => 'pagePath' ], [ 'name' => 'pageTitle' ] ],
        'metricHeaders'    => [ [ 'name' => 'screenPageViews' ], [ 'name' => 'totalUsers' ] ],
        'rows'             => [],
    ];
    $emptyEvents = [
        'dimensionHeaders' => [ [ 'name' => 'eventName' ] ],
        'metricHeaders'    => [ [ 'name' => 'eventCount' ], [ 'name' => 'totalUsers' ] ],
        'rows'             => [],
    ];

    Http::fakeSequence()
        ->push( $empty, 200 )
        ->push( $emptyEvents, 200 );

    $client = new Ga4DataClient(
        app( 'config' ),
        app( HttpFactory::class ),
        makeStubTokenManager( 'x' ),
    );

    $fetcher = new GaTopContentFetcher( $client );
    $fetcher->fetch( makeStubConnection(), DateRange::lastDays( 30 ), null, 9999 );

    Http::assertSent( function ( $request ): bool {
        return 100 === ( $request->data()['limit'] ?? null );
    } );
} );
