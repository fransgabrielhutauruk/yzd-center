import '../css/app.css';
import 'vue-sonner/style.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'YZD Trading';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        // Translation helper
        const __ = (key, replace = {}) => {
            let translation = props.initialPage.props.locale?.translations[key] || key;
            Object.keys(replace).forEach((search) => {
                translation = translation.replace(':' + search, replace[search]);
            });
            return translation;
        };

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mixin({ methods: { __ } }) // make __ available in all templates
            .mount(el);
    },
    progress: {
        color: '#9333ea', // Tailwind purple-600
    },
});
