#!/bin/bash

echo "🚨 EMERGENCY PERFORMANCE FIX - Reducing CSS/JS load"
echo "================================================"

# Create backup
echo "Creating backup..."
BACKUP_DIR="storage/emergency-backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Backup current admin bundle
cp resources/css/bundles/admin.css "$BACKUP_DIR/"
cp resources/css/filament/admin/theme.css "$BACKUP_DIR/"

# Create new minimal admin bundle
echo "Creating minimal CSS bundle..."
cat > resources/css/bundles/admin-minimal.css << 'EOF'
/* EMERGENCY MINIMAL ADMIN BUNDLE */
/* This reduces 57 @imports to essential ones only */

/* Base Filament Theme */
@import '../../../vendor/filament/filament/resources/css/theme.css';

/* Core essentials only */
@import '../filament/admin/core.css';
@import '../filament/admin/responsive.css';
@import '../filament/admin/components.css';
@import '../filament/admin/utilities.css';
@import '../filament/admin/menu-fixes.css';
@import '../filament/admin/login-optimized.css';

/* EMERGENCY CLICK FIX */
* {
    pointer-events: auto !important;
}

.fi-sidebar-open::before {
    display: none !important;
}
EOF

# Replace the bloated admin.css
mv resources/css/bundles/admin.css "$BACKUP_DIR/admin-original.css"
cp resources/css/bundles/admin-minimal.css resources/css/bundles/admin.css

# Disable unused JavaScript files
echo "Disabling unused JavaScript files..."
cd public/js
for file in *.disabled *.old *.backup *-old.js *-backup.js *-disabled.js; do
    if [ -f "$file" ]; then
        mv "$file" "../../$BACKUP_DIR/" 2>/dev/null || true
    fi
done
cd ../..

# Create minimal vite config
echo "Creating minimal vite config..."
cp vite.config.js "$BACKUP_DIR/"

cat > vite.config.js << 'EOF'
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Minimal CSS - only essential bundles
                'resources/css/bundles/admin.css',
                'resources/css/bundles/portal.css',
                
                // Minimal JS - only essential bundles
                'resources/js/bundles/admin.js',
                'resources/js/bundles/portal.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                manualChunks: {
                    'admin': ['resources/js/bundles/admin.js'],
                    'portal': ['resources/js/bundles/portal.js'],
                }
            }
        }
    }
});
EOF

# Rebuild assets
echo "Rebuilding assets with minimal config..."
npm run build

echo "✅ Emergency performance fix applied!"
echo ""
echo "Results:"
echo "- Reduced CSS imports from 57 to 7"
echo "- Removed individual CSS entry points"
echo "- Cleaned up unused JavaScript files"
echo "- Applied emergency click fix"
echo ""
echo "Backup created in: $BACKUP_DIR"
echo ""
echo "To restore original files:"
echo "cp $BACKUP_DIR/* resources/css/bundles/"
echo "cp $BACKUP_DIR/vite.config.js ."