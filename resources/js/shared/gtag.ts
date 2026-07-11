/**
 * Shared client-side gtag helpers used by the React and Vue tracking
 * components. Framework-agnostic; call `installGa4Snippet(...)` once
 * per app to inject the standard gtag.js script.
 */

export type Ga4Config = Record<string, unknown>

export interface InstallGa4Options {
    /** The GA4 measurement ID (e.g. "G-XXXXXXX"). */
    measurementId: string
    /** Extra `gtag('config', ...)` options. */
    config?: Ga4Config
    /** When true, wait for the `ap-analytics-consent` event before firing. */
    respectConsent?: boolean
}

declare global {
    interface Window {
        dataLayer?: unknown[]
        gtag?: (...args: unknown[]) => void
        __apAnalyticsConsent?: { analytics?: boolean }
    }
}

/**
 * Whether the current environment can run gtag.js — safe to call from SSR
 * because it does not touch `window`.
 */
export function canInstallGa4(): boolean {
    return typeof window !== 'undefined' && typeof document !== 'undefined'
}

/**
 * Install the standard gtag.js snippet with the given measurement ID.
 *
 * Multiple calls with the same measurement ID are a no-op; the snippet
 * is only injected once per page.
 */
export function installGa4Snippet(options: InstallGa4Options): void {
    if (!canInstallGa4()) {
        return
    }
    if (!options.measurementId) {
        return
    }

    const marker = `ap-ga4-${options.measurementId}`
    if (document.getElementById(marker)) {
        return
    }

    const script = document.createElement('script')
    script.async = true
    script.id = marker
    script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(options.measurementId)}`
    document.head.appendChild(script)

    window.dataLayer = window.dataLayer || []
    const gtag = (...args: unknown[]): void => {
        window.dataLayer!.push(args)
    }
    window.gtag = gtag

    gtag('js', new Date())

    if (options.respectConsent !== false) {
        const granted = window.__apAnalyticsConsent?.analytics === true
        if (!granted) {
            gtag('consent', 'default', { analytics_storage: 'denied' })
        }
    }

    gtag('config', options.measurementId, options.config ?? {})
}
