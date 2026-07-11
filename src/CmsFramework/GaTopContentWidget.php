<?php

/**
 * CMS framework AdminWidget wrapper for the GA top-content component.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\CmsFramework;

use ArtisanPackUI\AnalyticsGoogle\Livewire\GaTopContent;
use ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Contracts\AdminWidgetInterface;

/**
 * Registers the GA top pages + top events surface as an admin
 * dashboard widget for the `artisanpack-ui/cms-framework` package.
 *
 * See {@see GaOverviewWidget} for the class-loading contract.
 *
 * @since 1.0.0
 */
class GaTopContentWidget extends GaTopContent implements AdminWidgetInterface
{
    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     capability?: string,
     *     default_options?: array<string, mixed>
     * }
     */
    public static function getWidgetInfo(): array
    {
        return [
            'title'           => __( 'Top pages and events' ),
            'description'     => __( 'The busiest pages by view count and the most-fired events for a configurable date range.' ),
            'capability'      => 'view_analytics',
            'default_options' => [
                'days'       => 30,
                'limit'      => 10,
                'propertyId' => null,
            ],
        ];
    }
}
