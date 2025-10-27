#!/bin/bash
###############################################################################
# deploy-staging.sh - Deploy branch to Staging Environment
###############################################################################
# Usage: ./scripts/deploy-staging.sh [branch-name]
# Example: ./scripts/deploy-staging.sh feature/customer-portal
#
# What this script does:
# 1. Switches staging to specified branch
# 2. Pulls latest changes
# 3. Runs database migrations
# 4. Clears caches
# 5. Restarts services
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
STAGING_DIR="/var/www/api-gateway"
PHP_FPM_SERVICE="php8.3-fpm"
BRANCH="${1:-feature/customer-portal}"

echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}   AskPro AI - Staging Deployment${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
echo -e "Branch: ${GREEN}${BRANCH}${NC}"
echo -e "Environment: ${YELLOW}staging.askproai.de${NC}"
echo ""

# Check if running as correct user
if [ "$(whoami)" == "root" ]; then
    echo -e "${RED}❌ Do not run this script as root${NC}"
    exit 1
fi

# Step 1: Navigate to staging directory
echo -e "${YELLOW}[1/8]${NC} Navigating to staging directory..."
cd "$STAGING_DIR" || exit 1

# Step 2: Fetch latest changes
echo -e "${YELLOW}[2/8]${NC} Fetching latest changes from Git..."
git fetch origin || exit 1

# Step 3: Checkout branch
echo -e "${YELLOW}[3/8]${NC} Switching to branch: ${BRANCH}..."
git checkout "$BRANCH" || exit 1

# Step 4: Pull latest changes
echo -e "${YELLOW}[4/8]${NC} Pulling latest changes..."
git pull origin "$BRANCH" || exit 1

# Step 5: Install/Update dependencies
echo -e "${YELLOW}[5/8]${NC} Installing Composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader || exit 1

# Step 6: Run database migrations (with --force for staging)
echo -e "${YELLOW}[6/8]${NC} Running database migrations..."
php artisan migrate --env=staging --force || {
    echo -e "${RED}❌ Migration failed! Check logs.${NC}"
    exit 1
}

# Step 7: Clear all caches
echo -e "${YELLOW}[7/8]${NC} Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize

# Step 8: Restart PHP-FPM
echo -e "${YELLOW}[8/8]${NC} Restarting PHP-FPM..."
sudo systemctl reload "$PHP_FPM_SERVICE" || {
    echo -e "${YELLOW}⚠️  Could not reload PHP-FPM (may need sudo)${NC}"
}

# Success
echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Staging deployment successful!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo ""
echo -e "🌐 Test at: ${BLUE}https://staging.askproai.de${NC}"
echo -e "📋 Logs: ${BLUE}tail -f storage/logs/laravel.log${NC}"
echo ""

# Show git info
COMMIT=$(git rev-parse --short HEAD)
AUTHOR=$(git log -1 --pretty=format:'%an')
DATE=$(git log -1 --pretty=format:'%ar')

echo -e "Deployed:"
echo -e "  Commit: ${GREEN}${COMMIT}${NC}"
echo -e "  Author: ${AUTHOR}"
echo -e "  Date: ${DATE}"
echo ""
