---
title: GA Top Content
---

# GA Top Content

The top-content surface renders **top pages by page views** and **top events by count** for a configurable date range.

Every framework consumes the same [`GET /analytics-google/top-content`](HTTP-Endpoints) payload, so the numbers always match.

## Livewire

Auto-registered as `analytics-google::ga-top-content` when Livewire is installed.

```blade
<livewire:analytics-google::ga-top-content
    :days="30"
    :limit="10"
    :property-id="null"
/>
```

### Props

| Prop | Type | Default | Purpose |
|---|---|---|---|
| `days` | int | `30` | Days of history to summarize. Clamped to `1..DateRange::MAX_DAYS` (730). |
| `limit` | int | `10` (`GaTopContentFetcher::DEFAULT_LIMIT`) | Max rows per list. Clamped to `1..100`. |
| `propertyId` | string \| null | `null` | Optional per-instance override. |

### Public actions

- `refresh()` — re-fetches from GA4. Called automatically on `mount` and whenever `days` changes.

### Public state (view-visible)

- `topPages` — `list<{ path, title, views, users }>` sorted by views descending.
- `topEvents` — `list<{ event, count, users }>` sorted by count descending.
- `errorMessage` — friendly message when a fetch fails; `null` on success.
- `baseInstalled` — `false` when the base package is missing.

### Error branches

Same as [GA Overview](Components-GA-Overview) — install prompt when base missing, error message otherwise.

## React

```tsx
import { GaTopContent } from '@artisanpack-ui/analytics-google-js/react'

<GaTopContent initialDays={30} limit={10} propertyId={null} />
```

### Props

```ts
interface GaTopContentProps {
    initialDays?: number  // default 30
    limit?: number        // default 10
    propertyId?: string | null
    baseUrl?: string      // default '/analytics-google/top-content'
    fetchImpl?: typeof fetch
}
```

Range picker options: `[7, 14, 30, 90]`. Override by publishing and editing `resources/js/react/GaTopContent.tsx`.

## Vue

```vue
<script setup lang="ts">
import { GaTopContent } from '@artisanpack-ui/analytics-google-js/vue'
</script>

<template>
    <GaTopContent :initial-days="30" :limit="10" :property-id="null" />
</template>
```

Same semantics as the React component.

## Underlying data

Both frameworks receive the same JSON shape from `GET /analytics-google/top-content`:

```json
{
    "range": { "startDate": "29daysAgo", "endDate": "today" },
    "top_pages": [
        { "path": "/", "title": "Home", "views": 1234, "users": 800 },
        { "path": "/blog/hello", "title": "Hello, world", "views": 320, "users": 210 }
    ],
    "top_events": [
        { "event": "page_view", "count": 4567, "users": 800 },
        { "event": "click", "count": 213, "users": 150 }
    ]
}
```

## Related

- [GA Overview](Components-GA-Overview) — the sister surface.
- [HTTP Endpoints](HTTP-Endpoints) — the `/top-content` endpoint reference.
- [`GaTopContentFetcher`](API-Reference-GA-Top-Content-Fetcher) — the fetch logic shared across surfaces.
- [`GaTopContentData`](API-Reference-GA-Top-Content-Data) — the payload DTO.
