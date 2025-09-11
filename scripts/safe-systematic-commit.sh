#!/bin/bash
#
# Safe Systematic Commit - Sicherer Ansatz für 880 Änderungen
# UltraThink SuperClaude Framework
#

set -e
set -o pipefail

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔒 Safe Systematic Git Organization${NC}"
echo "===================================="
echo ""

# Current state
CURRENT_BRANCH=$(git branch --show-current)
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
echo -e "${YELLOW}Current branch:${NC} $CURRENT_BRANCH"
echo -e "${YELLOW}Total changes:${NC} $(git status --porcelain | wc -l)"
echo ""

# Step 1: Create safety backup
echo -e "${BLUE}Step 1: Creating safety backup...${NC}"
git add -A
git commit -m "WIP: Safety backup before reorganization - $TIMESTAMP

This commit contains all 880 changes as a safety checkpoint.
Will be reorganized into proper feature branches.

Total changes:
- Modified files: $(git status --porcelain | grep '^M' | wc -l)
- Deleted files: $(git status --porcelain | grep '^D' | wc -l)  
- New files: $(git status --porcelain | grep '^??' | wc -l)

Generated with Claude Code via Happy

Co-Authored-By: Claude <noreply@anthropic.com>
Co-Authored-By: Happy <yesreply@happy.engineering>" || {
    echo -e "${YELLOW}No changes to commit or already committed${NC}"
}

BACKUP_COMMIT=$(git rev-parse HEAD)
echo -e "${GREEN}✅ Backup commit created: $BACKUP_COMMIT${NC}"
echo ""

# Step 2: Create feature branches from the backup
echo -e "${BLUE}Step 2: Creating organized feature branches...${NC}"

# Security & Performance Branch
echo -e "${YELLOW}Creating security-performance branch...${NC}"
git checkout -b fix/security-performance-$TIMESTAMP
git reset --mixed $BACKUP_COMMIT~1  # Go back before backup
git add app/Http/Middleware/VerifyCalcomSignature.php 2>/dev/null || true
git add app/Models/Call.php 2>/dev/null || true
git add app/Models/Appointment.php 2>/dev/null || true
git add app/Models/Staff.php 2>/dev/null || true
git add app/Models/Branch.php 2>/dev/null || true
git add app/Providers/RouteServiceProvider.php 2>/dev/null || true

if [ $(git diff --cached --name-only | wc -l) -gt 0 ]; then
    git commit -m "fix: Enhanced security and performance optimizations

- Webhook signature verification with timing-safe comparison
- Rate limiting for webhook endpoints  
- N+1 query prevention with eager loading
- Debug logging cleanup for production

Generated with Claude Code via Happy

Co-Authored-By: Claude <noreply@anthropic.com>
Co-Authored-By: Happy <yesreply@happy.engineering>"
    echo -e "${GREEN}✅ Security & Performance branch created${NC}"
else
    echo -e "${YELLOW}No security/performance changes to commit${NC}"
    git checkout $CURRENT_BRANCH
    git branch -D fix/security-performance-$TIMESTAMP
fi

# Cal.com V2 Migration Branch
echo -e "${YELLOW}Creating Cal.com V2 migration branch...${NC}"
git checkout $CURRENT_BRANCH
git checkout -b feat/calcom-v2-complete-$TIMESTAMP
git reset --mixed $BACKUP_COMMIT~1
git add app/Services/CalcomMigrationService.php 2>/dev/null || true
git add app/Console/Commands/CalcomMigrationStatus.php 2>/dev/null || true
git add app/Services/CalcomV2Service.php 2>/dev/null || true
git add app/Services/CalcomHybridService.php 2>/dev/null || true
git add app/Console/Commands/SyncCalcomEventTypes.php 2>/dev/null || true

if [ $(git diff --cached --name-only | wc -l) -gt 0 ]; then
    git commit -m "feat: Cal.com V2 migration implementation

- Complete migration service with fallback handling
- Console command for migration monitoring  
- Fixed getEventTypes method issues
- 66.67% migration progress achieved

Generated with Claude Code via Happy

Co-Authored-By: Claude <noreply@anthropic.com>
Co-Authored-By: Happy <yesreply@happy.engineering>"
    echo -e "${GREEN}✅ Cal.com V2 Migration branch created${NC}"
else
    echo -e "${YELLOW}No Cal.com changes to commit${NC}"
    git checkout $CURRENT_BRANCH
    git branch -D feat/calcom-v2-complete-$TIMESTAMP
fi

# Test Infrastructure Branch
echo -e "${YELLOW}Creating test infrastructure branch...${NC}"
git checkout $CURRENT_BRANCH
git checkout -b test/comprehensive-coverage-$TIMESTAMP
git reset --mixed $BACKUP_COMMIT~1
git add tests/ -f 2>/dev/null || true
git add phpunit.xml 2>/dev/null || true

if [ $(git diff --cached --name-only | wc -l) -gt 0 ]; then
    git commit -m "test: Comprehensive test coverage implementation

- Webhook security tests (12 cases)
- Billing calculation tests (16 cases)
- Test infrastructure for regression prevention

Generated with Claude Code via Happy

Co-Authored-By: Claude <noreply@anthropic.com>
Co-Authored-By: Happy <yesreply@happy.engineering>"
    echo -e "${GREEN}✅ Test Infrastructure branch created${NC}"
else
    echo -e "${YELLOW}No test changes to commit${NC}"
    git checkout $CURRENT_BRANCH
    git branch -D test/comprehensive-coverage-$TIMESTAMP
fi

# Documentation & Scripts Branch
echo -e "${YELLOW}Creating documentation branch...${NC}"
git checkout $CURRENT_BRANCH
git checkout -b docs/ultrathink-cleanup-$TIMESTAMP
git reset --mixed $BACKUP_COMMIT~1
git add docs/ -f 2>/dev/null || true
git add scripts/ -f 2>/dev/null || true
git add *.md 2>/dev/null || true

if [ $(git diff --cached --name-only | wc -l) -gt 0 ]; then
    git commit -m "docs: Repository cleanup and documentation

- Organized documentation in docs/reports/
- Created automated cleanup scripts
- UltraThink recovery procedures
- Repository reorganization documentation

Generated with Claude Code via Happy

Co-Authored-By: Claude <noreply@anthropic.com>
Co-Authored-By: Happy <yesreply@happy.engineering>"
    echo -e "${GREEN}✅ Documentation branch created${NC}"
else
    echo -e "${YELLOW}No documentation changes to commit${NC}"
    git checkout $CURRENT_BRANCH
    git branch -D docs/ultrathink-cleanup-$TIMESTAMP
fi

# Return to original branch  
git checkout $CURRENT_BRANCH

echo ""
echo -e "${GREEN}════════════════════════════════════════${NC}"
echo -e "${GREEN}✨ Safe Systematic Commit Complete!${NC}"
echo -e "${GREEN}════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE}Summary:${NC}"
echo -e "Backup Commit: ${YELLOW}$BACKUP_COMMIT${NC}"
echo -e "Original Branch: ${YELLOW}$CURRENT_BRANCH${NC}"
echo ""
echo -e "${BLUE}Created Branches:${NC}"
git branch | grep $TIMESTAMP | while read branch; do
    echo -e "  ${GREEN}✓${NC} $branch"
done
echo ""
echo -e "${BLUE}Next Steps:${NC}"
echo "1. Review each branch: git log --oneline -n 3"
echo "2. Test changes: php artisan test"
echo "3. Push branches: git push origin <branch-name>"
echo "4. Create Pull Requests for review"
echo ""
echo -e "${YELLOW}To view changes in a branch:${NC}"
echo "  git checkout <branch-name>"
echo "  git diff HEAD~1"
echo ""
echo -e "${YELLOW}To reset if something went wrong:${NC}"
echo "  git checkout $CURRENT_BRANCH"
echo "  git reset --hard $BACKUP_COMMIT"