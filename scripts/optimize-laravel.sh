#!/bin/bash
# Laravel Optimization Script
# Optimizes Laravel for production

set -e

echo "Starting Laravel optimization..."

# 1. Clear all caches first
echo "Clearing old caches..."
php artisan optimize:clear

# 2. Cache configuration
echo "Caching configuration..."
php artisan config:cache

# 3. Cache views
echo "Caching views..."
php artisan view:cache

# 4. Cache events
echo "Caching events..."
php artisan event:cache

# 5. Optimize autoloader
echo "Optimizing autoloader..."
composer dump-autoload --optimize

# 6. Cache icons (Filament)
echo "Caching icons..."
php artisan icons:cache

# 7. Create optimized class loader
echo "Running general optimization..."
php artisan optimize

# Note: Route caching skipped due to conflicts
# Can be fixed later by resolving duplicate route names

echo "Optimization complete!"
echo "Note: Route caching skipped due to duplicate route names."