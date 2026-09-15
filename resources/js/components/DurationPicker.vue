<script setup>
import { computed } from 'vue';
import {
    DURATION_STEP_MINUTES,
    durationPresets,
    formatDuration,
    MAX_DURATION_MINUTES,
    MIN_DURATION_MINUTES,
} from '../support/duration';

const props = defineProps({
    modelValue: {
        type: Number,
        required: true,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    estimateMinutes: {
        type: Number,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue']);
const canDecrease = computed(() => props.modelValue > MIN_DURATION_MINUTES);
const canIncrease = computed(() => props.modelValue < MAX_DURATION_MINUTES);

function setDuration(minutes) {
    emit('update:modelValue', minutes);
}
</script>

<template>
    <div class="duration-picker" aria-label="Quick worklog duration">
        <p class="duration-display" aria-live="polite">{{ formatDuration(modelValue) }}</p>
        <div class="duration-adjustments">
            <button
                type="button"
                :disabled="disabled || !canDecrease"
                aria-label="Decrease duration by 15 minutes"
                @click="setDuration(modelValue - DURATION_STEP_MINUTES)"
            >
                −15m
            </button>
            <button
                type="button"
                :disabled="disabled || !canIncrease"
                aria-label="Increase duration by 15 minutes"
                @click="setDuration(modelValue + DURATION_STEP_MINUTES)"
            >
                +15m
            </button>
        </div>
        <div class="duration-presets" aria-label="Duration presets">
            <button
                v-for="preset in durationPresets"
                :key="preset"
                type="button"
                :class="{ selected: modelValue === preset, 'is-estimate': estimateMinutes === preset }"
                :disabled="disabled"
                :aria-pressed="modelValue === preset"
                @click="setDuration(preset)"
            >
                {{ formatDuration(preset) }}<span v-if="estimateMinutes === preset" aria-hidden="true"> ★</span>
            </button>
        </div>
    </div>
</template>
