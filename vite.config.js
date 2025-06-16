import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.js',
                'resources/js/column-toggle-enhancements.js',
                'resources/js/column-selector-fix.js',
                'resources/js/column-editor-modern.js'
            ],
            refresh: true,
        }),
    ],
});
