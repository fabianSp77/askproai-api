#!/bin/bash
# ================================================================
# Performance Killer Cleanup Script
# Moves problematic JavaScript files to deprecated folder
# ================================================================

DEPRECATED_DIR="/var/www/api-gateway/public/js/deprecated-fixes-20250730"

echo "🧹 Cleaning up performance-killing JavaScript files..."

# List of files to move (if they exist)
FILES_TO_MOVE=(
    "wizard-interaction-debugger.js"
    "ultimate-portal-interactions.js"
    "global-table-scroll-fix.js"
    "calls-table-scroll-fix.js"
    "filament-v3-fixes.js"
    "responsive-zoom-handler.js"
    "alpine-diagnostic-fix.js"
)

# Move files from app directory
for file in "${FILES_TO_MOVE[@]}"; do
    if [ -f "/var/www/api-gateway/public/js/app/$file" ]; then
        echo "Moving app/$file..."
        mv "/var/www/api-gateway/public/js/app/$file" "$DEPRECATED_DIR/app/"
    fi
done

# Find and move files with problematic patterns
echo "🔍 Finding files with performance issues..."

# Files with setInterval < 1000ms
grep -l "setInterval.*[1-9][0-9]\{1,2\})" /var/www/api-gateway/public/js/*.js 2>/dev/null | while read file; do
    if [[ ! "$file" =~ deprecated-fixes ]]; then
        echo "Moving $(basename "$file") (fast setInterval)..."
        mv "$file" "$DEPRECATED_DIR/"
    fi
done

# Files with document.querySelectorAll('*')
grep -l "document\.querySelectorAll.*\*" /var/www/api-gateway/public/js/*.js 2>/dev/null | while read file; do
    if [[ ! "$file" =~ deprecated-fixes ]]; then
        echo "Moving $(basename "$file") (querySelectorAll *)..."
        mv "$file" "$DEPRECATED_DIR/"
    fi
done

echo "✅ Cleanup complete!"
echo ""
echo "📊 Summary:"
echo "- Moved performance-intensive scripts to: $DEPRECATED_DIR"
echo "- These scripts were causing:"
echo "  • CPU spikes from fast intervals"
echo "  • Memory leaks from DOM scanning"
echo "  • UI freezes from excessive operations"
echo ""
echo "⚠️  If any functionality breaks, check the deprecated folder."