<script setup>
import { onMounted, ref } from 'vue';
import IssueGroup from './IssueGroup.vue';
import SprintHeader from './SprintHeader.vue';
import TodaySummary from './TodaySummary.vue';

const sprint = ref(null);
const issues = ref([]);
const isLoading = ref(true);
const error = ref('');
const selectedSubtaskKey = ref(null);
const todaySummary = ref(null);

async function loadSprint() {
    isLoading.value = true;
    error.value = '';

    try {
        const response = await fetch('/api/sprint', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        const body = await response.json().catch(() => ({}));

        if (response.status === 401 || response.status === 419) {
            window.location.assign('/login');

            return;
        }

        if (!response.ok || !Object.hasOwn(body, 'issues')) {
            error.value = body.message ?? 'Unable to load the active sprint. Please try again.';

            return;
        }

        sprint.value = body.sprint ?? null;
        issues.value = Array.isArray(body.issues) ? body.issues : [];
        selectedSubtaskKey.value = null;
    } catch {
        error.value = 'Unable to load the active sprint. Check your connection and try again.';
    } finally {
        isLoading.value = false;
    }
}

function selectSubtask(key) {
    selectedSubtaskKey.value = selectedSubtaskKey.value === key ? null : key;
}

async function refreshReadData() {
    await Promise.all([
        loadSprint(),
        todaySummary.value?.refresh(),
    ]);
}

onMounted(loadSprint);
</script>

<template>
    <section class="sprint-view" aria-labelledby="sprint-view-title">
        <SprintHeader :sprint="sprint" :is-refreshing="isLoading" @refresh="refreshReadData" />
        <TodaySummary ref="todaySummary" />

        <div class="sprint-content" aria-live="polite">
            <div v-if="isLoading && !sprint" class="sprint-state sprint-loading" role="status">
                <span class="spinner spinner-dark" aria-hidden="true"></span>
                Loading active sprint…
            </div>

            <div v-else-if="error && !sprint" class="sprint-state status-message status-error" role="alert">
                <span class="status-icon" aria-hidden="true">!</span>
                <div>
                    <strong>Unable to load sprint</strong>
                    <p>{{ error }}</p>
                </div>
            </div>

            <div v-else-if="!sprint" class="sprint-state sprint-empty">
                <h2>No active sprint found.</h2>
                <p>Refresh after an active sprint becomes available in Jira.</p>
            </div>

            <template v-else>
                <div v-if="error" class="sprint-refresh-error status-message status-error" role="alert">
                    <span class="status-icon" aria-hidden="true">!</span>
                    <div><strong>Unable to refresh sprint</strong><p>{{ error }}</p></div>
                </div>
                <div v-if="issues.length === 0" class="sprint-state sprint-empty">
                    <h2>No subtasks assigned to you in the active sprint.</h2>
                    <p>Your assigned subtasks will appear here when Jira returns them.</p>
                </div>
                <div v-else class="issue-groups">
                    <IssueGroup
                        v-for="issue in issues"
                        :key="issue.key"
                        :issue="issue"
                        :selected-subtask-key="selectedSubtaskKey"
                        @select-subtask="selectSubtask"
                        @close-subtask="selectedSubtaskKey = null"
                    />
                </div>
            </template>
        </div>
    </section>
</template>
