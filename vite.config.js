import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/call-detail-full-width.css',
                'resources/css/calcom-atoms.css',  // New: Cal.com Atoms styling
                'resources/js/app.js',
                'resources/js/app-admin.js',  // Separate admin bundle
                'resources/js/calcom-atoms.jsx'  // New: Cal.com Atoms React bundle
            ],
            refresh: true,
        }),
        react(),  // Enable React support
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'react-vendor': ['react', 'react-dom'],
                    'calcom': ['@calcom/atoms'],
                },
            },
        },
    },
});
