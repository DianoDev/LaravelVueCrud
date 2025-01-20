import './bootstrap.js';
import { createApp, defineAsyncComponent } from 'vue/dist/vue.esm-bundler';
import { createPinia } from 'pinia';
import mitt from 'mitt';
import Toast from "vue-toastification";
const pinia = createPinia();
const app = createApp({});

const components = import.meta.glob('./components/**/*.vue', {eager: true});
Object.entries(components).forEach(([path, definition]) => {
    const componentName = path.split('/').pop().split('.')[0];
    if(componentName.indexOf('__') === -1) {
        app.component(componentName, definition.default)
    }
})

app.use(Toast, {});
app.use(pinia);
app.config.globalProperties.$events = mitt();
app.provide('events', app.config.globalProperties.$events);
app.mount('#app');

