---
title: HTTP Endpoints
---

# HTTP Endpoints

Two HTTP endpoints back the React and Vue reporting components. They are server-authoritative so both frameworks see identical numbers, and they only register when the base [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) package is installed.

## Mount configuration

```php
'routes' => [
    'enabled'    => true,
    'prefix'     => 'analytics-google',
    'middleware' => [ 'web', 'auth' ],
],
```

- **`enabled: false`** — routes are not registered. Livewire components still work; React and Vue components fail on the first fetch. Correct choice for headless / API-only apps that don't need the shared endpoints.
- **`prefix`** — mount point. Full paths become `<prefix>/overview` and `<prefix>/top-content`.
- **`middleware`** — applied to both routes. Default requires a session and an authenticated user.

## `GET /analytics-google/overview`

Route name: `analytics-google.overview`. Handled by `GaOverviewController::__invoke()`.

### Query parameters

| Parameter | Type | Default | Notes |
|---|---|---|---|
| `days` | int | `30` | Clamped to `1..DateRange::MAX_DAYS` (730). Non-numeric input falls back to `30`. |
| `property_id` | string | — | Optional override. Empty / non-string → uses `analytics-google.reporting.property_id`. |

### Success response (`200`)

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
        { "date": "2026-06-12", "sessions": 45, "users": 30, "page_views": 180 }
    ]
}
```

The shape is `GaOverviewData::toArray()` — see [[API Reference/GA Overview Data|`GaOverviewData`]].

## `GET /analytics-google/top-content`

Route name: `analytics-google.top-content`. Handled by `GaTopContentController::__invoke()`.

### Query parameters

| Parameter | Type | Default | Notes |
|---|---|---|---|
| `days` | int | `30` | Clamped to `1..730`. |
| `limit` | int | `10` | Clamped to `1..100`. |
| `property_id` | string | — | Optional override. |

### Success response (`200`)

```json
{
    "range": { "startDate": "29daysAgo", "endDate": "today" },
    "top_pages": [
        { "path": "/", "title": "Home", "views": 1234, "users": 800 }
    ],
    "top_events": [
        { "event": "page_view", "count": 4567, "users": 800 }
    ]
}
```

The shape is `GaTopContentData::toArray()` — see [[API Reference/GA Top Content Data|`GaTopContentData`]].

## Error responses

Both endpoints use the same error contract. Codes are strings so the React and Vue components can branch on them without parsing localized message text.

| HTTP status | `error` code | Cause | Recommended UI |
|---|---|---|---|
| `401` | `unauthenticated` | `$request->user()` is null. Middleware normally handles this, but the controllers double-check. | Redirect to login. |
| `409` | `not_connected` | The user has no active `GoogleConnection`. | Prompt to connect via the base package's `/google/auth/connect`. |
| `501` | `base_not_installed` | `BaseInstalled::check()` returned `false`. Also flipped into the body as `"baseInstalled": false`. | Show install instructions. |
| `502` | `reporting_error` | `ReportingException` from the Data API layer — auth, transport, or API error. | Show `message`; may indicate reconnect is needed. |

Error body shape:

```json
{
    "error": "not_connected",
    "message": "Connect a Google account to view analytics."
}
```

The `501` response additionally includes `"baseInstalled": false` so a caller can branch without parsing the code.

## The React / Vue clients

The shared TypeScript clients under `resources/js/shared/overview.ts` and `shared/top-content.ts` wrap these endpoints. Both throw a typed error on non-2xx responses so consumers can display code-driven UI:

```ts
import { fetchGaOverview, GaOverviewError } from './shared/overview'

try {
    const data = await fetchGaOverview({ days: 30 })
} catch (e) {
    if (e instanceof GaOverviewError) {
        e.code    // 'not_connected' | 'base_not_installed' | ...
        e.status  // 409, 501, ...
        e.message // localized message from server
    }
}
```

`FetchOverviewOptions.baseUrl` lets you point at a non-default mount path (useful if you moved `analytics-google.routes.prefix`) or at a stub in tests. Pass `fetchImpl` to inject a specific fetch implementation for SSR or tests.

## Related

- [[Components]] — the surfaces that consume these endpoints.
- [[Server-Side Reporting/Graceful Degradation|Graceful Degradation]] — the exception matrix that maps to each error code.
- [[Configuration]] — the full `routes.*` reference.
