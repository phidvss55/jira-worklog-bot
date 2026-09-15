<script setup>
import { ref } from 'vue';
import DurationPicker from './DurationPicker.vue';
import { durationForApi, formatDuration } from '../support/duration';

const props = defineProps({
    subtask: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close']);
const minutes = ref(30);
const isSubmitting = ref(false);
const error = ref('');
const success = ref('');

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function submit() {
    if (isSubmitting.value) {
        return;
    }

    isSubmitting.value = true;
    error.value = '';
    success.value = '';

    try {
        const response = await fetch('/api/worklogs', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({
                ticket: props.subtask.key,
                duration: durationForApi(minutes.value),
            }),
        });
        const body = await response.json().catch(() => ({}));

        if (response.status === 401 || response.status === 419) {
            window.location.assign('/login');

            return;
        }

        if (!response.ok || body.success !== true) {
            error.value = body.message ?? 'Unable to log work. Please try again.';

            return;
        }

        success.value = `Logged ${formatDuration(minutes.value)} to ${props.subtask.key}.`;
        window.dispatchEvent(new Event('worklog-created'));
    } catch {
        error.value = 'Unable to reach the application. Check your connection and try again.';
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <section class="quick-worklog" :aria-label="`Log work for ${subtask.key}`">
        <DurationPicker v-model="minutes" :disabled="isSubmitting" />
        <button class="quick-log-button" type="button" :disabled="isSubmitting" @click="submit">
            <span v-if="isSubmitting" class="spinner" aria-hidden="true"></span>
            {{ isSubmitting ? 'Logging work…' : `Log ${formatDuration(minutes)}` }}
        </button>
        <div class="quick-worklog-message" aria-live="polite">
            <p v-if="success" class="quick-success">✓ {{ success }}</p>
            <p v-else-if="error" class="quick-error" role="alert">{{ error }}</p>
        </div>
        <button v-if="success" class="quick-close-button" type="button" @click="emit('close')">Close</button>
    </section>
</template>
