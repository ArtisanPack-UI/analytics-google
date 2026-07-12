---
title: Analytics Parent Integration
---

# Analytics Parent Integration

When [`artisanpack-ui/analytics`](https://github.com/ArtisanPack-UI/analytics) is installed, this package registers itself as the `google-ga4` provider so client-side tracking runs behind the parent's consent gate instead of firing unconditionally.

## What "provider" means

The analytics parent owns:

- A **consent banner** the user interacts with once.
- A **provider registry** that lets service packages contribute trackers.
- An `@analyticsScripts` output that emits every enabled provider's tracker only after consent is granted.

This package contributes an `AnalyticsProviderInterface` implementation that delegates configuration reads to the underlying `Ga4Provider` and returns the `gtag.js` snippet for the parent to inject.

## Registration flow

The service provider registers on `booted` so the parent's own provider has already had a chance to bind its container entries:

```php
$this->app->booted( function (): void {
    $analytics = $this->app->make( \ArtisanPackUI\Analytics\Analytics::class );

    $providerName = (string) $this->app[ 'config' ]->get( 'analytics-google.provider_name', 'google-ga4' );

    $analytics->extend( $providerName, static function ( $app ) use ( $providerName ) {
        $ga4 = $app->make( Ga4Provider::class );

        return new Providers\Ga4AnalyticsProviderAdapter( $ga4, $providerName );
    } );
} );
```

## Enabling the provider

On the analytics parent's config (`config/artisanpack/analytics.php` or equivalent), add `google-ga4` to `active_providers`:

```php
'active_providers' => [
    'google-ga4',
    // …other providers
],
```

Rename via `analytics-google.provider_name` if you already have a service registered under `google-ga4`.

## The adapter

`ArtisanPackUI\AnalyticsGoogle\Providers\Ga4AnalyticsProviderAdapter` implements the parent's `AnalyticsProviderInterface`:

- `getName(): string` — returns the configured provider name.
- `isEnabled(): bool` — returns `$this->ga4->isEnabled()` (true when a measurement ID is set and tracking is enabled).
- `getConfig(): array` — returns `{ measurement_id, respect_consent, options }` for the parent's dashboard status view.
- `trackerScript(): string` — returns the `Gtag::render()` output for `@analyticsScripts`.
- `trackPageView(PageViewData $data): void` — **no-op**. Client-side gtag.js emits page views from the browser; there is no server side to send here.
- `trackEvent(EventData $data): void` — **no-op**. Same reason.

If you need server-emitted GA4 events (Measurement Protocol), use the provider that ships with the analytics parent. This adapter deliberately confines itself to the client-side gtag.js surface.

## Conditional loading

The adapter references types from `ArtisanPackUI\Analytics\Contracts\*` — those symbols only exist when the parent is installed. The service provider guards registration behind `class_exists()` and `interface_exists()` so the standalone code path (without the parent) never touches the adapter class:

```php
if ( ! class_exists( \ArtisanPackUI\Analytics\Analytics::class ) ) {
    return;
}
if ( ! interface_exists( \ArtisanPackUI\Analytics\Contracts\AnalyticsProviderInterface::class ) ) {
    return;
}
```

## Consent gate vs. built-in `respect_consent`

Two independent mechanisms combine when the parent is installed:

1. **Provider gate** — the parent only emits enabled providers' tracker scripts after the user has granted consent. If the user rejects, `@analyticsScripts` skips this package's snippet entirely.
2. **Built-in `respect_consent` guard** — even if the snippet does end up on the page, the emitted `gtag('consent', 'default', …)` respects `window.__apAnalyticsConsent.analytics`. See [Consent Integration](Client-Side-Tracking-Consent-Integration).

In practice the two agree: the parent writes `window.__apAnalyticsConsent` when it grants the category and, at the same time, includes the provider in its output. The double guard means a race between the two mechanisms cannot briefly fire tracking against a user who hasn't consented.

## When the parent is not installed

The package still boots, the `Ga4Provider` still exists in the container, and `AnalyticsGoogle::provider()` still returns a valid instance — but nothing wires it up to a consent banner. In this mode the `@ga4Snippet` directive is the only path to emit the tracker, and its `respect_consent` guard is the only defence.

## Related

- [Consent Integration](Client-Side-Tracking-Consent-Integration) — the browser-side consent flag details.
- [`Ga4Provider`](API-Reference-GA4-Provider) — the provider class this adapter delegates to.
- [Configuration](Configuration) — the `provider_name` key.
