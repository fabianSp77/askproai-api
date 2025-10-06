#!/bin/bash
# Security Activation Script
# Purpose: Safe production deployment with automated verification
# Date: 2025-10-01

set -e  # Exit on error

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   Security Fix Activation - Production        ║${NC}"
echo -e "${BLUE}║   Date: 2025-10-01                             ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════╝${NC}"
echo ""

# Step 1: Pre-activation checks
echo -e "${YELLOW}📋 Step 1: Pre-Activation Security Checks${NC}"
echo "Running comprehensive security verification..."
echo ""

./SECURITY_VERIFICATION_COMMANDS.sh pre

echo ""
read -p "Pre-activation checks complete. Continue with activation? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo -e "${RED}Activation cancelled by user.${NC}"
    exit 1
fi

# Step 2: Confirm restart
echo ""
echo -e "${YELLOW}⚠️  Step 2: Application Restart Required${NC}"
echo "This will reload PHP-FPM to activate security fixes."
echo ""
read -p "Proceed with restart? (yes/no): " restart_confirm

if [ "$restart_confirm" != "yes" ]; then
    echo -e "${RED}Restart cancelled by user.${NC}"
    exit 1
fi

# Step 3: Clear caches and restart
echo ""
echo -e "${BLUE}🔄 Clearing caches and restarting application...${NC}"

php artisan config:cache
php artisan route:cache

echo "Reloading PHP-FPM..."
sudo systemctl reload php8.2-fpm

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Application restarted successfully${NC}"
else
    echo -e "${RED}❌ Failed to restart application${NC}"
    echo "Please check PHP-FPM status: sudo systemctl status php8.2-fpm"
    exit 1
fi

# Step 4: Wait for application to stabilize
echo ""
echo -e "${BLUE}⏳ Waiting 10 seconds for application to stabilize...${NC}"
sleep 10

# Step 5: Post-activation checks
echo ""
echo -e "${YELLOW}🔍 Step 3: Post-Activation Verification${NC}"
echo "Running security verification on live system..."
echo ""

./SECURITY_VERIFICATION_COMMANDS.sh post

echo ""
echo -e "${BLUE}════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Security Activation Complete!${NC}"
echo -e "${BLUE}════════════════════════════════════════════════${NC}"
echo ""
echo "Next steps:"
echo "1. Monitor for 15 minutes using:"
echo "   ./SECURITY_VERIFICATION_COMMANDS.sh"
echo ""
echo "2. Check status periodically:"
echo "   ./SECURITY_VERIFICATION_COMMANDS.sh status"
echo ""
echo "3. Watch for first webhook/call to verify security:"
echo "   tail -f storage/logs/laravel.log | grep 'sanitize\\|tenant\\|rate'"
echo ""
echo "4. Review full documentation:"
echo "   cat claudedocs/SECURITY_VERIFICATION_SUMMARY.md"
echo ""
echo "🚨 Rollback available if needed:"
echo "   See SECURITY_ACTIVATION_CHECKLIST.md section 'Emergency Fixes'"
echo ""
echo -e "${GREEN}Security fixes are now active and protecting production.${NC}"
echo ""
