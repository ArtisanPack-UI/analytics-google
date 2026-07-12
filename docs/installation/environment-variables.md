---
title: Environment Variables
---

# Environment Variables

Every env var the package reads, in one place.

## Core

| Variable | Type | Default | Read by |
|---|---|---|---|
| `GA4_MEASUREMENT_ID` | string | `null` | `analytics-google.tracking.measurement_id`. The GA4 measurement ID (`G-XXXXXXX`) used by the `@ga4Snippet` directive and the React/Vue `Ga4Snippet` components. Empty → tracker emits nothing. |
| `GA4_TRACKING_ENABLED` | bool | `true` | `analytics-google.tracking.enabled`. Master switch for the tracker. Set to `false` to disable in staging / CI without unsetting the measurement ID. |
| `GA4_PROPERTY_ID` | string | `null` | `analytics-google.reporting.property_id`. The numeric GA4 property ID Data API queries target. Missing → `Ga4DataClient::runReport()` throws [`ReportingException::missingConfiguration()`](API-Reference-Exceptions). |

## Framework env vars this package leans on

| Variable | Why it matters |
|---|---|
| `APP_KEY` | Not read directly here. The base [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) package uses it to encrypt the OAuth tokens `Ga4DataClient` pulls via `TokenManager`. Rotate carefully. |
| `CACHE_STORE` (Laravel 11+) / `CACHE_DRIVER` (Laravel 10) | The store used to cache `runReport` responses when `analytics-google.reporting.cache_ttl > 0`. |
| `SESSION_DRIVER` | Not read directly. The base package's OAuth flow needs a working session driver to complete the handshake that produces a valid `GoogleConnection`. |

## Not env-backed

These keys have no env fallback and must be set in `config/analytics-google.php` if you want to override them:

- `analytics-google.tracking.config` — extra `gtag('config', ...)` options.
- `analytics-google.tracking.respect_consent` — whether the emitted snippet defers to the analytics parent's consent gate.
- `analytics-google.reporting.api_base` — GA4 Data API base URL.
- `analytics-google.reporting.timeout` — request timeout in seconds.
- `analytics-google.reporting.cache_ttl` — cache TTL in seconds.
- `analytics-google.provider_name` — the name registered with the analytics parent.
- `analytics-google.scopes` — Google OAuth scopes contributed to the base's registry.
- `analytics-google.routes.enabled` / `.prefix` / `.middleware` — HTTP endpoint configuration.

Full description of every key: [Configuration reference](Installation-Configuration).

## Example `.env`

Tracking only (base not installed, no reporting):

```env
APP_URL=https://your-app.test
APP_KEY=base64:...

GA4_MEASUREMENT_ID=G-XXXXXXX
```

Tracking + reporting (base installed):

```env
APP_URL=https://your-app.test
APP_KEY=base64:...

GA4_MEASUREMENT_ID=G-XXXXXXX
GA4_PROPERTY_ID=123456789

# The base package's env vars — see the google package's docs.
GOOGLE_CLIENT_ID=1234567890-abcdef.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxxxxxxxxxxxxxxxxxxx
GOOGLE_REDIRECT_URI=https://your-app.test/google/auth/callback
```

Disabled in a test environment:

```env
GA4_TRACKING_ENABLED=false
```

Leave `GA4_MEASUREMENT_ID` in place if you want; it will still be ignored while `GA4_TRACKING_ENABLED=false`.
