<script setup>
import SubtaskItem from './SubtaskItem.vue';
import { formatStatus, statusClass } from '../support/status';

defineProps({
    issue: {
        type: Object,
        required: true,
    },
    selectedSubtaskKey: {
        type: String,
        default: null,
    },
});

defineEmits(['select-subtask', 'close-subtask']);
</script>

<template>
    <section class="issue-group" :aria-labelledby="`issue-${issue.key}`">
        <header class="issue-group-header">
            <div class="issue-copy">
                <span class="issue-key">{{ issue.key }}</span>
                <h2 :id="`issue-${issue.key}`">{{ issue.summary }}</h2>
                <span v-if="issue.issueType" class="issue-type">{{ issue.issueType }}</span>
            </div>
            <span class="status-pill" :class="statusClass(issue.status)">{{ formatStatus(issue.status) }}</span>
        </header>
        <ul class="subtask-list">
            <SubtaskItem
                v-for="subtask in issue.subtasks"
                :key="subtask.key"
                :subtask="subtask"
                :selected="selectedSubtaskKey === subtask.key"
                @select="$emit('select-subtask', subtask.key)"
                @close="$emit('close-subtask')"
            />
        </ul>
    </section>
</template>
