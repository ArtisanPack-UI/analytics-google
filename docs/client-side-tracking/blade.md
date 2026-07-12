---
title: Blade
---

# Blade — `@ga4Snippet`

The `@ga4Snippet` directive emits the GA4 `gtag.js` snippet with the configured measurement ID. Drop it into your layout `<head>`:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>{{ $title }}</title>

    @ga4Snippet
</head>
<body>
    …
</body>
</html>
```

## What it emits

When `analytics-google.tracking.measurement_id` is `G-XXXXXXX`, `respect_consent` is `true`, and `config` is `[ 'anonymize_ip' => true ]`, the directive renders:

```html
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXX"></script>
<script>
window.dataLayer=window.dataLayer||[];
function gtag(){dataLayer.push(arguments);}
gtag('js',new Date());
if(!window.__apAnalyticsConsent||window.__apAnalyticsConsent.analytics!==true){
    gtag('consent','default',{analytics_storage:'denied'});
}
gtag('config',"G-XXXXXXX",{"anonymize_ip":true});
</script>
```

When `respect_consent` is `false`, the `if(!window.__apAnalyticsConsent…)` guard is omitted so tracking fires immediately.

When the measurement ID is empty or `tracking.enabled` is `false`, the directive emits nothing — no `<script>` tags at all. Safe to leave in a shared layout across dev, staging, and production.

## Under the hood

The directive is a one-line delegate to the `Gtag` service:

```php
Blade::directive( 'ga4Snippet', static function (): string {
    return '<?php echo app( \\ArtisanPackUI\\AnalyticsGoogle\\Tracking\\Gtag::class )->render(); ?>';
} );
```

You can render the same string outside Blade via the helper:

```php
$snippet = ga4Snippet();
```

Or from the container directly:

```php
$snippet = app( \ArtisanPackUI\AnalyticsGoogle\Tracking\Gtag::class )->render();
```

Full class reference: [`AnalyticsGoogle`](API-Reference-Analytics-Google) and the `Gtag` section of [API Reference/Helpers](API-Reference-Helpers).

## Escaping and safety

The rendered snippet uses `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` on both the measurement ID and the `config` array. That guarantees nothing in either value can break out of the surrounding `<script>` tag. `measurement_id` is also `rawurlencode`'d before insertion into the src URL.

That said, treat both values as trusted — anything you put in `analytics-google.tracking.config` ends up in the executed JS. Don't put user input there.

## Related

- [React](Client-Side-Tracking-React) — same snippet from a React component.
- [Vue](Client-Side-Tracking-Vue) — same snippet from a Vue component.
- [Consent Integration](Client-Side-Tracking-Consent-Integration) — how `respect_consent` interacts with the analytics parent.
- [Helpers](API-Reference-Helpers) — the `ga4Snippet()` function.
