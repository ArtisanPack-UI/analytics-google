---
title: Vue
---

# Vue — `Ga4Snippet`

`Ga4Snippet` is the Vue 3 equivalent of the [`@ga4Snippet`](Client-Side-Tracking-Blade) directive. It injects the gtag.js snippet into `<head>` in an `onMounted` hook and renders nothing itself.

## Publish the source

```bash
php artisan vendor:publish --tag=analytics-google-js
```

Copies the Vue sources to `resources/js/vendor/analytics-google/vue/`. Or point your bundler at `vendor/artisanpack-ui/analytics-google/resources/js/vue/` directly.

## Import

```vue
<script setup lang="ts">
import { Ga4Snippet } from '@artisanpack-ui/analytics-google-js/vue'
// or, from your vendor-published copy:
// import { Ga4Snippet } from './vendor/analytics-google/vue'
</script>
```

## Usage

Drop it into your app root layout:

```vue
<script setup lang="ts">
import { Ga4Snippet } from '@artisanpack-ui/analytics-google-js/vue'
</script>

<template>
    <Ga4Snippet measurement-id="G-XXXXXXX" />
    <RouterView />
</template>
```

The template renders no visible markup (just an HTML comment). The snippet is injected imperatively into `<head>` via `installGa4Snippet(...)`.

## Props

```ts
defineProps<{
    measurementId: string
    config?: Record<string, unknown>
    respectConsent?: boolean
}>()
```

- **`measurement-id`** — the `G-XXXXXXX` value. Required.
- **`config`** — extra `gtag('config', ...)` options. Merged verbatim.
- **`respect-consent`** — when `true` (default), the snippet sets `analytics_storage: 'denied'` unless `window.__apAnalyticsConsent.analytics === true`. Set to `false` to fire immediately.

Vue's prop-name transformation applies — `measurementId` in JSX is `measurement-id` in a template attribute.

## SSR safe

`installGa4Snippet` short-circuits when `window` or `document` is undefined, so rendering `<Ga4Snippet />` during Nuxt SSR is a no-op and does not throw.

## Related

- [Blade](Client-Side-Tracking-Blade) — server-rendered equivalent.
- [React](Client-Side-Tracking-React) — same component, React flavor.
- [Consent Integration](Client-Side-Tracking-Consent-Integration) — how `respect-consent` interacts with the analytics parent.
