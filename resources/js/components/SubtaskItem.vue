<script setup>
import { formatStatus, statusClass } from '../support/status';
import QuickWorklog from './QuickWorklog.vue';

defineProps({
    subtask: {
        type: Object,
        required: true,
    },
    selected: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['select', 'close']);
</script>

<template>
    <li class="subtask-item" :class="{ 'is-selected': selected }" :data-loggable="subtask.loggable === true ? 'true' : 'false'">
        <button
            v-if="subtask.loggable === true"
            class="subtask-select"
            type="button"
            :aria-expanded="selected"
            @click="$emit('select')"
        >
            <div class="issue-copy">
                <span class="issue-key">{{ subtask.key }}</span>
                <p>{{ subtask.summary }}</p>
            </div>
            <span class="status-pill" :class="statusClass(subtask.status)">{{ formatStatus(subtask.status) }}</span>
        </button>
        <div v-else class="subtask-static">
            <div class="issue-copy">
                <span class="issue-key">{{ subtask.key }}</span>
                <p>{{ subtask.summary }}</p>
            </div>
            <span class="status-pill" :class="statusClass(subtask.status)">{{ formatStatus(subtask.status) }}</span>
        </div>
        <QuickWorklog v-if="selected && subtask.loggable === true" :subtask="subtask" @close="$emit('close')" />
    </li>
</template>
