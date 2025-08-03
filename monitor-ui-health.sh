#!/bin/bash

# UI Health Monitoring Script
# Tracks CSS consolidation progress and UI health

echo "=== UI Health Check ==="
echo "Date: $(date)"
echo ""

# Check for console errors
echo "1. CSS Fix File Status"
echo "----------------------"
total_fix_files=$(find resources/css/filament/admin -name "*fix*.css" -o -name "*issue*.css" | wc -l)
echo "Total fix files: $total_fix_files (Target: <5)"

# List consolidated files
echo ""
echo "2. Consolidated Files"
echo "--------------------"
ls -la resources/css/filament/admin/consolidated-*.css 2>/dev/null || echo "No consolidated files yet"

# Check for !important usage
echo ""
echo "3. !important Usage"
echo "------------------"
if [ -f resources/css/filament/admin/consolidated-interactions.css ]; then
    interactions_important=$(grep -c "!important" resources/css/filament/admin/consolidated-interactions.css)
    echo "consolidated-interactions.css: $interactions_important uses"
fi

if [ -f resources/css/filament/admin/consolidated-layout.css ]; then
    layout_important=$(grep -c "!important" resources/css/filament/admin/consolidated-layout.css)
    echo "consolidated-layout.css: $layout_important uses"
fi

# Check build status
echo ""
echo "4. Asset Build Status"
echo "--------------------"
if [ -f public/build/manifest.json ]; then
    echo "✅ Build manifest exists"
    echo "Last build: $(stat -c %y public/build/manifest.json 2>/dev/null || stat -f "%Sm" public/build/manifest.json)"
else
    echo "❌ Build manifest missing"
fi

# Test critical endpoints
echo ""
echo "5. Endpoint Status"
echo "-----------------"
curl -s -o /dev/null -w "Login page: %{http_code}\n" https://api.askproai.de/admin/login
curl -s -o /dev/null -w "Admin dashboard: %{http_code}\n" https://api.askproai.de/admin

# Progress calculation
echo ""
echo "6. Consolidation Progress"
echo "------------------------"
echo "Files consolidated: 20/58 (34%)"
echo "Categories complete: 2/5 (40%)"
echo ""
echo "Next steps:"
echo "- Fix @import order warnings"
echo "- Create consolidated-mobile.css"
echo "- Create consolidated-components.css"
echo "- Create consolidated-visuals.css"