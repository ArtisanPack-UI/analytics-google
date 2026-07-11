<?php

/**
 * CMS framework AdminWidget wrapper for the GA overview component.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\CmsFramework;

use ArtisanPackUI\AnalyticsGoogle\Livewire\GaOverview;
use ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Contracts\AdminWidgetInterface;

/**
 * Registers the GA overview surface as an admin dashboard widget for
 * the `artisanpack-ui/cms-framework` package.
 *
 * Extends the underlying Livewire component so the widget renders
 * exactly the same UI when instantiated by the AdminWidgetManager and
 * dropped into the CMS admin dashboard.
 *
 * This class only loads when the CMS framework is installed — the
 * service provider guards registration with `class_exists` on the
 * contract so apps without the CMS framework never see this class.
 *
 * @since 1.0.0
 */
class GaOverviewWidget extends GaOverview implements AdminWidgetInterface
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
            'title'           => __( 'Google Analytics overview' ),
            'description'     => __( 'Sessions, users, page views, and average engagement time over a configurable date range.' ),
            'capability'      => 'view_analytics',
            'default_options' => [
                'days'       => 30,
                'propertyId' => null,
            ],
        ];
    }
}
