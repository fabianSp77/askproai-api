#!/bin/bash
set -e

echo "Setting up AskProAI Onboarding System..."
echo "========================================"

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Initialize onboarding data
echo "Initializing tutorials and achievements..."
php artisan askproai:initialize-onboarding --fresh

# Clear caches
echo "Clearing caches..."
php artisan optimize:clear

# Generate optimized files
echo "Optimizing application..."
php artisan optimize

echo ""
echo "✅ Onboarding system setup complete!"
echo ""
echo "Next steps:"
echo "1. Visit /admin/onboarding to start the setup wizard"
echo "2. The OnboardingProgressWidget will appear on the dashboard"
echo "3. Interactive tutorials will guide users through the interface"
echo "4. Achievements will be awarded as milestones are reached"
echo ""