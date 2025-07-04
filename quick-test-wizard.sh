#!/bin/bash
# Quick Setup Wizard V2 - Quick Test

echo "🚀 Starting Quick Setup Wizard V2 Test..."

# 1. Clear all caches
echo "Clearing caches..."
php artisan optimize:clear

# 2. Rebuild assets
echo "Building assets..."
npm run build

# 3. Test wizard access
echo "Testing wizard access..."
curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de/admin/quick-setup-wizard-v2 | grep -q "200" && echo "✅ Wizard accessible" || echo "❌ Wizard not accessible"

echo "✅ Test complete!"
echo ""
echo "Next steps:"
echo "1. Open https://api.askproai.de/admin"
echo "2. Login as admin@askproai.de"
echo "3. Navigate to Quick Setup Wizard V2"
echo "4. Test all 7 steps"