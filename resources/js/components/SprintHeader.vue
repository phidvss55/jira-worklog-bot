<script setup>
defineProps({
    sprint: {
        type: Object,
        default: null,
    },
    isRefreshing: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['refresh']);

function formatDate(date) {
    if (!date) {
        return 'Date unavailable';
    }

    return new Intl.DateTimeFormat(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(`${date}T00:00:00`));
}
</script>

<template>
    <header class="sprint-header">
        <div>
            <p class="eyebrow">Active sprint</p>
            <h1 id="sprint-view-title">Jira Worklog</h1>
            <p v-if="sprint" class="sprint-name">{{ sprint.name }}</p>
            <p v-if="sprint" class="sprint-dates">
                {{ formatDate(sprint.startDate) }} – {{ formatDate(sprint.endDate) }}
            </p>
        </div>
        <button class="refresh-button" type="button" :disabled="isRefreshing" @click="$emit('refresh')">
            {{ isRefreshing ? 'Refreshing…' : 'Refresh' }}
        </button>
    </header>
</template>
