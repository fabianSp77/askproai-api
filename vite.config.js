import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/call-detail-full-width.css',
                'resources/css/premium-dark-theme.css',  // Premium Dashboard dark theme
                'resources/js/app.js',
                'resources/js/app-admin.js'  // Separate admin bundle
            ],
            refresh: true,
        }),
    ],
});
