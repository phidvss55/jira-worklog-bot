<script setup>
import { reactive, ref } from 'vue';

const props = defineProps({
    defaultDate: {
        type: String,
        required: true,
    },
    defaultTime: {
        type: String,
        required: true,
    },
});

const form = reactive({
    ticket: '',
    duration: '',
    date: props.defaultDate,
    time: props.defaultTime,
});
const errors = reactive({});
const isSubmitting = ref(false);
const isLoggingOut = ref(false);
const result = ref(null);
const generalError = ref('');
const generalErrorTitle = ref('Unable to log work');

function normalizeTicket(event) {
    form.ticket = event.target.value.toUpperCase();
}

function toApiDate(date) {
    const [year, month, day] = date.split('-');

    return `${day}/${month}/${year}`;
}

function displayDuration(duration) {
    return duration.replace(/h(?=\d)/, 'h ');
}

function clearErrors() {
    Object.keys(errors).forEach((field) => delete errors[field]);
    generalError.value = '';
    generalErrorTitle.value = 'Unable to log work';
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function logout() {
    if (isLoggingOut.value) {
        return;
    }

    isLoggingOut.value = true;
    generalError.value = '';

    try {
        const response = await fetch('/logout', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
        });

        if (response.ok || response.status === 401 || response.status === 419) {
            window.location.assign('/login');

            return;
        }

        generalErrorTitle.value = 'Unable to log out';
        generalError.value = 'Please try again.';
    } catch {
        generalErrorTitle.value = 'Unable to log out';
        generalError.value = 'Check your connection and try again.';
    } finally {
        isLoggingOut.value = false;
    }
}

async function submit() {
    if (isSubmitting.value) {
        return;
    }

    isSubmitting.value = true;
    result.value = null;
    clearErrors();

    const submitted = {
        ticket: form.ticket,
        duration: form.duration,
        date: toApiDate(form.date),
        time: form.time,
    };

    try {
        const response = await fetch('/api/worklogs', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(submitted),
        });
        const body = await response.json().catch(() => ({}));

        if (response.status === 401 || response.status === 419) {
            window.location.assign('/login');

            return;
        }

        if (response.status === 422) {
            Object.assign(errors, body.errors ?? {});
            generalError.value = body.message ?? 'Check the highlighted fields and try again.';

            return;
        }

        if (!response.ok || body.success !== true) {
            generalError.value = body.message ?? 'Unable to log work. Please try again.';

            return;
        }

        result.value = {
            ticket: body.data.ticket,
            duration: displayDuration(body.data.duration),
            date: submitted.date,
            time: submitted.time,
            notificationSent: typeof body.notificationSent === 'boolean' ? body.notificationSent : null,
        };
    } catch {
        generalError.value = 'Unable to reach the application. Check your connection and try again.';
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <section class="worklog-card" aria-labelledby="worklog-title">
        <header class="card-header">
            <div class="brand-mark" aria-hidden="true">JW</div>
            <div>
                <p class="eyebrow">Personal workspace</p>
                <h1 id="worklog-title">Jira Worklog</h1>
                <p class="subtitle">Log your working time without breaking focus.</p>
            </div>
            <button class="logout-button" type="button" :disabled="isLoggingOut" @click="logout">
                {{ isLoggingOut ? 'Logging out…' : 'Log out' }}
            </button>
        </header>

        <form class="worklog-form" novalidate @submit.prevent="submit">
            <div class="field-group">
                <label for="ticket">Jira ticket</label>
                <input
                    id="ticket"
                    :value="form.ticket"
                    name="ticket"
                    type="text"
                    autocomplete="off"
                    autocapitalize="characters"
                    placeholder="BKM4-1234"
                    required
                    :aria-invalid="Boolean(errors.ticket)"
                    :aria-describedby="errors.ticket ? 'ticket-error' : undefined"
                    @input="normalizeTicket"
                >
                <p v-if="errors.ticket" id="ticket-error" class="field-error">{{ errors.ticket[0] }}</p>
            </div>

            <div class="field-group">
                <label for="duration">Duration</label>
                <input
                    id="duration"
                    v-model.trim="form.duration"
                    name="duration"
                    type="text"
                    inputmode="text"
                    autocomplete="off"
                    placeholder="2h15m"
                    required
                    :aria-invalid="Boolean(errors.duration)"
                    :aria-describedby="errors.duration ? 'duration-help duration-error' : 'duration-help'"
                >
                <p id="duration-help" class="field-help">Examples: 30m, 1h, 1h30m, 2h15m</p>
                <p v-if="errors.duration" id="duration-error" class="field-error">{{ errors.duration[0] }}</p>
            </div>

            <div class="date-time-grid">
                <div class="field-group">
                    <label for="date">Date</label>
                    <input
                        id="date"
                        v-model="form.date"
                        name="date"
                        type="date"
                        required
                        :aria-invalid="Boolean(errors.date)"
                        :aria-describedby="errors.date ? 'date-error' : undefined"
                    >
                    <p v-if="errors.date" id="date-error" class="field-error">{{ errors.date[0] }}</p>
                </div>

                <div class="field-group">
                    <label for="time">Start time</label>
                    <input
                        id="time"
                        v-model="form.time"
                        name="time"
                        type="time"
                        required
                        :aria-invalid="Boolean(errors.time)"
                        :aria-describedby="errors.time ? 'time-error' : undefined"
                    >
                    <p v-if="errors.time" id="time-error" class="field-error">{{ errors.time[0] }}</p>
                </div>
            </div>

            <button class="primary-button" type="submit" :disabled="isSubmitting">
                <span v-if="isSubmitting" class="spinner" aria-hidden="true"></span>
                {{ isSubmitting ? 'Logging work…' : 'Log Work' }}
            </button>
        </form>

        <div class="result-region" aria-live="polite" aria-atomic="true">
            <div v-if="result" class="status-message status-success" role="status">
                <span class="status-icon" aria-hidden="true">✓</span>
                <div>
                    <strong>Worklog added</strong>
                    <p>{{ result.ticket }} · {{ result.duration }} · {{ result.date }} {{ result.time }}</p>
                    <small v-if="result.notificationSent === true">Google Chat notified.</small>
                    <small v-else-if="result.notificationSent === false" class="notification-warning">
                        Google Chat notification could not be sent.
                    </small>
                </div>
            </div>

            <div v-else-if="generalError" class="status-message status-error" role="alert">
                <span class="status-icon" aria-hidden="true">!</span>
                <div>
                    <strong>{{ generalErrorTitle }}</strong>
                    <p>{{ generalError }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
