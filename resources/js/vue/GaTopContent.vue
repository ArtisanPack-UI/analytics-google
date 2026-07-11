<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue'
import {
    fetchGaTopContent,
    GaTopContentError,
    type FetchTopContentOptions,
    type GaTopContentData,
} from '../shared/top-content'

const props = withDefaults(
    defineProps<{
        initialDays?: number
        limit?: number
        propertyId?: string | null
        baseUrl?: string
        fetchImpl?: FetchTopContentOptions['fetchImpl']
    }>(),
    { initialDays: 30, limit: 10, propertyId: null },
)

const RANGE_OPTIONS = [7, 14, 30, 90]

const days = ref<number>(props.initialDays)
const data = ref<GaTopContentData | null>(null)
const errorMessage = ref<string | null>(null)
const errorCode = ref<string | null>(null)
const loading = ref<boolean>(false)

let activeController: AbortController | null = null

async function load(nextDays: number) {
    activeController?.abort()
    const controller = new AbortController()
    activeController = controller

    loading.value = true
    errorMessage.value = null
    errorCode.value = null

    try {
        const result = await fetchGaTopContent({
            days: nextDays,
            limit: props.limit,
            propertyId: props.propertyId,
            baseUrl: props.baseUrl,
            fetchImpl: props.fetchImpl,
            signal: controller.signal,
        })
        if (controller.signal.aborted) return
        data.value = result
    } catch (e) {
        if (controller.signal.aborted) return
        if (e instanceof GaTopContentError) {
            errorCode.value = e.code
            errorMessage.value = e.message
        } else if (e instanceof Error) {
            errorMessage.value = e.message
        }
    } finally {
        if (!controller.signal.aborted) {
            loading.value = false
        }
    }
}

onMounted(() => load(days.value))
onUnmounted(() => activeController?.abort())

watch(days, (next) => load(next))

function formatInt(value: number | undefined): string {
    return new Intl.NumberFormat().format(Math.round(value ?? 0))
}
</script>

<template>
    <div v-if="errorCode === 'base_not_installed'" class="ap-ga-top-content__missing-base" role="alert">
        <p>{{ errorMessage }}</p>
        <pre><code>composer require artisanpack-ui/google</code></pre>
    </div>

    <div v-else class="ap-ga-top-content">
        <header class="ap-ga-top-content__header">
            <h2 class="ap-ga-top-content__title">Top pages and events</h2>
            <label class="ap-ga-top-content__range">
                <span class="ap-ga-top-content__range-label">Range</span>
                <select v-model.number="days" :disabled="loading">
                    <option v-for="option in RANGE_OPTIONS" :key="option" :value="option">
                        Last {{ option }} days
                    </option>
                </select>
            </label>
        </header>

        <div v-if="errorMessage" class="ap-ga-top-content__error" role="alert">
            {{ errorMessage }}
        </div>

        <div v-else class="ap-ga-top-content__grid">
            <section class="ap-ga-top-content__panel" aria-labelledby="ap-ga-top-pages-heading">
                <h3 id="ap-ga-top-pages-heading">Top pages</h3>
                <table v-if="data && data.top_pages.length > 0" class="ap-ga-top-content__table">
                    <thead>
                        <tr>
                            <th scope="col">Page</th>
                            <th scope="col">Views</th>
                            <th scope="col">Users</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in data.top_pages" :key="`${index}:${row.path}`">
                            <th scope="row">
                                <span class="ap-ga-top-content__page-title">
                                    {{ row.title !== '' ? row.title : row.path }}
                                </span>
                                <span
                                    v-if="row.title !== '' && row.title !== row.path"
                                    class="ap-ga-top-content__page-path"
                                >{{ row.path }}</span>
                            </th>
                            <td>{{ formatInt(row.views) }}</td>
                            <td>{{ formatInt(row.users) }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="ap-ga-top-content__empty">No page views for this range yet.</p>
            </section>

            <section class="ap-ga-top-content__panel" aria-labelledby="ap-ga-top-events-heading">
                <h3 id="ap-ga-top-events-heading">Top events</h3>
                <table v-if="data && data.top_events.length > 0" class="ap-ga-top-content__table">
                    <thead>
                        <tr>
                            <th scope="col">Event</th>
                            <th scope="col">Count</th>
                            <th scope="col">Users</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in data.top_events" :key="`${index}:${row.event}`">
                            <th scope="row">{{ row.event }}</th>
                            <td>{{ formatInt(row.count) }}</td>
                            <td>{{ formatInt(row.users) }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="ap-ga-top-content__empty">No events for this range yet.</p>
            </section>
        </div>
    </div>
</template>
