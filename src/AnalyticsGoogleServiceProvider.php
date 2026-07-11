<?php

/**
 * AnalyticsGoogle service provider.
 *
 * Bootstraps the Google Analytics (GA4) provider — client-side gtag
 * tracking always, plus server-side Data API reporting and provider
 * registration with the analytics parent when the base google
 * package is installed.
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

use ArtisanPackUI\AnalyticsGoogle\Providers\Ga4Provider;
use ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient;
use ArtisanPackUI\AnalyticsGoogle\Support\BaseInstalled;
use ArtisanPackUI\AnalyticsGoogle\Tracking\Gtag;
use ArtisanPackUI\Google\Tokens\TokenManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the AnalyticsGoogle package.
 *
 * @since 1.0.0
 */
class AnalyticsGoogleServiceProvider extends ServiceProvider
{
    /**
     * @since 1.0.0
     */
    public function register(): void
    {
        $this->mergeConfigFrom( __DIR__ . '/../config/analytics-google.php', 'analytics-google' );

        $this->app->singleton( Gtag::class, fn ( Application $app ): Gtag => new Gtag( $app[ 'config' ] ) );

        $this->app->singleton( Ga4Provider::class, fn ( Application $app ): Ga4Provider => new Ga4Provider(
            $app[ 'config' ],
            $app->make( Gtag::class ),
        ) );

        $this->app->singleton( Ga4DataClient::class, fn ( Application $app ): Ga4DataClient => new Ga4DataClient(
            $app[ 'config' ],
            $app->make( HttpFactory::class ),
            BaseInstalled::check() ? $app->make( TokenManager::class ) : null,
        ) );

        $this->app->singleton( 'analytics-google', function ( Application $app ): AnalyticsGoogle {
            return new AnalyticsGoogle(
                $app->make( Gtag::class ),
                $app->make( Ga4Provider::class ),
                BaseInstalled::check() ? $app->make( Ga4DataClient::class ) : null,
            );
        } );
    }

    /**
     * @since 1.0.0
     */
    public function boot(): void
    {
        $this->publishes( [
            __DIR__ . '/../config/analytics-google.php' => config_path( 'analytics-google.php' ),
        ], 'analytics-google-config' );

        $this->publishes( [
            __DIR__ . '/../resources/views' => resource_path( 'views/vendor/analytics-google' ),
        ], 'analytics-google-views' );

        $this->publishes( [
            __DIR__ . '/../resources/js' => resource_path( 'js/vendor/analytics-google' ),
        ], 'analytics-google-js' );

        $this->loadViewsFrom( __DIR__ . '/../resources/views', 'analytics-google' );

        $this->registerBladeDirectives();
        $this->registerRoutes();
        $this->registerGoogleScopeHook();
        $this->registerLivewireComponents();
        $this->registerAnalyticsProvider();
    }

    /**
     * Register the `@ga4Snippet` Blade directive. Standalone — no
     * dependencies on the base or the analytics parent.
     *
     * @since 1.0.0
     */
    protected function registerBladeDirectives(): void
    {
        Blade::directive( 'ga4Snippet', static function (): string {
            return '<?php echo app( \\ArtisanPackUI\\AnalyticsGoogle\\Tracking\\Gtag::class )->render(); ?>';
        } );
    }

    /**
     * Load the reporting HTTP routes when the base is installed. The
     * routes hit the GoogleConnection model so we cannot register them
     * without the base package on the autoloader.
     *
     * @since 1.0.0
     */
    protected function registerRoutes(): void
    {
        if ( ! BaseInstalled::check() ) {
            return;
        }

        if ( false === (bool) $this->app[ 'config' ]->get( 'analytics-google.routes.enabled', true ) ) {
            return;
        }

        Route::group( [
            'prefix'     => (string) $this->app[ 'config' ]->get( 'analytics-google.routes.prefix', 'analytics-google' ),
            'middleware' => (array) $this->app[ 'config' ]->get( 'analytics-google.routes.middleware', [ 'web', 'auth' ] ),
        ], function (): void {
            $this->loadRoutesFrom( __DIR__ . '/../routes/web.php' );
        } );
    }

    /**
     * Contribute the analytics.readonly scope to the base google
     * package's ScopeRegistry so the single-consent screen covers GA4
     * reporting alongside whatever other Google services the app uses.
     *
     * Skips silently if the hooks helpers or the base package are
     * missing — issue #5 requires we do not fatal in either case.
     *
     * @since 1.0.0
     */
    protected function registerGoogleScopeHook(): void
    {
        if ( ! BaseInstalled::check() ) {
            return;
        }

        if ( ! function_exists( 'addFilter' ) ) {
            return;
        }

        $config = $this->app[ 'config' ];

        addFilter( 'ap.google.scopes', static function ( array $scopes ) use ( $config ): array {
            $ours = (array) $config->get( 'analytics-google.scopes', [] );

            return array_values( array_unique( array_merge( $scopes, array_map( 'strval', $ours ) ) ) );
        } );
    }

    /**
     * Register Livewire components when Livewire is installed. Livewire
     * is an optional peer — apps without it can still use the Blade
     * directive and the React/Vue components without penalty.
     *
     * @since 1.0.0
     */
    protected function registerLivewireComponents(): void
    {
        if ( ! class_exists( \Livewire\Livewire::class ) ) {
            return;
        }

        \Livewire\Livewire::component(
            'analytics-google::ga-overview',
            Livewire\GaOverview::class,
        );
    }

    /**
     * Register the GA4 provider with the analytics parent's provider
     * registry. Runs on `booted` so the parent's ServiceProvider has
     * had a chance to bind its container entries first regardless of
     * declaration order.
     *
     * The provider registered here is a thin adapter that satisfies the
     * parent's AnalyticsProviderInterface. It intentionally no-ops the
     * server-side track methods — this package's tracking side is
     * client-side gtag.js, and the parent already ships a
     * Measurement-Protocol-backed provider for the server-side case.
     * The value we add is running through the parent's consent gate.
     *
     * @since 1.0.0
     */
    protected function registerAnalyticsProvider(): void
    {
        if ( ! class_exists( \ArtisanPackUI\Analytics\Analytics::class ) ) {
            return;
        }

        if ( ! interface_exists( \ArtisanPackUI\Analytics\Contracts\AnalyticsProviderInterface::class ) ) {
            return;
        }

        $this->app->booted( function (): void {
            if ( ! $this->app->bound( \ArtisanPackUI\Analytics\Analytics::class ) ) {
                return;
            }

            $analytics = $this->app->make( \ArtisanPackUI\Analytics\Analytics::class );

            if ( ! method_exists( $analytics, 'extend' ) ) {
                return;
            }

            $providerName = (string) $this->app[ 'config' ]->get( 'analytics-google.provider_name', 'google-ga4' );

            $analytics->extend( $providerName, static function ( $app ) use ( $providerName ) {
                $ga4 = $app->make( Ga4Provider::class );

                return new Providers\Ga4AnalyticsProviderAdapter( $ga4, $providerName );
            } );
        } );
    }
}
