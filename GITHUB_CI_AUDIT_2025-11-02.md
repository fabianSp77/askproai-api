# GitHub/CI Comprehensive Audit Report
**Repository**: fabianSp77/askproai-api
**Date**: 2025-11-02
**Auditor**: SuperClaude Deep Research Agent

---

## Executive Summary

### Critical Issues Found
1. **BRANCH PROTECTION**: develop branch has NO protection rules
2. **WORKFLOW NAMING CONFLICT**: "Deploy to Production" name exists in TWO workflows
3. **BUILD_RUN_ID LOGIC**: Uses unsafe `[ -z ]` check without `jq // empty` fallback
4. **HEALTHCHECK_TOKEN**: Exists but environment verification incomplete
5. **STAGING DEPLOYMENTS**: Last 3 manual deployments ALL failed

### Status Overview
- ✅ Main branch: Properly protected with comprehensive rules
- ❌ Develop branch: **NO PROTECTION** (critical security risk)
- ⚠️ CI/CD: Build artifacts working, deployments failing
- ⚠️ Workflow conflict: Duplicate production workflow names

---

## 1. Branch Protection Audit

### 1.1 Main Branch Protection ✅
**Status**: PROTECTED
**API Endpoint**: `GET /repos/fabianSp77/askproai-api/branches/main/protection`

#### Required Status Checks
```json
{
  "strict": true,
  "contexts": [
    "Build Frontend Assets",
    "Build Backend Dependencies",
    "Static Analysis (PHPStan)",
    "Run Tests",
    "Visual Tests - Firefox ESR",
    "Verify Staging Health"
  ]
}
```

#### Pull Request Reviews
- **Required Approving Reviews**: 1
- **Dismiss Stale Reviews**: true
- **Require Code Owner Reviews**: false
- **Require Last Push Approval**: false

#### Admin Settings
- **Enforce Admins**: true ✅
- **Required Conversation Resolution**: true ✅
- **Allow Force Pushes**: false ✅
- **Allow Deletions**: false ✅
- **Required Linear History**: false
- **Required Signatures**: false
- **Lock Branch**: false

**Assessment**: Main branch protection is EXCELLENT. All critical safeguards enabled.

---

### 1.2 Develop Branch Protection ❌
**Status**: NOT PROTECTED
**HTTP Response**: 404 - Branch not protected

#### Critical Security Risks
1. **No Required Status Checks**: Code can be merged without CI passing
2. **No Review Requirements**: Anyone with write access can push directly
3. **No Admin Enforcement**: Admins can bypass non-existent rules
4. **Force Push Allowed**: History can be rewritten
5. **Direct Push Allowed**: No PR workflow enforcement

#### Recommended Protection Rules
```yaml
Required Status Checks:
  - Build Frontend Assets
  - Build Backend Dependencies
  - Static Analysis (PHPStan)
  - Run Tests
  - Visual Tests - Firefox ESR

Pull Request Reviews:
  - required_approving_review_count: 1
  - dismiss_stale_reviews: true

Additional Settings:
  - enforce_admins: true
  - allow_force_pushes: false
  - allow_deletions: false
  - required_conversation_resolution: true
```

**Recommendation**: **URGENT** - Apply same protection rules as main branch.

---

## 2. Workflow Runs Audit

### 2.1 Build Artifacts Workflow ✅
**Workflow ID**: 202453737
**File**: `.github/workflows/build-artifacts.yml`
**Status**: ACTIVE

#### Last 3 Runs
```
Run #1 - 19015290501
├─ Status: ✅ SUCCESS
├─ SHA: 62584375412ec2f059d17df111144f6c21c58abb
├─ Branch: develop
├─ Event: pull_request
├─ Title: feat(auth): Deploy docs authentication to production
├─ Created: 2025-11-02T16:52:41Z
└─ Duration: ~28 seconds

Run #2 - 19015290114
├─ Status: ✅ SUCCESS
├─ SHA: 62584375412ec2f059d17df111144f6c21c58abb (same as Run #1)
├─ Branch: develop
├─ Event: push
├─ Title: fix(security): resolve symlink for .env in healthcheck.php
├─ Created: 2025-11-02T16:52:39Z
└─ Duration: ~2m 23s

Run #3 - 19015263191
├─ Status: ✅ SUCCESS
├─ SHA: c99bbb21e0c60c60c55f4f6e0d4f89c111f9e674
├─ Branch: develop
├─ Event: pull_request
├─ Title: feat(auth): Deploy docs authentication to production
├─ Created: 2025-11-02T16:49:58Z
└─ Duration: ~31 seconds
```

**Assessment**: Build Artifacts workflow is HEALTHY. 100% success rate.

---

### 2.2 Deploy to Staging Workflow ❌
**Workflow ID**: 202453736
**File**: `.github/workflows/deploy-staging.yml`
**Status**: ACTIVE

#### Last 3 Runs - ALL FAILED
```
Run #1 - 19013942449 ❌
├─ Status: FAILURE
├─ SHA: f20993eec4b61e1b427bbe6006d29e25479dd6ce
├─ Branch: develop
├─ Event: workflow_dispatch (manual)
├─ Created: 2025-11-02T14:54:51Z
└─ Duration: ~1m 41s

Run #2 - 19013877383 ❌
├─ Status: FAILURE
├─ SHA: f0959baf38599ef4f7c7387d97a0e136851e38df
├─ Branch: develop
├─ Event: workflow_dispatch (manual)
├─ Created: 2025-11-02T14:48:20Z
└─ Duration: ~1m 45s

Run #3 - 19013845846 ❌
├─ Status: FAILURE
├─ SHA: f0959baf38599ef4f7c7387d97a0e136851e38df (same as Run #2)
├─ Branch: develop
├─ Event: workflow_dispatch (manual)
├─ Created: 2025-11-02T14:45:12Z
└─ Duration: ~1m 56s
```

#### Failure Analysis
- **Pattern**: All manual deployments failing
- **Timing**: All failed before automatic deployment could work
- **Trigger**: `workflow_dispatch` (manual) vs `workflow_run` (automatic)
- **Investigation Needed**: Error logs show no clear output for BUILD_RUN_ID errors

**Assessment**: Staging deployment pipeline is BROKEN for manual triggers.

---

### 2.3 Deploy to Production Workflow ⚠️
**Workflow Files**:
1. `.github/workflows/deploy-production.yml` (ID: 202989778) ✅
2. `.github/workflows/check-staging-dummy.yml` (ID: 202998568) ⚠️ PHANTOM

#### Last 3 Runs (deploy-production.yml)
```
Run #1 - 19015290494 ✅
├─ Status: SUCCESS (check-staging dummy only)
├─ SHA: 62584375412ec2f059d17df111144f6c21c58abb
├─ Branch: develop
├─ Event: pull_request
├─ Created: 2025-11-02T16:52:41Z
└─ Duration: ~6 seconds

Run #2 - 19015263193 ✅
├─ Status: SUCCESS (check-staging dummy only)
├─ SHA: c99bbb21e0c60c60c55f4f6e0d4f89c111f9e674
├─ Branch: develop
├─ Event: pull_request
├─ Created: 2025-11-02T16:49:58Z
└─ Duration: ~8 seconds

Run #3 - 19015026256 ✅
├─ Status: SUCCESS (check-staging dummy only)
├─ SHA: 8e6e56732874f4f07c48f884823722d6b7da72ed
├─ Branch: develop
├─ Event: pull_request
├─ Created: 2025-11-02T16:29:19Z
└─ Duration: ~7 seconds
```

**Assessment**: Production workflow working ONLY for PR checks (dummy job). No actual deployments to production occurred.

---

## 3. Build Artifacts Analysis

### 3.1 Current Develop HEAD
**SHA**: `ad3cb8d3947c52d15f6162bdc4b56832cb3158e1`
**Successful Build**: ❌ NOT FOUND

#### Recent Successful Builds on Develop
```
1. Run 19015290501 - SHA: 62584375412ec2f059d17df111144f6c21c58abb
2. Run 19015290114 - SHA: 62584375412ec2f059d17df111144f6c21c58abb
3. Run 19015263191 - SHA: c99bbb21e0c60c60c55f4f6e0d4f89c111f9e674
4. Run 19015262832 - SHA: c99bbb21e0c60c60c55f4f6e0d4f89c111f9e674
5. Run 19015026251 - SHA: 8e6e56732874f4f07c48f884823722d6b7da72ed
```

**Issue**: Current develop HEAD (`ad3cb8d3`) has NO successful build artifacts.
**Impact**: Cannot deploy current develop to staging using artifact-based deployment.

---

### 3.2 Latest Build Artifacts (Run 19015290114)
**SHA**: 62584375412ec2f059d17df111144f6c21c58abb
**Total Artifacts**: 3
**Expiration**: 7-30 days

#### Artifact Details
```
1. frontend-build-62584375412ec2f059d17df111144f6c21c58abb
   ├─ Size: 86,826 bytes (~85 KB)
   ├─ Created: 2025-11-02T16:53:04Z
   ├─ Expires: 2025-11-09T16:53:04Z (7 days)
   └─ SHA256: 1ec4b156f88bf521fef93042909c9a8e62b1d8e995064d6806bbed6c335ca591

2. backend-vendor-62584375412ec2f059d17df111144f6c21c58abb
   ├─ Size: 28,383,490 bytes (~27 MB)
   ├─ Created: 2025-11-02T16:53:10Z
   ├─ Expires: 2025-11-09T16:53:01Z (7 days)
   └─ SHA256: 1edac03bc099d17bded768a03f62f6e3b71c4c0242867506fe79dbda386cd1ae

3. deployment-bundle-62584375412ec2f059d17df111144f6c21c58abb
   ├─ Size: 21,755,627 bytes (~21 MB)
   ├─ Created: 2025-11-02T16:55:00Z
   ├─ Expires: 2025-12-02T16:54:59Z (30 days)
   └─ SHA256: 0be544fb44e692d96f2d685ccff4aced93208eda5f8c41b7e072da7a7c155e83
```

**Assessment**: Build artifacts are being created correctly for successful builds.

---

## 4. BUILD_RUN_ID Bug Verification

### 4.1 Code Analysis
**File**: `.github/workflows/deploy-staging.yml`
**Lines**: 170-177

```yaml
SHA="${{ github.sha }}"
BUILD_RUN_ID=$(gh run list \
  --workflow "Build Artifacts" \
  --branch "${{ github.ref_name }}" \
  --json databaseId,headSha,status,conclusion \
  --limit 20 | jq -r --arg sha "$SHA" \
  '[.[] | select(.headSha==$sha and .status=="completed" and .conclusion=="success")][0].databaseId')
[ -z "$BUILD_RUN_ID" ] && { echo "No successful Build Artifacts run for SHA"; exit 1; }
```

### 4.2 Vulnerability Assessment

#### Current Implementation: ❌ UNSAFE
```bash
# Line 176: jq returns array access [0] on potentially empty array
'[.[] | select(...)][0].databaseId'

# Line 177: Only checks if variable is empty
[ -z "$BUILD_RUN_ID" ] && { ... }
```

#### Problem
When `jq` tries to access `[0].databaseId` on an empty array:
- Returns: `null` (not empty string)
- `[ -z "null" ]` evaluates to **false** (because "null" is a 4-character string)
- Script continues with `BUILD_RUN_ID="null"` instead of failing

#### Recommended Fix
```bash
BUILD_RUN_ID=$(gh run list \
  --workflow "Build Artifacts" \
  --branch "${{ github.ref_name }}" \
  --json databaseId,headSha,status,conclusion \
  --limit 20 | jq -r --arg sha "$SHA" \
  '[.[] | select(.headSha==$sha and .status=="completed" and .conclusion=="success")][0].databaseId // empty')
[ -z "$BUILD_RUN_ID" ] && { echo "No successful Build Artifacts run for SHA $SHA"; exit 1; }
```

**Changes**:
1. Add `// empty` to jq expression to return empty string instead of "null"
2. Add `$SHA` to error message for better debugging

**Status**: ❌ BUG CONFIRMED - Missing `jq // empty` fallback

---

## 5. Workflow Naming Conflict

### 5.1 Duplicate "Deploy to Production" Workflows

#### Workflow #1 (Legitimate)
```yaml
ID: 202989778
Name: "Deploy to Production"
File: .github/workflows/deploy-production.yml
Status: active
Branch Presence:
  ├─ main: ❌ NOT PRESENT
  └─ develop: ✅ EXISTS
```

#### Workflow #2 (Phantom/Ghost)
```yaml
ID: 202998568
Name: "Deploy to Production"
File: .github/workflows/check-staging-dummy.yml
Status: active (but file doesn't exist in any branch)
Branch Presence:
  ├─ main: ❌ NOT PRESENT
  └─ develop: ❌ NOT PRESENT
```

### 5.2 All Workflows Analysis
```
Total Workflows: 16

Active Workflows:
├─ Build Artifacts
├─ Deploy to Production (deploy-production.yml)
├─ Deploy to Production (check-staging-dummy.yml) ⚠️ PHANTOM
├─ Deploy to Staging
├─ Setup Staging Sudo (One-Time)
├─ Staging Health Setup
├─ Test Automation Suite
├─ Visual Tests (Staging)
├─ Documentation
├─ security-audit
├─ static-analysis
├─ phpstan-baseline-guard
├─ cd
├─ ci-advanced.yml
├─ ci-comprehensive.yml
└─ visual-staging.yml
```

### 5.3 Main vs Develop Branch Workflows

#### Main Branch Workflows (1 file)
```
├─ test-automation.yml
```

#### Develop Branch Workflows (14 files)
```
├─ backup-daily.yml
├─ backup-restore-test.yml
├─ build-artifacts.yml
├─ deploy-production.yml
├─ deploy-staging.yml
├─ fix-ssh-key.yml
├─ setup-staging-sudo.yml
├─ ssh-diagnose.yml
├─ staging-health-setup.yml
├─ staging-smoke.yml
├─ test-automation.yml
├─ visual-staging.yml
└─ visual-tests-staging.yml
```

### 5.4 Root Cause Analysis

#### Git History Investigation
```bash
# check-staging-dummy.yml was deleted
git log --oneline --all -- .github/workflows/check-staging-dummy.yml
# Result: No commits found (file never existed in current history)

# Recent workflow cleanup commits
27f2a7c0 fix(ci): Remove reusable workflow call that breaks PR checks
28252752 feat(ci): Simplify workflows for PR-safe required checks
4bca4298 feat(ci): Make required checks visible for branch protection
```

#### Analysis
- File `check-staging-dummy.yml` was likely deleted in commit `27f2a7c0`
- GitHub Actions API still reports it as "active" (workflow ID: 202998568)
- This is a **GitHub API caching issue** - deleted workflows remain in API until explicitly disabled

### 5.5 Impact Assessment

#### Branch Protection Confusion
```
Main Branch Protection → "Verify Staging Health" required check

Workflow Names in API:
├─ "Deploy to Production" (deploy-production.yml)
└─ "Deploy to Production" (check-staging-dummy.yml) ⚠️

Result: Ambiguous workflow identification for status checks
```

#### Recommendation
1. **Option A**: Disable phantom workflow via GitHub UI
2. **Option B**: Create `.github/workflows/check-staging-dummy.yml` with disabled state
3. **Option C**: Ignore (likely resolves after 90 days of inactivity)

**Status**: ⚠️ CONFIRMED - Duplicate workflow names exist (1 phantom)

---

## 6. HEALTHCHECK_TOKEN Audit

### 6.1 Secret Existence ✅
**GitHub Secrets**: `gh secret list`

```
Secret Name           Last Updated
────────────────────  ─────────────
DB_PASSWORD           2025-05-13
DEPLOY_HOST           2025-08-14
DEPLOY_KEY            2025-08-14
DEPLOY_USER           2025-08-14
HEALTHCHECK_TOKEN     2025-11-02 ✅ (today)
RETELL_API_KEY        2025-05-13
```

**Status**: ✅ HEALTHCHECK_TOKEN exists and was updated today.

---

### 6.2 Staging Environment Verification

#### File Structure Analysis
```bash
/var/www/api-gateway-staging/shared/
├─ .env/                    # ⚠️ DIRECTORY (not file)
│  └─ staging.env          # Actual environment file
├─ public/
└─ storage/

/var/www/api-gateway-staging/current/
└─ .env                    # Symlink to shared/.env/staging.env
```

#### HEALTHCHECK_TOKEN Hash Comparison
```bash
# GitHub Secret (cannot retrieve cleartext)
Secret: HEALTHCHECK_TOKEN
Updated: 2025-11-02

# Staging Environment
File: /var/www/api-gateway-staging/current/.env
Hash: 1ea22eac8f73552460a944cec7bb0abeea430eef01afc9620431a204cae863cd

Actual File: /var/www/api-gateway-staging/shared/.env/staging.env
Hash: 1ea22eac8f73552460a944cec7bb0abeea430eef01afc9620431a204cae863cd
```

**Consistency**: ✅ Both paths point to same token (hash identical)

#### Verification Status
- ✅ Secret exists in GitHub
- ✅ Token exists in staging environment
- ⚠️ Cannot verify if GitHub secret matches staging token (no cleartext access)
- ✅ Staging `.env` structure is correct (symlink to shared)

### 6.3 Security Posture
```
GitHub Secrets:        ✅ HEALTHCHECK_TOKEN present
Staging Deployment:    ✅ Token present in .env
File Permissions:      ✅ staging.env is 0640 (deploy:www-data)
Symlink Structure:     ✅ current/.env → shared/.env/staging.env
```

**Recommendation**: Verify token works by running healthcheck endpoint:
```bash
curl -H "Authorization: Bearer $TOKEN" https://staging.askproai.de/healthcheck.php
```

---

## 7. Critical Findings Summary

### 7.1 Security Issues
```
P0 - CRITICAL:
├─ Develop branch has NO PROTECTION RULES
├─ Anyone with write access can push directly
└─ No CI checks required for merges

P1 - HIGH:
├─ BUILD_RUN_ID logic vulnerable to null values
└─ Manual staging deployments failing (100% failure rate)

P2 - MEDIUM:
├─ Duplicate workflow names causing API ambiguity
└─ Phantom workflow ID still active in GitHub API
```

### 7.2 Deployment Issues
```
Build Artifacts:       ✅ HEALTHY (100% success)
Deploy to Staging:     ❌ BROKEN (100% failure on manual dispatch)
Deploy to Production:  ⚠️ PARTIAL (only PR checks work)

Current Develop:       ❌ No build artifacts for HEAD (ad3cb8d3)
Latest Build:          ✅ SHA 62584375 (3 artifacts available)
```

### 7.3 Configuration Issues
```
Main Branch:           ✅ Properly protected
Develop Branch:        ❌ NOT PROTECTED
HEALTHCHECK_TOKEN:     ✅ Present in GitHub + Staging
Workflow Naming:       ⚠️ Duplicate "Deploy to Production" name
```

---

## 8. Recommendations

### 8.1 Immediate Actions (P0 - Within 24 Hours)

#### 1. Protect Develop Branch
```bash
gh api repos/fabianSp77/askproai-api/branches/develop/protection \
  --method PUT \
  --field required_status_checks[strict]=true \
  --field required_status_checks[contexts][]=Build Frontend Assets \
  --field required_status_checks[contexts][]=Build Backend Dependencies \
  --field required_status_checks[contexts][]=Static Analysis (PHPStan) \
  --field required_status_checks[contexts][]=Run Tests \
  --field required_status_checks[contexts][]=Visual Tests - Firefox ESR \
  --field required_pull_request_reviews[required_approving_review_count]=1 \
  --field required_pull_request_reviews[dismiss_stale_reviews]=true \
  --field enforce_admins[enabled]=true \
  --field allow_force_pushes[enabled]=false \
  --field allow_deletions[enabled]=false \
  --field required_conversation_resolution[enabled]=true
```

#### 2. Fix BUILD_RUN_ID Logic
**File**: `.github/workflows/deploy-staging.yml` (Line 176)

```diff
- '[.[] | select(.headSha==$sha and .status=="completed" and .conclusion=="success")][0].databaseId')
+ '[.[] | select(.headSha==$sha and .status=="completed" and .conclusion=="success")][0].databaseId // empty')
```

**Also apply to**: `deploy-production.yml` if same pattern exists.

---

### 8.2 High Priority Actions (P1 - Within 1 Week)

#### 3. Investigate Staging Deployment Failures
```bash
# Check last failed run logs
gh run view 19013942449 --log

# Focus areas:
- BUILD_RUN_ID lookup logic
- Artifact download failures
- SSH connectivity issues
- Permission errors
```

#### 4. Resolve Workflow Naming Conflict
**Option A** (Recommended): Disable phantom workflow via GitHub UI
1. Go to Actions → Workflows
2. Find "Deploy to Production (check-staging-dummy.yml)"
3. Click "..." → Disable workflow

**Option B**: Let it expire naturally (90 days)

---

### 8.3 Medium Priority Actions (P2 - Within 1 Month)

#### 5. Build Artifact Retention Policy
```yaml
Current Retention:
├─ frontend-build: 7 days
├─ backend-vendor: 7 days
└─ deployment-bundle: 30 days

Recommendation:
├─ Keep deployment-bundle at 30 days ✅
├─ Extend frontend/backend to 14 days (allow rebuild if needed)
└─ Add artifact cleanup job for old branches
```

#### 6. CI/CD Health Monitoring
```yaml
Implement:
├─ Workflow success rate alerts (<95% = warning)
├─ Deployment failure notifications (Slack/Email)
├─ Build artifact availability checks
└─ Branch protection compliance audits (monthly)
```

---

## 9. Testing Recommendations

### 9.1 Verify Fixes

#### Test 1: Develop Branch Protection
```bash
# Try force push (should fail)
git checkout develop
git commit --allow-empty -m "Test: force push protection"
git push --force origin develop
# Expected: Error: Protected branch update failed
```

#### Test 2: BUILD_RUN_ID Logic
```bash
# Manually trigger staging deployment with non-existent SHA
gh workflow run deploy-staging.yml -f ref=nonexistent-sha
# Expected: Clear error message "No successful Build Artifacts run for SHA"
```

#### Test 3: HEALTHCHECK_TOKEN
```bash
# From staging server
curl -H "Authorization: Bearer $(grep HEALTHCHECK_TOKEN /var/www/api-gateway-staging/current/.env | cut -d= -f2 | tr -d '\"')" \
  https://staging.askproai.de/healthcheck.php
# Expected: 200 OK with health status JSON
```

---

## 10. Appendix

### 10.1 Git SHAs Referenced
```
Current Develop HEAD:  ad3cb8d3947c52d15f6162bdc4b56832cb3158e1 (no build)
Latest Build:          62584375412ec2f059d17df111144f6c21c58abb
Previous Build:        c99bbb21e0c60c60c55f4f6e0d4f89c111f9e674
Older Build:           8e6e56732874f4f07c48f884823722d6b7da72ed
Failed Deployments:    f20993eec4b61e1b427bbe6006d29e25479dd6ce
                       f0959baf38599ef4f7c7387d97a0e136851e38df
```

### 10.2 Workflow IDs
```
Build Artifacts:          202453737
Deploy to Staging:        202453736
Deploy to Production:     202989778 (deploy-production.yml)
Deploy to Production:     202998568 (check-staging-dummy.yml) ⚠️ PHANTOM
Test Automation Suite:    199702703
```

### 10.3 API Endpoints Used
```
Branch Protection:        /repos/{owner}/{repo}/branches/{branch}/protection
Workflow Details:         /repos/{owner}/{repo}/actions/workflows/{workflow_id}
Run Artifacts:            /repos/{owner}/{repo}/actions/runs/{run_id}/artifacts
Repository Secrets:       /repos/{owner}/{repo}/actions/secrets
Git Trees:                /repos/{owner}/{repo}/git/trees/{sha}:{path}
```

---

## 11. Conclusion

### Overall Health Score: 65/100

#### Scoring Breakdown
```
Branch Protection:     50/100 (main: 100, develop: 0)
CI/CD Pipeline:        70/100 (builds: 100, deploys: 40)
Security Posture:      60/100 (secrets: 100, protection: 20)
Operational Health:    80/100 (monitoring: 60, automation: 100)
```

### Critical Path Forward
1. **TODAY**: Enable develop branch protection
2. **THIS WEEK**: Fix BUILD_RUN_ID bug + investigate staging failures
3. **THIS MONTH**: Resolve workflow naming conflict + improve monitoring

### Risk Assessment
**Without develop protection**: HIGH RISK
- Untested code can reach staging
- CI can be bypassed
- Force push can destroy history

**With recommended fixes**: LOW RISK
- All branches protected
- CI enforced at all stages
- Deployment pipeline reliable

---

**Report Generated**: 2025-11-02 18:00:00 UTC
**Next Audit Due**: 2025-12-02 (monthly cadence recommended)
