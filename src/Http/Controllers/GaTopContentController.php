<?php

/**
 * HTTP controller powering the React and Vue GA top-content components.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\Http\Controllers;

use ArtisanPackUI\AnalyticsGoogle\Exceptions\BaseNotInstalledException;
use ArtisanPackUI\AnalyticsGoogle\Exceptions\ReportingException;
use ArtisanPackUI\AnalyticsGoogle\Reporting\DateRange;
use ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient;
use ArtisanPackUI\AnalyticsGoogle\Reporting\GaTopContentFetcher;
use ArtisanPackUI\AnalyticsGoogle\Support\BaseInstalled;
use ArtisanPackUI\AnalyticsGoogle\Support\GoogleConnectionResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Returns the top pages + top events payload the React and Vue
 * components render. Server-authoritative so both frameworks see
 * identical numbers.
 *
 * @since 1.0.0
 */
class GaTopContentController
{
    public function __invoke( Request $request ): JsonResponse
    {
        if ( ! BaseInstalled::check() ) {
            return response()->json( [
                'error'         => 'base_not_installed',
                'message'       => __( 'The GA4 top content view requires the artisanpack-ui/google base package.' ),
                'baseInstalled' => false,
            ], 501 );
        }

        $days  = $this->clampDays( $request->query( 'days', 30 ) );
        $limit = $this->clampLimit( $request->query( 'limit', GaTopContentFetcher::DEFAULT_LIMIT ) );

        $propertyIdInput = $request->query( 'property_id' );
        $propertyId      = is_string( $propertyIdInput ) && '' !== $propertyIdInput ? $propertyIdInput : null;

        $user = $request->user();

        if ( null === $user ) {
            return response()->json( [
                'error'   => 'unauthenticated',
                'message' => __( 'Sign in to view GA4 analytics.' ),
            ], 401 );
        }

        $connection = app( GoogleConnectionResolver::class )->forUser( $user );

        if ( null === $connection ) {
            return response()->json( [
                'error'   => 'not_connected',
                'message' => __( 'Connect a Google account to view analytics.' ),
            ], 409 );
        }

        try {
            /** @var Ga4DataClient $client */
            $client  = app( Ga4DataClient::class );
            $fetcher = new GaTopContentFetcher( $client );
            $data    = $fetcher->fetch( $connection, DateRange::lastDays( $days ), $propertyId, $limit );

            return response()->json( $data->toArray() );
        } catch ( BaseNotInstalledException $e ) {
            return response()->json( [
                'error'   => 'base_not_installed',
                'message' => $e->getMessage(),
            ], 501 );
        } catch ( ReportingException $e ) {
            return response()->json( [
                'error'   => 'reporting_error',
                'message' => $e->getMessage(),
            ], 502 );
        }
    }

    protected function clampDays( mixed $raw ): int
    {
        if ( ! is_scalar( $raw ) ) {
            return 30;
        }

        $days = (int) $raw;

        if ( $days < 1 ) {
            return 1;
        }

        return min( $days, DateRange::MAX_DAYS );
    }

    protected function clampLimit( mixed $raw ): int
    {
        if ( ! is_scalar( $raw ) ) {
            return GaTopContentFetcher::DEFAULT_LIMIT;
        }

        $limit = (int) $raw;

        if ( $limit < 1 ) {
            return 1;
        }

        return min( $limit, 100 );
    }
}
