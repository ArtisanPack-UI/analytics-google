<?php

/**
 * GA overview Livewire component.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\Livewire;

use ArtisanPackUI\AnalyticsGoogle\Exceptions\BaseNotInstalledException;
use ArtisanPackUI\AnalyticsGoogle\Exceptions\ReportingException;
use ArtisanPackUI\AnalyticsGoogle\Reporting\DateRange;
use ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient;
use ArtisanPackUI\AnalyticsGoogle\Reporting\GaOverviewData;
use ArtisanPackUI\AnalyticsGoogle\Reporting\GaOverviewFetcher;
use ArtisanPackUI\AnalyticsGoogle\Support\BaseInstalled;
use ArtisanPackUI\AnalyticsGoogle\Support\GoogleConnectionResolver;
use ArtisanPackUI\Google\Models\GoogleConnection;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;

/**
 * Renders the GA overview surface: four headline metrics and a daily
 * trend chart for a configurable date range.
 *
 * Requires the base google package (for OAuth) to be installed and a
 * connected Google account resolvable via `resolveConnection()`. When
 * the base is missing the component renders a `install the base
 * package` message instead of blowing up.
 *
 * @since 1.0.0
 */
class GaOverview extends Component
{
    /**
     * Number of days to summarise, ending today.
     */
    public int $days = 30;

    /**
     * Optional property ID override. Falls back to config when null.
     */
    public ?string $propertyId = null;

    /**
     * Loaded totals; null while the component has never fetched or has
     * encountered an error.
     *
     * @var array<string, float>|null
     */
    public ?array $totals = null;

    /**
     * Loaded daily trend; empty until the first successful fetch.
     *
     * @var list<array{date: string, sessions: float, users: float, page_views: float}>
     */
    public array $trend = [];

    /**
     * Last error surfaced to the user. Null on success.
     */
    public ?string $errorMessage = null;

    /**
     * Whether the base google package is installed. Consulted by the
     * view to render an install prompt when false.
     */
    public bool $baseInstalled = true;

    public function mount( int $days = 30, ?string $propertyId = null ): void
    {
        $this->days          = $this->clampDays( $days );
        $this->propertyId    = $propertyId;
        $this->baseInstalled = BaseInstalled::check();

        if ( ! $this->baseInstalled ) {
            return;
        }

        $this->refresh();
    }

    /**
     * Livewire wire:model.change target — re-fetch when the range changes.
     */
    public function updatedDays( int $value ): void
    {
        $this->days = $this->clampDays( $value );
        $this->refresh();
    }

    /**
     * Fetch (or re-fetch) the overview from GA4.
     *
     * Exceptions from the reporting side are captured onto
     * {@see $errorMessage} so the component can surface a friendly
     * message instead of surfacing a 500.
     *
     * @since 1.0.0
     */
    public function refresh(): void
    {
        $this->errorMessage = null;

        if ( ! BaseInstalled::check() ) {
            $this->baseInstalled = false;

            return;
        }

        try {
            $connection = $this->resolveConnection();

            if ( null === $connection ) {
                $this->errorMessage = __( 'Connect a Google account to view analytics.' );

                return;
            }

            /** @var Ga4DataClient $client */
            $client = app( Ga4DataClient::class );

            $fetcher  = new GaOverviewFetcher( $client );
            $overview = $fetcher->fetch( $connection, DateRange::lastDays( $this->days ), $this->propertyId );

            $this->applyOverview( $overview );
        } catch ( BaseNotInstalledException ) {
            $this->baseInstalled = false;
        } catch ( ReportingException $e ) {
            $this->errorMessage = $e->getMessage();
        } catch ( Throwable $e ) {
            $this->errorMessage = __( 'Could not load analytics: :message', [ 'message' => $e->getMessage() ] );
        }
    }

    public function render(): View
    {
        return view( 'analytics-google::livewire.ga-overview' );
    }

    /**
     * Clamp days into the range GA4 will answer quickly. Prevents an
     * authenticated user from binding an arbitrarily large value and
     * forcing multi-decade lookups on every wire:poll.
     *
     * @since 1.0.0
     */
    protected function clampDays( int $days ): int
    {
        return max( 1, min( $days, DateRange::MAX_DAYS ) );
    }

    /**
     * Resolve the GoogleConnection the component uses to hit GA4.
     *
     * Delegates to {@see GoogleConnectionResolver}; applications with
     * multi-property or tenant-scoped needs can override the container
     * binding for that class to affect both this component and the
     * HTTP endpoint that backs the React/Vue overview.
     *
     * @since 1.0.0
     */
    protected function resolveConnection(): ?GoogleConnection
    {
        return app( GoogleConnectionResolver::class )->forUser( auth()->user() );
    }

    protected function applyOverview( GaOverviewData $overview ): void
    {
        $this->totals = $overview->totals;
        $this->trend  = $overview->trend;
    }
}
