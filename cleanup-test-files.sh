#!/bin/bash

BACKUP_DIR="storage/test-files-backup-20250729"
echo "=== Cleaning up test/debug files from public ==="
echo "Backup directory: $BACKUP_DIR"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Count files
TOTAL_COUNT=$(find public -name "*test*.php" -o -name "*debug*.php" -o -name "*fix*.php" | grep -v "index.php" | wc -l)
echo "Found $TOTAL_COUNT test/debug files to move"
echo ""

# Move files
echo "Moving files..."
find public -name "*test*.php" -o -name "*debug*.php" -o -name "*fix*.php" | grep -v "index.php" | while read file; do
    echo "Moving: $file"
    mv "$file" "$BACKUP_DIR/"
done

# Also move HTML test files
echo ""
echo "Moving HTML test files..."
find public -name "*test*.html" -o -name "*debug*.html" -o -name "*fix*.html" | while read file; do
    echo "Moving: $file"
    mv "$file" "$BACKUP_DIR/"
done

# Count what's left
REMAINING=$(find public -name "*.php" | grep -v "index.php" | wc -l)
echo ""
echo "=== Cleanup complete ==="
echo "Moved files to: $BACKUP_DIR"
echo "Remaining PHP files in public: $REMAINING"