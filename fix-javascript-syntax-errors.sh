#!/bin/bash
# Fix JavaScript syntax errors caused by emergency fix
# Created: 2025-01-15

echo "🔧 Fixing JavaScript syntax errors..."
echo ""

# Restore JavaScript files from backup
BACKUP_DIR=$(ls -dt storage/emergency-backup-* | head -1)

if [ -d "$BACKUP_DIR/js-backup" ]; then
    echo "Found backup at: $BACKUP_DIR/js-backup"
    
    # Restore specific problematic files
    echo "Restoring Filament JavaScript files..."
    
    # Restore support.js and echo.js
    if [ -f "$BACKUP_DIR/js-backup/filament/support/support.js" ]; then
        cp "$BACKUP_DIR/js-backup/filament/support/support.js" public/js/filament/support/support.js
        echo "✅ Restored support.js"
    fi
    
    if [ -f "$BACKUP_DIR/js-backup/filament/filament/echo.js" ]; then
        cp "$BACKUP_DIR/js-backup/filament/filament/echo.js" public/js/filament/filament/echo.js
        echo "✅ Restored echo.js"
    fi
    
    # Fix widget-display-fix.js manually (restore specific file)
    if [ -f "$BACKUP_DIR/js-backup/widget-display-fix.js" ]; then
        cp "$BACKUP_DIR/js-backup/widget-display-fix.js" public/js/widget-display-fix.js
        echo "✅ Restored widget-display-fix.js"
    fi
    
    echo ""
    echo "✅ Critical JavaScript files restored!"
    
else
    echo "⚠️  No backup found. Attempting manual fix..."
    
    # Alternative: Download original files from CDN or restore manually
    echo "Please restore the following files manually:"
    echo "- public/js/filament/support/support.js"
    echo "- public/js/filament/filament/echo.js"
    echo "- public/js/widget-display-fix.js"
fi

# Clear browser cache reminder
echo ""
echo "📌 IMPORTANT: Clear browser cache (Ctrl+F5) after fix!"
echo ""

# Check if files are readable
echo "Checking file integrity..."
for file in "public/js/filament/support/support.js" "public/js/filament/filament/echo.js" "public/js/widget-display-fix.js"; do
    if [ -f "$file" ]; then
        SIZE=$(wc -c < "$file")
        if [ $SIZE -gt 100 ]; then
            echo "✅ $file - OK ($SIZE bytes)"
        else
            echo "❌ $file - Too small, might be corrupted"
        fi
    else
        echo "❌ $file - Missing"
    fi
done

echo ""
echo "✅ Fix script completed!"