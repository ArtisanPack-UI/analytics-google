<?php

/**
 * GA4 Measurement Protocol client.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\Tracking;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends events to GA4 over the Measurement Protocol.
 *
 * Deliberately takes primitives rather than the analytics parent's DTOs, so
 * this class is usable — and testable — whether or not that package is
 * installed. The adapter that does own those DTOs maps them onto these calls.
 *
 * Every send is best-effort. This runs on the ingest request path, where a
 * visitor is waiting on a beacon response, so a GA4 outage must degrade to
 * "GA4 missed some hits" and never to "the host application is slow or
 * throwing".
 *
 * @since 1.1.0
 */
class MeasurementProtocol
{
	/**
	 * The Measurement Protocol collection endpoint.
	 *
	 * @var string
	 */
	protected const COLLECT_URL = 'https://www.google-analytics.com/mp/collect';

	/**
	 * The validation endpoint. Reports payload problems that the live
	 * endpoint accepts silently.
	 *
	 * @var string
	 */
	protected const DEBUG_URL = 'https://www.google-analytics.com/debug/mp/collect';

	/**
	 * GA4's per-event parameter ceiling.
	 *
	 * @var int
	 */
	protected const MAX_EVENT_PARAMS = 25;

	/**
	 * GA4's event name length ceiling.
	 *
	 * @var int
	 */
	protected const MAX_EVENT_NAME_LENGTH = 40;

	/**
	 * GA4's parameter name length ceiling.
	 *
	 * @var int
	 */
	protected const MAX_PARAM_NAME_LENGTH = 40;

	/**
	 * Prefix applied to names GA4 would reject for starting with something
	 * other than a letter, or for using a reserved prefix. Deliberately not
	 * `ga_`, `google_` or `firebase_`, all of which are themselves reserved.
	 *
	 * @var string
	 */
	protected const NAME_PREFIX = 'e_';

	public function __construct(
		protected ConfigRepository $config,
		protected HttpFactory $http,
	) {
	}

	/**
	 * Whether server-side forwarding is configured and switched on.
	 *
	 * Both credentials are required. A measurement ID alone is enough for the
	 * client-side tag but not for the Measurement Protocol, and silently
	 * sending nothing because the secret is missing is the failure mode this
	 * package has already been bitten by — callers should surface this.
	 *
	 * @since 1.1.0
	 */
	public function isConfigured(): bool
	{
		if ( ! (bool) $this->config->get( 'analytics-google.tracking.server_side', true ) ) {
			return false;
		}

		return '' !== $this->measurementId() && '' !== $this->apiSecret();
	}

	/**
	 * Forward a page view.
	 *
	 * @param array{path?: string|null, title?: string|null, referrer?: string|null, visitor_id?: string|null, session_id?: string|null, utm_source?: string|null, utm_medium?: string|null, utm_campaign?: string|null} $data Page view fields.
	 *
	 * @since 1.1.0
	 */
	public function pageView( array $data ): void
	{
		if ( ! $this->isConfigured() ) {
			return;
		}

		$params = [
			// GA4 expects a URL here, not a path. Sending a bare path leaves
			// hostname and page-path reporting empty.
			'page_location'        => $this->pageLocation( $data['path'] ?? null ),
			'page_title'           => $data['title'] ?? '',
			'engagement_time_msec' => 1,
		];

		if ( ! empty( $data['referrer'] ) ) {
			$params['page_referrer'] = (string) $data['referrer'];
		}

		foreach ( [ 'utm_source' => 'source', 'utm_medium' => 'medium', 'utm_campaign' => 'campaign' ] as $key => $mapped ) {
			if ( ! empty( $data[ $key ] ) ) {
				$params[ $mapped ] = (string) $data[ $key ];
			}
		}

		$this->send( $data, 'page_view', $params );
	}

	/**
	 * Forward a custom event.
	 *
	 * @param string                                                                                                     $name Event name.
	 * @param array{properties?: array<string, mixed>|null, category?: string|null, value?: float|null, path?: string|null, visitor_id?: string|null, session_id?: string|null} $data Event fields.
	 *
	 * @since 1.1.0
	 */
	public function event( string $name, array $data ): void
	{
		if ( ! $this->isConfigured() ) {
			return;
		}

		$name = $this->normalizeEventName( $name );

		if ( '' === $name ) {
			return;
		}

		$params = [ 'engagement_time_msec' => 1 ];

		if ( ! empty( $data['category'] ) ) {
			$params['event_category'] = (string) $data['category'];
		}

		if ( isset( $data['value'] ) && null !== $data['value'] ) {
			$params['value'] = (float) $data['value'];
		}

		if ( ! empty( $data['path'] ) ) {
			$params['page_location'] = $this->pageLocation( $data['path'] );
		}

		foreach ( (array) ( $data['properties'] ?? [] ) as $key => $value ) {
			if ( count( $params ) >= self::MAX_EVENT_PARAMS ) {
				break;
			}

			if ( ! is_scalar( $value ) && null !== $value ) {
				continue;
			}

			$params[ $this->normalizeParamName( (string) $key ) ] = $value;
		}

		$this->send( $data, $name, $params );
	}

	/**
	 * Build and dispatch a Measurement Protocol payload.
	 *
	 * @param array<string, mixed> $data   Source fields, used for the client ID.
	 * @param string               $name   The GA4 event name.
	 * @param array<string, mixed> $params The GA4 event parameters.
	 *
	 * @since 1.1.0
	 */
	protected function send( array $data, string $name, array $params ): void
	{
		$payload = [
			'client_id' => $this->clientId( $data['visitor_id'] ?? null ),
			'events'    => [
				[
					'name'   => $name,
					'params' => $params,
				],
			],
		];

		$sessionId = $this->sessionId( $data['session_id'] ?? null );

		if ( null !== $sessionId ) {
			$payload['events'][0]['params']['session_id'] = $sessionId;
		}

		$url = sprintf(
			'%s?measurement_id=%s&api_secret=%s',
			$this->config->get( 'analytics-google.tracking.debug', false ) ? self::DEBUG_URL : self::COLLECT_URL,
			rawurlencode( $this->measurementId() ),
			rawurlencode( $this->apiSecret() ),
		);

		try {
			$response = $this->http
				->timeout( (int) $this->config->get( 'analytics-google.tracking.timeout', 3 ) )
				->asJson()
				->post( $url, $payload );

			// The live endpoint returns 204 for anything it accepts, including
			// payloads it then discards, so a non-2xx is the only transport-level
			// signal worth reporting.
			if ( $response->failed() ) {
				Log::warning( 'GA4 Measurement Protocol request failed', [
					'status' => $response->status(),
					'event'  => $name,
				] );

				return;
			}

			// The validation endpoint answers 200 with a validationMessages
			// array describing what it would have rejected. Reading only the
			// status code there would make debug mode report nothing, which is
			// the opposite of what it is for.
			$validationMessages = $response->json( 'validationMessages' );

			if ( is_array( $validationMessages ) && [] !== $validationMessages ) {
				Log::warning( 'GA4 Measurement Protocol rejected the payload', [
					'event'    => $name,
					'messages' => $validationMessages,
				] );
			}
		} catch ( Throwable $e ) {
			// Never let GA4 take the host application's request with it.
			Log::warning( 'GA4 Measurement Protocol request errored', [
				'error' => $e->getMessage(),
				'event' => $name,
			] );
		}
	}

	/**
	 * Resolve the GA4 client ID.
	 *
	 * Reuses the parent's visitor ID so every hit from one visitor groups into
	 * a single GA4 user. GA4 documents `client_id` as a string with no format
	 * requirement, so an opaque value such as a UUID is passed through
	 * unchanged: stability across a visitor's hits is what makes user counts
	 * meaningful, and rewriting a stable ID into GA's conventional
	 * `<random>.<timestamp>` shape would either break that stability or be a
	 * pointless deterministic re-encoding.
	 *
	 * The one thing the conventional format buys — joining server-side hits to
	 * a gtag.js `_ga` cookie — is not available to us anyway: that value is not
	 * knowable here, and running both surfaces against the same events
	 * double-counts regardless.
	 *
	 * Without a visitor ID the fallback is a per-hit random ID, which GA4 reads
	 * as a separate user each time. That consequence is accepted knowingly and
	 * documented.
	 *
	 * @since 1.1.0
	 */
	protected function clientId( ?string $visitorId ): string
	{
		if ( is_string( $visitorId ) && '' !== trim( $visitorId ) ) {
			return trim( $visitorId );
		}

		return sprintf( '%d.%d', random_int( 100000000, 999999999 ), time() );
	}

	/**
	 * Resolve a GA4-acceptable session ID, or null to omit it.
	 *
	 * Unlike `client_id`, GA4 is strict here: `session_id` must match `^\d+$`.
	 * The parent's session identifiers are UUIDs, which do not, and sending
	 * one produces a rejected or mis-attributed hit rather than an error.
	 *
	 * Omitting it lets GA4 derive its own session. That loses the parent's
	 * session grouping, but a session GA4 builds itself is more useful than
	 * one it refuses, and the alternative fails invisibly.
	 *
	 * @since 1.1.0
	 */
	protected function sessionId( mixed $sessionId ): ?string
	{
		if ( ! is_string( $sessionId ) && ! is_int( $sessionId ) ) {
			return null;
		}

		$sessionId = trim( (string) $sessionId );

		// Digits only, and non-zero. `ctype_digit` alone would accept "0".
		if ( 1 !== preg_match( '/^\d+$/', $sessionId ) || '0' === ltrim( $sessionId, '0' ) || '' === ltrim( $sessionId, '0' ) ) {
			return null;
		}

		return $sessionId;
	}

	/**
	 * Build an absolute URL for `page_location`.
	 *
	 * @since 1.1.0
	 */
	protected function pageLocation( ?string $path ): string
	{
		$path = (string) ( $path ?? '' );

		// Already absolute — pass it through untouched.
		if ( 1 === preg_match( '#^https?://#i', $path ) ) {
			return $path;
		}

		$base = (string) (
			$this->config->get( 'analytics-google.tracking.page_location_base' )
			?? $this->config->get( 'app.url', '' )
		);

		if ( '' === $base ) {
			return $path;
		}

		return rtrim( $base, '/' ) . '/' . ltrim( $path, '/' );
	}

	/**
	 * Normalize an event name to GA4's rules.
	 *
	 * @since 1.1.0
	 */
	protected function normalizeEventName( string $name ): string
	{
		return $this->normalizeName( $name, self::MAX_EVENT_NAME_LENGTH );
	}

	/**
	 * Normalize a parameter name to GA4's rules.
	 *
	 * @since 1.1.0
	 */
	protected function normalizeParamName( string $name ): string
	{
		return $this->normalizeName( $name, self::MAX_PARAM_NAME_LENGTH );
	}

	/**
	 * Coerce a name into something GA4 will accept.
	 *
	 * GA4's rules for both event and parameter names: alphanumerics and
	 * underscores only, and it must **start with a letter**. Names starting
	 * with an underscore, or with `ga_` / `google_` / `firebase_`, are
	 * reserved and rejected.
	 *
	 * The letter requirement is why a leading digit cannot simply be prefixed
	 * with an underscore — that swaps one rejected name for another. An
	 * alphabetic prefix is used instead, and the length cap is applied after
	 * prefixing so the result cannot exceed the limit.
	 *
	 * GA4 discards non-conforming events silently, so getting this wrong looks
	 * exactly like getting it right until the reports stay empty.
	 *
	 * @param string $name      The name to normalize.
	 * @param int    $maxLength The GA4 length ceiling for this kind of name.
	 *
	 * @since 1.1.0
	 */
	protected function normalizeName( string $name, int $maxLength ): string
	{
		$normalized = (string) preg_replace( '/[^a-zA-Z0-9_]/', '_', $name );

		if ( '' === $normalized ) {
			return '';
		}

		// Must start with a letter, and must not use a reserved prefix.
		if ( 1 !== preg_match( '/^[a-zA-Z]/', $normalized )
			|| 1 === preg_match( '/^(ga|google|firebase)_/i', $normalized ) ) {
			$normalized = self::NAME_PREFIX . $normalized;
		}

		return substr( $normalized, 0, $maxLength );
	}

	/**
	 * @since 1.1.0
	 */
	protected function measurementId(): string
	{
		return trim( (string) ( $this->config->get( 'analytics-google.tracking.measurement_id' ) ?? '' ) );
	}

	/**
	 * @since 1.1.0
	 */
	protected function apiSecret(): string
	{
		return trim( (string) ( $this->config->get( 'analytics-google.tracking.api_secret' ) ?? '' ) );
	}
}
