---
title: Exceptions
---

# Exceptions

Two exception types live in `ArtisanPackUI\AnalyticsGoogle\Exceptions`. Both extend `\RuntimeException`.

## `BaseNotInstalledException`

`ArtisanPackUI\AnalyticsGoogle\Exceptions\BaseNotInstalledException` — thrown by the reporting side when the base [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) package is not installed.

### Thrown by

- `Ga4DataClient::runReport()` via `ensureBaseInstalled()`.
- `Ga4DataClient::runReport()` when the injected `TokenManager` is null (partial install).
- Caught by the Livewire reporting components and re-surfaced as an `install the base package` view branch.
- Caught by the HTTP controllers and translated to a `501 base_not_installed` JSON response.

### Factories

- **`forReporting(): self`** — the standard message: *"The artisanpack-ui/google base package is required for GA4 Data API reporting. Install it with `composer require artisanpack-ui/google` to enable server-side reporting features."*

### Example

```php
try {
    $response = $client->runReport( $request, $connection );
} catch ( BaseNotInstalledException $e ) {
    return view( 'settings.install-base', [ 'message' => $e->getMessage() ] );
}
```

## `ReportingException`

`ArtisanPackUI\AnalyticsGoogle\Exceptions\ReportingException` — wraps every low-level HTTP or configuration failure encountered while talking to the GA4 Data API into one typed exception.

### Thrown by

`Ga4DataClient::runReport()`.

### Factories

- **`missingConfiguration( string $key ): self`** — `"GA4 Data API configuration ':key' is missing."`. Fired when `property_id` is missing.
- **`missingConnection(): self`** — `"No connected Google account was resolved for the GA4 Data API request."`. Fired when no `GoogleConnection` can be located. (Currently unused by `Ga4DataClient` itself — the client requires a connection to be passed — but callers writing their own wrappers may want it.)
- **`apiError( int $status, string $body ): self`** — `"GA4 Data API returned an error (status :status): :body"`. Non-2xx response bodies are preserved verbatim so operators can read the GA4 error code from the message.
- **`authenticationFailed( Throwable $previous ): self`** — `"Reconnect your Google account to continue viewing analytics."`. Fired when `TokenManager` throws `TokenRefreshException`. The user needs to reconnect.
- **`transportFailure( Throwable $previous ): self`** — `"Could not reach the GA4 Data API: :message"`. Fired on `ConnectionException` (DNS, connection refused, timeout).

### Default handling

- The **Livewire components** capture into `$errorMessage` and render it inline.
- The **HTTP controllers** translate to a `502 reporting_error` JSON response.
- Custom callers should catch it themselves and render an appropriate message.

### Example

```php
try {
    $response = $client->runReport( $request, $connection );
} catch ( ReportingException $e ) {
    return redirect()->back()->with( 'error', $e->getMessage() );
}
```

## Related

- [Graceful Degradation](Server-Side-Reporting-Graceful-Degradation) — the exception-to-fix matrix.
- [HTTP Endpoints](HTTP-Endpoints) — how exceptions map to JSON error codes.
