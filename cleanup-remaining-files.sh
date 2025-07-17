#!/bin/bash
# Cleanup remaining test files after emergency fix
# Created: 2025-01-15

echo "🧹 Cleaning up remaining test files..."
echo ""

# Create archive directory
ARCHIVE_DIR="storage/archived-test-files-html-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$ARCHIVE_DIR"

# Count files
HTML_COUNT=$(find public -name "*.html" | grep -E "(test|admin|debug|demo|portal)" | wc -l)
PHP_COUNT=$(find public -name "*.php" | grep -E "(test|debug)" | wc -l)

echo "Found:"
echo "- $HTML_COUNT HTML test files"
echo "- $PHP_COUNT PHP test files"
echo ""

if [ $HTML_COUNT -gt 0 ] || [ $PHP_COUNT -gt 0 ]; then
    echo "Archiving files to: $ARCHIVE_DIR"
    
    # Archive HTML files
    find public -name "*.html" | grep -E "(test|admin|debug|demo|portal)" | while read file; do
        mv "$file" "$ARCHIVE_DIR/" 2>/dev/null
    done
    
    # Archive remaining PHP test files
    find public -name "*.php" | grep -E "(test|debug)" | while read file; do
        mv "$file" "$ARCHIVE_DIR/" 2>/dev/null
    done
    
    echo "✅ Files archived successfully"
else
    echo "ℹ️  No test files found"
fi

echo ""
echo "Remaining files in public:"
find public -name "*.html" -o -name "*.php" | grep -E "(test|admin|debug|demo|portal)" | wc -l

echo ""
echo "✅ Cleanup complete!"