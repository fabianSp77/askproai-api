#!/bin/bash

# IMMEDIATE ACTION CHECKLIST - Execute in Order
# Created: 2025-07-22

echo "🚀 ULTRATHINK IMMEDIATE ACTION CHECKLIST"
echo "========================================"
echo ""

# Color codes
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Function to pause and wait for confirmation
confirm() {
    read -p "✅ Press ENTER when complete..."
    echo ""
}

echo -e "${YELLOW}STEP 1: Commit Portal Fixes${NC}"
echo "Run: ./commit-essential-changes.sh"
echo "Then: git commit -m \"fix: Portal authentication and session handling\""
confirm

echo -e "${YELLOW}STEP 2: Security Audit${NC}"
echo "Checking for exposed credentials..."
grep -r "password\|secret\|key" --include="*.php" --include="*.js" public/ 2>/dev/null | grep -v "password:" | head -5
echo "Clean old logs..."
find storage/logs -name "*.log" -mtime +7 -type f | wc -l
echo "files will be deleted"
confirm

echo -e "${YELLOW}STEP 3: Create Safety Backup${NC}"
echo "Creating backup tag..."
git tag -a backup-$(date +%Y%m%d-%H%M%S) -m "Backup before major cleanup"
echo "✅ Backup tag created"
confirm

echo -e "${YELLOW}STEP 4: Commit API Controllers${NC}"
echo "Stage API v2 controllers..."
git add app/Http/Controllers/Api/V2/*.php 2>/dev/null
git add app/Services/*.php 2>/dev/null
echo "Ready to commit API updates"
confirm

echo -e "${YELLOW}STEP 5: Check System Health${NC}"
echo "Testing portal login..."
curl -s -X POST https://api.askproai.de/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@askproai.de","password":"DemoPass123!"}' | grep -q "token" && echo "✅ Login API working" || echo "❌ Login API failed"

echo ""
echo "Testing database connection..."
php -r "try { \$pdo = new PDO('mysql:host=127.0.0.1;dbname=askproai_db', 'askproai_user', 'lkZ57Dju9EDjrMxn'); echo '✅ Database connection OK'; } catch(Exception \$e) { echo '❌ Database connection failed'; }"
echo ""
confirm

echo -e "${YELLOW}STEP 6: Create Production Branch${NC}"
echo "Creating stable production branch..."
echo "Run: git checkout -b production/stable-$(date +%Y-%m-%d)"
echo "Then: git push origin production/stable-$(date +%Y-%m-%d)"
confirm

echo -e "${GREEN}✅ IMMEDIATE ACTIONS COMPLETE!${NC}"
echo ""
echo "📊 Next Priority Actions:"
echo "1. Review remaining 600+ uncommitted files"
echo "2. Set up monitoring alerts"
echo "3. Document the working portal setup"
echo "4. Plan feature roadmap meeting"
echo ""
echo "📈 Current Status:"
git status --short | wc -l | xargs echo "- Uncommitted files:"
find archive/ -type f | wc -l | xargs echo "- Archived files:"
echo ""
echo "🎯 Remember: Ship working code, iterate based on feedback!"