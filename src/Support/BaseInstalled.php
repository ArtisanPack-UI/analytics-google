<?php

/**
 * Presence check for the `artisanpack-ui/google` base package.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\Support;

/**
 * A cheap, cache-friendly detector for whether the base google package
 * is installed. Used to gate reporting APIs and the reporting Livewire
 * components so the package remains bootable when the base is absent.
 *
 * @since 1.0.0
 */
final class BaseInstalled
{
    /**
     * Cached result so repeated calls do not walk the autoloader.
     */
    private static ?bool $cached = null;

    /**
     * Return true when the base package's public entry points are all
     * loadable. Uses `class_exists()` on the two classes reporting
     * touches (`Google` aggregator and `TokenManager`) so a partial
     * install is treated as absent.
     *
     * @since 1.0.0
     */
    public static function check(): bool
    {
        if ( null !== self::$cached ) {
            return self::$cached;
        }

        return self::$cached = class_exists( \ArtisanPackUI\Google\Google::class )
            && class_exists( \ArtisanPackUI\Google\Tokens\TokenManager::class );
    }

    /**
     * Reset the memoized result. Only used by tests.
     *
     * @since 1.0.0
     */
    public static function reset(): void
    {
        self::$cached = null;
    }

    /**
     * Force the memoized result to a specific value. Test-only helper
     * for exercising the "base not installed" branch without literally
     * uninstalling the package from the autoloader.
     *
     * @since 1.0.0
     */
    public static function setForTesting( ?bool $value ): void
    {
        self::$cached = $value;
    }
}
