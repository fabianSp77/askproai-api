#!/bin/bash

###############################################################################
# Laravel Storage & Cache Permissions Fix
###############################################################################
# Purpose: Fix ownership and permissions for Laravel storage/ and bootstrap/cache/
# Use Case: When artisan commands run as root create files that www-data can't write to
# Symptom: "Permission denied" errors when compiling views or writing cache
#
# Usage:
#   ./scripts/fix-permissions.sh
#
# Safe to run anytime - idempotent operation
###############################################################################

set -e  # Exit on error

echo "🔧 Laravel Permissions Fix"
echo "================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root (needed for chown)
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}❌ Error: This script must be run as root${NC}"
    echo "   Run with: sudo ./scripts/fix-permissions.sh"
    exit 1
fi

echo "Step 1/5: Checking current ownership..."
VIEWS_OWNER=$(stat -c '%U' storage/framework/views/ 2>/dev/null || echo "unknown")
echo "   Current views/ owner: $VIEWS_OWNER"

if [ "$VIEWS_OWNER" = "www-data" ]; then
    echo -e "   ${GREEN}✓ Already correct${NC}"
else
    echo -e "   ${YELLOW}⚠ Needs fixing${NC}"
fi

echo ""
echo "Step 2/5: Fixing storage/ ownership..."
chown -R www-data:www-data storage/
echo -e "   ${GREEN}✓ storage/ → www-data:www-data${NC}"

echo ""
echo "Step 3/5: Fixing bootstrap/cache/ ownership..."
chown -R www-data:www-data bootstrap/cache/
echo -e "   ${GREEN}✓ bootstrap/cache/ → www-data:www-data${NC}"

echo ""
echo "Step 4/5: Setting proper permissions..."
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
echo -e "   ${GREEN}✓ Permissions set to 775${NC}"

echo ""
echo "Step 5/5: Clearing Laravel caches..."
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
echo -e "   ${GREEN}✓ All caches cleared${NC}"

echo ""
echo "================================"
echo -e "${GREEN}✅ Permissions fixed successfully!${NC}"
echo ""
echo "Verification:"
echo "  - storage/framework/views/ owner: $(stat -c '%U:%G' storage/framework/views/)"
echo "  - bootstrap/cache/ owner: $(stat -c '%U:%G' bootstrap/cache/)"
echo ""
echo "Next steps:"
echo "  1. Test your application"
echo "  2. Avoid 'sudo php artisan' - use 'sudo -u www-data php artisan' instead"
echo ""
