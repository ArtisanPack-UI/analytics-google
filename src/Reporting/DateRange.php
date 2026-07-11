<?php

/**
 * Immutable start/end date range for a GA4 Data API request.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\Reporting;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Throwable;

/**
 * Simple value object wrapping a start and end date in the format the
 * GA4 Data API expects (YYYY-MM-DD, plus the aliases "today",
 * "yesterday", and "NdaysAgo" that GA4 accepts natively).
 *
 * @since 1.0.0
 */
final class DateRange
{
    private const RELATIVE_PATTERN = '/^(today|yesterday|\d+daysAgo)$/i';

    /**
     * @param  string  $startDate  Start date (YYYY-MM-DD or GA4 alias).
     * @param  string  $endDate  End date (YYYY-MM-DD or GA4 alias).
     */
    public function __construct(
        public readonly string $startDate,
        public readonly string $endDate,
    ) {
        $this->guard( $startDate, 'startDate' );
        $this->guard( $endDate, 'endDate' );
    }

    /**
     * Build a range covering the last N days ending today.
     *
     * @since 1.0.0
     */
    public static function lastDays( int $days ): self
    {
        if ( $days < 1 ) {
            throw new InvalidArgumentException( 'DateRange::lastDays expects a positive day count.' );
        }

        return new self( ( $days - 1 ) . 'daysAgo', 'today' );
    }

    /**
     * Build a range from two DateTime-like values.
     *
     * @since 1.0.0
     */
    public static function between( DateTimeInterface|string $start, DateTimeInterface|string $end ): self
    {
        return new self(
            $start instanceof DateTimeInterface ? $start->format( 'Y-m-d' ) : (string) $start,
            $end instanceof DateTimeInterface ? $end->format( 'Y-m-d' ) : (string) $end,
        );
    }

    /**
     * Serialize the range to the shape the Data API expects inside a
     * runReport request.
     *
     * @since 1.0.0
     *
     * @return array{startDate: string, endDate: string}
     */
    public function toArray(): array
    {
        return [
            'startDate' => $this->startDate,
            'endDate'   => $this->endDate,
        ];
    }

    /**
     * Guard that the raw string is either a GA4 relative alias or a
     * parseable calendar date. Runs at construction so callers get an
     * error at the point they build the range, not deep inside an API
     * response parser.
     */
    private function guard( string $value, string $label ): void
    {
        if ( '' === $value ) {
            throw new InvalidArgumentException( sprintf( 'DateRange::%s cannot be empty.', $label ) );
        }

        if ( 1 === preg_match( self::RELATIVE_PATTERN, $value ) ) {
            return;
        }

        try {
            Carbon::parse( $value );
        } catch ( Throwable $e ) {
            throw new InvalidArgumentException(
                sprintf( 'DateRange::%s value "%s" is not a valid GA4 date or alias.', $label, $value ),
                previous: $e,
            );
        }
    }
}
