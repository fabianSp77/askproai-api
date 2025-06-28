import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/css/filament/admin/theme.css',
                'resources/css/filament/admin/agent-management.css',
                'resources/css/filament/admin/company-integration-portal-clean.css',
                'resources/css/filament/admin/company-integration-portal-v2.css',
                'resources/css/filament/admin/responsive-fixes.css',
                'resources/css/filament/admin/ultimate-theme.css',
                'resources/css/filament/admin/ultra-calls.css',
                'resources/css/filament/admin/ultra-appointments.css',
                'resources/css/filament/admin/ultra-customers.css',
                'resources/css/filament/admin/retell-ultimate.css',
                'resources/css/filament/admin/retell-control-center.css',
                'resources/css/filament/admin/quick-docs-enhanced.css',
                'resources/js/app.js',
                'resources/js/retell-control-center.js',
                'resources/js/quick-docs-enhanced.js',
                'resources/js/agent-management.js',
                'resources/js/column-toggle-enhancements.js',
                'resources/js/column-selector-fix.js',
                'resources/js/column-editor-modern.js',
                'resources/js/company-integration-portal.js',
                'resources/js/askproai-ui-components.js',
                'resources/js/alpine-dropdown-fix.js',
                'resources/js/ultimate-ui-system-simple.js',
                'resources/js/responsive-zoom-handler.js',
                'resources/js/pusher-integration.js'
            ],
            refresh: true,
        }),
    ],
});
