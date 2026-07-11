<?php

/**
 * Fetch logic for the GA top pages and events surface.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\Reporting;

use ArtisanPackUI\Google\Models\GoogleConnection;

/**
 * Pulls the top pages (by page views) and top events (by count) from
 * the GA4 Data API for a configurable date range.
 *
 * Shared by the Livewire component and the HTTP endpoint that powers
 * the React and Vue components so all three surfaces see identical
 * numbers for the same input.
 *
 * @since 1.0.0
 */
class GaTopContentFetcher
{

    /**
     * Default upper bound on rows returned per list. GA4 will happily
     * return thousands; the surface only shows a handful so the fetch
     * stays cheap and cacheable.
     */
    public const DEFAULT_LIMIT      = 10;
    private const METRIC_PAGE_VIEWS = 'screenPageViews';

    private const METRIC_EVENT_COUNT = 'eventCount';

    private const METRIC_USERS = 'totalUsers';

    private const DIMENSION_PAGE_PATH = 'pagePath';

    private const DIMENSION_PAGE_TITLE = 'pageTitle';

    private const DIMENSION_EVENT_NAME = 'eventName';

    public function __construct(
        protected Ga4DataClient $client,
    ) {
    }

    /**
     * Fetch top pages and top events for the given range.
     *
     * @since 1.0.0
     */
    public function fetch(
        GoogleConnection $connection,
        DateRange $range,
        ?string $propertyId = null,
        int $limit = self::DEFAULT_LIMIT,
    ): GaTopContentData {
        $limit = $this->clampLimit( $limit );

        $pages = $this->client->runReport(
            ReportRequest::make(
                range: $range,
                metrics: [
                    self::METRIC_PAGE_VIEWS,
                    self::METRIC_USERS,
                ],
                dimensions: [
                    self::DIMENSION_PAGE_PATH,
                    self::DIMENSION_PAGE_TITLE,
                ],
                orderBys: [
                    [ 'metric' => self::METRIC_PAGE_VIEWS, 'desc' => true ],
                ],
                limit: $limit,
            ),
            $connection,
            $propertyId,
        );

        $events = $this->client->runReport(
            ReportRequest::make(
                range: $range,
                metrics: [
                    self::METRIC_EVENT_COUNT,
                    self::METRIC_USERS,
                ],
                dimensions: [
                    self::DIMENSION_EVENT_NAME,
                ],
                orderBys: [
                    [ 'metric' => self::METRIC_EVENT_COUNT, 'desc' => true ],
                ],
                limit: $limit,
            ),
            $connection,
            $propertyId,
        );

        return new GaTopContentData(
            topPages: $this->parsePages( $pages ),
            topEvents: $this->parseEvents( $events ),
            range: $range,
        );
    }

    /**
     * @return list<array{path: string, title: string, views: float, users: float}>
     */
    protected function parsePages( ReportResponse $response ): array
    {
        $out = [];

        foreach ( $response->rows() as $row ) {
            $out[] = [
                'path'  => (string) ( $row[ self::DIMENSION_PAGE_PATH ] ?? '' ),
                'title' => (string) ( $row[ self::DIMENSION_PAGE_TITLE ] ?? '' ),
                'views' => (float) ( $row[ self::METRIC_PAGE_VIEWS ] ?? 0 ),
                'users' => (float) ( $row[ self::METRIC_USERS ] ?? 0 ),
            ];
        }

        usort( $out, static fn ( array $a, array $b ): int => $b['views'] <=> $a['views'] );

        return $out;
    }

    /**
     * @return list<array{event: string, count: float, users: float}>
     */
    protected function parseEvents( ReportResponse $response ): array
    {
        $out = [];

        foreach ( $response->rows() as $row ) {
            $out[] = [
                'event' => (string) ( $row[ self::DIMENSION_EVENT_NAME ] ?? '' ),
                'count' => (float) ( $row[ self::METRIC_EVENT_COUNT ] ?? 0 ),
                'users' => (float) ( $row[ self::METRIC_USERS ] ?? 0 ),
            ];
        }

        usort( $out, static fn ( array $a, array $b ): int => $b['count'] <=> $a['count'] );

        return $out;
    }

    /**
     * Clamp a caller-supplied limit into a sane range so we never send
     * `limit: 0` or absurd values through to the Data API.
     */
    protected function clampLimit( int $limit ): int
    {
        return max( 1, min( $limit, 100 ) );
    }
}
