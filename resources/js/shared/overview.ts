/**
 * Shared client for the GA overview HTTP endpoint. Consumed by the
 * React and Vue components so both frameworks talk to the exact
 * shape the server serialises.
 */

export interface GaOverviewTotals {
    sessions: number
    users: number
    page_views: number
    avg_engagement_seconds: number
}

export interface GaOverviewTrendPoint {
    date: string
    sessions: number
    users: number
    page_views: number
}

export interface GaOverviewData {
    range: { startDate: string; endDate: string }
    totals: GaOverviewTotals
    trend: GaOverviewTrendPoint[]
}

export interface FetchOverviewOptions {
    /** Base URL for the overview endpoint. Defaults to `/analytics-google/overview`. */
    baseUrl?: string
    /** Number of days to include in the range. Defaults to 30. */
    days?: number
    /** Optional property ID override. Uses the server-configured property when omitted. */
    propertyId?: string | null
    /** Optional fetch instance override, useful for SSR / testing. */
    fetchImpl?: typeof fetch
    /** Optional AbortSignal to cancel the request. */
    signal?: AbortSignal
}

export class GaOverviewError extends Error {
    public readonly code: string
    public readonly status: number
    constructor(code: string, status: number, message: string) {
        super(message)
        this.name = 'GaOverviewError'
        this.code = code
        this.status = status
    }
}

/**
 * Fetch the overview payload from the server.
 *
 * Throws {@link GaOverviewError} on any non-2xx response so consumers
 * can display code-driven UI (e.g. "connect a Google account") rather
 * than parsing raw JSON.
 */
export async function fetchGaOverview(options: FetchOverviewOptions = {}): Promise<GaOverviewData> {
    const {
        baseUrl = '/analytics-google/overview',
        days = 30,
        propertyId = null,
        fetchImpl = typeof fetch !== 'undefined' ? fetch : undefined,
        signal,
    } = options

    if (!fetchImpl) {
        throw new GaOverviewError(
            'no_fetch',
            0,
            'A global fetch implementation is not available. Pass options.fetchImpl explicitly.',
        )
    }

    const params = new URLSearchParams({ days: String(days) })
    if (propertyId) {
        params.set('property_id', propertyId)
    }

    const url = `${baseUrl}?${params.toString()}`
    const response = await fetchImpl(url, {
        headers: { Accept: 'application/json' },
        signal,
        credentials: 'same-origin',
    })

    if (!response.ok) {
        let code = 'http_error'
        let message = `Request failed with status ${response.status}`
        try {
            const body = (await response.json()) as { error?: string; message?: string }
            if (body?.error) code = body.error
            if (body?.message) message = body.message
        } catch {
            /* body wasn't JSON — keep the defaults */
        }
        throw new GaOverviewError(code, response.status, message)
    }

    return (await response.json()) as GaOverviewData
}
