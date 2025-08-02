#!/bin/bash

echo "🔧 Building Content Overflow Fix..."

# Install dependencies if needed
npm install

# Build assets with Vite
echo "📦 Building assets..."
npm run build

# Clear all caches
echo "🧹 Clearing caches..."
php artisan optimize:clear
php artisan filament:clear-cached-components

# Run tests if Cypress is available
if command -v cypress &> /dev/null; then
    echo "🧪 Running overflow tests..."
    npx cypress run --spec "cypress/e2e/content-overflow.cy.js" --config video=false
else
    echo "⚠️  Cypress not found, skipping tests"
fi

echo "✅ Content Overflow Fix deployed!"
echo ""
echo "📋 What was fixed:"
echo "  - Removed overflow-x-clip from main layout"
echo "  - Changed w-screen to w-full on main container"
echo "  - Set maxContentWidth to Full"
echo "  - Added responsive overflow handling"
echo ""
echo "🔍 Test in browser:"
echo "  1. Visit https://api.askproai.de/admin"
echo "  2. Check that no content is cut off on the right"
echo "  3. Verify horizontal scrollbar appears when needed"
echo "  4. Test on different screen sizes"