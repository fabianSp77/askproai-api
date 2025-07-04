#!/bin/bash

echo "Running AskProAI Test Suite"
echo "=========================="

# Clear caches
php artisan config:clear
php artisan cache:clear

# Run tests with better output
php artisan test --parallel --stop-on-failure

# Show coverage if requested
if [ "$1" == "--coverage" ]; then
    php artisan test --coverage --min=80
fi
