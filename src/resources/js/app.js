import '../css/app.css';
import 'vue-sonner/style.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import * as Sentry from '@sentry/vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'YZD Center';

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

        const vueApp = createApp({ render: () => h(App, props) });

        // Inisialisasi Sentry Frontend (Vue) jika VITE_SENTRY_DSN terisi
        if (import.meta.env.VITE_SENTRY_DSN) {
            Sentry.init({
                app: vueApp,
                dsn: import.meta.env.VITE_SENTRY_DSN,
                environment: import.meta.env.MODE,
                sendDefaultPii: false,
                tracesSampleRate: 0,
                beforeSend(event) {
                    // Pastikan konteks user hanya id dan role (zero PII)
                    if (event.user) {
                        event.user = {
                            id: event.user.id,
                            role: event.user.role,
                        };
                    }
                    return event;
                },
            });

            const authUser = props.initialPage.props.auth?.user;
            if (authUser?.id) {
                Sentry.setUser({
                    id: String(authUser.id),
                    role: authUser.role,
                });
            }
        }

        return vueApp
            .use(plugin)
            .use(ZiggyVue)
            .mixin({ methods: { __ } }) // make __ available in all templates
            .mount(el);
    },
    progress: {
        color: '#9333ea', // Tailwind purple-600
    },
});
