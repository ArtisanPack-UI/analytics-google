<?php

/**
 * Data object backing the GA overview components.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\Reporting;

/**
 * Shape of the payload each GA overview surface (Livewire, React, Vue)
 * consumes. The Livewire component renders it directly; the React and
 * Vue components pull the same shape from the overview HTTP endpoint.
 *
 * @since 1.0.0
 */
final class GaOverviewData
{
    /**
     * @param  array{sessions: float, users: float, page_views: float, avg_engagement_seconds: float}  $totals
     * @param  list<array{date: string, sessions: float, users: float, page_views: float}>  $trend
     */
    public function __construct(
        public readonly array $totals,
        public readonly array $trend,
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
            'range'  => $this->range->toArray(),
            'totals' => $this->totals,
            'trend'  => $this->trend,
        ];
    }
}
