<?php

/**
 * AnalyticsGoogle package HTTP routes.
 *
 * Backs the React and Vue GA overview components. Loaded from the
 * service provider under the configurable route prefix so host apps
 * can move / disable it without editing the package.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

use ArtisanPackUI\AnalyticsGoogle\Http\Controllers\GaOverviewController;
use ArtisanPackUI\AnalyticsGoogle\Http\Controllers\GaTopContentController;
use Illuminate\Support\Facades\Route;

Route::get( 'overview', GaOverviewController::class )
    ->name( 'analytics-google.overview' );

Route::get( 'top-content', GaTopContentController::class )
    ->name( 'analytics-google.top-content' );
