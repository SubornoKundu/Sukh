import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

// A picture that cannot be loaded (deleted upload, old "recently viewed" entry, bad link)
// shows a neutral placeholder instead of broken-image text. Runs once per picture.
window.addEventListener('error', (event) => {
    const img = event.target;
    if (!(img instanceof HTMLImageElement) || img.dataset.fallbackDone || img.hasAttribute('data-no-fallback')) return;
    img.dataset.fallbackDone = '1';
    img.removeAttribute('srcset');
    img.src = '/images/placeholder.svg';
}, true);

const meta = (name) => document.head.querySelector(`meta[name="${name}"]`)?.getAttribute('content');
const appName = meta('application-name') || import.meta.env.VITE_APP_NAME || 'ShopEase';
const brandColor = meta('theme-color') || '#16a34a';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: brandColor,
    },
});
