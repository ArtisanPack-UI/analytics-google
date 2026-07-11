<?php

/**
 * AnalyticsGoogle package helper functions.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

use ArtisanPackUI\AnalyticsGoogle\AnalyticsGoogle;
use ArtisanPackUI\AnalyticsGoogle\Tracking\Gtag;

if ( ! function_exists( 'analyticsGoogle' ) ) {
    /**
     * Get the AnalyticsGoogle instance.
     *
     * @since 1.0.0
     */
    function analyticsGoogle(): AnalyticsGoogle
    {
        return app( 'analytics-google' );
    }
}

if ( ! function_exists( 'ga4Snippet' ) ) {
    /**
     * Render the GA4 gtag.js snippet as a string.
     *
     * Convenience wrapper around the `@ga4Snippet` Blade directive for
     * codepaths that need the snippet outside a Blade template.
     *
     * @since 1.0.0
     */
    function ga4Snippet(): string
    {
        return app( Gtag::class )->render();
    }
}
