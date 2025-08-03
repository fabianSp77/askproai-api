#!/bin/bash

# Cleanup Old Login Fix Files
# Date: 2025-08-02
# This script archives deprecated login fix files

echo "🧹 Cleaning up old login fix files..."

# Create archive directory
ARCHIVE_DIR="storage/archived-login-fixes-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$ARCHIVE_DIR"

# List of deprecated files to archive
DEPRECATED_FILES=(
    "public/js/login-enhancer.js"
    "public/js/login-overlay-remover.js"
    "public/js/login-form-fix.js"
    "public/css/login-page-clean.css"
    "public/css/login-mobile-fix.css"
    "public/css/login-fix-*.css"
    "public/js/login-fix-*.js"
    "public/js/deprecated-fixes-*"
)

# Archive deprecated files
for pattern in "${DEPRECATED_FILES[@]}"; do
    for file in $pattern; do
        if [ -f "$file" ]; then
            echo "Archiving: $file"
            mv "$file" "$ARCHIVE_DIR/" 2>/dev/null || true
        fi
    done
done

# Count archived files
ARCHIVED_COUNT=$(ls -1 "$ARCHIVE_DIR" 2>/dev/null | wc -l)

if [ "$ARCHIVED_COUNT" -gt 0 ]; then
    echo "✅ Archived $ARCHIVED_COUNT deprecated login fix files to $ARCHIVE_DIR"
else
    echo "ℹ️ No deprecated login fix files found to archive"
fi

# Clean up empty backup directories
find storage/ -name "login-backup-*" -type d -empty -delete 2>/dev/null || true

echo "🎉 Login fix cleanup complete!"