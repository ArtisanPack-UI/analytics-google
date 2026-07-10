<?php

/**
 * AnalyticsGoogle service provider.
 *
 * Bootstraps the Google Analytics (GA4) provider that plugs into the
 * shared analytics package. Relies on the shared google base package
 * for OAuth2, token, and scope services.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle;

use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the AnalyticsGoogle package.
 *
 * Binds the main AnalyticsGoogle class and boots GA4 provider
 * registration with the ArtisanPack UI analytics package.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */
class AnalyticsGoogleServiceProvider extends ServiceProvider
{
    /**
     * Registers any application services.
     *
     * Binds the AnalyticsGoogle class as a singleton in the container.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton( 'analytics-google', function ( $app ) {
            return new AnalyticsGoogle();
        } );
    }

    /**
     * Bootstraps any application services.
     *
     * Add package bootstrapping here such as:
     * - Configuration publishing: $this->publishes([...])
     * - Migration loading: $this->loadMigrationsFrom(...)
     * - Analytics provider registration
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function boot(): void
    {
        // Add your package bootstrapping here
    }
}
