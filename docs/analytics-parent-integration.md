---
title: Analytics Parent Integration
---

# Analytics Parent Integration

When [`artisanpack-ui/analytics`](https://github.com/ArtisanPack-UI/analytics) is installed, this package registers itself as the `google-ga4` provider so client-side tracking runs behind the parent's consent gate instead of firing unconditionally.

## What "provider" means

The analytics parent owns:

- A **consent banner** the user interacts with once.
- A **provider registry** that lets service packages contribute trackers.
- An `@analyticsScripts` output that emits every enabled provider's tracker only after consent is granted.

This package contributes an `AnalyticsProviderInterface` implementation that delegates configuration reads to the underlying `Ga4Provider` and returns the `gtag.js` snippet for the parent to inject.

## Registration flow

The service provider registers on `booted` so the parent's own provider has already had a chance to bind its container entries:

```php
$this->app->booted( function (): void {
    $analytics = $this->app->make( \ArtisanPackUI\Analytics\Analytics::class );

    $providerName = (string) $this->app[ 'config' ]->get( 'analytics-google.provider_name', 'google-ga4' );

    $analytics->extend( $providerName, static function ( $app ) use ( $providerName ) {
        $ga4 = $app->make( Ga4Provider::class );

        return new Providers\Ga4AnalyticsProviderAdapter(
            $ga4,
            $providerName,
            $app->make( MeasurementProtocol::class ),
        );
    } );
} );
```

The third argument is what makes `trackPageView()` and `trackEvent()` forward. It
defaults to `null` so existing instantiations keep working, but an adapter built
without it silently does not forward — if you are registering the adapter
yourself rather than relying on this package's service provider, pass it.

## Enabling the provider

On the analytics parent's config (`config/artisanpack/analytics.php` or equivalent), add `google-ga4` to `active_providers`:

```php
'active_providers' => [
    'google-ga4',
    // …other providers
],
```

Rename via `analytics-google.provider_name` if you already have a service registered under `google-ga4`.

## The adapter

`ArtisanPackUI\AnalyticsGoogle\Providers\Ga4AnalyticsProviderAdapter` implements the parent's `AnalyticsProviderInterface`:

- `getName(): string` — returns the configured provider name.
- `isEnabled(): bool` — true when *either* the client-side tag or server-side forwarding is configured. Both are checked because the parent drops providers reporting `false` from `getActiveProviders()`, so answering on the tag alone would silently disable forwarding for anyone who turned the snippet off.
- `getConfig(): array` — returns `{ measurement_id, respect_consent, options }` for the parent's dashboard status view.
- `trackerScript(): string` — returns the `Gtag::render()` output for `@analyticsScripts`.
- `trackPageView(PageViewData $data): void` — forwards to GA4 over the Measurement Protocol.
- `trackEvent(EventData $data): void` — forwards to GA4 over the Measurement Protocol.

> **Changed in 1.1.0.** `trackPageView()` and `trackEvent()` were previously no-ops. Adding `google-ga4` to the parent's `active_providers` registered a provider that sent nothing and reported no error, which reads as a configuration problem from the consuming application. They now forward.

### Server-side forwarding

Forwarding needs an API secret in addition to the measurement ID. Create one in
GA4 under **Admin → Data Streams → (your stream) → Measurement Protocol API
secrets**:

```dotenv
GA4_MEASUREMENT_ID=G-XXXXXXX
GA4_API_SECRET=your-measurement-protocol-secret
ANALYTICS_ACTIVE_PROVIDERS=local,google-ga4
```

Forwarding stays off until both credentials are present, and can be switched
off independently with `tracking.server_side` (`GA4_SERVER_SIDE_TRACKING=false`).

**Do not run both halves against the same events.** Client-side `gtag.js` and
server-side forwarding are complementary rather than alternatives, but anything
they both observe is counted twice. Per event stream, either render the snippet
*or* forward.

Worth knowing before relying on forwarding:

- Measurement Protocol hits carry less attribution than a `gtag.js` tag.
  Referrer, geography, device and session stitching are weaker or absent unless
  supplied explicitly, so reports will not match a client-side tag.
- The parent's visitor ID is reused as the GA4 `client_id`, so hits from one
  visitor group into a single GA4 user. Where the parent has no visitor ID —
  anonymous mode, for instance — a per-hit random ID is generated instead, and
  GA4 will read those hits as separate users.
- **Sessions are GA4's, not the parent's.** GA4 requires `session_id` to match
  `^\d+$`, and the parent's session identifiers are UUIDs. A UUID is dropped
  rather than sent, because GA4 rejects or mis-attributes an invalid one
  instead of erroring, and GA4 derives its own session instead. Forwarded hits
  therefore will not share session boundaries with the parent's dashboard.
- Event and parameter names are coerced to GA4's rules: letters, digits and
  underscores, starting with a letter, 40 characters. Names starting with a
  digit or underscore, or with the reserved `ga_` / `google_` / `firebase_`
  prefixes, are prefixed with `e_` — GA4 discards non-conforming events
  silently, so a rejected name looks exactly like a working one.

To debug a payload that sends successfully but never appears in reports, set
`GA4_MEASUREMENT_PROTOCOL_DEBUG=true` to target GA4's validation endpoint,
which reports problems the live endpoint accepts silently.

The analytics parent also ships its own Measurement-Protocol provider under the
name `google`, configured through a separate `ANALYTICS_GOOGLE_*` env surface.
Use one or the other; running both against the same property double-counts.

## Conditional loading

The adapter references types from `ArtisanPackUI\Analytics\Contracts\*` — those symbols only exist when the parent is installed. The service provider guards registration behind `class_exists()` and `interface_exists()` so the standalone code path (without the parent) never touches the adapter class:

```php
if ( ! class_exists( \ArtisanPackUI\Analytics\Analytics::class ) ) {
    return;
}
if ( ! interface_exists( \ArtisanPackUI\Analytics\Contracts\AnalyticsProviderInterface::class ) ) {
    return;
}
```

## Consent gate vs. built-in `respect_consent`

Two independent mechanisms combine when the parent is installed:

1. **Provider gate** — the parent only emits enabled providers' tracker scripts after the user has granted consent. If the user rejects, `@analyticsScripts` skips this package's snippet entirely.
2. **Built-in `respect_consent` guard** — even if the snippet does end up on the page, the emitted `gtag('consent', 'default', …)` respects `window.__apAnalyticsConsent.analytics`. See [Consent Integration](Client-Side-Tracking-Consent-Integration).

In practice the two agree: the parent writes `window.__apAnalyticsConsent` when it grants the category and, at the same time, includes the provider in its output. The double guard means a race between the two mechanisms cannot briefly fire tracking against a user who hasn't consented.

## When the parent is not installed

The package still boots, the `Ga4Provider` still exists in the container, and `AnalyticsGoogle::provider()` still returns a valid instance — but nothing wires it up to a consent banner. In this mode the `@ga4Snippet` directive is the only path to emit the tracker, and its `respect_consent` guard is the only defence.

## Related

- [Consent Integration](Client-Side-Tracking-Consent-Integration) — the browser-side consent flag details.
- [`Ga4Provider`](API-Reference-GA4-Provider) — the provider class this adapter delegates to.
- [Configuration](Configuration) — the `provider_name` key.
