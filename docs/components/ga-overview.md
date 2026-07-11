---
title: GA Overview
---

# GA Overview

The GA overview surface renders four headline metrics — **sessions**, **users**, **page views**, and **average engagement time** — plus a daily trend chart for a configurable date range.

Every framework consumes the same [[HTTP Endpoints|`GET /analytics-google/overview`]] payload (Livewire renders it directly; React and Vue fetch it), so the numbers always match.

## Livewire

Auto-registered as `analytics-google::ga-overview` when Livewire is installed.

```blade
<livewire:analytics-google::ga-overview
    :days="30"
    :property-id="null"
/>
```

### Props

| Prop | Type | Default | Purpose |
|---|---|---|---|
| `days` | int | `30` | Days of history to summarize. Clamped to `1..DateRange::MAX_DAYS` (730). |
| `propertyId` | string \| null | `null` | Optional per-instance override. Falls back to `analytics-google.reporting.property_id`. |

### Public actions

- `refresh()` — re-fetches from GA4. Called automatically on `mount` and whenever `days` changes. Wire up to `wire:poll` for auto-refresh or to a button for manual refresh.

### Public state (view-visible)

- `totals` — `{ sessions, users, page_views, avg_engagement_seconds }` (all `float`), or `null` before the first successful fetch.
- `trend` — `list<{ date, sessions, users, page_views }>` sorted by date ascending.
- `errorMessage` — friendly message when a fetch fails; `null` on success.
- `baseInstalled` — `false` when the base package is missing.

### Error branches

- `baseInstalled === false` → renders the install-the-base prompt.
- `errorMessage !== null` → renders the message. Common messages: `Connect a Google account to view analytics.`, `Reconnect your Google account to continue viewing analytics.`, arbitrary API messages from `ReportingException::apiError()`.

## React

```tsx
import { GaOverview } from '@artisanpack-ui/analytics-google-js/react'

<GaOverview initialDays={30} limit={undefined} propertyId={null} />
```

### Props

```ts
interface GaOverviewProps {
    initialDays?: number      // default 30
    propertyId?: string | null
    baseUrl?: string          // default '/analytics-google/overview'
    fetchImpl?: typeof fetch  // override for SSR / tests
}
```

The component ships a built-in range picker with options `[7, 14, 30, 90]` — override by publishing the source and editing the constant array at the top of `resources/js/react/GaOverview.tsx`.

### Error handling

Any non-2xx response throws `GaOverviewError` with a `code` and `message`. The component renders a code-specific branch when `code === 'base_not_installed'` and falls back to the raw message otherwise. See [[HTTP Endpoints]] for the error-code list.

## Vue

```vue
<script setup lang="ts">
import { GaOverview } from '@artisanpack-ui/analytics-google-js/vue'
</script>

<template>
    <GaOverview :initial-days="30" :property-id="null" />
</template>
```

### Props

```ts
{
    initialDays?: number         // default 30
    propertyId?: string | null   // default null
    baseUrl?: string
    fetchImpl?: typeof fetch
}
```

Identical semantics to the React component.

## Underlying data

Both frameworks receive the same JSON shape from `GET /analytics-google/overview`:

```json
{
    "range": { "startDate": "29daysAgo", "endDate": "today" },
    "totals": {
        "sessions": 1234,
        "users": 800,
        "page_views": 4567,
        "avg_engagement_seconds": 92.4
    },
    "trend": [
        { "date": "2026-06-12", "sessions": 45, "users": 30, "page_views": 180 },
        ...
    ]
}
```

## Related

- [[Components/GA Top Content|GA Top Content]] — the sister surface.
- [[HTTP Endpoints]] — the `/overview` endpoint reference.
- [[API Reference/GA Overview Fetcher|`GaOverviewFetcher`]] — the fetch logic shared across surfaces.
- [[API Reference/GA Overview Data|`GaOverviewData`]] — the payload DTO.
