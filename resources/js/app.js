import '@/bootstrap';

import { createApp } from 'vue';
import App from '@/components/App.vue';
import { createPinia } from 'pinia';
import router from '@/router';

import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";


import '../css/app.css';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);

app.use(router);

app.use(Toast);

app.mount('#app');