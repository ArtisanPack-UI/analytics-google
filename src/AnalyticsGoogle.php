<?php

/**
 * Main AnalyticsGoogle class.
 *
 * Entry point for the Google Analytics (GA4) provider. Aggregates the
 * client-side tracker, server-side reporting client, and provider
 * registration state. Accessed via the `analyticsGoogle()` helper
 * function or the AnalyticsGoogle facade.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle;

use ArtisanPackUI\AnalyticsGoogle\Providers\Ga4Provider;
use ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient;
use ArtisanPackUI\AnalyticsGoogle\Support\BaseInstalled;
use ArtisanPackUI\AnalyticsGoogle\Tracking\Gtag;

/**
 * Convenience aggregator for the AnalyticsGoogle services.
 *
 * @since 1.0.0
 */
class AnalyticsGoogle
{
    public function __construct(
        protected Gtag $gtag,
        protected Ga4Provider $provider,
        protected ?Ga4DataClient $dataClient = null,
    ) {
    }

    /**
     * The client-side gtag.js snippet renderer.
     *
     * @since 1.0.0
     */
    public function gtag(): Gtag
    {
        return $this->gtag;
    }

    /**
     * The provider descriptor for the analytics parent.
     *
     * @since 1.0.0
     */
    public function provider(): Ga4Provider
    {
        return $this->provider;
    }

    /**
     * The GA4 Data API client. Available only when the base google
     * package is installed; otherwise returns null so callers can
     * feature-detect rather than catching an exception.
     *
     * @since 1.0.0
     */
    public function dataClient(): ?Ga4DataClient
    {
        if ( ! $this->hasReporting() ) {
            return null;
        }

        return $this->dataClient;
    }

    /**
     * Whether the reporting side is usable in the current environment.
     *
     * @since 1.0.0
     */
    public function hasReporting(): bool
    {
        return null !== $this->dataClient && BaseInstalled::check();
    }
}
