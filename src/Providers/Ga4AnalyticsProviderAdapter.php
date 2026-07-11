<?php

/**
 * Adapter registering the GA4 provider with the analytics parent.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\Providers;

use ArtisanPackUI\Analytics\Contracts\AnalyticsProviderInterface;
use ArtisanPackUI\Analytics\Data\EventData;
use ArtisanPackUI\Analytics\Data\PageViewData;

/**
 * Adapter that satisfies the parent's AnalyticsProviderInterface while
 * delegating configuration reads to the underlying {@see Ga4Provider}.
 *
 * The `trackPageView` and `trackEvent` methods are intentional no-ops:
 * this package's tracking side runs in the browser via gtag.js, so
 * server-side calls have nothing to do. The parent already ships a
 * Measurement-Protocol-backed provider for callers that need
 * server-emitted events; the value this adapter adds is running the
 * client-side snippet through the parent's consent gate.
 *
 * This class is only autoloaded when the parent package is present,
 * so referencing its contracts here does not break the standalone
 * boot path required by issue #5.
 *
 * @since 1.0.0
 */
class Ga4AnalyticsProviderAdapter implements AnalyticsProviderInterface
{
    public function __construct(
        protected Ga4Provider $ga4,
        protected string $name,
    ) {
    }

    /**
     * @since 1.0.0
     */
    public function trackPageView( PageViewData $data ): void
    {
        // Client-side gtag.js emits page views from the browser.
    }

    /**
     * @since 1.0.0
     */
    public function trackEvent( EventData $data ): void
    {
        // Client-side gtag.js emits events from the browser.
    }

    /**
     * @since 1.0.0
     */
    public function isEnabled(): bool
    {
        return $this->ga4->isEnabled();
    }

    /**
     * @since 1.0.0
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->ga4->getConfig();
    }

    /**
     * Access to the client-side tracker script for callers that want to
     * inject it into the parent's `@analyticsScripts` output.
     *
     * @since 1.0.0
     */
    public function trackerScript(): string
    {
        return $this->ga4->trackerScript();
    }
}
