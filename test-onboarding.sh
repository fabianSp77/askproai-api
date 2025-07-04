#!/bin/bash

# Automated Onboarding Test Script

echo "🚀 Testing Automated Onboarding Command"
echo "======================================"

# Test 1: Quick setup in test mode
echo -e "
📋 Test 1: Quick Setup (Test Mode)"
php artisan askproai:onboard --quick --test --no-interaction

# Test 2: Medical practice
echo -e "
📋 Test 2: Medical Practice"
php artisan askproai:onboard \
    --company="Dr. Test Praxis" \
    --industry=medical \
    --phone="+49 30 98765432" \
    --admin-email="admin@testpraxis.de" \
    --test \
    --no-interaction

# Test 3: Show help
echo -e "
📋 Test 3: Command Help"
php artisan askproai:onboard --help

echo -e "
✅ Tests completed!"