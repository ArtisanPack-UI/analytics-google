<?php

/**
 * GA4 analytics provider.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\Providers;

use ArtisanPackUI\AnalyticsGoogle\Tracking\Gtag;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * Registers this package as a provider with the `artisanpack-ui/analytics`
 * parent. The parent gates the provider behind its consent banner, so
 * tracking will not fire until the user has granted the analytics
 * category — matching the privacy-first defaults of the parent.
 *
 * The concrete tracking work is delegated to {@see Gtag} for client-side
 * emission. Server-side event tracking (via the GA4 Measurement Protocol)
 * is intentionally out of scope for this provider — the parent package
 * already ships a Measurement-Protocol-backed provider for that case.
 *
 * @since 1.0.0
 */
class Ga4Provider
{
    public function __construct(
        protected ConfigRepository $config,
        protected Gtag $gtag,
    ) {
    }

    /**
     * The name the parent knows this provider by.
     *
     * @since 1.0.0
     */
    public function getName(): string
    {
        return (string) $this->config->get( 'analytics-google.provider_name', 'google-ga4' );
    }

    /**
     * Whether the provider is configured and enabled.
     *
     * @since 1.0.0
     */
    public function isEnabled(): bool
    {
        return $this->gtag->isConfigured();
    }

    /**
     * The tracker rendered to a `<script>` snippet the parent can inject
     * into its tracker container or `@analyticsScripts` output.
     *
     * @since 1.0.0
     */
    public function trackerScript(): string
    {
        return $this->gtag->render();
    }

    /**
     * The provider configuration snapshot the parent uses in its
     * dashboard status view.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return [
            'measurement_id'  => $this->gtag->measurementId(),
            'respect_consent' => $this->gtag->respectsConsent(),
            'options'         => $this->gtag->configOptions(),
        ];
    }
}
