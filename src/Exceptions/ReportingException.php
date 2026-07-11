<?php

/**
 * Exception raised when a GA4 Data API request fails.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Wraps low-level HTTP or configuration failures encountered while
 * talking to the GA4 Data API into a single typed exception callers
 * can catch and surface to their users.
 *
 * @since 1.0.0
 */
class ReportingException extends RuntimeException
{
    /**
     * Raised when a required configuration value is missing.
     *
     * @since 1.0.0
     */
    public static function missingConfiguration( string $key ): self
    {
        return new self( __( 'GA4 Data API configuration ":key" is missing.', [ 'key' => $key ] ) );
    }

    /**
     * Raised when no connected Google account is available to authenticate
     * the request.
     *
     * @since 1.0.0
     */
    public static function missingConnection(): self
    {
        return new self( __( 'No connected Google account was resolved for the GA4 Data API request.' ) );
    }

    /**
     * Raised when the Data API returned a non-2xx response.
     *
     * @since 1.0.0
     */
    public static function apiError( int $status, string $body ): self
    {
        return new self( __( 'GA4 Data API returned an error (status :status): :body', [
            'status' => $status,
            'body'   => $body,
        ] ) );
    }

    /**
     * Raised when the OAuth token could not be refreshed / is no longer
     * valid — surfaced separately so callers can prompt the user to
     * reconnect the Google account instead of blaming GA4.
     *
     * @since 1.0.0
     */
    public static function authenticationFailed( Throwable $previous ): self
    {
        return new self(
            __( 'Reconnect your Google account to continue viewing analytics.' ),
            0,
            $previous,
        );
    }

    /**
     * Raised when the Data API call itself could not be delivered
     * (connection refused, DNS failure, timeout) — the transport layer
     * failed before any HTTP status was received.
     *
     * @since 1.0.0
     */
    public static function transportFailure( Throwable $previous ): self
    {
        return new self(
            __( 'Could not reach the GA4 Data API: :message', [ 'message' => $previous->getMessage() ] ),
            0,
            $previous,
        );
    }
}
