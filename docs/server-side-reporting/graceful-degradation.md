---
title: Graceful Degradation
---

# Graceful Degradation

The package is deliberately safe to install without the base [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) package. This page documents exactly what "safe" means — what still works, what fails cleanly, and what error surfaces callers see.

## The detection primitive

`ArtisanPackUI\AnalyticsGoogle\Support\BaseInstalled::check()` returns `true` when both of these classes are loadable:

- `ArtisanPackUI\Google\Google`
- `ArtisanPackUI\Google\Tokens\TokenManager`

The result is memoized so repeated calls do not walk the autoloader. Tests can reset the cache via `BaseInstalled::reset()` or force a value with `BaseInstalled::setForTesting()`.

## What still works without the base

- The service provider boots and registers all its bindings.
- The `@ga4Snippet` Blade directive.
- `installGa4Snippet(...)` and the React / Vue `<Ga4Snippet />` components.
- The `analyticsGoogle()` helper, the `AnalyticsGoogle` facade, `AnalyticsGoogle::gtag()`, `AnalyticsGoogle::provider()`.
- The `Ga4Provider` and its registration with the analytics parent (still gates the tracker behind the parent's consent).

## What fails cleanly

- **`Ga4DataClient::runReport()`** — throws [[API Reference/Exceptions|`BaseNotInstalledException`]] before any HTTP work. The message is a plain-English `The artisanpack-ui/google base package is required for GA4 Data API reporting. Install it with 'composer require artisanpack-ui/google' to enable server-side reporting features.`
- **`Ga4DataClient::isAvailable()`** — returns `false`. Callers can check this to hide reporting UI cleanly instead of catching an exception.
- **`AnalyticsGoogle::dataClient()`** — returns `null`. Callers can feature-detect via `if ( analyticsGoogle()->hasReporting() )` rather than checking `isAvailable()`.
- **`AnalyticsGoogle::hasReporting()`** — returns `false`.
- **The Livewire components** — `GaOverview` and `GaTopContent` set `$baseInstalled = false` in `mount()` and skip the fetch. The published view branches on this flag to render an "install the base package" prompt instead of blowing up.
- **The HTTP endpoints** — `GaOverviewController` and `GaTopContentController` short-circuit and return a `501 base_not_installed` JSON response:

  ```json
  {
      "error": "base_not_installed",
      "message": "The GA4 overview requires the artisanpack-ui/google base package.",
      "baseInstalled": false
  }
  ```

  The React and Vue components branch on `error === 'base_not_installed'` and render a code block showing the install command.
- **The routes themselves** — not even registered when the base is missing (they call into a nonexistent `GoogleConnection` model, so registration would fail earlier). This is intentional; the check happens in `registerRoutes()`.
- **Scope contribution** — `registerGoogleScopeHook()` short-circuits, since there is no `ScopeRegistry` to contribute to.

## Feature detection

The most reliable check is `AnalyticsGoogle::hasReporting()`:

```php
if ( analyticsGoogle()->hasReporting() ) {
    // Reporting UI, controller wiring, jobs, etc.
}
```

Under the hood it composes `Ga4DataClient::isAvailable()` (which itself calls `BaseInstalled::check()`), so it stays honest even when a partial install has left one of the two required classes missing.

## Exception matrix

| Symptom | Exception | Fix |
|---|---|---|
| `Ga4DataClient::runReport()` throws with `The artisanpack-ui/google base package is required…` | `BaseNotInstalledException` | `composer require artisanpack-ui/google`. |
| HTTP 501 `base_not_installed` | same, surfaced as JSON | same. |
| Reporting throws `GA4 Data API configuration ":key" is missing.` | `ReportingException::missingConfiguration()` | Set `GA4_PROPERTY_ID`. |
| Reporting throws `Reconnect your Google account to continue viewing analytics.` | `ReportingException::authenticationFailed()` | User needs to reconnect on the base's `/google/auth/connect`. |
| Reporting throws `Could not reach the GA4 Data API: …` | `ReportingException::transportFailure()` | Network / firewall issue. |
| Reporting throws `GA4 Data API returned an error (status ...)` | `ReportingException::apiError()` | Read the body — usually a scope, property-access, or property-ID issue. |

Full exception reference: [[API Reference/Exceptions|Exceptions]].

## Related

- [[API Reference/Exceptions|Exceptions]]
- [[HTTP Endpoints]] — the response-shape details of the `501` and other error codes.
- [[Components]] — how the surfaces render the "install the base" prompt.
