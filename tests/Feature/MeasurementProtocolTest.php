<?php

declare( strict_types=1 );

use ArtisanPackUI\Analytics\Analytics;
use ArtisanPackUI\Analytics\Data\EventData;
use ArtisanPackUI\Analytics\Data\PageViewData;
use ArtisanPackUI\AnalyticsGoogle\Providers\Ga4AnalyticsProviderAdapter;
use ArtisanPackUI\AnalyticsGoogle\Providers\Ga4Provider;
use ArtisanPackUI\AnalyticsGoogle\Tracking\MeasurementProtocol;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach( function (): void {
	config()->set( 'app.url', 'https://docs.example.test' );
	config()->set( 'analytics-google.tracking.measurement_id', 'G-TESTID123' );
	config()->set( 'analytics-google.tracking.api_secret', 'test-secret' );
	config()->set( 'analytics-google.tracking.server_side', true );
	config()->set( 'analytics-google.tracking.debug', false );

	Http::preventStrayRequests();

	// One stub, registered once, resolving its response at call time.
	//
	// Http::fake() appends stubs and the first match wins, so a second
	// fake() for the same URL in a test is silently ignored — which made the
	// transport-failure tests here pass without ever exercising a failure.
	// Tests change the response through ga4Respond() instead.
	//
	// Held in a static rather than on the test case: a dynamic property on
	// TestCase is deprecated as of PHP 8.2.
	ga4Respond( Http::response( '', 204 ) );

	Http::fake( [
		'www.google-analytics.com/*' => function ( Request $request ) {
			$response = ga4Response();

			return is_callable( $response ) ? $response( $request ) : $response;
		},
	] );
} );

/**
 * Set the response the faked GA4 endpoint will return.
 *
 * Accepts a closure so a test can throw from the transport.
 */
function ga4Respond( mixed $response ): void
{
	ga4ResponseStore( $response );
}

/**
 * The response the faked GA4 endpoint is currently set to return.
 */
function ga4Response(): mixed
{
	return ga4ResponseStore();
}

/**
 * Backing store for the faked GA4 response.
 *
 * @param mixed $response The response to store, or nothing to read the current one.
 */
function ga4ResponseStore( mixed $response = null ): mixed
{
	static $stored = null;

	if ( 1 === func_num_args() ) {
		$stored = $response;
	}

	return $stored;
}

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
		// 'session-xyz' is not GA4-valid, so it is deliberately omitted —
		// covered directly by the session id tests below.
		->and( $payload['events'][0]['params'] )->not->toHaveKey( 'session_id' );
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

test( 'an empty page location base still falls back to app.url', function (): void {
	// `GA4_PAGE_LOCATION_BASE=` with no value reads as an empty string, not
	// null, so a null-coalescing fallback would leave the path bare — and a
	// bare page_location empties GA4's hostname and page-path reporting.
	config()->set( 'analytics-google.tracking.page_location_base', '' );

	measurementProtocol()->pageView( [ 'path' => '/docs/one' ] );

	expect( sentPayload()['events'][0]['params']['page_location'] )
		->toBe( 'https://docs.example.test/docs/one' );
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

test( 'an opaque visitor id is passed through unchanged as the client id', function (): void {
	// GA4 documents client_id as a string with no format requirement. What
	// matters is that one visitor keeps one value, so a UUID is passed through
	// rather than re-encoded into GA's conventional shape.
	measurementProtocol()->event( 'docs_code_copy', [
		'visitor_id' => '9f8c1c7e-2b1a-4c3d-8e5f-6a7b8c9d0e1f',
	] );

	expect( sentPayload()['client_id'] )->toBe( '9f8c1c7e-2b1a-4c3d-8e5f-6a7b8c9d0e1f' );
} );

test( 'a non-numeric session id is omitted rather than sent', function (): void {
	// GA4 requires session_id to match ^\d+$. The parent's session IDs are
	// UUIDs, and sending one produces a rejected or mis-attributed hit rather
	// than an error, so it is dropped and GA4 derives its own session.
	measurementProtocol()->pageView( [
		'path'       => '/docs/one',
		'session_id' => '11111111-2222-4333-8444-555555555555',
	] );

	expect( sentPayload()['events'][0]['params'] )->not->toHaveKey( 'session_id' );
} );

test( 'a numeric session id is forwarded', function (): void {
	measurementProtocol()->pageView( [ 'path' => '/docs/one', 'session_id' => '1724130000' ] );

	expect( sentPayload()['events'][0]['params']['session_id'] )->toBe( '1724130000' );
} );

test( 'a zero or partially numeric session id is rejected', function ( mixed $sessionId ): void {
	measurementProtocol()->pageView( [ 'path' => '/docs/one', 'session_id' => $sessionId ] );

	expect( sentPayload()['events'][0]['params'] )->not->toHaveKey( 'session_id' );
} )->with( [
	'zero'             => [ '0' ],
	'padded zero'      => [ '000' ],
	'digits then text' => [ '12345abc' ],
	'negative'         => [ '-1' ],
	'empty'            => [ '' ],
	'array'            => [ [ 'nope' ] ],
] );

test( 'validation messages from the debug endpoint are logged', function (): void {
	// The debug endpoint answers 200 with a validationMessages array. Reading
	// only the status code there would make debug mode report nothing, which
	// is the opposite of what it is for.
	config()->set( 'analytics-google.tracking.debug', true );

	ga4Respond( Http::response( [
		'validationMessages' => [
			[
				'fieldPath'      => 'events[0].name',
				'description'    => 'Event at index 0 has invalid name.',
				'validationCode' => 'NAME_INVALID',
			],
		],
	], 200 ) );

	Log::shouldReceive( 'warning' )
		->once()
		->withArgs( function ( string $message, array $context ): bool {
			return str_contains( $message, 'rejected the payload' )
				&& 'NAME_INVALID' === $context['messages'][0]['validationCode'];
		} );

	measurementProtocol()->pageView( [ 'path' => '/docs/one' ] );

	expect( true )->toBeTrue();
} );

test( 'an empty validation message list is not logged', function (): void {
	config()->set( 'analytics-google.tracking.debug', true );

	ga4Respond( Http::response( [ 'validationMessages' => [] ], 200 ) );

	Log::shouldReceive( 'warning' )->never();

	measurementProtocol()->pageView( [ 'path' => '/docs/one' ] );

	expect( true )->toBeTrue();
} );

test( 'event names are normalized to GA4 rules', function ( string $input, string $expected ): void {
	measurementProtocol()->event( $input, [] );

	expect( sentPayload()['events'][0]['name'] )->toBe( $expected );
} )->with( [
	'spaces and dots'      => [ 'docs.code copy', 'docs_code_copy' ],
	// GA4 requires a name to start with a *letter*. Prefixing a leading digit
	// with an underscore would swap one rejected name for another, because
	// a leading underscore is reserved too.
	'leading digit'        => [ '2fa_enabled', 'e_2fa_enabled' ],
	'leading underscore'   => [ '_internal_event', 'e__internal_event' ],
	// `ga_`, `google_` and `firebase_` are reserved prefixes.
	'reserved ga prefix'   => [ 'ga_session_start', 'e_ga_session_start' ],
	'reserved google'      => [ 'google_signup', 'e_google_signup' ],
	'punctuation'          => [ 'checkout:step-1', 'checkout_step_1' ],
	'already valid'        => [ 'docs_code_copy', 'docs_code_copy' ],
	'over forty chars'     => [ str_repeat( 'a', 60 ), str_repeat( 'a', 40 ) ],
	// The cap applies after prefixing, so the result cannot exceed 40 even
	// when the prefix is added.
	'prefixed and over'    => [ '9' . str_repeat( 'b', 60 ), 'e_9' . str_repeat( 'b', 37 ) ],
] );

test( 'normalized event names always satisfy GA4 rules', function ( string $input ): void {
	// Belt to the dataset above: whatever the input, the name that actually
	// leaves must be something GA4 will accept, since it discards
	// non-conforming events silently.
	measurementProtocol()->event( $input, [] );

	$name = sentPayload()['events'][0]['name'];

	expect( $name )->toMatch( '/^[a-zA-Z][a-zA-Z0-9_]*$/' )
		->and( strlen( $name ) )->toBeLessThanOrEqual( 40 )
		->and( $name )->not->toStartWith( 'ga_' )
		->and( $name )->not->toStartWith( 'google_' )
		->and( $name )->not->toStartWith( 'firebase_' );
} )->with( [
	'spaces and dots'      => 'docs.code copy',
	'leading digit'        => '2fa_enabled',
	'leading underscore'   => '_internal_event',
	'reserved ga prefix'   => 'ga_session_start',
	'reserved google'      => 'google_signup',
	'reserved firebase'    => 'firebase_init',
	'punctuation only'     => '---',
	'prefixed and over'    => '9xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
] );

test( 'an event with an empty name is dropped rather than sent', function (): void {
	// There is no name GA4 would accept here, so the hit is not worth making.
	measurementProtocol()->event( '', [] );

	Http::assertNothingSent();
} );

test( 'custom parameter names are normalized to GA4 rules too', function (): void {
	measurementProtocol()->event( 'docs_view', [
		'properties' => [ '2nd-attempt' => 'yes', 'ga_internal' => 'no', 'fine_name' => 'ok' ],
	] );

	$params = sentPayload()['events'][0]['params'];

	expect( $params )->toHaveKey( 'e_2nd_attempt' )
		->toHaveKey( 'e_ga_internal' )
		->toHaveKey( 'fine_name' );
} );

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

test( 'the parameter cap still holds once a valid session ID is added', function (): void {
	// The cap is applied to the properties, but `session_id` is appended
	// afterwards. Without a reserved slot that combination ships 26
	// parameters, and GA4 discards the event without saying so.
	$properties = [];

	for ( $i = 0; $i < 40; $i++ ) {
		$properties[ 'prop_' . $i ] = $i;
	}

	measurementProtocol()->event( 'big_event', [
		'properties' => $properties,
		'session_id' => '1712345678',
	] );

	$params = sentPayload()['events'][0]['params'];

	expect( count( $params ) )->toBeLessThanOrEqual( 25 )
		->and( $params )->toHaveKey( 'session_id' )
		->and( $params['session_id'] )->toBe( '1712345678' );
} );

test( 'a custom session_id property cannot bypass session ID validation', function (): void {
	// Straight through the property loop, this would reach GA4 unvalidated —
	// and GA4 rejects or mis-attributes a malformed session ID rather than
	// erroring.
	measurementProtocol()->event( 'sneaky_event', [
		'properties' => [ 'session_id' => 'not-a-number' ],
	] );

	expect( sentPayload()['events'][0]['params'] )->not->toHaveKey( 'session_id' );
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
	ga4Respond( fn () => throw new RuntimeException( 'network down' ) );

	Log::shouldReceive( 'warning' )
		->once()
		->withArgs( fn ( string $message ): bool => str_contains( $message, 'errored' ) );

	measurementProtocol()->pageView( [ 'path' => '/docs/one' ] );

	// Reaching here at all is the assertion: the throw was contained.
	expect( true )->toBeTrue();
} );

test( 'a non-2xx response never propagates to the caller', function (): void {
	ga4Respond( Http::response( 'bad request', 400 ) );

	Log::shouldReceive( 'warning' )
		->once()
		->withArgs( fn ( string $message, array $context ): bool => str_contains( $message, 'failed' ) && 400 === $context['status'] );

	measurementProtocol()->event( 'docs_code_copy', [] );

	expect( true )->toBeTrue();
} );

test( 'the API secret is redacted out of logged transport errors', function (): void {
	// The secret rides in the query string per Google's spec, and Guzzle puts
	// the full request URI in its transport exception messages. Logging one
	// verbatim writes a credential that can post events to the property into
	// the application log.
	ga4Respond( fn () => throw new RuntimeException( 'cURL error 28 for https://www.google-analytics.com/mp/collect?measurement_id=G-TESTID123&api_secret=test-secret' ) );

	Log::shouldReceive( 'warning' )
		->once()
		->withArgs( fn ( string $message, array $context ): bool => ! str_contains( $context['error'], 'test-secret' )
			&& str_contains( $context['error'], '***' ) );

	measurementProtocol()->pageView( [ 'path' => '/docs/one' ] );

	expect( true )->toBeTrue();
} );

test( 'the adapter registered with the analytics parent forwards to GA4', function (): void {
	// Every other adapter test constructs the adapter by hand, so dropping the
	// MeasurementProtocol argument from the service provider's registration
	// would leave them all green while forwarding silently stopped — exactly
	// the regression this release exists to fix.
	$adapter = app( Analytics::class )->provider( 'google-ga4' );

	$adapter->trackPageView( new PageViewData( path: '/docs/one' ) );

	expect( sentPayload()['events'][0]['name'] )->toBe( 'page_view' )
		->and( sentPayload()['events'][0]['params']['page_location'] )
		->toBe( 'https://docs.example.test/docs/one' );
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
