<?php

/**
 * GA4 Data API client.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\Reporting;

use ArtisanPackUI\AnalyticsGoogle\Exceptions\BaseNotInstalledException;
use ArtisanPackUI\AnalyticsGoogle\Exceptions\ReportingException;
use ArtisanPackUI\AnalyticsGoogle\Support\BaseInstalled;
use ArtisanPackUI\Google\Exceptions\TokenRefreshException;
use ArtisanPackUI\Google\Models\GoogleConnection;
use ArtisanPackUI\Google\Tokens\TokenManager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;

/**
 * Server-side client for the GA4 Data API.
 *
 * Runs `runReport` requests against a configured property using an
 * access token from the base google package's TokenManager.
 * Callers should catch {@see ReportingException} for API/config
 * errors and {@see BaseNotInstalledException} for the case where the
 * base package is missing (that case usually indicates a deployment
 * gap the operator needs to see, not a runtime failure to swallow).
 *
 * @since 1.0.0
 */
class Ga4DataClient
{
    public function __construct(
        protected ConfigRepository $config,
        protected HttpFactory $http,
        protected ?TokenManager $tokens = null,
        protected ?CacheRepository $cache = null,
    ) {
    }

    /**
     * Execute a runReport request against the configured property.
     *
     * @since 1.0.0
     *
     * @param  ReportRequest  $request  The report parameters.
     * @param  GoogleConnection  $connection  A connected Google account
     *                                        authorized for the property.
     * @param  string|null  $propertyId  Optional property override; falls
     *                                   back to the configured property.
     *
     * @throws BaseNotInstalledException
     * @throws ReportingException
     */
    public function runReport(
        ReportRequest $request,
        GoogleConnection $connection,
        ?string $propertyId = null,
    ): ReportResponse {
        $this->ensureBaseInstalled();

        $property = (string) ( $propertyId ?? $this->config->get( 'analytics-google.reporting.property_id' ) ?? '' );

        if ( '' === $property ) {
            throw ReportingException::missingConfiguration( 'analytics-google.reporting.property_id' );
        }

        if ( null === $this->tokens ) {
            throw BaseNotInstalledException::forReporting();
        }

        $payload  = $request->toApiPayload();
        $cacheKey = $this->cacheKey( $connection, $property, $payload );
        $cacheTtl = (int) $this->config->get( 'analytics-google.reporting.cache_ttl', 0 );

        if ( $cacheTtl > 0 && null !== $this->cache && $this->cache->has( $cacheKey ) ) {
            $cached = $this->cache->get( $cacheKey );

            if ( is_array( $cached ) ) {
                return new ReportResponse( $cached );
            }
        }

        try {
            $accessToken = $this->tokens->getValidAccessToken( $connection );
        } catch ( TokenRefreshException $e ) {
            throw ReportingException::authenticationFailed( $e );
        }

        $apiBase = (string) $this->config->get(
            'analytics-google.reporting.api_base',
            'https://analyticsdata.googleapis.com/v1beta',
        );

        $endpoint = sprintf( '%s/properties/%s:runReport', rtrim( $apiBase, '/' ), rawurlencode( $property ) );
        $timeout  = (int) $this->config->get( 'analytics-google.reporting.timeout', 30 );

        try {
            $response = $this->http
                ->timeout( $timeout )
                ->withToken( $accessToken )
                ->acceptJson()
                ->asJson()
                ->post( $endpoint, $payload );
        } catch ( ConnectionException $e ) {
            throw ReportingException::transportFailure( $e );
        }

        if ( ! $response->successful() ) {
            throw ReportingException::apiError( $response->status(), (string) $response->body() );
        }

        $body = $response->json();
        $body = is_array( $body ) ? $body : [];

        if ( $cacheTtl > 0 && null !== $this->cache ) {
            $this->cache->put( $cacheKey, $body, $cacheTtl );
        }

        return new ReportResponse( $body );
    }

    /**
     * Whether the reporting side is usable in the current environment.
     *
     * Callers can use this to hide reporting UI cleanly when the base
     * package is not installed rather than catching an exception.
     *
     * @since 1.0.0
     */
    public function isAvailable(): bool
    {
        return BaseInstalled::check() && null !== $this->tokens;
    }

    /**
     * Deterministic cache key for a (connection, property, payload)
     * tuple. The connection identity is included so distinct users
     * cannot see each other's cached rows.
     *
     * @param  array<string, mixed>  $payload
     *
     * @since 1.0.0
     */
    protected function cacheKey( GoogleConnection $connection, string $property, array $payload ): string
    {
        $connectionId = (string) ( $connection->getKey() ?? $connection->google_user_id ?? 'anon' );
        $hash         = hash( 'sha256', $property . '|' . $connectionId . '|' . json_encode( $payload ) );

        return 'analytics-google:runReport:' . $hash;
    }

    /**
     * Throw when the base package's public surface is not loadable.
     *
     * Intentionally checks {@see BaseInstalled} instead of relying on the
     * $tokens null-check alone so the failure mode is the same whether
     * the whole package is missing or the service-provider binding was
     * skipped. The token manager may be null even when the base is
     * installed if the parent app has excluded the provider.
     */
    protected function ensureBaseInstalled(): void
    {
        if ( ! BaseInstalled::check() ) {
            throw BaseNotInstalledException::forReporting();
        }
    }
}
