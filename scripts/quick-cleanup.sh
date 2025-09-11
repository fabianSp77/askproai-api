#!/bin/bash
#
# Quick Cleanup Script - Sofortige Bereinigung der offensichtlichen Probleme
# Part of UltraThink Recovery Process
#

set -e

echo "🧹 Starting Quick Cleanup..."
echo "This will remove obvious unnecessary files."
echo ""

# Counter
REMOVED_COUNT=0

# Remove backup files
echo "Removing backup files (.bak, .backup, .old)..."
find /var/www/api-gateway -type f \( -name "*.bak" -o -name "*.backup" -o -name "*.old" \) -delete
REMOVED_COUNT=$((REMOVED_COUNT + $(find /var/www/api-gateway -type f \( -name "*.bak" -o -name "*.backup" -o -name "*.old" \) 2>/dev/null | wc -l)))
echo "✅ Backup files removed"

# Remove .DS_Store files
echo "Removing .DS_Store files..."
find /var/www/api-gateway -name ".DS_Store" -delete 2>/dev/null || true
echo "✅ .DS_Store files removed"

# Move reports to docs directory
echo "Organizing documentation..."
cd /var/www/api-gateway
mkdir -p docs/reports docs/archive

# Move report files
for file in *_REPORT.md *_STATUS.md *_COMPLETE.md *_FIXED.md; do
    if [ -f "$file" ]; then
        mv "$file" docs/reports/ 2>/dev/null || true
        REMOVED_COUNT=$((REMOVED_COUNT + 1))
    fi
done
echo "✅ Reports moved to docs/reports/"

# Move old migration files to archive
mkdir -p database/migrations_archive/deleted
for file in $(git status --porcelain | grep '^.D.*migration' | awk '{print $2}'); do
    if [ -f "$file" ]; then
        git rm "$file" 2>/dev/null || true
        REMOVED_COUNT=$((REMOVED_COUNT + 1))
    fi
done
echo "✅ Deleted migrations handled"

echo ""
echo "📊 Summary:"
echo "- Files cleaned up: $REMOVED_COUNT+"
echo "- Documentation organized: ✅"
echo "- Backup files removed: ✅"
echo ""
echo "Next: Review with 'git status' and commit changes"