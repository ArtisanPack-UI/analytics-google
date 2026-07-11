<?php

/**
 * Shared fetch logic for the GA overview surfaces.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\Reporting;

use ArtisanPackUI\Google\Models\GoogleConnection;
use Illuminate\Support\Carbon;

/**
 * Pulls the four headline metrics (sessions, users, page views, avg
 * engagement time) and a daily trend from the GA4 Data API for a
 * configurable date range.
 *
 * Shared by the Livewire component and the HTTP endpoint that powers
 * the React and Vue components so all three surfaces see identical
 * numbers for the same input.
 *
 * @since 1.0.0
 */
class GaOverviewFetcher
{
    private const METRIC_SESSIONS      = 'sessions';

    private const METRIC_USERS         = 'totalUsers';

    private const METRIC_PAGE_VIEWS    = 'screenPageViews';

    private const METRIC_AVG_ENGAGE    = 'averageSessionDuration';

    public function __construct(
        protected Ga4DataClient $client,
    ) {
    }

    /**
     * Fetch overview totals and daily trend for the given range.
     *
     * @since 1.0.0
     */
    public function fetch( GoogleConnection $connection, DateRange $range, ?string $propertyId = null ): GaOverviewData
    {
        $totals = $this->client->runReport(
            ReportRequest::make(
                range: $range,
                metrics: [
                    self::METRIC_SESSIONS,
                    self::METRIC_USERS,
                    self::METRIC_PAGE_VIEWS,
                    self::METRIC_AVG_ENGAGE,
                ],
            ),
            $connection,
            $propertyId,
        );

        $trend = $this->client->runReport(
            ReportRequest::make(
                range: $range,
                metrics: [
                    self::METRIC_SESSIONS,
                    self::METRIC_USERS,
                    self::METRIC_PAGE_VIEWS,
                ],
                dimensions: [ 'date' ],
            ),
            $connection,
            $propertyId,
        );

        return new GaOverviewData(
            totals: $this->parseTotals( $totals ),
            trend: $this->parseTrend( $trend ),
            range: $range,
        );
    }

    /**
     * @return array{sessions: float, users: float, page_views: float, avg_engagement_seconds: float}
     */
    protected function parseTotals( ReportResponse $response ): array
    {
        $rows  = $response->rows();
        $first = $rows[0] ?? [];

        return [
            'sessions'               => (float) ( $first[ self::METRIC_SESSIONS ] ?? 0 ),
            'users'                  => (float) ( $first[ self::METRIC_USERS ] ?? 0 ),
            'page_views'             => (float) ( $first[ self::METRIC_PAGE_VIEWS ] ?? 0 ),
            'avg_engagement_seconds' => (float) ( $first[ self::METRIC_AVG_ENGAGE ] ?? 0 ),
        ];
    }

    /**
     * @return list<array{date: string, sessions: float, users: float, page_views: float}>
     */
    protected function parseTrend( ReportResponse $response ): array
    {
        $rows = $response->rows();
        $out  = [];

        foreach ( $rows as $row ) {
            $out[] = [
                'date'       => $this->normalizeDate( $row['date'] ?? '' ),
                'sessions'   => (float) ( $row[ self::METRIC_SESSIONS ] ?? 0 ),
                'users'      => (float) ( $row[ self::METRIC_USERS ] ?? 0 ),
                'page_views' => (float) ( $row[ self::METRIC_PAGE_VIEWS ] ?? 0 ),
            ];
        }

        usort( $out, static fn ( array $a, array $b ): int => strcmp( (string) $a['date'], (string) $b['date'] ) );

        return $out;
    }

    /**
     * GA4 returns "date" as YYYYMMDD; reshape to YYYY-MM-DD for display.
     * Parses via Carbon so impossible calendar dates (e.g. "20260230")
     * are rejected instead of reshaped into invalid output.
     */
    protected function normalizeDate( string $raw ): string
    {
        if ( 8 !== strlen( $raw ) || ! ctype_digit( $raw ) ) {
            return $raw;
        }

        $parsed = Carbon::createFromFormat( '!Ymd', $raw );

        if ( null === $parsed || $parsed->format( 'Ymd' ) !== $raw ) {
            return $raw;
        }

        return $parsed->format( 'Y-m-d' );
    }
}
