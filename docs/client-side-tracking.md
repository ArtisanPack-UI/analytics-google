---
title: Client-Side Tracking
---

# Client-Side Tracking

The client-side surface emits the standard Google-hosted `gtag.js` snippet on your pages. It does **not** require the base [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) package — no OAuth token is involved, since tracking hits Google's servers directly from the browser.

## The three renderers

The same snippet is available in three flavors:

| Renderer | Import | Best for |
|---|---|---|
| Blade | `@ga4Snippet` | Server-rendered Laravel apps. |
| React | `import { Ga4Snippet } from '@artisanpack-ui/analytics-google-js/react'` | React SPAs / Inertia. |
| Vue | `import { Ga4Snippet } from '@artisanpack-ui/analytics-google-js/vue'` | Vue SPAs / Inertia. |

All three delegate to the same `Gtag` (PHP) or `installGa4Snippet` (JS) implementation so the emitted markup is identical.

## Prerequisite: measurement ID

Set `GA4_MEASUREMENT_ID` in `.env`:

```env
GA4_MEASUREMENT_ID=G-XXXXXXX
```

Or set `analytics-google.tracking.measurement_id` directly. If it's empty, every renderer emits nothing — safe to leave enabled in every environment.

## Consent-aware by default

By default, tracking defers the first `page_view` until the browser sets `window.__apAnalyticsConsent.analytics = true`. When [`artisanpack-ui/analytics`](https://github.com/ArtisanPack-UI/analytics) is installed, its consent banner writes that flag once the user grants the `analytics` category. Without the parent, the emitted snippet uses `gtag('consent', 'default', { analytics_storage: 'denied' })` until something else sets the flag.

To fire tracking unconditionally, set:

```php
'tracking' => [
    'respect_consent' => false,
],
```

Full details: [Consent Integration](Client-Side-Tracking-Consent-Integration).

## Deeper topics

- [Blade — `@ga4Snippet`](Client-Side-Tracking-Blade)
- [React — `Ga4Snippet`](Client-Side-Tracking-React)
- [Vue — `Ga4Snippet`](Client-Side-Tracking-Vue)
- [Consent Integration with the analytics parent](Client-Side-Tracking-Consent-Integration)
