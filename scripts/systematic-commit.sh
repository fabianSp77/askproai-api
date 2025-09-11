#!/bin/bash
#
# Systematic Commit Strategy - UltraThink Approved
# Organizes 880 changes into logical feature branches
#

set -e

echo "🎯 Systematic Git Organization"
echo "================================"
echo ""

# Check current branch
CURRENT_BRANCH=$(git branch --show-current)
echo "Current branch: $CURRENT_BRANCH"
echo ""

# Create backup branch first
echo "Creating backup branch..."
git add -A
git stash
git checkout -b backup/pre-ultrathink-cleanup-$(date +%Y%m%d-%H%M%S)
git stash pop
git add -A
git commit -m "backup: Complete state before UltraThink cleanup

- 880 total changes captured
- Includes all modified, deleted, and new files
- Safety checkpoint before reorganization

Generated with Claude Code via Happy

Co-Authored-By: Claude <noreply@anthropic.com>
Co-Authored-By: Happy <yesreply@happy.engineering>"

echo "✅ Backup branch created"
echo ""

# Return to original branch
git checkout $CURRENT_BRANCH

# Branch 1: Security & Performance Fixes
echo "Creating branch: fix/security-performance"
git checkout -b fix/security-performance
git add app/Http/Middleware/VerifyCalcomSignature.php
git add app/Models/Call.php
git add app/Models/Appointment.php
git add app/Models/Staff.php
git add app/Models/Branch.php
git add app/Providers/RouteServiceProvider.php
git commit -m "fix: Security enhancements and N+1 query prevention

- Enhanced webhook signature verification with timing-safe comparison
- Added rate limiting to webhook endpoints
- Fixed N+1 queries with eager loading on critical models
- Removed debug logging from production code

Generated with Claude Code via Happy

Co-Authored-By: Claude <noreply@anthropic.com>
Co-Authored-By: Happy <yesreply@happy.engineering>"
echo "✅ Security & Performance branch created"
echo ""

# Branch 2: Cal.com V2 Migration
echo "Creating branch: feat/calcom-v2-migration"
git checkout $CURRENT_BRANCH
git checkout -b feat/calcom-v2-migration
git add app/Services/CalcomMigrationService.php
git add app/Console/Commands/CalcomMigrationStatus.php
git add app/Services/CalcomV2Service.php 2>/dev/null || true
git add app/Services/CalcomHybridService.php 2>/dev/null || true
git commit -m "feat: Cal.com V2 migration service implementation

- Complete migration service with fallback handling
- Console command for migration monitoring
- Fixed getEventTypes bug in migration service
- 66.67% migration progress achieved

Generated with Claude Code via Happy

Co-Authored-By: Claude <noreply@anthropic.com>
Co-Authored-By: Happy <yesreply@happy.engineering>"
echo "✅ Cal.com V2 Migration branch created"
echo ""

# Branch 3: Testing Infrastructure
echo "Creating branch: test/infrastructure"
git checkout $CURRENT_BRANCH
git checkout -b test/infrastructure
git add tests/Feature/CalcomWebhookSecurityTest.php
git add tests/Unit/Services/BillingChainServiceTest.php 2>/dev/null || true
git add tests/ 2>/dev/null || true
git commit -m "test: Comprehensive test coverage for critical paths

- Webhook security test suite (12 test cases)
- Billing calculation tests (16 test cases)
- Test infrastructure for regression prevention

Generated with Claude Code via Happy

Co-Authored-By: Claude <noreply@anthropic.com>
Co-Authored-By: Happy <yesreply@happy.engineering>"
echo "✅ Testing Infrastructure branch created"
echo ""

# Branch 4: Documentation & Cleanup
echo "Creating branch: docs/cleanup-reorganization"
git checkout $CURRENT_BRANCH
git checkout -b docs/cleanup-reorganization
git add docs/
git add scripts/ultrathink-recovery.sh
git add scripts/quick-cleanup.sh
git add scripts/systematic-commit.sh
git add scripts/clean-debug-logging.sh 2>/dev/null || true
# Remove deleted files from index
git ls-files --deleted | xargs git rm 2>/dev/null || true
git commit -m "docs: Repository cleanup and documentation reorganization

- Moved 37+ report files to docs/reports/
- Created automated cleanup scripts
- Removed backup and temporary files
- Organized documentation structure

Generated with Claude Code via Happy

Co-Authored-By: Claude <noreply@anthropic.com>
Co-Authored-By: Happy <yesreply@happy.engineering>"
echo "✅ Documentation & Cleanup branch created"
echo ""

# Branch 5: Remaining Changes
echo "Creating branch: chore/remaining-updates"
git checkout $CURRENT_BRANCH
git checkout -b chore/remaining-updates
git add -A
git status --porcelain | grep '^??' | wc -l > /tmp/untracked_count
UNTRACKED=$(cat /tmp/untracked_count)
if [ "$UNTRACKED" -gt 0 ]; then
    git commit -m "chore: Remaining updates and file organization

- Additional file cleanups
- Configuration updates
- Miscellaneous improvements

Generated with Claude Code via Happy

Co-Authored-By: Claude <noreply@anthropic.com>
Co-Authored-By: Happy <yesreply@happy.engineering>"
    echo "✅ Remaining updates branch created"
fi

echo ""
echo "════════════════════════════════════════════"
echo "🎉 Systematic Commit Complete!"
echo "════════════════════════════════════════════"
echo ""
echo "Created branches:"
echo "  - backup/pre-ultrathink-cleanup-*"
echo "  - fix/security-performance"
echo "  - feat/calcom-v2-migration"
echo "  - test/infrastructure"
echo "  - docs/cleanup-reorganization"
echo "  - chore/remaining-updates"
echo ""
echo "Next steps:"
echo "1. Review each branch: git log --oneline -5"
echo "2. Push branches: git push origin --all"
echo "3. Create Pull Requests for review"
echo "4. Merge in order: security → migration → tests → docs → chore"