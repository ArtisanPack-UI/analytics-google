<?php

/**
 * HTTP controller powering the React and Vue GA overview components.
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
use ArtisanPackUI\AnalyticsGoogle\Reporting\GaOverviewFetcher;
use ArtisanPackUI\AnalyticsGoogle\Support\BaseInstalled;
use ArtisanPackUI\Google\Models\GoogleConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Returns the overview payload the React and Vue components render.
 * Server-authoritative so both frameworks see identical numbers.
 *
 * @since 1.0.0
 */
class GaOverviewController
{
    public function __invoke( Request $request ): JsonResponse
    {
        if ( ! BaseInstalled::check() ) {
            return response()->json( [
                'error'         => 'base_not_installed',
                'message'       => __( 'The GA4 overview requires the artisanpack-ui/google base package.' ),
                'baseInstalled' => false,
            ], 501 );
        }

        $days = max( 1, (int) $request->query( 'days', 30 ) );

        $propertyIdInput = $request->query( 'property_id' );
        $propertyId      = ( null === $propertyIdInput || '' === $propertyIdInput ) ? null : (string) $propertyIdInput;

        $user = $request->user();

        if ( null === $user ) {
            return response()->json( [
                'error'   => 'unauthenticated',
                'message' => __( 'Sign in to view GA4 analytics.' ),
            ], 401 );
        }

        /** @var GoogleConnection|null $connection */
        $connection = GoogleConnection::query()
            ->where( 'user_id', $user->getAuthIdentifier() )
            ->where( 'status', GoogleConnection::STATUS_CONNECTED )
            ->orderByDesc( 'updated_at' )
            ->first();

        if ( null === $connection ) {
            return response()->json( [
                'error'   => 'not_connected',
                'message' => __( 'Connect a Google account to view analytics.' ),
            ], 409 );
        }

        try {
            /** @var Ga4DataClient $client */
            $client   = app( Ga4DataClient::class );
            $fetcher  = new GaOverviewFetcher( $client );
            $overview = $fetcher->fetch( $connection, DateRange::lastDays( $days ), $propertyId );

            return response()->json( $overview->toArray() );
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
}
