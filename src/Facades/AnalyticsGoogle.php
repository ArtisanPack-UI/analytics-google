<?php

/**
 * AnalyticsGoogle Facade.
 *
 * Provides static access to the AnalyticsGoogle class.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * AnalyticsGoogle Facade.
 *
 * @see \ArtisanPackUI\AnalyticsGoogle\AnalyticsGoogle
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */
class AnalyticsGoogle extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @since 1.0.0
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'analytics-google';
    }
}
