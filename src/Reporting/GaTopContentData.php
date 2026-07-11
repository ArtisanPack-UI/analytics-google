<?php

/**
 * Data object backing the top pages and events surface.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\Reporting;

/**
 * Shape of the payload the GA top-pages-and-events surfaces consume.
 *
 * @since 1.0.0
 */
final class GaTopContentData
{
    /**
     * @param  list<array{path: string, title: string, views: float, users: float}>  $topPages
     * @param  list<array{event: string, count: float, users: float}>  $topEvents
     */
    public function __construct(
        public readonly array $topPages,
        public readonly array $topEvents,
        public readonly DateRange $range,
    ) {
    }

    /**
     * Convert into the JSON shape expected by the React/Vue components.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'range'      => $this->range->toArray(),
            'top_pages'  => $this->topPages,
            'top_events' => $this->topEvents,
        ];
    }
}
