#!/bin/bash

# Company Integration Portal UI Fix Commands
# Run these commands to apply the fixes

echo "🔧 Fixing Company Integration Portal UI..."

# 1. Clear all caches
echo "📦 Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# 2. Rebuild assets
echo "🏗️ Rebuilding assets..."
npm run build

# 3. Clear compiled views
echo "🗑️ Clearing compiled views..."
rm -rf storage/framework/views/*

# 4. Optimize
echo "⚡ Optimizing..."
php artisan optimize

# 5. Clear browser cache reminder
echo ""
echo "✅ Server-side fixes applied!"
echo ""
echo "⚠️  IMPORTANT: Clear your browser cache!"
echo "   - Chrome: Cmd+Shift+R (Mac) or Ctrl+Shift+R (Windows/Linux)"
echo "   - Or open Developer Tools > Network tab > check 'Disable cache'"
echo ""
echo "🔍 Test the page at: /admin/company-integration-portal"
echo ""
echo "If issues persist:"
echo "1. Check browser console for errors (F12)"
echo "2. Try incognito/private mode"
echo "3. Check network tab for failed resource loads"