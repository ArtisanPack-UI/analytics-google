---
title: React
---

# React — `Ga4Snippet`

`Ga4Snippet` is the React equivalent of the [[Client-Side Tracking/Blade|`@ga4Snippet`]] directive. It injects the gtag.js snippet into `<head>` on mount and renders `null` — no DOM footprint of its own.

## Publish the source

```bash
php artisan vendor:publish --tag=analytics-google-js
```

Copies the React sources to `resources/js/vendor/analytics-google/react/`. Or point your bundler at `vendor/artisanpack-ui/analytics-google/resources/js/react/` directly.

## Import

```tsx
import { Ga4Snippet } from '@artisanpack-ui/analytics-google-js/react'
// or, from your vendor-published copy:
// import { Ga4Snippet } from './vendor/analytics-google/react'
```

## Usage

Drop it at the top of your app root (or wherever your layout renders `<head>`):

```tsx
export function App() {
    return (
        <>
            <Ga4Snippet measurementId="G-XXXXXXX" />
            <YourRoutes />
        </>
    )
}
```

The snippet is injected imperatively into `<head>` via `installGa4Snippet(...)`. Rendering the component many times with the same measurement ID is a no-op — a script tag with `id="ap-ga4-<measurementId>"` marks the injection.

## Props

```ts
interface InstallGa4Options {
    measurementId: string
    config?: Record<string, unknown>
    respectConsent?: boolean
}
```

- **`measurementId`** — the `G-XXXXXXX` value. Required.
- **`config`** — extra `gtag('config', ...)` options. Merged verbatim.
- **`respectConsent`** — when `true` (default), the snippet sets `analytics_storage: 'denied'` unless `window.__apAnalyticsConsent.analytics === true`. Set to `false` to fire immediately.

## SSR safe

`installGa4Snippet` short-circuits when `window` or `document` is undefined, so rendering `<Ga4Snippet />` during SSR is a no-op and does not throw.

## Related

- [[Client-Side Tracking/Blade|Blade]] — server-rendered equivalent.
- [[Client-Side Tracking/Vue|Vue]] — same component, Vue flavor.
- [[Client-Side Tracking/Consent Integration|Consent Integration]] — how `respectConsent` interacts with the analytics parent.
- [[API Reference/Helpers|Helpers]] — the `installGa4Snippet` shared client.
