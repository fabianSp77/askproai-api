import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Core Styles
                'resources/css/app.css',
                'resources/css/filament/admin/theme.css',
                
                // Consolidated Admin Fixes
                'resources/css/admin-consolidated-fixes.css',
                'resources/css/askpro-ui-fixes.css',
                'resources/css/filament-menu-clean.css',
                
                // Essential Component Styles
                'resources/css/filament/admin/unified-responsive.css',
                'resources/css/filament/admin/table-scroll-indicators.css',
                'resources/css/filament/admin/calls-table-fix.css',
                'resources/css/filament/admin/agent-management.css',
                'resources/css/filament/admin/retell-control-center.css',
                'resources/css/filament/admin/professional-navigation.css',
                'resources/css/filament/admin/billing-alerts-improvements.css',
                'resources/css/filament/admin/professional-mobile-menu.css',
                'resources/css/call-detail-modern.css',
                
                // Core JavaScript
                'resources/js/app.js',
                'resources/js/app-filament-compatible.js',
                
                // Consolidated Alpine & UI
                'resources/js/alpine-consolidated.js',
                'resources/js/filament-override-fix.js',
                'resources/js/ultimate-ui-system-simple.js',
                
                // Essential Components
                'resources/js/sidebar-store.js',
                'resources/js/retell-control-center.js',
                'resources/js/agent-management.js',
                'resources/js/company-integration-portal.js',
                'resources/js/dropdown-manager.js',
                'resources/js/pusher-integration.js',
                'resources/js/wizard-progress-enhancer.js',
                
                // React Applications (Portal)
                'resources/js/PortalAppModern.jsx',
                'resources/js/portal-dashboard-optimized.jsx',
                'resources/js/portal-calls-optimized.jsx',
                'resources/js/portal-appointments-optimized.jsx',
                'resources/js/portal-billing-optimized.jsx',
                
                // Admin React Portal
                'resources/js/admin.jsx',
                
                // Login
                'resources/js/login.jsx',
                'resources/js/login-form-fix.js'
            ],
            refresh: true,
        }),
        react(),
    ],
    
    // Build optimizations
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    // Group vendor libraries
                    'vendor-react': ['react', 'react-dom'],
                    'vendor-alpine': ['alpinejs'],
                    'vendor-ui': ['@headlessui/react', '@heroicons/react'],
                },
            },
        },
        
        // Enable CSS code splitting
        cssCodeSplit: true,
        
        // Optimize chunk size
        chunkSizeWarningLimit: 1000,
        
        // Enable minification in production
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
    },
    
    // Development server optimizations
    server: {
        hmr: {
            overlay: false, // Disable error overlay in development
        },
    },
    
    // Dependency optimization
    optimizeDeps: {
        include: [
            'alpinejs',
            'react',
            'react-dom',
            '@headlessui/react',
            '@heroicons/react/24/outline',
            '@heroicons/react/24/solid',
        ],
    },
});