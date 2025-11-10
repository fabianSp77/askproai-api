#!/bin/bash
###############################################################################
# QUICKSTART: Security Fixes for PR #723
# Run this script to automatically apply all security fixes
###############################################################################

set -euo pipefail

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo "==========================================================================="
echo "PR #723 Security Fix - Automated Deployment"
echo "==========================================================================="
echo ""

# Check if we're in the right directory
if [ ! -f "public/healthcheck.php" ]; then
    echo -e "${RED}Error: Must run from /var/www/api-gateway directory${NC}"
    exit 1
fi

# Step 1: Backup original files
echo -e "${BLUE}[1/6]${NC} Backing up original files..."
cp public/healthcheck.php public/healthcheck.php.backup.$(date +%Y%m%d-%H%M%S)
cp app/Http/Controllers/DocsAuthController.php app/Http/Controllers/DocsAuthController.php.backup.$(date +%Y%m%d-%H%M%S)
echo -e "${GREEN}✓${NC} Backups created"
echo ""

# Step 2: Apply fixes
echo -e "${BLUE}[2/6]${NC} Applying security fixes..."
if [ -f "public/healthcheck.php.FIXED" ]; then
    cp public/healthcheck.php.FIXED public/healthcheck.php
    echo -e "${GREEN}✓${NC} Fixed public/healthcheck.php (P0 - Bearer token)"
else
    echo -e "${RED}✗${NC} Missing: public/healthcheck.php.FIXED"
    exit 1
fi

if [ -f "app/Http/Controllers/DocsAuthController.php.FIXED" ]; then
    cp app/Http/Controllers/DocsAuthController.php.FIXED app/Http/Controllers/DocsAuthController.php
    echo -e "${GREEN}✓${NC} Fixed DocsAuthController.php (P1 - Session fixation)"
else
    echo -e "${RED}✗${NC} Missing: app/Http/Controllers/DocsAuthController.php.FIXED"
    exit 1
fi
echo ""

# Step 3: Generate new token
echo -e "${BLUE}[3/6]${NC} Generating new HEALTHCHECK_TOKEN..."
NEW_TOKEN=$(openssl rand -base64 32)
echo -e "${GREEN}✓${NC} New token generated: ${YELLOW}$NEW_TOKEN${NC}"
echo ""

# Step 4: Update .env
echo -e "${BLUE}[4/6]${NC} Updating .env file..."
if grep -q "HEALTHCHECK_TOKEN=" .env; then
    sed -i.backup "s/HEALTHCHECK_TOKEN=.*/HEALTHCHECK_TOKEN=$NEW_TOKEN/" .env
    echo -e "${GREEN}✓${NC} .env updated"
else
    echo "HEALTHCHECK_TOKEN=$NEW_TOKEN" >> .env
    echo -e "${GREEN}✓${NC} .env updated (new entry)"
fi
echo ""

# Step 5: Verify fixes
echo -e "${BLUE}[5/6]${NC} Verifying fixes..."

# Check for hardcoded token (should not exist)
if grep -q "PewleIAhHC8IliNYhuKYG8W9iMMdrz0Ed3If6kCIMH0=" public/healthcheck.php; then
    echo -e "${RED}✗${NC} Hardcoded token still exists in healthcheck.php!"
    exit 1
else
    echo -e "${GREEN}✓${NC} Hardcoded token removed"
fi

# Check for session regeneration (should exist)
if grep -q 'session()->regenerate()' app/Http/Controllers/DocsAuthController.php; then
    echo -e "${GREEN}✓${NC} Session regeneration added"
else
    echo -e "${RED}✗${NC} Session regeneration not found in DocsAuthController.php!"
    exit 1
fi
echo ""

# Step 6: Summary
echo -e "${BLUE}[6/6]${NC} Deployment Summary"
echo "==========================================================================="
echo -e "${GREEN}✓${NC} P0 - Bearer token vulnerability fixed"
echo -e "${GREEN}✓${NC} P1 - Session fixation vulnerability fixed"
echo -e "${GREEN}✓${NC} New token generated and configured"
echo ""
echo -e "${YELLOW}IMPORTANT:${NC} You must also update GitHub Actions secrets:"
echo ""
echo "  gh secret set HEALTHCHECK_TOKEN --body \"$NEW_TOKEN\" --repo <your-repo>"
echo ""
echo -e "${YELLOW}NEXT STEPS:${NC}"
echo "  1. Update GitHub Actions secret (see above)"
echo "  2. Run tests: ./tests/security/test-pr723-fixes.sh"
echo "  3. Commit changes: git add . && git commit -m 'fix(security): P0+P1 vulnerabilities'"
echo "  4. Deploy to staging: git push origin develop"
echo ""
echo "==========================================================================="
echo -e "${GREEN}Security fixes applied successfully!${NC}"
echo "==========================================================================="
