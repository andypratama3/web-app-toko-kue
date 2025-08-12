import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/script_homepage.js',
                'resources/css/argon-dashboard-tailwind.css',
                'resources/js/argon-dashboard-tailwind.js',
                'resources/js/sidenav-burger.js',
                'resources/js/sidenav-burger.js',
            ],
            refresh: true,
        }),
    ],
});
