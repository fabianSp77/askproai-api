#!/bin/bash

echo "Building Mobile UI Fixes..."

# Install dependencies if needed
npm install

# Build assets with Vite
npm run build

# Clear Filament cache
php artisan filament:cache-components
php artisan filament:clear-cached-components

# Clear Laravel caches
php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run Cypress tests (optional)
if command -v cypress &> /dev/null; then
    echo "Running mobile UI tests..."
    npx cypress run --spec "cypress/e2e/mobile-ui.cy.js" --config video=false
fi

echo "Build complete!"