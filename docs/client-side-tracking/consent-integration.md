---
title: Consent Integration
---

# Consent Integration

The client-side tracker respects a per-browser consent flag by default. This page documents exactly how that gate works, how it plays with the [`artisanpack-ui/analytics`](https://github.com/ArtisanPack-UI/analytics) parent, and how to bypass it when you need to.

## The consent flag

The tracker checks a single boolean when it runs:

```js
window.__apAnalyticsConsent?.analytics === true
```

- **`true`** — tracking fires normally.
- **anything else** — the tracker sets `gtag('consent', 'default', { analytics_storage: 'denied' })` before configuring the property. Google respects `analytics_storage: 'denied'` by not writing cookies and not sending events until consent is granted.

The flag is a plain browser-side toggle. Nothing on the server reads or writes it.

## With the analytics parent installed

When [`artisanpack-ui/analytics`](https://github.com/ArtisanPack-UI/analytics) is present, its consent banner takes ownership of the flag:

1. The parent renders its banner on first load.
2. The user grants the `analytics` category.
3. The parent writes `window.__apAnalyticsConsent = { analytics: true, ... }`.
4. This package's tracker — whether from `@ga4Snippet`, `<Ga4Snippet />` in React, or `<Ga4Snippet />` in Vue — respects the flag on the *next* page load, when the snippet re-evaluates the guard. Existing pages that were rendered before consent do not retroactively fire; only navigation refreshes the guard.

## Without the analytics parent installed

Nothing writes `window.__apAnalyticsConsent`, so the guard remains `undefined` and the tracker deliberately keeps `analytics_storage: 'denied'`.

If you're standalone and don't want the guard at all, disable it explicitly — see below.

## Disabling the guard

Set `analytics-google.tracking.respect_consent` to `false`:

```php
'tracking' => [
    'respect_consent' => false,
],
```

For the React and Vue components, pass `respectConsent={false}` / `:respect-consent="false"` on the `<Ga4Snippet />` element.

With the guard off, the emitted snippet skips the `gtag('consent', 'default', …)` call entirely and `gtag('config', …)` runs on every page load.

## Provider registration and the parent's consent gate

Independent of the guard above, the package registers itself as the `google-ga4` provider with the analytics parent via `Analytics::extend()`. This is what causes the parent to include the tracker in its `@analyticsScripts` output only after consent. See [[Analytics Parent Integration]] for how the two mechanisms compose.

## Debugging

- Confirm the emitted snippet with browser DevTools → **Elements** — look for the injected `<script async src="https://www.googletagmanager.com/gtag/js?id=G-…">` tag.
- To check the guard's current value: `window.__apAnalyticsConsent?.analytics` in the console.
- If tracking never fires: set `respect_consent: false` temporarily to confirm the tracker itself is loading, then re-enable and debug the flag.

## Related

- [[Analytics Parent Integration]] — how the `google-ga4` provider registration works.
- [[Client-Side Tracking/Blade|Blade]], [[Client-Side Tracking/React|React]], [[Client-Side Tracking/Vue|Vue]] — the three renderers all obey the same guard.
