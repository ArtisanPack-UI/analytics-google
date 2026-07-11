<?php

/**
 * Exception thrown when the google base package is required but absent.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\Exceptions;

use RuntimeException;

/**
 * Thrown by reporting APIs when the `artisanpack-ui/google` base package
 * is not installed. Catching this exception lets callers show a clear
 * install/upgrade message instead of a class-not-found fatal.
 *
 * @since 1.0.0
 */
class BaseNotInstalledException extends RuntimeException
{
    /**
     * Build a helpful default message pointing at the missing dependency.
     *
     * @since 1.0.0
     */
    public static function forReporting(): self
    {
        return new self( __( 'The artisanpack-ui/google base package is required for GA4 Data API reporting. Install it with `composer require artisanpack-ui/google` to enable server-side reporting features.' ) );
    }
}
