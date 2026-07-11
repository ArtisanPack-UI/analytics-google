---
title: Helpers
---

# Helpers, facades, and `Gtag`

Small, always-available surface for the client-side and container-access bits.

## Helper functions

Both live in `src/helpers.php` (auto-loaded via `composer.json`'s `files` autoload).

### `analyticsGoogle(): AnalyticsGoogle`

```php
function analyticsGoogle(): AnalyticsGoogle
{
    return app( 'analytics-google' );
}
```

Return the `AnalyticsGoogle` aggregator — same singleton the facade points at.

### `ga4Snippet(): string`

```php
function ga4Snippet(): string
{
    return app( \ArtisanPackUI\AnalyticsGoogle\Tracking\Gtag::class )->render();
}
```

Convenience wrapper around `Gtag::render()` for codepaths outside Blade. Returns the `<script>` snippet or an empty string when the tracker is unconfigured or disabled.

Both helpers are guarded with `if ( ! function_exists( ... ) )` so an app-level redefinition wins.

## Facade

`ArtisanPackUI\AnalyticsGoogle\Facades\AnalyticsGoogle` — a thin `Illuminate\Support\Facades\Facade` with `getFacadeAccessor(): string => 'analytics-google'`.

Auto-registered by Laravel package discovery as the `AnalyticsGoogle` alias.

```php
use ArtisanPackUI\AnalyticsGoogle\Facades\AnalyticsGoogle;

AnalyticsGoogle::gtag()->render();
AnalyticsGoogle::hasReporting();
```

## `Gtag`

`ArtisanPackUI\AnalyticsGoogle\Tracking\Gtag` — the client-side snippet renderer. Registered as a singleton under `Tracking\Gtag::class`.

### Constructor

```php
public function __construct(
    protected ConfigRepository $config,
)
```

### Methods

- **`isConfigured(): bool`** — `true` when `tracking.enabled` is true and `tracking.measurement_id` is non-empty.
- **`measurementId(): ?string`** — the configured measurement ID, or `null` when empty.
- **`configOptions(): array<string, mixed>`** — the extra `gtag('config', ...)` options from `tracking.config`.
- **`respectsConsent(): bool`** — the value of `tracking.respect_consent`.
- **`render(): string`** — the `<script async src="…gtag/js?id=…">` + inline `<script>` block, or an empty string when `isConfigured()` is `false`. Uses `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` on both the measurement ID and the config array to prevent `<script>` breakout.

### Example

```php
$gtag = app( \ArtisanPackUI\AnalyticsGoogle\Tracking\Gtag::class );

if ( $gtag->isConfigured() ) {
    echo $gtag->render();
}
```

## Related

- [[Client-Side Tracking/Blade|`@ga4Snippet` directive]]
- [[API Reference/Analytics Google|`AnalyticsGoogle`]]
