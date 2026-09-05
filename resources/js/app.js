import { createApp } from 'vue';
import App from './App.vue';

const root = document.querySelector('#app');

if (root) {
    createApp(App, {
        defaultDate: root.dataset.defaultDate,
        defaultTime: root.dataset.defaultTime,
    }).mount(root);
}
