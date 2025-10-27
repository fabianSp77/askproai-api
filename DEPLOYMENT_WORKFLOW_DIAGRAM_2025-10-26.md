# Deployment Workflow & Git Strategy - Visual Guide
**Date**: 2025-10-26
**Feature**: Customer Portal
**Target Audience**: Developers, DevOps, Product Team

---

## Part 1: Overall Deployment Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                    CUSTOMER PORTAL DEPLOYMENT                        │
│                          (Oct 26 - Nov 23)                           │
└─────────────────────────────────────────────────────────────────────┘

DEVELOPMENT (Weeks 1-3)
├─ Code on feature/customer-portal
├─ Local testing + unit tests
├─ Push changes → GitHub
└─ GitHub Actions: Run all tests (unit, integration, E2E, security)

                              ↓

STAGING VALIDATION (Week 4)
├─ Deploy to staging.askproai.de
├─ Run complete validation checklist (40+ items)
├─ QA team manual testing
├─ Fix issues on feature branch
├─ Re-deploy to staging (automated)
└─ Product team approval

                              ↓

PRODUCTION DEPLOYMENT (Week 5)
├─ Create PR: feature/customer-portal → main
├─ Code review + 2 approvals
├─ GitHub Actions: Run all tests
├─ Merge to main
├─ GitHub Actions: Auto-deploy to production
├─ Feature flag: FEATURE_CUSTOMER_PORTAL=false (SAFE DEFAULT)
└─ 24-hour monitoring

                              ↓

GRADUAL ROLLOUT (Weeks 6+)
├─ Week 5: Enable for 2-3 pilot companies
├─ Week 6: Monitor for 1 week (no issues)
├─ Week 7: Enable for 10% of customers
├─ Week 8: Enable for 50% (if stable)
├─ Week 9+: Full rollout (100%)
└─ Daily monitoring + weekly reviews
```

---

## Part 2: Git Branch Strategy

```
┌──────────────────────────────────────────────────────────────────┐
│                      GIT BRANCH STRATEGY                          │
└──────────────────────────────────────────────────────────────────┘

MAIN BRANCH (Production)
    ↑
    │ ← [MERGE] After PR approval
    │
    ├─────────────────────────────────────────┐
    │                                         │
    │  feature/customer-portal                │  Other features
    │  ├─ Local development                   │  (stable)
    │  ├─ Push → Staging deployment           │
    │  ├─ Validation + fixes                  │
    │  ├─ PR creation                         │
    │  ├─ Code review                         │
    │  └─ MERGE → Main                        │
    │     └─ Auto-deploy to production        │
    │        └─ Feature flag OFF              │
    │                                         │
    │  fix/portal-issue (if needed)           │
    │  ├─ Branch from feature/customer-portal │
    │  ├─ Fix bug quickly                     │
    │  ├─ PR → feature/customer-portal        │
    │  └─ MERGE (continues cycle)             │
    │                                         │
    │  fix/hotfix-production (urgent)         │
    │  ├─ Branch from main                    │
    │  ├─ Fix production bug                  │
    │  ├─ PR → main (fast-track)              │
    │  ├─ MERGE → main                        │
    │  └─ Can also merge back to feature/*    │
    │                                         │
    │  fix/admin-allowlist-and-proxies        │
    │  ├─ Standard feature development        │
    │  ├─ Independent from portal             │
    │  └─ Can merge to main independently     │
    │                                         │
    └─────────────────────────────────────────┘

PRODUCTION RELEASES:
    main → Deploy immediately (GitHub Actions auto)
    Feature flags control which features are active
    No manual deployment steps needed
```

---

## Part 3: Environment Progression

```
┌─────────────────────────────────────────────────────────────────────┐
│                    ENVIRONMENT PROGRESSION                           │
└─────────────────────────────────────────────────────────────────────┘

DEVELOPER MACHINE
    ├─ .env (local)
    ├─ Feature: OFF (testing locally)
    ├─ Database: Local SQLite or MySQL
    ├─ Redis: Local (or disabled)
    └─ Tests: vendor/bin/pest

                      ↓ (git push)

GITHUB ACTIONS CI
    ├─ .env.testing (GitHub Actions)
    ├─ All features: Default OFF
    ├─ Database: MySQL 8.0 service container
    ├─ Redis: Redis 7 service container
    ├─ Tests: Unit, Integration, E2E, Security, Performance
    └─ Exit code: 0 = success, 1 = fail

                      ↓ (deployment script)

STAGING ENVIRONMENT
    ├─ .env.staging
    ├─ Feature: CUSTOMER_PORTAL=true (ENABLED FOR TESTING)
    ├─ Database: askproai_staging (copy of production)
    ├─ Redis: askpro_staging_* prefix
    ├─ Cache: Separate from production
    ├─ URL: https://staging.askproai.de
    ├─ Users: Same as production (password=test123)
    ├─ Admin IP: Allow all (for testing)
    └─ Logs: Same level as production (debug)

                      ↓ (git merge + auto-deploy)

PRODUCTION ENVIRONMENT
    ├─ .env (production)
    ├─ Feature: CUSTOMER_PORTAL=false (DISABLED BY DEFAULT)
    ├─ Database: askproai_db (production data)
    ├─ Redis: askpro_cache_* prefix
    ├─ URL: https://api.askproai.de
    ├─ Users: Real customers
    ├─ Admin IP: Limited (212.91.238.41, etc.)
    ├─ Logs: info level
    └─ Monitoring: 24h post-deployment
```

---

## Part 4: Feature Flag Control Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│              FEATURE FLAG CONTROL FLOW                               │
└─────────────────────────────────────────────────────────────────────┘

REQUEST to /portal (Public User)
    ↓
ROUTING LAYER (Laravel Router)
    ├─ Route registered: /portal → CustomerPortal\Dashboard
    └─ Middleware: feature:customer_portal
        ↓
FEATURE FLAG CHECK (CheckFeatureFlag Middleware)
    ├─ Read: config('features.customer_portal')
    ├─ Gets value from: env('FEATURE_CUSTOMER_PORTAL')
    ├─ Reads from: .env file or environment
    │
    ├─ IF FALSE (disabled):
    │   └─ Return 404 Not Found (pretend route doesn't exist)
    │       └─ User never knows feature exists (security!)
    │
    └─ IF TRUE (enabled):
        ↓
    AUTHENTICATION CHECK
        ├─ Is user logged in?
        │   ├─ NO → Redirect to /admin/login
        │   └─ YES → Continue
        │
        ├─ Can user access portal?
        │   ├─ User::canAccessCustomerPortal() → true/false
        │   └─ YES → Continue
        │
        └─ Load page with user's data
            └─ Multi-tenant isolation enforced

REQUEST to /admin/some-resource
    ↓
ADMIN ROUTES (Filament)
    ├─ Portal feature flag: DOES NOT APPLY
    ├─ Admin routes independent
    └─ Works regardless of FEATURE_CUSTOMER_PORTAL

REQUEST to /api/webhook (Retell/Cal.com)
    ↓
API ROUTES
    ├─ Portal feature flag: DOES NOT APPLY
    ├─ API routes independent
    └─ Feature flags don't affect webhooks
```

---

## Part 5: Feature Flag Toggle Points

```
┌─────────────────────────────────────────────────────────────────────┐
│            WHERE TO ENABLE/DISABLE FEATURES                          │
└─────────────────────────────────────────────────────────────────────┘

OPTION 1: Direct .env File Edit
    ├─ Location: /var/www/api-gateway/.env
    ├─ Command: nano .env
    ├─ Edit: FEATURE_CUSTOMER_PORTAL=false → true
    ├─ Reload: php artisan config:cache
    ├─ Restart: sudo systemctl restart php8.2-fpm
    └─ Time: Immediate (30 seconds)

OPTION 2: Using Script (Recommended)
    ├─ Location: /scripts/toggle-feature-flag.php
    ├─ Command: php /scripts/toggle-feature-flag.php production customer_portal true
    ├─ Handles: Cache clearing automatically
    ├─ Logging: Records who changed what when
    └─ Time: Immediate (5 seconds)

OPTION 3: Admin Panel (Future - Phase 2)
    ├─ Location: /admin/settings/features
    ├─ Method: GUI toggle switches
    ├─ Features: Real-time updates, audit trail
    └─ Status: Not yet implemented

ENVIRONMENT OVERRIDE (Docker/CI)
    ├─ Location: Docker environment variables
    ├─ Method: -e FEATURE_CUSTOMER_PORTAL=true
    ├─ Use: Testing, CI/CD pipelines
    └─ Precedence: Overrides .env

RECOMMENDATION FOR GRADUAL ROLLOUT:
    ├─ Start: FEATURE_CUSTOMER_PORTAL=false in production
    ├─ Test: Use staging with feature=true
    ├─ After approval: Enable in production
    ├─ Monitor: For 24 hours
    ├─ Gradual: Enable for specific companies via
    │           FEATURE_CUSTOMER_PORTAL_TEST_COMPANIES=15,42,103
    └─ Full: Remove test company limit
```

---

## Part 6: Testing Workflow

```
┌─────────────────────────────────────────────────────────────────────┐
│                      TESTING WORKFLOW                                │
└─────────────────────────────────────────────────────────────────────┘

DEVELOPER: Local Testing (on feature/customer-portal)
    ├─ Run unit tests: vendor/bin/pest tests/Unit/
    ├─ Run feature tests: vendor/bin/pest tests/Feature/
    ├─ Test manual scenarios locally
    ├─ Feature flag: OFF (test both disabled and enabled)
    └─ Commit & push → GitHub

                              ↓

GITHUB ACTIONS: Automated Testing (on push)
    ├─ Unit Tests (PHPUnit)
    │   ├─ Database: MySQL 8.0
    │   ├─ Redis: Redis 7
    │   └─ Coverage: > 80%
    │
    ├─ Integration Tests (Feature)
    │   ├─ Full database interactions
    │   └─ API endpoints
    │
    ├─ E2E Tests (Playwright)
    │   ├─ Portal login flow
    │   ├─ Call history display
    │   └─ Appointments calendar
    │
    ├─ Performance Tests (K6)
    │   └─ Booking flow: < 45s
    │
    ├─ Security Tests
    │   ├─ PHPStan static analysis (level 8)
    │   ├─ SQL injection prevention
    │   ├─ XSS prevention
    │   └─ CSRF protection
    │
    └─ Results: Email + GitHub status badge
        ├─ SUCCESS ✅ → Continue to staging
        └─ FAILURE ❌ → Push fixes, retry

                              ↓

STAGING DEPLOYMENT (automatic or manual)
    ├─ Pull feature/customer-portal branch
    ├─ Run migrations: php artisan migrate --force
    ├─ Clear caches: php artisan config:cache
    ├─ Health check: curl https://staging.askproai.de/health
    └─ Ready for QA testing

                              ↓

QA MANUAL TESTING (Staging)
    ├─ Login with test account
    ├─ Access portal: /portal
    ├─ Test call history view
    ├─ Test appointments calendar
    ├─ Test feature flag disable → 404
    ├─ Test feature flag enable → works
    ├─ Test multi-tenant isolation
    ├─ Document any issues
    └─ Approval: Signed off by QA lead

                              ↓

IF ISSUES FOUND:
    ├─ Create issue in GitHub
    ├─ Create fix/ branch from feature/customer-portal
    ├─ Fix code + commit
    ├─ Push → GitHub Actions runs tests again
    ├─ Re-deploy to staging (automatic)
    ├─ QA re-tests the fix
    └─ If OK → Merge back to feature/customer-portal

                              ↓

READY FOR PRODUCTION:
    ├─ All tests passing ✅
    ├─ Staging QA approved ✅
    ├─ Product team approved ✅
    └─ → Create PR to main

                              ↓

CODE REVIEW (Main PR)
    ├─ 2 senior developers review
    ├─ Security review: Feature flag implementation
    ├─ Performance review: No N+1 queries
    ├─ Testing review: Coverage > 80%
    └─ Approve & merge

                              ↓

PRODUCTION DEPLOYMENT (automatic)
    ├─ GitHub Actions triggers
    ├─ Deploy to api.askproai.de
    ├─ Feature flag: DISABLED (safe!)
    ├─ Smoke tests run (health check, basic routes)
    ├─ Slack notification: Deployment complete
    └─ Monitoring: 24h log watch

                              ↓

ROLLOUT (Manual, Gradual)
    ├─ Day 1-3: Monitor logs (no portal feature yet)
    ├─ Day 4: Enable for 2-3 pilot companies
    ├─ Day 5-11: Monitor production data
    ├─ Day 12: Enable for 10% of customers
    ├─ Day 19: Enable for 50% (if stable)
    ├─ Day 26: Full rollout (100%)
    └─ Ongoing: Weekly monitoring
```

---

## Part 7: Deployment Timeline Calendar

```
┌──────────────────────────────────────────────────────────────────────┐
│                    OCTOBER - NOVEMBER 2025                            │
└──────────────────────────────────────────────────────────────────────┘

WEEK 1 (Oct 26-30) - Infrastructure Setup
    Mon: Create staging.askproai.de vhost + database
    Tue: Setup .env.staging, SSL certificate
    Wed: Test basic connectivity
    Thu: Setup health checks, logging
    Fri: Infrastructure validation complete ✅

WEEK 2 (Nov 2-6) - Deployment Automation
    Mon: Create GitHub Actions staging workflow
    Tue: Test automatic deployment from feature branch
    Wed: Create deployment scripts (deploy-staging.sh, etc.)
    Thu: Test database sync procedures
    Fri: Full CI/CD pipeline ready ✅

WEEK 3 (Nov 9-13) - Feature Development Finalization
    Mon-Fri: Final development on feature/customer-portal
    Daily: GitHub Actions runs all tests
    Daily: Code fixes based on test results
    Fri: Feature complete, ready for staging ✅

WEEK 4 (Nov 16-20) - Staging Validation
    Mon: Deploy feature/customer-portal to staging
    Tue-Wed: QA runs testing checklist (40+ items)
    Thu: Fix issues found, re-deploy
    Fri: Staging validation complete, approval ✅

WEEK 5 (Nov 23-27) - Production Deployment
    Mon: Create PR: feature/customer-portal → main
    Tue: Code review (2 approvals needed)
    Wed: Merge to main (tests run)
    Thu: GitHub Actions auto-deploys to production
    Thu-Fri: 24-hour monitoring
    Fri: Deployment complete ✅

WEEK 6-9 (Nov 30 - Dec 21) - Gradual Rollout
    Week 6: Enable for 2-3 pilot companies
    Week 7: Monitor (no issues found)
    Week 8: Enable for 10% of customers
    Week 9: Enable for 50% (further rollout decision)

WEEK 10+ (Dec 28+) - Full Deployment
    Rollout to 100% of customers
    Ongoing monitoring + support
    Plan next phase features (CRM, Services, Staff)
```

---

## Part 8: Rollback Decision Tree

```
┌──────────────────────────────────────────────────────────────────────┐
│                      ROLLBACK DECISION TREE                           │
└──────────────────────────────────────────────────────────────────────┘

ISSUE DETECTED IN PRODUCTION
    │
    ├─ Severity: CRITICAL (users cannot access portal)
    │   │
    │   ├─ Immediate Action: Disable feature flag
    │   │   ├─ FEATURE_CUSTOMER_PORTAL=false
    │   │   ├─ php artisan config:cache
    │   │   ├─ sudo systemctl restart php8.2-fpm
    │   │   └─ Time: 30 seconds
    │   │
    │   ├─ Verify: Portal returns 404 immediately
    │   │
    │   └─ Recovery: 30 minutes
    │       ├─ Investigate root cause
    │       ├─ Fix code on feature branch
    │       ├─ Test on staging
    │       ├─ Re-enable when fixed
    │       └─ Monitor for 24 hours
    │
    ├─ Severity: HIGH (partial functionality broken)
    │   │
    │   ├─ Monitor: Check if impacts many users
    │   │
    │   ├─ Option A: Disable immediately
    │   │   ├─ If > 10% of portal users affected
    │   │   ├─ Symptom: Users see errors, blank pages, 500s
    │   │   └─ Disable feature flag
    │   │
    │   └─ Option B: Keep enabled + hot-fix
    │       ├─ If < 10% affected
    │       ├─ Create emergency fix/ branch
    │       ├─ Fast-track to production
    │       └─ Monitor closely
    │
    ├─ Severity: MEDIUM (minor issues)
    │   │
    │   └─ Plan hotfix for next deployment
    │       ├─ Document issue
    │       ├─ Fix on feature branch
    │       ├─ Test on staging
    │       ├─ Deploy with next release
    │       └─ No need to disable feature
    │
    └─ Severity: LOW (cosmetic, informational)
        │
        └─ Plan for next release
            ├─ Log issue
            ├─ Feature remains enabled
            └─ Fix in regular release cycle

GIT ROLLBACK (Code Issue):
    ├─ Find deployment commit: git log --oneline
    ├─ Revert code: git revert <commit-hash>
    ├─ Push: git push origin main
    ├─ GitHub Actions: Auto-deploys reverted version
    └─ Time: 5 minutes

DATABASE ROLLBACK (Migration Failed):
    ├─ Only if migrations caused critical failure
    ├─ Run: php artisan migrate:rollback --force
    ├─ Verify app works: curl https://api.askproai.de/health
    ├─ This rolls back LAST migration batch
    └─ Time: 1-2 minutes
    ├─ ⚠️ WARNING: May lose data from latest migration
    └─ Better: Use feature flag disable (safer)

FEATURE FLAG DISABLE (Recommended First Step):
    ├─ No code changes needed
    ├─ No data changes
    ├─ Instant effect (< 1 minute)
    ├─ Easy to re-enable when fixed
    ├─ No risk of data loss
    └─ ✅ BEST OPTION for production issues
```

---

## Part 9: Success Metrics

```
┌──────────────────────────────────────────────────────────────────────┐
│                    SUCCESS METRICS                                    │
└──────────────────────────────────────────────────────────────────────┘

DEVELOPMENT PHASE (Weeks 1-3):
    ✅ All GitHub Actions tests passing
    ✅ Code coverage > 80%
    ✅ Zero security scanning issues
    ✅ All E2E tests passing
    ✅ Feature implemented as designed

STAGING VALIDATION PHASE (Week 4):
    ✅ 40+ validation checklist items passing
    ✅ Portal loads without errors
    ✅ All CRUD operations working
    ✅ Multi-tenant isolation verified
    ✅ Feature flag enable/disable working
    ✅ QA sign-off obtained

PRODUCTION DEPLOYMENT PHASE (Week 5):
    ✅ Automated deployment succeeds
    ✅ Health check passes
    ✅ Application starts successfully
    ✅ No deployment-related errors in logs
    ✅ Feature disabled by default (safe)
    ✅ No customer impact during deployment

24-HOUR MONITORING (Post-deployment):
    ✅ Error rate: Same as before deployment
    ✅ Performance: No degradation
    ✅ Uptime: 99.9%+
    ✅ Log errors: 0 portal-related errors
    ✅ Database: No query anomalies
    ✅ Redis cache: Normal hit ratio

GRADUAL ROLLOUT PHASE (Weeks 6+):
    PILOT PHASE (2-3 companies):
        ✅ No complaints from pilot users
        ✅ No error spikes
        ✅ Feature functions correctly
        ✅ Users can access portal
        ✅ Call history displays accurately
        ✅ Appointments shown correctly

    10% ROLLOUT:
        ✅ Still no major issues
        ✅ Positive user feedback
        ✅ No performance degradation
        ✅ Error rates stable

    50% ROLLOUT:
        ✅ Feature performing as expected
        ✅ High user satisfaction
        ✅ All metrics normal
        ✅ Ready for full rollout

    100% ROLLOUT:
        ✅ All customers have access
        ✅ Portal actively used
        ✅ System stable and reliable
        ✅ Phase 2 planning begins

LONG-TERM SUCCESS (Month 1+):
    ✅ User adoption > 70% (portal accessed)
    ✅ Average session duration > 5 min
    ✅ Feature completion: Phase 1 + early Phase 2
    ✅ User satisfaction: > 4.0/5.0 rating
    ✅ Support tickets: < 5% related to portal
    ✅ Performance: Consistent < 3s page loads
```

---

## Part 10: Communication Templates

### Staging Ready Announcement
```
Subject: Customer Portal - Staging Ready for Testing

Team,

The customer portal feature is now deployed to staging for comprehensive testing.

Environment:
  URL: https://staging.askproai.de
  Login: Use test credentials (password: test123)
  Database: Production data (sanitized)

What to Test:
  1. Call history view with transcripts
  2. Appointments calendar
  3. Dashboard statistics
  4. Feature flag behavior (enable/disable)
  5. Multi-tenant isolation (different companies)

Timeline:
  Testing period: Nov 16-20 (1 week)
  Issues: Report in GitHub Issues with "staging" label
  Fixes: Deploy automatically when merged to feature branch
  Approval: Expected by Nov 20

Access: All team members have credentials (see Slack #deployments)

Questions? Contact: [PM Name]
```

### Production Deployment Announcement
```
Subject: 🚀 Customer Portal - Deploying to Production

Team,

The customer portal feature has been approved and will deploy to production
on Nov 23 (Thursday).

Deployment Details:
  Branch: feature/customer-portal → main
  Time: 2025-11-23 14:00 UTC
  Duration: ~5 minutes
  Downtime: None expected
  Feature Status: DISABLED by default (safe rollout)

What's Being Deployed:
  ✅ Portal infrastructure (/portal route)
  ✅ Call history page
  ✅ Appointments page
  ✅ Dashboard

What's NOT Active Yet:
  ❌ Feature available to customers
  ❌ Portal accessible (returns 404)
  ❌ This will happen after monitoring period

Safety Measures:
  ✅ Automated tests: 100% passing
  ✅ Staging validation: Complete
  ✅ Rollback: One command (disable feature flag)
  ✅ Monitoring: 24-hour log watch

Post-Deployment:
  1. Hour 1: Smoke tests & health checks
  2. Hours 2-24: Continuous monitoring
  3. Day 2-3: Stability assessment
  4. Day 4+: Gradual rollout to customers

On-Call Engineer: [Name] (Slack: @[oncall])
Escalation: Contact PM if issues

Questions before 11/22? Reply all.
```

### Gradual Rollout Announcement
```
Subject: 📊 Customer Portal - Rollout Phase 1 (2-3 Pilot Companies)

Team,

Staging validation is complete with excellent results. We're proceeding to
gradual rollout starting today.

Phase 1: Pilot Companies (Nov 24-30)
  ├─ Companies: ID 15, 42 (TBD)
  ├─ Users: ~50 people
  ├─ Feature: ENABLED for these companies only
  ├─ Monitoring: Daily checks, weekly report
  └─ Success Criteria: 0 critical issues, positive feedback

If Pilot Successful (Dec 1+):
  ├─ Phase 2: 10% of customers (~2,000 people)
  ├─ Phase 3: 50% of customers (~10,000 people)
  └─ Phase 4: 100% of customers (all users)

How to Know Your Company is in Pilot:
  ├─ Check /portal - should show portal (not 404)
  ├─ Or: Check .env FEATURE_CUSTOMER_PORTAL_TEST_COMPANIES
  └─ Or: Ask your account manager

Feedback:
  ├─ Positive: Send to [PM]
  ├─ Issues: Create GitHub issue or email [Support]
  └─ Suggestions: Slack #feature-requests

Monitoring Dashboard: [Link]
Status Page: https://api.askproai.de/health

Questions? Slack #portal-launch
```

---

## Quick Reference: Commands by Phase

```
DEVELOPMENT PHASE:
    git checkout -b feature/customer-portal
    git commit -m "feat: add customer portal"
    git push -u origin feature/customer-portal
    vendor/bin/pest  (run tests locally)

STAGING PHASE:
    bash scripts/deploy-staging.sh feature/customer-portal
    bash scripts/sync-staging-database.sh
    tail -f storage/logs/laravel.log
    curl https://staging.askproai.de/portal

PRODUCTION PHASE:
    git push origin feature/customer-portal
    # Create PR on GitHub (web UI)
    # Wait for reviews + approval
    # Click "Merge" button
    # GitHub Actions auto-deploys
    curl https://api.askproai.de/health

ROLLOUT PHASE:
    php scripts/toggle-feature-flag.php production customer_portal true
    php scripts/toggle-feature-flag.php production customer_portal false (rollback)
    grep FEATURE_CUSTOMER_PORTAL .env
    tail -f storage/logs/laravel.log | grep -i portal

MONITORING:
    watch -n 5 'curl -s https://api.askproai.de/health | jq .'
    redis-cli INFO stats
    tail -f storage/logs/laravel.log
    mysql -e "SELECT COUNT(*) FROM retell_call_sessions WHERE created_at > NOW() - INTERVAL 1 HOUR;"
```

---

**End of Workflow Guide**

This visual guide provides team members with clear understanding of:
- When each phase occurs
- What testing happens at each stage
- How to communicate status
- What commands to run
- How to handle issues
- Success metrics to track

Print this guide and post in team Slack channel #deployments.
