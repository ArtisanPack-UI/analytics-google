<?php

/**
 * Client-side gtag.js snippet renderer.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\Tracking;

use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * Emits the standard Google Analytics 4 gtag.js snippet with a
 * configurable measurement ID.
 *
 * Rendering is a no-op when tracking is disabled or no measurement ID
 * is configured; callers can safely place `@ga4Snippet` on every page
 * without conditional wrappers.
 *
 * @since 1.0.0
 */
class Gtag
{
    public function __construct(
        protected ConfigRepository $config,
    ) {
    }

    /**
     * Whether client-side tracking is enabled and has a measurement ID.
     *
     * @since 1.0.0
     */
    public function isConfigured(): bool
    {
        if ( ! (bool) $this->config->get( 'analytics-google.tracking.enabled', true ) ) {
            return false;
        }

        return '' !== (string) ( $this->config->get( 'analytics-google.tracking.measurement_id' ) ?? '' );
    }

    /**
     * The configured GA4 measurement ID (e.g. "G-XXXXXXX"), or null.
     *
     * @since 1.0.0
     */
    public function measurementId(): ?string
    {
        $id = $this->config->get( 'analytics-google.tracking.measurement_id' );

        if ( null === $id || '' === $id ) {
            return null;
        }

        return (string) $id;
    }

    /**
     * Extra `gtag('config', ...)` options serialized safely.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function configOptions(): array
    {
        $options = $this->config->get( 'analytics-google.tracking.config', [] );

        return is_array( $options ) ? $options : [];
    }

    /**
     * Whether the tracker should defer sending the page_view until the
     * analytics parent's consent banner grants the 'analytics' category.
     *
     * @since 1.0.0
     */
    public function respectsConsent(): bool
    {
        return (bool) $this->config->get( 'analytics-google.tracking.respect_consent', true );
    }

    /**
     * Render the gtag.js `<script>` snippet, or empty string when the
     * tracker is not configured.
     *
     * The snippet is safe to render on every page: when tracking is
     * disabled or no measurement ID is configured, nothing is emitted.
     *
     * @since 1.0.0
     */
    public function render(): string
    {
        if ( ! $this->isConfigured() ) {
            return '';
        }

        $measurementId = (string) $this->measurementId();
        $safeJsonFlags = JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        $configJson    = json_encode( $this->configOptions(), $safeJsonFlags );

        if ( false === $configJson ) {
            $configJson = '{}';
        }

        $urlId        = rawurlencode( $measurementId );
        $jsId         = json_encode( $measurementId, $safeJsonFlags );
        $consentGuard = $this->respectsConsent()
            ? "if(!window.__apAnalyticsConsent||window.__apAnalyticsConsent.analytics!==true){gtag('consent','default',{analytics_storage:'denied'});}"
            : '';

        return <<<HTML
<script async src="https://www.googletagmanager.com/gtag/js?id={$urlId}"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());{$consentGuard}gtag('config',{$jsId},{$configJson});</script>
HTML;
    }
}
