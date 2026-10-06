import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import '../../css/app.css';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Whistle-Works';

createInertiaApp({
    title: (title) => `${title} — ${appName}`,
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#6366f1',
        showSpinner: true,
    },
});
