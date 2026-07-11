<?php

declare( strict_types=1 );

use ArtisanPackUI\AnalyticsGoogle\Exceptions\BaseNotInstalledException;
use ArtisanPackUI\AnalyticsGoogle\Exceptions\ReportingException;
use ArtisanPackUI\AnalyticsGoogle\Reporting\DateRange;
use ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient;
use ArtisanPackUI\AnalyticsGoogle\Reporting\ReportRequest;
use ArtisanPackUI\AnalyticsGoogle\Support\BaseInstalled;
use ArtisanPackUI\Google\Models\GoogleConnection;
use ArtisanPackUI\Google\Tokens\TokenManager;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;

beforeEach( function (): void {
    BaseInstalled::setForTesting( true );

    config()->set( 'analytics-google.reporting.property_id', '123456789' );
} );

it( 'runs a report and returns a parsed response', function (): void {
    Http::fake( [
        '*' => Http::response( [
            'dimensionHeaders' => [ [ 'name' => 'date' ] ],
            'metricHeaders'    => [ [ 'name' => 'sessions' ] ],
            'rows'             => [
                [ 'dimensionValues' => [ [ 'value' => '20260101' ] ], 'metricValues' => [ [ 'value' => '42' ] ] ],
            ],
        ], 200 ),
    ] );

    $client = new Ga4DataClient(
        config: app( 'config' ),
        http: app( HttpFactory::class ),
        tokens: makeTokenManagerReturning( 'test-access-token' ),
    );

    $connection = makeConnectedGoogleConnection();

    $response = $client->runReport(
        ReportRequest::make( range: DateRange::lastDays( 1 ), metrics: [ 'sessions' ], dimensions: [ 'date' ] ),
        $connection,
    );

    expect( $response->rows() )->toBe( [ [ 'date' => '20260101', 'sessions' => '42' ] ] );

    Http::assertSent( function ( Illuminate\Http\Client\Request $request ): bool {
        expect( $request->url() )->toContain( 'properties/123456789:runReport' );
        expect( $request->header( 'Authorization' )[0] ?? '' )->toBe( 'Bearer test-access-token' );

        return true;
    } );
} );

it( 'throws a well-typed exception when the property ID is missing', function (): void {
    config()->set( 'analytics-google.reporting.property_id', null );

    $client = new Ga4DataClient(
        config: app( 'config' ),
        http: app( HttpFactory::class ),
        tokens: makeTokenManagerReturning( 'x' ),
    );

    $client->runReport(
        ReportRequest::make( range: DateRange::lastDays( 1 ), metrics: [ 'sessions' ] ),
        makeConnectedGoogleConnection(),
    );
} )->throws( ReportingException::class );

it( 'throws BaseNotInstalledException when the base package is not detected', function (): void {
    BaseInstalled::setForTesting( false );

    $client = new Ga4DataClient(
        config: app( 'config' ),
        http: app( HttpFactory::class ),
        tokens: makeTokenManagerReturning( 'x' ),
    );

    $client->runReport(
        ReportRequest::make( range: DateRange::lastDays( 1 ), metrics: [ 'sessions' ] ),
        makeConnectedGoogleConnection(),
    );
} )->throws( BaseNotInstalledException::class );

it( 'throws BaseNotInstalledException when the TokenManager is null', function (): void {
    $client = new Ga4DataClient(
        config: app( 'config' ),
        http: app( HttpFactory::class ),
        tokens: null,
    );

    $client->runReport(
        ReportRequest::make( range: DateRange::lastDays( 1 ), metrics: [ 'sessions' ] ),
        makeConnectedGoogleConnection(),
    );
} )->throws( BaseNotInstalledException::class );

it( 'wraps Data API error responses in a ReportingException', function (): void {
    Http::fake( [
        '*' => Http::response( [ 'error' => 'permission denied' ], 403 ),
    ] );

    $client = new Ga4DataClient(
        config: app( 'config' ),
        http: app( HttpFactory::class ),
        tokens: makeTokenManagerReturning( 'x' ),
    );

    $client->runReport(
        ReportRequest::make( range: DateRange::lastDays( 1 ), metrics: [ 'sessions' ] ),
        makeConnectedGoogleConnection(),
    );
} )->throws( ReportingException::class );

it( 'reports isAvailable() based on base install + token manager presence', function (): void {
    $client = new Ga4DataClient( app( 'config' ), app( HttpFactory::class ), makeTokenManagerReturning( 'x' ) );
    expect( $client->isAvailable() )->toBeTrue();

    BaseInstalled::setForTesting( false );
    expect( $client->isAvailable() )->toBeFalse();

    BaseInstalled::setForTesting( true );
    $noTokens = new Ga4DataClient( app( 'config' ), app( HttpFactory::class ), null );
    expect( $noTokens->isAvailable() )->toBeFalse();
} );

function makeTokenManagerReturning( string $token ): TokenManager
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

function makeConnectedGoogleConnection(): GoogleConnection
{
    $connection                    = new GoogleConnection();
    $connection->id                = 1;
    $connection->user_id           = 1;
    $connection->google_user_id    = 'test-user';
    $connection->email             = 'test@example.com';
    $connection->status            = GoogleConnection::STATUS_CONNECTED;
    $connection->exists            = true;

    return $connection;
}
