<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { formatDuration } from '../support/duration';

const summary = ref(null);
const error = ref('');
const isLoading = ref(true);

async function loadSummary() {
    isLoading.value = true;
    error.value = '';

    try {
        const response = await fetch('/api/worklogs/today', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        const body = await response.json().catch(() => ({}));

        if (response.status === 401 || response.status === 419) {
            window.location.assign('/login');

            return;
        }

        if (!response.ok || !Number.isInteger(body.totalMinutes) || !Number.isInteger(body.targetMinutes)) {
            error.value = body.message ?? 'Unable to load today’s total.';

            return;
        }

        summary.value = body;
    } catch {
        error.value = 'Unable to load today’s total.';
    } finally {
        isLoading.value = false;
    }
}

function refreshAfterWorklog() {
    loadSummary();
}

function progressWidth() {
    if (!summary.value || summary.value.targetMinutes <= 0) {
        return 0;
    }

    return Math.min((summary.value.totalMinutes / summary.value.targetMinutes) * 100, 100);
}

function isOverTarget() {
    return summary.value && summary.value.totalMinutes > summary.value.targetMinutes;
}

function hasReachedTarget() {
    return summary.value && summary.value.totalMinutes === summary.value.targetMinutes;
}

onMounted(() => {
    loadSummary();
    window.addEventListener('worklog-created', refreshAfterWorklog);
});

onBeforeUnmount(() => {
    window.removeEventListener('worklog-created', refreshAfterWorklog);
});

defineExpose({ refresh: loadSummary });
</script>

<template>
    <section class="today-summary" aria-label="Today’s worklog summary">
        <template v-if="isLoading">
            <p>Today’s worklog total is loading…</p>
        </template>
        <template v-else-if="summary">
            <div class="today-summary-copy">
                <span>Today</span>
                <strong :class="{ 'is-over-target': isOverTarget() }">
                    {{ formatDuration(summary.totalMinutes) }} / {{ formatDuration(summary.targetMinutes) }}
                </strong>
            </div>
            <div class="today-progress" aria-hidden="true">
                <span :class="{ 'is-over-target': isOverTarget() }" :style="{ width: `${progressWidth()}%` }"></span>
            </div>
            <p v-if="isOverTarget()" class="today-target-note">+{{ formatDuration(summary.totalMinutes - summary.targetMinutes) }} over target</p>
            <p v-else-if="hasReachedTarget()" class="today-target-note">Target reached</p>
        </template>
        <p v-else class="today-summary-error">{{ error }}</p>
    </section>
</template>
