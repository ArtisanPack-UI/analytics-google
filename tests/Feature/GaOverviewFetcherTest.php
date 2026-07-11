<?php

declare( strict_types=1 );

use ArtisanPackUI\AnalyticsGoogle\Reporting\DateRange;
use ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient;
use ArtisanPackUI\AnalyticsGoogle\Reporting\GaOverviewFetcher;
use ArtisanPackUI\AnalyticsGoogle\Support\BaseInstalled;
use ArtisanPackUI\Google\Models\GoogleConnection;
use ArtisanPackUI\Google\Tokens\TokenManager;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;

beforeEach( function (): void {
    BaseInstalled::setForTesting( true );
    config()->set( 'analytics-google.reporting.property_id', '999' );
} );

it( 'parses totals and trend rows from two GA4 calls', function (): void {
    $totalsResponse = [
        'dimensionHeaders' => [],
        'metricHeaders'    => [
            [ 'name' => 'sessions' ],
            [ 'name' => 'totalUsers' ],
            [ 'name' => 'screenPageViews' ],
            [ 'name' => 'averageSessionDuration' ],
        ],
        'rows' => [
            [
                'dimensionValues' => [],
                'metricValues'    => [
                    [ 'value' => '250' ],
                    [ 'value' => '200' ],
                    [ 'value' => '900' ],
                    [ 'value' => '124.5' ],
                ],
            ],
        ],
    ];

    $trendResponse = [
        'dimensionHeaders' => [ [ 'name' => 'date' ] ],
        'metricHeaders'    => [
            [ 'name' => 'sessions' ],
            [ 'name' => 'totalUsers' ],
            [ 'name' => 'screenPageViews' ],
        ],
        'rows' => [
            [
                'dimensionValues' => [ [ 'value' => '20260102' ] ],
                'metricValues'    => [ [ 'value' => '150' ], [ 'value' => '120' ], [ 'value' => '600' ] ],
            ],
            [
                'dimensionValues' => [ [ 'value' => '20260101' ] ],
                'metricValues'    => [ [ 'value' => '100' ], [ 'value' => '80' ], [ 'value' => '300' ] ],
            ],
        ],
    ];

    Http::fakeSequence()
        ->push( $totalsResponse, 200 )
        ->push( $trendResponse, 200 );

    $client = new Ga4DataClient(
        app( 'config' ),
        app( HttpFactory::class ),
        makeStubTokenManager( 'x' ),
    );

    $fetcher = new GaOverviewFetcher( $client );

    $overview = $fetcher->fetch(
        makeStubConnection(),
        DateRange::lastDays( 7 ),
    );

    expect( $overview->totals )->toBe( [
        'sessions'               => 250.0,
        'users'                  => 200.0,
        'page_views'             => 900.0,
        'avg_engagement_seconds' => 124.5,
    ] );

    expect( $overview->trend )->toBe( [
        [ 'date' => '2026-01-01', 'sessions' => 100.0, 'users' => 80.0, 'page_views' => 300.0 ],
        [ 'date' => '2026-01-02', 'sessions' => 150.0, 'users' => 120.0, 'page_views' => 600.0 ],
    ] );
} );

function makeStubTokenManager( string $token ): TokenManager
{
    return new class( $token ) extends TokenManager {
        public function __construct( private string $token )
        {
        }

        public function getValidAccessToken( GoogleConnection $connection ): string
        {
            return $this->token;
        }
    };
}

function makeStubConnection(): GoogleConnection
{
    $connection         = new GoogleConnection();
    $connection->status = GoogleConnection::STATUS_CONNECTED;
    $connection->exists = true;

    return $connection;
}
