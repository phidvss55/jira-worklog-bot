<script setup>
import WorklogForm from './components/WorklogForm.vue';
import SprintView from './components/SprintView.vue';

defineProps({
    defaultDate: {
        type: String,
        required: true,
    },
    defaultTime: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <main class="page-shell">
        <div class="app-layout">
            <SprintView />

            <details class="manual-worklog">
                <summary>Manual worklog</summary>
                <p>Use this fallback for a ticket or date that is not in the active sprint view.</p>
                <WorklogForm :default-date="defaultDate" :default-time="defaultTime" />
            </details>
        </div>
    </main>
</template>
