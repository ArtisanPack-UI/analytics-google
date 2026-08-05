<?php

declare( strict_types=1 );

use ArtisanPackUI\Analytics\Data\EventData;
use ArtisanPackUI\Analytics\Data\PageViewData;
use ArtisanPackUI\AnalyticsGoogle\Providers\Ga4AnalyticsProviderAdapter;
use ArtisanPackUI\AnalyticsGoogle\Providers\Ga4Provider;
use ArtisanPackUI\AnalyticsGoogle\Tracking\MeasurementProtocol;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach( function (): void {
	config()->set( 'app.url', 'https://docs.example.test' );
	config()->set( 'analytics-google.tracking.measurement_id', 'G-TESTID123' );
	config()->set( 'analytics-google.tracking.api_secret', 'test-secret' );
	config()->set( 'analytics-google.tracking.server_side', true );
	config()->set( 'analytics-google.tracking.debug', false );

	Http::preventStrayRequests();
	Http::fake( [ 'www.google-analytics.com/*' => Http::response( '', 204 ) ] );
} );

/**
 * Resolve the Measurement Protocol client from the container.
 */
function measurementProtocol(): MeasurementProtocol
{
	return app( MeasurementProtocol::class );
}

/**
 * The decoded body of the single Measurement Protocol request that was sent.
 *
 * @return array<string, mixed>
 */
function sentPayload(): array
{
	$sent = [];

	Http::recorded( function ( Request $request ) use ( &$sent ): bool {
		$sent[] = $request;

		return true;
	} );

	expect( $sent )->toHaveCount( 1, 'Expected exactly one Measurement Protocol request' );

	return json_decode( $sent[0]->body(), true );
}

/**
 * The URL of the single request that was sent.
 */
function sentUrl(): string
{
	$url = '';

	Http::recorded( function ( Request $request ) use ( &$url ): bool {
		$url = $request->url();

		return true;
	} );

	return $url;
}

test( 'a page view is forwarded as a GA4 page_view event', function (): void {
	measurementProtocol()->pageView( [
		'path'       => '/docs/getting-started',
		'title'      => 'Getting Started',
		'referrer'   => 'https://duckduckgo.com/',
		'visitor_id' => 'visitor-abc',
		'session_id' => 'session-xyz',
	] );

	$payload = sentPayload();

	expect( $payload['client_id'] )->toBe( 'visitor-abc' )
		->and( $payload['events'][0]['name'] )->toBe( 'page_view' )
		->and( $payload['events'][0]['params']['page_title'] )->toBe( 'Getting Started' )
		->and( $payload['events'][0]['params']['page_referrer'] )->toBe( 'https://duckduckgo.com/' )
		->and( $payload['events'][0]['params']['session_id'] )->toBe( 'session-xyz' );
} );

test( 'page_location is sent as an absolute URL, not a bare path', function (): void {
	// GA4 expects a URL here. A bare path leaves hostname and page-path
	// reporting empty, which is easy to miss because the hit still succeeds.
	measurementProtocol()->pageView( [ 'path' => '/docs/getting-started' ] );

	expect( sentPayload()['events'][0]['params']['page_location'] )
		->toBe( 'https://docs.example.test/docs/getting-started' );
} );

test( 'an already absolute path is passed through untouched', function (): void {
	measurementProtocol()->pageView( [ 'path' => 'https://other.example/page' ] );

	expect( sentPayload()['events'][0]['params']['page_location'] )
		->toBe( 'https://other.example/page' );
} );

test( 'the page location base can be overridden', function (): void {
	config()->set( 'analytics-google.tracking.page_location_base', 'https://cdn.example.test/' );

	measurementProtocol()->pageView( [ 'path' => '/docs/one' ] );

	expect( sentPayload()['events'][0]['params']['page_location'] )
		->toBe( 'https://cdn.example.test/docs/one' );
} );

test( 'UTM parameters are mapped onto GA4 traffic source params', function (): void {
	measurementProtocol()->pageView( [
		'path'         => '/docs/one',
		'utm_source'   => 'newsletter',
		'utm_medium'   => 'email',
		'utm_campaign' => 'launch',
	] );

	$params = sentPayload()['events'][0]['params'];

	expect( $params['source'] )->toBe( 'newsletter' )
		->and( $params['medium'] )->toBe( 'email' )
		->and( $params['campaign'] )->toBe( 'launch' );
} );

test( 'the visitor id becomes the GA4 client id so hits group into one user', function (): void {
	measurementProtocol()->event( 'docs_code_copy', [ 'visitor_id' => 'visitor-123' ] );

	expect( sentPayload()['client_id'] )->toBe( 'visitor-123' );
} );

test( 'a missing visitor id falls back to a generated client id', function (): void {
	measurementProtocol()->event( 'docs_code_copy', [] );

	expect( sentPayload()['client_id'] )->toMatch( '/^\d{9}\.\d+$/' );
} );

test( 'event names are normalized to GA4 rules', function ( string $input, string $expected ): void {
	measurementProtocol()->event( $input, [] );

	expect( sentPayload()['events'][0]['name'] )->toBe( $expected );
} )->with( [
	'spaces and dots'   => [ 'docs.code copy', 'docs_code_copy' ],
	'leading digit'     => [ '2fa_enabled', '_2fa_enabled' ],
	'punctuation'       => [ 'checkout:step-1', 'checkout_step_1' ],
	'already valid'     => [ 'docs_code_copy', 'docs_code_copy' ],
	'over forty chars'  => [ str_repeat( 'a', 60 ), str_repeat( 'a', 40 ) ],
] );

test( 'event properties are capped at the GA4 parameter limit', function (): void {
	// GA4 rejects events carrying more than 25 parameters. Sending 40 and
	// hoping is the kind of thing that fails silently in production.
	$properties = [];

	for ( $i = 0; $i < 40; $i++ ) {
		$properties[ 'prop_' . $i ] = $i;
	}

	measurementProtocol()->event( 'big_event', [ 'properties' => $properties ] );

	expect( count( sentPayload()['events'][0]['params'] ) )->toBeLessThanOrEqual( 25 );
} );

test( 'non-scalar event properties are dropped rather than sent', function (): void {
	measurementProtocol()->event( 'nested_event', [
		'properties' => [ 'ok' => 'yes', 'nested' => [ 'a' => 1 ] ],
	] );

	$params = sentPayload()['events'][0]['params'];

	expect( $params )->toHaveKey( 'ok' )->not->toHaveKey( 'nested' );
} );

test( 'nothing is sent without an API secret', function (): void {
	// The measurement ID alone is enough for the client-side tag but not for
	// the Measurement Protocol.
	config()->set( 'analytics-google.tracking.api_secret', null );

	expect( measurementProtocol()->isConfigured() )->toBeFalse();

	measurementProtocol()->pageView( [ 'path' => '/docs/one' ] );

	Http::assertNothingSent();
} );

test( 'nothing is sent without a measurement id', function (): void {
	config()->set( 'analytics-google.tracking.measurement_id', null );

	measurementProtocol()->pageView( [ 'path' => '/docs/one' ] );

	Http::assertNothingSent();
} );

test( 'server side forwarding can be switched off independently', function (): void {
	config()->set( 'analytics-google.tracking.server_side', false );

	expect( measurementProtocol()->isConfigured() )->toBeFalse();

	measurementProtocol()->pageView( [ 'path' => '/docs/one' ] );

	Http::assertNothingSent();
} );

test( 'debug mode targets the validation endpoint', function (): void {
	config()->set( 'analytics-google.tracking.debug', true );

	measurementProtocol()->pageView( [ 'path' => '/docs/one' ] );

	expect( sentUrl() )->toContain( '/debug/mp/collect' );
} );

test( 'credentials are sent as query parameters', function (): void {
	measurementProtocol()->pageView( [ 'path' => '/docs/one' ] );

	expect( sentUrl() )
		->toContain( 'measurement_id=G-TESTID123' )
		->toContain( 'api_secret=test-secret' );
} );

test( 'a transport failure never propagates to the caller', function (): void {
	// This runs on the ingest request path with a visitor waiting on a beacon
	// response. GA4 being down must degrade to lost hits, never to a failed
	// request in the host application.
	Http::fake( [ 'www.google-analytics.com/*' => fn () => throw new RuntimeException( 'network down' ) ] );

	measurementProtocol()->pageView( [ 'path' => '/docs/one' ] );

	expect( true )->toBeTrue();
} );

test( 'a non-2xx response never propagates to the caller', function (): void {
	Http::fake( [ 'www.google-analytics.com/*' => Http::response( 'bad request', 400 ) ] );

	measurementProtocol()->event( 'docs_code_copy', [] );

	expect( true )->toBeTrue();
} );

test( 'the provider adapter forwards page views through the Measurement Protocol', function (): void {
	// Until 1.1.0 this method was an intentional no-op, so adding google-ga4
	// to the parent's active_providers registered a provider that sent
	// nothing and reported no error.
	$adapter = new Ga4AnalyticsProviderAdapter(
		app( Ga4Provider::class ),
		'google-ga4',
		measurementProtocol(),
	);

	$adapter->trackPageView( new PageViewData(
		path: '/docs/getting-started',
		title: 'Getting Started',
		referrer: 'https://duckduckgo.com/',
		sessionId: 'session-xyz',
		visitorId: 'visitor-abc',
		utmSource: 'newsletter',
	) );

	$payload = sentPayload();

	expect( $payload['client_id'] )->toBe( 'visitor-abc' )
		->and( $payload['events'][0]['name'] )->toBe( 'page_view' )
		->and( $payload['events'][0]['params']['page_location'] )->toBe( 'https://docs.example.test/docs/getting-started' )
		->and( $payload['events'][0]['params']['source'] )->toBe( 'newsletter' );
} );

test( 'the provider adapter forwards events through the Measurement Protocol', function (): void {
	$adapter = new Ga4AnalyticsProviderAdapter(
		app( Ga4Provider::class ),
		'google-ga4',
		measurementProtocol(),
	);

	$adapter->trackEvent( new EventData(
		name: 'docs.code copy',
		properties: [ 'content_key' => 'core/getting-started' ],
		sessionId: 'session-xyz',
		visitorId: 'visitor-abc',
		path: '/docs/getting-started',
		value: 4.0,
		category: 'docs',
	) );

	$payload = sentPayload();

	expect( $payload['events'][0]['name'] )->toBe( 'docs_code_copy' )
		->and( $payload['events'][0]['params']['event_category'] )->toBe( 'docs' )
		// Loose comparison: JSON encodes 4.0 as `4`, so the round-trip yields
		// an int. GA4 accepts either, and pinning the PHP type here would be
		// asserting on json_encode rather than on behaviour.
		->and( $payload['events'][0]['params']['value'] )->toEqual( 4.0 )
		->and( $payload['events'][0]['params']['content_key'] )->toBe( 'core/getting-started' );
} );

test( 'the adapter reports enabled when only server side forwarding is configured', function (): void {
	// The parent drops providers reporting false from getActiveProviders(),
	// so answering on the client-side tag alone would silently disable
	// forwarding for anyone who turned the snippet off.
	config()->set( 'analytics-google.tracking.enabled', false );

	$adapter = new Ga4AnalyticsProviderAdapter(
		app( Ga4Provider::class ),
		'google-ga4',
		measurementProtocol(),
	);

	expect( $adapter->isEnabled() )->toBeTrue();
} );

test( 'the adapter still works when no Measurement Protocol client is supplied', function (): void {
	// The constructor argument is optional so existing instantiations keep
	// working; without a client the methods must no-op rather than fatal.
	$adapter = new Ga4AnalyticsProviderAdapter( app( Ga4Provider::class ), 'google-ga4' );

	$adapter->trackPageView( new PageViewData( path: '/docs/one' ) );
	$adapter->trackEvent( new EventData( name: 'noop' ) );

	Http::assertNothingSent();
} );
