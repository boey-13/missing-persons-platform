import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';
// Import Vue Google Maps
import VueGoogleMaps from '@fawmi/vue-google-maps';
import '@fortawesome/fontawesome-free/css/all.min.css'


const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(Toast)
            .use(VueGoogleMaps, {
                load: {
                    key: 'AIzaSyBeC9m6RUZuBayw3noXePJbch5gjSWC06Y',  //google maps API key
                    libraries: 'places',
                },
            })
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
