#!/bin/bash

# CSS Consolidation Cleanup Script
# Safely removes old fragmented CSS files after consolidation

echo "🧹 CSS Consolidation Cleanup"
echo "=============================="

# Backup directory
BACKUP_DIR="/var/www/api-gateway/public/css/backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"

# List of fragmented CSS files to remove (after backup)
OLD_CSS_FILES=(
    "admin-consolidated-fixes.css"
    "admin-emergency-fix.css"
    "admin-layout-fix.css"
    "admin-pointer-fix.css"
    "askpro-ui-fixes.css"
    "dashboard-fixes.css"
    "dashboard-visual-fixes.css"
    "dropdown-close-fix.css"
    "dropdown-fix-minimal.css"
    "dropdown-functionality-fix.css"
    "emergency-clickable-fix.css"
    "emergency-mobile-fix.css"
    "filament-button-fixes.css"
    "filament-contrast-fix.css"
    "filament-emergency-fix.css"
    "filament-hotfix.css"
    "filament-hotfix-override.css"
    "filament-stats-overflow-fix.css"
    "fix-text-overflow.css"
    "login-input-emergency-fix.css"
    "login-input-force-fix.css"
    "login-mobile-fix.css"
    "login-overlay-fix.css"
    "portal-click-fix-final.css"
    "portal-modern-ui.css"
    "portal-responsive-fixes.css"
    "portal-universal-fix.css"
    "unified-ui-fixes.css"
)

# List of JavaScript files to remove (old fixes)
OLD_JS_FILES=(
    "portal-alpine-fix.js"
    "portal-viewport-optimizer.js"
    "bypass-all-handlers.js"
    "bypass-hook-js.js"
)

echo "📦 Creating backup..."

# Backup CSS files
for file in "${OLD_CSS_FILES[@]}"; do
    if [ -f "/var/www/api-gateway/public/css/$file" ]; then
        echo "  Backing up: $file"
        cp "/var/www/api-gateway/public/css/$file" "$BACKUP_DIR/"
    fi
done

# Backup JS files
for file in "${OLD_JS_FILES[@]}"; do
    if [ -f "/var/www/api-gateway/public/js/$file" ]; then
        echo "  Backing up: $file"
        cp "/var/www/api-gateway/public/js/$file" "$BACKUP_DIR/"
    fi
done

echo "✅ Backup created at: $BACKUP_DIR"

# Ask for confirmation before deleting
echo ""
read -p "🗑️  Remove old fragmented CSS/JS files? (y/N): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "🗑️  Removing old files..."
    
    # Remove CSS files
    for file in "${OLD_CSS_FILES[@]}"; do
        if [ -f "/var/www/api-gateway/public/css/$file" ]; then
            echo "  Removing: css/$file"
            rm "/var/www/api-gateway/public/css/$file"
        fi
    done
    
    # Remove JS files
    for file in "${OLD_JS_FILES[@]}"; do
        if [ -f "/var/www/api-gateway/public/js/$file" ]; then
            echo "  Removing: js/$file"
            rm "/var/www/api-gateway/public/js/$file"
        fi
    done
    
    echo "✅ Old files removed successfully"
else
    echo "⏭️  Skipping file removal"
fi

echo ""
echo "🔧 Building optimized assets..."

# Build assets with CSS minification
cd /var/www/api-gateway
npm run build

echo ""
echo "📊 File size comparison:"
echo "  Unified CSS: $(du -h public/css/unified-portal-fixes.css 2>/dev/null | cut -f1)"
echo "  Alpine fix JS: $(du -h public/js/alpine-race-condition-fix.js 2>/dev/null | cut -f1)"

echo ""
echo "✨ CSS Consolidation Complete!"
echo ""
echo "📋 Summary:"
echo "  • Consolidated $(echo ${#OLD_CSS_FILES[@]}) CSS files into 1 unified file"
echo "  • Added WCAG 2.1 AA compliant mobile touch targets (48px minimum)"
echo "  • Fixed Alpine.js race conditions"
echo "  • Enabled CSS minification in Vite"
echo "  • Backup stored in: $BACKUP_DIR"
echo ""
echo "🚀 Next steps:"
echo "  1. Test mobile touch targets on devices"
echo "  2. Verify Alpine.js components load correctly"
echo "  3. Check admin portal navigation"
echo "  4. Monitor performance improvements"