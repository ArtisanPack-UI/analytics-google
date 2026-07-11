<div
    class="ap-ga-top-content"
    @if ($baseInstalled) wire:poll.60s="refresh" @endif
>
    @unless ($baseInstalled)
        <div class="ap-ga-top-content__missing-base" role="alert">
            <p>
                {{ __( 'GA4 top pages and events require the base google package. Install it with:' ) }}
            </p>
            <pre><code>composer require artisanpack-ui/google</code></pre>
        </div>
    @else
        <header class="ap-ga-top-content__header">
            <h2 class="ap-ga-top-content__title">{{ __( 'Top pages and events' ) }}</h2>

            <label class="ap-ga-top-content__range">
                <span class="ap-ga-top-content__range-label">{{ __( 'Range' ) }}</span>
                <select wire:model.change="days">
                    <option value="7">{{ __( 'Last 7 days' ) }}</option>
                    <option value="14">{{ __( 'Last 14 days' ) }}</option>
                    <option value="30">{{ __( 'Last 30 days' ) }}</option>
                    <option value="90">{{ __( 'Last 90 days' ) }}</option>
                </select>
            </label>
        </header>

        @if ($errorMessage)
            <div class="ap-ga-top-content__error" role="alert">
                {{ $errorMessage }}
            </div>
        @else
            <div class="ap-ga-top-content__grid">
                <section class="ap-ga-top-content__panel" aria-labelledby="ap-ga-top-pages-heading">
                    <h3 id="ap-ga-top-pages-heading">{{ __( 'Top pages' ) }}</h3>

                    @if (count( $topPages ) > 0)
                        <table class="ap-ga-top-content__table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __( 'Page' ) }}</th>
                                    <th scope="col">{{ __( 'Views' ) }}</th>
                                    <th scope="col">{{ __( 'Users' ) }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topPages as $row)
                                    <tr>
                                        <th scope="row">
                                            <span class="ap-ga-top-content__page-title">
                                                {{ '' !== $row['title'] ? $row['title'] : $row['path'] }}
                                            </span>
                                            @if ('' !== $row['title'] && $row['title'] !== $row['path'])
                                                <span class="ap-ga-top-content__page-path">{{ $row['path'] }}</span>
                                            @endif
                                        </th>
                                        <td>{{ number_format( $row['views'] ) }}</td>
                                        <td>{{ number_format( $row['users'] ) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="ap-ga-top-content__empty">{{ __( 'No page views for this range yet.' ) }}</p>
                    @endif
                </section>

                <section class="ap-ga-top-content__panel" aria-labelledby="ap-ga-top-events-heading">
                    <h3 id="ap-ga-top-events-heading">{{ __( 'Top events' ) }}</h3>

                    @if (count( $topEvents ) > 0)
                        <table class="ap-ga-top-content__table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __( 'Event' ) }}</th>
                                    <th scope="col">{{ __( 'Count' ) }}</th>
                                    <th scope="col">{{ __( 'Users' ) }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topEvents as $row)
                                    <tr>
                                        <th scope="row">{{ $row['event'] }}</th>
                                        <td>{{ number_format( $row['count'] ) }}</td>
                                        <td>{{ number_format( $row['users'] ) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="ap-ga-top-content__empty">{{ __( 'No events for this range yet.' ) }}</p>
                    @endif
                </section>
            </div>
        @endif
    @endunless
</div>
