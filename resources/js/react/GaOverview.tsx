import { useCallback, useEffect, useState } from 'react'
import {
    fetchGaOverview,
    GaOverviewError,
    type GaOverviewData,
    type FetchOverviewOptions,
} from '../shared/overview'

export type { GaOverviewData }

export interface GaOverviewProps {
    /** Initial range to load. Defaults to 30. */
    initialDays?: number
    /** Optional property ID override. */
    propertyId?: string | null
    /** Overrides the endpoint URL (useful for tests / non-default mount). */
    baseUrl?: string
    /** Override the fetch implementation. */
    fetchImpl?: FetchOverviewOptions['fetchImpl']
}

const RANGE_OPTIONS = [7, 14, 30, 90]

export function GaOverview(props: GaOverviewProps): JSX.Element {
    const { initialDays = 30, propertyId, baseUrl, fetchImpl } = props

    const [days, setDays] = useState<number>(initialDays)
    const [data, setData] = useState<GaOverviewData | null>(null)
    const [errorMessage, setErrorMessage] = useState<string | null>(null)
    const [errorCode, setErrorCode] = useState<string | null>(null)
    const [loading, setLoading] = useState<boolean>(false)

    const load = useCallback(
        async (nextDays: number, signal: AbortSignal) => {
            setLoading(true)
            setErrorMessage(null)
            setErrorCode(null)
            try {
                const result = await fetchGaOverview({
                    days: nextDays,
                    propertyId,
                    baseUrl,
                    fetchImpl,
                    signal,
                })
                if (signal.aborted) return
                setData(result)
            } catch (e) {
                if (signal.aborted) return
                if (e instanceof GaOverviewError) {
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
        [propertyId, baseUrl, fetchImpl],
    )

    useEffect(() => {
        const controller = new AbortController()
        load(days, controller.signal)
        return () => controller.abort()
    }, [days, load])

    if (errorCode === 'base_not_installed') {
        return (
            <div className="ap-ga-overview__missing-base" role="alert">
                <p>{errorMessage}</p>
                <pre>
                    <code>composer require artisanpack-ui/google</code>
                </pre>
            </div>
        )
    }

    const maxSessions = data?.trend.reduce((max, row) => Math.max(max, row.sessions), 0) || 1

    return (
        <div className="ap-ga-overview">
            <header className="ap-ga-overview__header">
                <h2 className="ap-ga-overview__title">Google Analytics overview</h2>
                <label className="ap-ga-overview__range">
                    <span className="ap-ga-overview__range-label">Range</span>
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
                <div className="ap-ga-overview__error" role="alert">
                    {errorMessage}
                </div>
            ) : (
                <>
                    <dl className="ap-ga-overview__totals">
                        <Tile label="Sessions" value={formatInt(data?.totals.sessions)} />
                        <Tile label="Users" value={formatInt(data?.totals.users)} />
                        <Tile label="Page views" value={formatInt(data?.totals.page_views)} />
                        <Tile
                            label="Avg. engagement"
                            value={`${formatMinutes(data?.totals.avg_engagement_seconds)} min`}
                        />
                    </dl>

                    <figure className="ap-ga-overview__chart" aria-label="Daily sessions and users trend">
                        {data && data.trend.length > 0 ? (
                            <ol className="ap-ga-overview__bars">
                                {data.trend.map((row) => {
                                    const height = Math.min(100, Math.round((row.sessions / maxSessions) * 100))
                                    return (
                                        <li
                                            key={row.date}
                                            className="ap-ga-overview__bar"
                                            style={{ ['--ap-bar-height' as string]: `${height}%` }}
                                            title={`${row.date}: ${formatInt(row.sessions)} sessions`}
                                        >
                                            <span className="visually-hidden">
                                                {row.date} — {formatInt(row.sessions)} sessions
                                            </span>
                                        </li>
                                    )
                                })}
                            </ol>
                        ) : (
                            <p className="ap-ga-overview__empty">No data for this range yet.</p>
                        )}
                    </figure>
                </>
            )}
        </div>
    )
}

function Tile({ label, value }: { label: string; value: string }): JSX.Element {
    return (
        <div className="ap-ga-overview__tile">
            <dt>{label}</dt>
            <dd>{value}</dd>
        </div>
    )
}

function formatInt(value: number | undefined): string {
    return new Intl.NumberFormat().format(Math.round(value ?? 0))
}

function formatMinutes(seconds: number | undefined): string {
    return ((seconds ?? 0) / 60).toFixed(1)
}
