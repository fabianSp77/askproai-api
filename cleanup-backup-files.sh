#!/bin/bash

# Cleanup script for backup files
# Created: 2025-06-13

echo "🧹 Starting cleanup of backup files..."
echo "=================================="

# Count files before deletion
BAK_COUNT=$(find . -name "*.bak*" -type f | wc -l)
BACKUP_COUNT=$(find . -name "*.backup*" -type f | wc -l)
OTHER_COUNT=$(find . -name "*.old" -o -name "*.temp" -o -name "*.tmp" -o -name "*.broken" | grep -v node_modules | grep -v vendor | wc -l)

echo "Files to be removed:"
echo "- .bak* files: $BAK_COUNT"
echo "- .backup* files: $BACKUP_COUNT"
echo "- .old/.temp/.tmp/.broken files: $OTHER_COUNT"
echo "- storage/logs/backups directory"
echo ""
echo "Total: $((BAK_COUNT + BACKUP_COUNT + OTHER_COUNT)) files"
echo ""

# Create backup directory for safety
BACKUP_DIR="cleanup_backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo "Creating safety backup in $BACKUP_DIR..."

# Move files instead of deleting directly (safer)
echo "Moving .bak* files..."
find . -name "*.bak*" -type f -exec mv {} "$BACKUP_DIR/" \; 2>/dev/null

echo "Moving .backup* files..."
find . -name "*.backup*" -type f -exec mv {} "$BACKUP_DIR/" \; 2>/dev/null

echo "Moving other old files..."
find . \( -name "*.old" -o -name "*.temp" -o -name "*.tmp" -o -name "*.broken" \) \
    -not -path "./node_modules/*" \
    -not -path "./vendor/*" \
    -exec mv {} "$BACKUP_DIR/" \; 2>/dev/null

# Move logs backup directory
if [ -d "storage/logs/backups" ]; then
    echo "Moving storage/logs/backups..."
    mv storage/logs/backups "$BACKUP_DIR/"
fi

# Clean up old log files
echo "Cleaning up old log files..."
find storage/logs -name "*.log.1" -mtime +7 -delete

# Count remaining files
REMAINING=$(find "$BACKUP_DIR" -type f | wc -l)

echo ""
echo "✅ Cleanup complete!"
echo "=================================="
echo "Moved $REMAINING files to $BACKUP_DIR"
echo ""
echo "To permanently delete these files, run:"
echo "rm -rf $BACKUP_DIR"
echo ""
echo "To restore, move files back from $BACKUP_DIR"