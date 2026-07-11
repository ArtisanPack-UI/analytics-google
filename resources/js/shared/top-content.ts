/**
 * Shared client for the GA top-content HTTP endpoint. Consumed by the
 * React and Vue components so both frameworks talk to the exact shape
 * the server serialises.
 */

export interface GaTopPageRow {
    path: string
    title: string
    views: number
    users: number
}

export interface GaTopEventRow {
    event: string
    count: number
    users: number
}

export interface GaTopContentData {
    range: { startDate: string; endDate: string }
    top_pages: GaTopPageRow[]
    top_events: GaTopEventRow[]
}

export interface FetchTopContentOptions {
    /** Base URL for the endpoint. Defaults to `/analytics-google/top-content`. */
    baseUrl?: string
    /** Number of days to include in the range. Defaults to 30. */
    days?: number
    /** Maximum rows per list. Defaults to 10. */
    limit?: number
    /** Optional property ID override. */
    propertyId?: string | null
    /** Optional fetch instance override, useful for SSR / testing. */
    fetchImpl?: typeof fetch
    /** Optional AbortSignal to cancel the request. */
    signal?: AbortSignal
}

export class GaTopContentError extends Error {
    public readonly code: string
    public readonly status: number
    constructor(code: string, status: number, message: string) {
        super(message)
        this.name = 'GaTopContentError'
        this.code = code
        this.status = status
    }
}

/**
 * Fetch the top-content payload from the server.
 *
 * Throws {@link GaTopContentError} on any non-2xx response so consumers
 * can display code-driven UI (e.g. "connect a Google account") rather
 * than parsing raw JSON.
 */
export async function fetchGaTopContent(options: FetchTopContentOptions = {}): Promise<GaTopContentData> {
    const {
        baseUrl = '/analytics-google/top-content',
        days = 30,
        limit = 10,
        propertyId = null,
        fetchImpl = typeof fetch !== 'undefined' ? fetch : undefined,
        signal,
    } = options

    if (!fetchImpl) {
        throw new GaTopContentError(
            'no_fetch',
            0,
            'A global fetch implementation is not available. Pass options.fetchImpl explicitly.',
        )
    }

    const params = new URLSearchParams({ days: String(days), limit: String(limit) })
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
        throw new GaTopContentError(code, response.status, message)
    }

    return (await response.json()) as GaTopContentData
}
