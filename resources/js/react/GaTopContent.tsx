import { useCallback, useEffect, useState } from 'react'
import {
    fetchGaTopContent,
    GaTopContentError,
    type GaTopContentData,
    type FetchTopContentOptions,
} from '../shared/top-content'

export type { GaTopContentData }

export interface GaTopContentProps {
    /** Initial range to load. Defaults to 30. */
    initialDays?: number
    /** Rows per list. Defaults to 10. */
    limit?: number
    /** Optional property ID override. */
    propertyId?: string | null
    /** Overrides the endpoint URL. */
    baseUrl?: string
    /** Override the fetch implementation. */
    fetchImpl?: FetchTopContentOptions['fetchImpl']
}

const RANGE_OPTIONS = [7, 14, 30, 90]

export function GaTopContent(props: GaTopContentProps): JSX.Element {
    const { initialDays = 30, limit = 10, propertyId, baseUrl, fetchImpl } = props

    const [days, setDays] = useState<number>(initialDays)
    const [data, setData] = useState<GaTopContentData | null>(null)
    const [errorMessage, setErrorMessage] = useState<string | null>(null)
    const [errorCode, setErrorCode] = useState<string | null>(null)
    const [loading, setLoading] = useState<boolean>(false)

    const load = useCallback(
        async (nextDays: number, signal: AbortSignal) => {
            setLoading(true)
            setErrorMessage(null)
            setErrorCode(null)
            try {
                const result = await fetchGaTopContent({
                    days: nextDays,
                    limit,
                    propertyId,
                    baseUrl,
                    fetchImpl,
                    signal,
                })
                if (signal.aborted) return
                setData(result)
            } catch (e) {
                if (signal.aborted) return
                if (e instanceof GaTopContentError) {
                    setErrorCode(e.code)
                    setErrorMessage(e.message)
                } else if (e instanceof Error) {
                    setErrorMessage(e.message)
                }
            } finally {
                if (!signal.aborted) {
                    setLoading(false)
                }
            }
        },
        [limit, propertyId, baseUrl, fetchImpl],
    )

    useEffect(() => {
        const controller = new AbortController()
        load(days, controller.signal)
        return () => controller.abort()
    }, [days, load])

    if (errorCode === 'base_not_installed') {
        return (
            <div className="ap-ga-top-content__missing-base" role="alert">
                <p>{errorMessage}</p>
                <pre>
                    <code>composer require artisanpack-ui/google</code>
                </pre>
            </div>
        )
    }

    return (
        <div className="ap-ga-top-content">
            <header className="ap-ga-top-content__header">
                <h2 className="ap-ga-top-content__title">Top pages and events</h2>
                <label className="ap-ga-top-content__range">
                    <span className="ap-ga-top-content__range-label">Range</span>
                    <select
                        value={days}
                        onChange={(event) => setDays(Number(event.target.value))}
                        disabled={loading}
                    >
                        {RANGE_OPTIONS.map((option) => (
                            <option key={option} value={option}>
                                Last {option} days
                            </option>
                        ))}
                    </select>
                </label>
            </header>

            {errorMessage ? (
                <div className="ap-ga-top-content__error" role="alert">
                    {errorMessage}
                </div>
            ) : (
                <div className="ap-ga-top-content__grid">
                    <section
                        className="ap-ga-top-content__panel"
                        aria-labelledby="ap-ga-top-pages-heading"
                    >
                        <h3 id="ap-ga-top-pages-heading">Top pages</h3>
                        {data && data.top_pages.length > 0 ? (
                            <table className="ap-ga-top-content__table">
                                <thead>
                                    <tr>
                                        <th scope="col">Page</th>
                                        <th scope="col">Views</th>
                                        <th scope="col">Users</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.top_pages.map((row, index) => (
                                        <tr key={`${index}:${row.path}`}>
                                            <th scope="row">
                                                <span className="ap-ga-top-content__page-title">
                                                    {row.title !== '' ? row.title : row.path}
                                                </span>
                                                {row.title !== '' && row.title !== row.path && (
                                                    <span className="ap-ga-top-content__page-path">
                                                        {row.path}
                                                    </span>
                                                )}
                                            </th>
                                            <td>{formatInt(row.views)}</td>
                                            <td>{formatInt(row.users)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        ) : (
                            <p className="ap-ga-top-content__empty">No page views for this range yet.</p>
                        )}
                    </section>

                    <section
                        className="ap-ga-top-content__panel"
                        aria-labelledby="ap-ga-top-events-heading"
                    >
                        <h3 id="ap-ga-top-events-heading">Top events</h3>
                        {data && data.top_events.length > 0 ? (
                            <table className="ap-ga-top-content__table">
                                <thead>
                                    <tr>
                                        <th scope="col">Event</th>
                                        <th scope="col">Count</th>
                                        <th scope="col">Users</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.top_events.map((row, index) => (
                                        <tr key={`${index}:${row.event}`}>
                                            <th scope="row">{row.event}</th>
                                            <td>{formatInt(row.count)}</td>
                                            <td>{formatInt(row.users)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        ) : (
                            <p className="ap-ga-top-content__empty">No events for this range yet.</p>
                        )}
                    </section>
                </div>
            )}
        </div>
    )
}

function formatInt(value: number | undefined): string {
    return new Intl.NumberFormat().format(Math.round(value ?? 0))
}
