<?php

/**
 * GA top pages and events Livewire component.
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
use ArtisanPackUI\AnalyticsGoogle\Reporting\GaTopContentData;
use ArtisanPackUI\AnalyticsGoogle\Reporting\GaTopContentFetcher;
use ArtisanPackUI\AnalyticsGoogle\Support\BaseInstalled;
use ArtisanPackUI\AnalyticsGoogle\Support\GoogleConnectionResolver;
use ArtisanPackUI\Google\Models\GoogleConnection;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;

/**
 * Renders the GA top pages (by views) and top events (by count) for a
 * configurable date range.
 *
 * Behaves like {@see GaOverview}: requires the base google package for
 * reporting, gracefully degrades to an "install the base" prompt when
 * the base is missing, and clamps the range/limit inputs so a caller
 * cannot bind absurd values.
 *
 * @since 1.0.0
 */
class GaTopContent extends Component
{
    public int $days = 30;

    public int $limit = GaTopContentFetcher::DEFAULT_LIMIT;

    public ?string $propertyId = null;

    /**
     * @var list<array{path: string, title: string, views: float, users: float}>
     */
    public array $topPages = [];

    /**
     * @var list<array{event: string, count: float, users: float}>
     */
    public array $topEvents = [];

    public ?string $errorMessage = null;

    public bool $baseInstalled = true;

    public function mount( int $days = 30, int $limit = GaTopContentFetcher::DEFAULT_LIMIT, ?string $propertyId = null ): void
    {
        $this->days          = $this->clampDays( $days );
        $this->limit         = $this->clampLimit( $limit );
        $this->propertyId    = $propertyId;
        $this->baseInstalled = BaseInstalled::check();

        if ( ! $this->baseInstalled ) {
            return;
        }

        $this->refresh();
    }

    public function updatedDays( int $value ): void
    {
        $this->days = $this->clampDays( $value );
        $this->refresh();
    }

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

            $fetcher = new GaTopContentFetcher( $client );
            $data    = $fetcher->fetch( $connection, DateRange::lastDays( $this->days ), $this->propertyId, $this->limit );

            $this->applyData( $data );
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
        return view( 'analytics-google::livewire.ga-top-content' );
    }

    protected function clampDays( int $days ): int
    {
        return max( 1, min( $days, DateRange::MAX_DAYS ) );
    }

    protected function clampLimit( int $limit ): int
    {
        return max( 1, min( $limit, 100 ) );
    }

    protected function resolveConnection(): ?GoogleConnection
    {
        return app( GoogleConnectionResolver::class )->forUser( auth()->user() );
    }

    protected function applyData( GaTopContentData $data ): void
    {
        $this->topPages  = $data->topPages;
        $this->topEvents = $data->topEvents;
    }
}
