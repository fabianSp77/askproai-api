import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: {
                // Main Application Bundle
                'app': 'resources/js/app.js',
                'app.styles': 'resources/css/app.css',
                
                // Admin Panel Bundle (Filament)
                'admin': 'resources/js/bundles/admin.js',
                'admin.styles': 'resources/css/bundles/admin.css',
                
                // Portal React Bundle
                'portal': 'resources/js/bundles/portal.jsx',
                
                // Login Page Bundle
                'login': 'resources/js/login.jsx',
                
                // Critical CSS (inline for fast loading)
                'critical': 'resources/css/bundles/critical.css',
                
                // Filament theme CSS files
                'filament.admin.theme': 'resources/css/filament/admin/theme.css',
                'filament.admin.sidebar-layout-fix': 'resources/css/filament/admin/sidebar-layout-fix.css',
                'filament.admin.unified-responsive': 'resources/css/filament/admin/unified-responsive.css',
                'filament.admin.icon-fixes': 'resources/css/filament/admin/icon-fixes.css',
                'filament.admin.icon-container-sizes': 'resources/css/filament/admin/icon-container-sizes.css',
                'filament.admin.form-layout-fixes': 'resources/css/filament/admin/form-layout-fixes.css',
                'filament.admin.table-scroll-indicators': 'resources/css/filament/admin/table-scroll-indicators.css',
                'filament.admin.content-width-fix': 'resources/css/filament/admin/content-width-fix.css',
                
                // Emergency fixes for GitHub Issues #476 & #478
                'filament.admin.emergency-fix-476': 'resources/css/filament/admin/emergency-fix-476.css',
                'filament.admin.emergency-icon-fix-478': 'resources/css/filament/admin/emergency-icon-fix-478.css',
                'filament.admin.consolidated-interactions': 'resources/css/filament/admin/consolidated-interactions.css',
                'filament.admin.consolidated-layout': 'resources/css/filament/admin/consolidated-layout.css'
            },
            refresh: true,
        }),
        react(),
    ],
    
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    // Vendor chunks
                    'vendor-react': ['react', 'react-dom', 'react-router-dom'],
                    'vendor-ui': ['@headlessui/react', '@heroicons/react/24/outline', '@heroicons/react/24/solid'],
                    'vendor-utils': ['axios'],
                },
                // Generate consistent chunk names for better caching
                chunkFileNames: (chunkInfo) => {
                    const facadeModuleId = chunkInfo.facadeModuleId ? chunkInfo.facadeModuleId.split('/').pop() : 'chunk';
                    return `js/chunks/${facadeModuleId}-[hash].js`;
                },
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name.endsWith('.css')) {
                        return 'css/[name]-[hash][extname]';
                    }
                    return 'assets/[name]-[hash][extname]';
                }
            }
        },
        // Enable CSS code splitting
        cssCodeSplit: true,
        // Increase chunk size warning limit
        chunkSizeWarningLimit: 1000,
        // Minify for production
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true
            }
        }
    },
    
    // Optimize dependencies
    optimizeDeps: {
        include: [
            'react',
            'react-dom',
            'react-router-dom',
            'axios',
            '@headlessui/react',
            '@heroicons/react/24/outline',
            '@heroicons/react/24/solid'
        ]
    },
    
    // Development server optimizations
    server: {
        hmr: {
            overlay: false
        },
        watch: {
            usePolling: false
        }
    }
});