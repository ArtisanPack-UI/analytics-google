---
title: Ga4Provider
---

# `Ga4Provider` and `Ga4AnalyticsProviderAdapter`

Two classes handle registration with the [`artisanpack-ui/analytics`](https://github.com/ArtisanPack-UI/analytics) parent. Full narrative details: [[Analytics Parent Integration]].

## `Ga4Provider`

`ArtisanPackUI\AnalyticsGoogle\Providers\Ga4Provider` — the provider descriptor. Always instantiated whether or not the parent is installed; used by `AnalyticsGoogle::provider()`.

### Constructor

```php
public function __construct(
    protected ConfigRepository $config,
    protected Gtag $gtag,
)
```

### Methods

- **`getName(): string`** — the provider name from `analytics-google.provider_name` (default `google-ga4`).
- **`isEnabled(): bool`** — delegates to `Gtag::isConfigured()` (measurement ID set and tracking enabled).
- **`trackerScript(): string`** — the `<script>` snippet from `Gtag::render()`, ready for the parent's `@analyticsScripts` output.
- **`getConfig(): array{measurement_id: ?string, respect_consent: bool, options: array<string, mixed>}`** — the config snapshot the parent's dashboard status view renders.

## `Ga4AnalyticsProviderAdapter`

`ArtisanPackUI\AnalyticsGoogle\Providers\Ga4AnalyticsProviderAdapter` implements the parent's `\ArtisanPackUI\Analytics\Contracts\AnalyticsProviderInterface`. Only autoloaded when the parent is installed — referencing its type without the parent would fatal.

### Constructor

```php
public function __construct(
    protected Ga4Provider $ga4,
    protected string $name,
)
```

Instantiated inside the parent's `extend()` callback in the service provider's `booted` hook.

### Methods

- **`getName(): string`** — the configured provider name (`'google-ga4'` by default).
- **`isEnabled(): bool`** — delegates to `Ga4Provider::isEnabled()`.
- **`getConfig(): array`** — delegates to `Ga4Provider::getConfig()`.
- **`trackerScript(): string`** — delegates to `Ga4Provider::trackerScript()`.
- **`trackPageView( PageViewData $data ): void`** — **intentional no-op**. Client-side gtag.js emits page views from the browser.
- **`trackEvent( EventData $data ): void`** — **intentional no-op**. Same reason.

For server-emitted GA4 events (Measurement Protocol), use the provider that ships with the analytics parent.

## Related

- [[Analytics Parent Integration]]
- [[Client-Side Tracking/Consent Integration|Consent Integration]]
