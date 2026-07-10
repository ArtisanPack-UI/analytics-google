<?php

/**
 * AnalyticsGoogle package helper functions.
 *
 * Global helper functions for the AnalyticsGoogle package.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

use ArtisanPackUI\AnalyticsGoogle\AnalyticsGoogle;

if ( ! function_exists( 'analyticsGoogle' ) ) {
    /**
     * Get the AnalyticsGoogle instance.
     *
     * @since 1.0.0
     *
     * @return AnalyticsGoogle
     */
    function analyticsGoogle(): AnalyticsGoogle
    {
        return app( 'analytics-google' );
    }
}

// Add your custom helper functions below
