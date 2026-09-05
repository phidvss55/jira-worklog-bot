<script setup>
import WorklogForm from './components/WorklogForm.vue';

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
        <WorklogForm :default-date="defaultDate" :default-time="defaultTime" />
    </main>
</template>
