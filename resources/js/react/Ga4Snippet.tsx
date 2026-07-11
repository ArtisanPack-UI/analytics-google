import { useEffect } from 'react'
import { installGa4Snippet, type InstallGa4Options } from '../shared/gtag'

/**
 * React component that injects the GA4 gtag.js snippet once on mount.
 *
 * The equivalent of the `@ga4Snippet` Blade directive for React apps:
 *
 * ```tsx
 * <Ga4Snippet measurementId="G-XXXXXXX" />
 * ```
 */
export function Ga4Snippet(props: InstallGa4Options): null {
    useEffect(() => {
        installGa4Snippet(props)
    }, [props.measurementId])

    return null
}
