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
use ArtisanPackUI\AnalyticsGoogle\Tracking\MeasurementProtocol;

/**
 * Adapter that satisfies the parent's AnalyticsProviderInterface, forwarding
 * what the parent collects to GA4 and exposing the client-side snippet.
 *
 * Both halves of GA4 tracking are available through this one provider:
 *
 *  - Server-side. `trackPageView()` and `trackEvent()` relay to GA4 over the
 *    Measurement Protocol, which needs `GA4_API_SECRET` alongside the
 *    measurement ID. Until 1.1.0 these were no-ops, so adding `google-ga4` to
 *    the parent's `active_providers` registered a provider that sent nothing
 *    and reported no error.
 *  - Client-side. `trackerScript()` returns the gtag.js snippet for the parent
 *    to render, which carries referrer, geography, device and session
 *    attribution that the Measurement Protocol does not.
 *
 * They are complementary, not alternatives, but running both will double-count
 * anything they both see. Pick one per event stream: leave `server_side` off
 * if the snippet is rendered, or leave the snippet out if you are forwarding.
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
        protected ?MeasurementProtocol $measurementProtocol = null,
    ) {
    }

    /**
     * Forward a page view to GA4.
     *
     * A no-op until 1.1.0; forwards over the Measurement Protocol since 1.1.0,
     * and still does nothing when no client was supplied.
     *
     * @since 1.0.0
     */
    public function trackPageView( PageViewData $data ): void
    {
        $this->measurementProtocol?->pageView( [
            'path'         => $data->path,
            'title'        => $data->title,
            'referrer'     => $data->referrer,
            'visitor_id'   => $data->visitorId,
            'session_id'   => $data->sessionId,
            'utm_source'   => $data->utmSource,
            'utm_medium'   => $data->utmMedium,
            'utm_campaign' => $data->utmCampaign,
        ] );
    }

    /**
     * Forward a custom event to GA4.
     *
     * A no-op until 1.1.0; forwards over the Measurement Protocol since 1.1.0,
     * and still does nothing when no client was supplied.
     *
     * @since 1.0.0
     */
    public function trackEvent( EventData $data ): void
    {
        $this->measurementProtocol?->event( $data->name, [
            'properties' => $data->properties,
            'category'   => $data->category,
            'value'      => $data->value,
            'path'       => $data->path,
            'visitor_id' => $data->visitorId,
            'session_id' => $data->sessionId,
        ] );
    }

    /**
     * @since 1.0.0
     */
    public function isEnabled(): bool
    {
        // Either half being configured counts. The parent drops providers
        // reporting false from `getActiveProviders()`, so answering on the
        // client-side tag alone would silently disable forwarding for anyone
        // who set up the Measurement Protocol and turned the snippet off.
        return $this->ga4->isEnabled() || (bool) $this->measurementProtocol?->isConfigured();
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
