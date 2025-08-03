#!/bin/bash

# Fix Widget Polling Intervals for Issue #476
# Reduces server load by increasing polling intervals

echo "🔧 Fixing Widget Polling Intervals..."
echo "===================================="

# Backup current widgets
mkdir -p storage/widget-backup-$(date +%Y%m%d-%H%M%S)
cp -r app/Filament/Admin/Widgets/* storage/widget-backup-$(date +%Y%m%d-%H%M%S)/

# Fix aggressive 5s polling widgets
echo "Fixing 5s polling widgets..."
find app/Filament/Admin/Widgets -name "*.php" -exec grep -l "pollingInterval = '5s'" {} \; | while read file; do
    echo "  Updating: $(basename $file)"
    sed -i "s/pollingInterval = '5s'/pollingInterval = '30s'/g" "$file"
done

# Fix 10s polling widgets
echo "Fixing 10s polling widgets..."
find app/Filament/Admin/Widgets -name "*.php" -exec grep -l "pollingInterval = '10s'" {} \; | while read file; do
    echo "  Updating: $(basename $file)"
    sed -i "s/pollingInterval = '10s'/pollingInterval = '60s'/g" "$file"
done

# Show results
echo ""
echo "Updated polling intervals:"
echo "========================="
grep -r "pollingInterval" app/Filament/Admin/Widgets/ | grep -E "'[0-9]+s'" | sort -t: -k2 | head -20

echo ""
echo "✅ Widget polling optimized!"
echo "   - 5s → 30s (6x reduction)"
echo "   - 10s → 60s (6x reduction)"
echo "   - Server load significantly reduced"