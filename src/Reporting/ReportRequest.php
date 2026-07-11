<?php

/**
 * Immutable description of a GA4 runReport request.
 *
 * @package    ArtisanPack_UI
 * @subpackage AnalyticsGoogle
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\AnalyticsGoogle\Reporting;

/**
 * Builder-friendly value object describing the metrics, dimensions,
 * and date range a caller wants to pull from the GA4 Data API. The
 * shape maps directly onto the runReport POST body.
 *
 * @since 1.0.0
 */
final class ReportRequest
{
    /**
     * @param  list<DateRange>  $dateRanges
     * @param  list<string>  $metrics
     * @param  list<string>  $dimensions
     * @param  int|null  $limit
     * @param  int|null  $offset
     */
    public function __construct(
        public readonly array $dateRanges,
        public readonly array $metrics,
        public readonly array $dimensions = [],
        public readonly ?int $limit = null,
        public readonly ?int $offset = null,
    ) {
    }

    /**
     * Convenience constructor for the common single-range case.
     *
     * @since 1.0.0
     *
     * @param  list<string>  $metrics
     * @param  list<string>  $dimensions
     */
    public static function make(
        DateRange $range,
        array $metrics,
        array $dimensions = [],
        ?int $limit = null,
        ?int $offset = null,
    ): self {
        return new self(
            dateRanges: [ $range ],
            metrics: $metrics,
            dimensions: $dimensions,
            limit: $limit,
            offset: $offset,
        );
    }

    /**
     * Serialize to the JSON payload the GA4 Data API's runReport endpoint expects.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function toApiPayload(): array
    {
        $payload = [
            'dateRanges' => array_map( fn ( DateRange $r ): array => $r->toArray(), $this->dateRanges ),
            'metrics'    => array_map( static fn ( string $name ): array => [ 'name' => $name ], $this->metrics ),
        ];

        if ( [] !== $this->dimensions ) {
            $payload['dimensions'] = array_map(
                static fn ( string $name ): array => [ 'name' => $name ],
                $this->dimensions,
            );
        }

        if ( null !== $this->limit ) {
            $payload['limit'] = $this->limit;
        }

        if ( null !== $this->offset ) {
            $payload['offset'] = $this->offset;
        }

        return $payload;
    }
}
