# E2E DEPLOYMENT VALIDATION & ROLLOUT-PLAN — STAGING ➜ PRODUCTION

**Version:** 2.0 (Reset & Complete Audit)
**Erstellt:** 2025-11-02 19:00 UTC
**Modus:** 🔴 **PLAN-FIRST** (NICHTS AUSGEFÜHRT)
**Zweck:** Vollständiger Deployment-Prozess mit IST↔SOLL Abgleich, Blocker-Identifikation, Fix-Plan

---

## ⚠️ STOP-BEDINGUNG

**Dieser Report ist NUR ein Plan.** NICHTS wird ausgeführt, bis eine Freigabe-Phrase gegeben wird:

- **"APPEND MISSING INFO"** → Report ergänzen
- **"STAGING-TEST FREIGEGEBEN"** → Staging-Validierung ausführen
- **"PROD-DEPLOY FREIGEGEBEN"** → Production-Deployment ausführen
- **"PROD-FIX FREIGEGEBEN"** → Spezifische Prod-Aktion ausführen
- **"ABBRECHEN"** → Stoppen, Status ausgeben

---

## 1️⃣ SOLL-ZUSAMMENFASSUNG (DEPLOYMENT-PROZESS)

Basierend auf:
- [DEPLOYMENT_HANDBUCH_FUER_DRITTE.html](https://api.askproai.de/docs/backup-system/DEPLOYMENT_HANDBUCH_FUER_DRITTE.html)
- [status-quo-deployment-prozess-2025-11-01.html](https://api.askproai.de/docs/backup-system/status-quo-deployment-prozess-2025-11-01.html)
- [E2E_DEPLOYMENT_VALIDATION_FINAL_2025-11-02_1300.html](https://api.askproai.de/docs/backup-system/E2E_DEPLOYMENT_VALIDATION_FINAL_2025-11-02_1300.html)
- `.github/workflows/build-artifacts.yml`, `deploy-staging.yml`, `deploy-production.yml`

### **BUILD-PHASE** (3-5 min)

1. **Trigger:** Merge to `develop` → Auto-trigger Build Artifacts Workflow
   - **Quelle:** DEPLOYMENT_HANDBUCH Zeilen 94-117

2. **Frontend Build (Vite)** + **Backend Build (Composer --no-dev --optimize-autoloader)**
   - **Quelle:** build-artifacts.yml Jobs 1-2
   - **Dauer:** ~3-5 Minuten
   - **Abbruch bei:** Compilation error

3. **Static Analysis (PHPStan Level 5)** + **Tests (Pest + MariaDB)**
   - **Quelle:** build-artifacts.yml Jobs 3-4
   - **Abbruch bei:** Analysis/Test failure

4. **PRE-BUNDLE GATES (Layer 1) — 9 Checks:**
   - artisan, composer.json, **public/index.php**, **vendor/autoload.php**, public/build/manifest.json, bootstrap/, config/, routes/, app/
   - **Quelle:** build-artifacts.yml:321-350, PROD_FIX_BUNDLE_GATES.md
   - **Abbruch bei:** Fehlende kritische Datei

5. **Bundle Creation:** `deployment-bundle-{SHA}.tar.gz` + SHA256 Checksum
   - **Retention:** 30 Tage
   - **Quelle:** DEPLOYMENT_HANDBUCH Zeilen 113-117
   - **Abbruch bei:** Upload error

---

### **STAGING-DEPLOY** (2-3 min)

6. **Trigger:** Manual `gh workflow run "Deploy to Staging" --ref develop`
   - **Quelle:** DEPLOYMENT_HANDBUCH Zeilen 120-131

7. **Pre-Deploy Health Check:** Staging erreichbar?
   - **Quelle:** deploy-staging.yml Job "check-health"
   - **Abbruch bei:** Staging down

8. **Pre-Deploy Backup:** App (tar.gz) + DB (mysqldump) + SHA256
   - **Quelle:** deploy-staging.yml Job "backup-staging"
   - **Dauer:** ~30 Sekunden

9. **Build Artifact Wait:** Polling for successful build (max 3 min, 18 attempts @ 10s)
   - **Quelle:** deploy-staging.yml:185-213, E2E_WORKFLOW_HARDENING Zeilen 195-200
   - **Abbruch bei:** No build after 3 min

10. **Bundle Download + SHA256 Verify**
    - **Quelle:** deploy-staging.yml:215-234
    - **Abbruch bei:** Checksum mismatch, Download failure

11. **Upload + Extract Bundle** to `/releases/{TIMESTAMP}-{SHA}`
    - **Quelle:** deploy-staging.yml:249-275
    - **Abbruch bei:** Disk full, Extract error

12. **PRE-SWITCH GATES (Layer 2) — 9 Checks + PHP Tests:**
    - Checks VOR Migrations: 9 Struktur-Checks + `php -r "require 'vendor/autoload.php'"` + `php artisan --version`
    - **Quelle:** deploy-staging.yml:277-329, PROD_FIX Zeilen 100-118
    - **Abbruch bei:** Gate failure → Symlink NICHT gewechselt

13. **Run Migrations:** `php artisan migrate --force`
    - **Quelle:** deploy-staging.yml:331-339
    - **Abbruch bei:** Migration error → Auto-Rollback

14. **Clear Caches (Pre-Symlink):** config, route, view, cache, optimize
    - **Quelle:** deploy-staging.yml:341-354
    - **Abbruch bei:** Non-critical (continue)

15. **Switch Symlink (Atomic):** `ln -snf {RELEASE} current`
    - **Quelle:** deploy-staging.yml:359-369
    - **Dauer:** < 1 Sekunde
    - **Abbruch bei:** Symlink error → Auto-Rollback

16. **POST-SYMLINK Cache Clear:** Clear ALL caches NACH Symlink
    - **Quelle:** deploy-staging.yml:371-387, E2E_WORKFLOW Zeilen 195-200
    - **Dauer:** ~6-8 Sekunden

17. **Reload PHP-FPM (OPcache):** `sudo -n service php8.3-fpm reload`
    - **Quelle:** deploy-staging.yml:389-431, E2E_WORKFLOW Zeilen 200
    - **Dauer:** ~4-5 Sekunden

18. **Grace Period:** 15 Sekunden warten für Cache-Propagation
    - **Quelle:** deploy-staging.yml:433-437

19. **Health Checks (Retry Logic):** 6 Attempts, 5s Intervall
    - `/health` (Bearer), `/api/health-check` (Bearer), `/healthcheck.php`
    - **Quelle:** deploy-staging.yml:439-500, E2E_VALIDATION Zeilen 88-135
    - **Abbruch bei:** All attempts fail → Auto-Rollback

20. **Vite Asset Validation:** `/build/manifest.json` + Asset verfügbar?
    - **Quelle:** deploy-staging.yml:502-525
    - **Abbruch bei:** Missing assets

---

### **PRODUCTION-DEPLOY** (nach Freigabe, 2-3 min)

21. **Trigger:** Merge `develop` → `main` ODER Manual
    - **Quelle:** DEPLOYMENT_HANDBUCH Zeilen 197-218

22. **Staging Health Check:** Staging seit ≥1h stabil?
    - **Quelle:** deploy-production.yml:34-44
    - **Abbruch bei:** Staging unhealthy

23. **Pre-Deploy Backup (Production):** App + DB + **NGINX Config** + SHA256
    - **Quelle:** deploy-production.yml:54-80
    - **Dauer:** ~60 Sekunden

24. **PRE-SWITCH GATES (Layer 3):** 9 Checks + PHP Tests VOR Symlink
    - **Quelle:** deploy-production.yml:142-174, PROD_FIX Zeilen 125-143
    - **Abbruch bei:** Gate failure → Production unverändert

25. **Switch Symlink (Atomic):** `ln -sfn {RELEASE} current`
    - **Quelle:** deploy-production.yml:185-187
    - **Dauer:** < 1 Sekunde (Zero-Downtime)

26. **Reload PHP-FPM:** `sudo systemctl reload php8.3-fpm`
    - **Quelle:** deploy-production.yml:189-192

27. **Health Checks (Production):** `/health` (HTTP 200), `/build/manifest.json`
    - **Quelle:** deploy-production.yml:208-230
    - **Abbruch bei:** Health fail → Auto-Rollback

28. **Auto-Rollback (bei Failure):** Symlink zu previous, Verify rollback health
    - **Quelle:** deploy-production.yml:235-272, E2E_VALIDATION Zeilen 221-235

---

## 2️⃣ GITHUB-/CI AUDIT (IST-ZUSTAND)

**Durchgeführt:** 2025-11-02 19:00 UTC
**Methode:** Read-only API Queries + Workflow-Datei-Analyse

### **Branch Protection**

#### Main Branch
```json
{
  "required_status_checks": null,
  "enforce_admins": null,
  "required_pull_request_reviews": null
}
```
❌ **KRITISCH:** Main branch hat **KEINE** Branch Protection!

**SOLL:** 6 Required Checks (build-frontend, build-backend, static-analysis, run-tests, create-deployment-bundle, check-staging)

#### Develop Branch
```
Branch Protection: NOT CONFIGURED
```
❌ **KRITISCH:** Develop branch NICHT geschützt!

**SOLL:** ≥4 Build-Checks, "Require up-to-date", ≥1 Review

---

### **Recent Workflow Runs**

#### Build Artifacts (Letzte 3)
| Run ID | Status | Head SHA | Created | Duration |
|--------|--------|----------|---------|----------|
| 19015290501 | ✅ success | 62584375 | 2025-11-02 16:52:41 | ~4 min |
| 19015290114 | ✅ success | 62584375 | 2025-11-02 16:52:39 | ~4 min |
| 19015263191 | ✅ success | c99bbb21 | 2025-11-02 16:49:58 | ~4 min |

**Current Develop HEAD:** `ad3cb8d3` - ❌ **NO BUILD EXISTS**

#### Deploy to Staging (Letzte 3)
| Run ID | Status | Head SHA | Created | Actor |
|--------|--------|----------|---------|-------|
| 19013942449 | ❌ failure | f20993ee | 2025-11-02 14:54:51 | workflow_dispatch |
| 19013877383 | ❌ failure | f0959baf | 2025-11-02 14:48:20 | workflow_dispatch |
| 19013845846 | ❌ failure | f0959baf | 2025-11-02 14:45:12 | workflow_dispatch |

⚠️ **ISSUE:** Letzten 3 Staging Deployments sind fehlgeschlagen (100% Failure Rate)!

#### Deploy to Production
```
Workflow Naming Conflict Detected:
- Legitimate: deploy-production.yml (ID: 202989778)
- Phantom: check-staging-dummy.yml (ID: 202998568) - deleted file, still active in API
```
⚠️ **P2 ISSUE:** Zwei Workflows mit Namen "Deploy to Production"

---

### **Build→Deploy Artefakte-Kopplung**

#### BUILD_RUN_ID Bug (P0)

**Code:** `deploy-staging.yml:171-177`
```yaml
BUILD_RUN_ID=$(gh run list \
  --workflow "Build Artifacts" \
  --branch "${{ github.ref_name }}" \
  --json databaseId,headSha,status,conclusion \
  --limit 20 | jq -r --arg sha "$SHA" \
  '[.[] | select(.headSha==$sha and .status=="completed" and .conclusion=="success")][0].databaseId')
[ -z "$BUILD_RUN_ID" ] && { echo "No successful Build Artifacts run for SHA"; exit 1; }
```

❌ **CONFIRMED BUG:**
- **Issue:** `jq` returns string `"null"` when no match found (nicht empty string)
- **Impact:** `[ -z "$BUILD_RUN_ID" ]` ist FALSE für `"null"` string → Download schlägt fehl
- **Source:** E2E_WORKFLOW_HARDENING Zeilen 86-97

**FIX REQUIRED:**
```yaml
# Line 176: Add fallback
'...[0].databaseId // empty'
# Line 177: Add null check
[ -z "$BUILD_RUN_ID" ] || [ "$BUILD_RUN_ID" = "null" ] && { ... exit 1; }
```

#### Artifacts (Letzte erfolgreiche Build für SHA 62584375)
| Artifact Name | Size | Expires In | Status |
|---------------|------|------------|--------|
| deployment-bundle-62584375 | 21 MB | 30 days | ✅ Available |
| backend-vendor-62584375 | 27 MB | 7 days | ✅ Available |
| frontend-build-62584375 | 85 KB | 7 days | ✅ Available |

---

### **Secrets & Configuration**

#### HEALTHCHECK_TOKEN
```
GitHub Secret:        EXISTS (updated today)
Staging .env Hash:    1ea22eac8f73552460a944cec7bb0abeea430eef01afc9620431a204cae863cd
Comparison:           ⏳ PENDING (manual CI vs. staging hash verification needed)
```

✅ **Secret vorhanden**
⚠️ **Hash-Vergleich:** Manual verification required (CI Secret SHA256 vs. Staging .env SHA256)

---

### **Workflow Hardening Features (Verified)**

✅ **Post-Symlink Cache Clear:** 1 Vorkommen (deploy-staging.yml:371-387)
✅ **PHP-FPM OPcache Reload:** 1 Vorkommen (deploy-staging.yml:389-431)
✅ **Grace Period (15s):** 1 Vorkommen (deploy-staging.yml:433-437)
✅ **Health Check Retry (6 attempts):** 1 Vorkommen (deploy-staging.yml:439-499)

**Source:** E2E_WORKFLOW_HARDENING Zeilen 180-216

---

## 3️⃣ SERVER-AUDIT (IST-ZUSTAND)

**Server:** 152.53.116.127 (staging.askproai.de)
**Durchgeführt:** 2025-11-02 19:00 UTC
**Methode:** Read-only SSH Queries

### **Current State**

**Current Symlink:**
```
/var/www/api-gateway-staging/current → releases/20251102_154900-f0959baf
```

**Last 3 Releases:**
```
1. 20251102_155523-f20993ee (15:55) ← NEWEST but NOT active
2. 20251102_154900-f0959baf (15:49) ← CURRENT (active)
3. 20251102_154552-f0959baf (15:45)
```

⚠️ **OBSERVATION:** Rollback detected! Newest release (f20993ee) deployed but symlink rolled back to f0959baf

---

### **Release Structure Verification**

**Current Release:** `/var/www/api-gateway-staging/releases/20251102_154900-f0959baf`

| File/Directory | Status |
|----------------|--------|
| artisan | ✅ EXISTS |
| composer.json | ✅ EXISTS |
| public/index.php | ✅ EXISTS |
| vendor/autoload.php | ✅ EXISTS |
| public/build/manifest.json | ✅ EXISTS |
| bootstrap/ | ✅ EXISTS |
| config/ | ✅ EXISTS |
| routes/ | ✅ EXISTS |
| app/ | ✅ EXISTS |

✅ **All 9 critical files/directories present**

---

### **Environment Configuration**

```
.env Symlink:   /var/www/api-gateway-staging/current/.env
Points To:      /var/www/api-gateway-staging/shared/.env/staging.env
Status:         ✅ EXISTS
APP_ENV:        staging
```

---

### **Sudoers Configuration**

```bash
(root) NOPASSWD: /usr/bin/systemctl reload nginx
(root) NOPASSWD: /usr/sbin/service nginx reload
(root) NOPASSWD: /usr/sbin/service php*-fpm reload
```

✅ **Passwordless sudo für Service-Reloads korrekt konfiguriert**

---

### **Health Endpoints**

| Endpoint | HTTP Status | Analysis |
|----------|-------------|----------|
| /health | 401 | Unauthorized (requires Bearer) |
| /api/health-check | 401 | Unauthorized (requires Bearer) |
| /healthcheck.php | 403 | Forbidden (Bearer validation failing) |

⚠️ **ISSUE:** Health endpoints return 401/403 (HEALTHCHECK_TOKEN issue or Bearer not sent correctly)

---

### **Permission Issues**

```
Directory:    /var/www/api-gateway-staging/current/public/docs
Owner:        root:root
Permissions:  drwxrwxr-x (775)
```

⚠️ **ISSUE:** `public/docs/` owned by root instead of deploy:www-data

**Impact:** May cause deployment failures when trying to modify this directory

---

## 4️⃣ IST↔SOLL DELTA & RISIKEN

| # | Thema | SOLL | IST | Impact | Status | Empfohlener Fix | Quelle |
|---|-------|------|-----|--------|--------|-----------------|--------|
| 1 | **Branch Protection (main)** | 6 Required Checks | null (keine Protection) | **P0** | ❌ CRITICAL | Branch Protection aktivieren: build-frontend, build-backend, static-analysis, run-tests, create-deployment-bundle, check-staging | GitHub Audit |
| 2 | **Branch Protection (develop)** | ≥4 Build-Checks, Reviews | NOT CONFIGURED | **P0** | ❌ CRITICAL | Copy main protection rules | GitHub Audit |
| 3 | **BUILD_RUN_ID null-Guard** | `jq // empty` + null-Check | nur `[ -z ]` Check | **P0** | ❌ CRITICAL | Fix: `jq -r '...[0].databaseId // empty'` + `[ "$BUILD_RUN_ID" = "null" ]` check | deploy-staging.yml:176-177 |
| 4 | **Current Develop Build** | BUILD EXISTS für HEAD | NO BUILD für ad3cb8d3 | **P0** | ❌ BLOCKER | Trigger Build Artifacts für ad3cb8d3 | GitHub Audit |
| 5 | **HEALTHCHECK_TOKEN Match** | CI Secret = Staging .env (sha256) | ⏳ PENDING Manual Verify | **P1** | ⚠️ PENDING | Hash-Vergleich durchführen | GitHub Secrets + Server Audit |
| 6 | **Staging Deployments** | Last 3 sollten success sein | Last 3 = 100% failure | **P1** | ❌ HIGH | Root Cause der Failures beheben | GitHub Workflow Runs |
| 7 | **Health Endpoints** | HTTP 200 mit Bearer | 401/403 Unauthorized | **P1** | ❌ HIGH | Token verification + Bearer header fix | Server Health Checks |
| 8 | **Production Workflow Conflict** | 1 eindeutiger Workflow | 2 Workflows ("deploy-production" + phantom) | **P2** | ⚠️ MEDIUM | Disable check-staging-dummy.yml via GitHub UI | GitHub Workflows |
| 9 | **public/docs/ Ownership** | deploy:www-data | root:root | **P2** | ⚠️ MEDIUM | `sudo chown -R deploy:www-data public/docs/` | Server Permissions |
| 10 | **Rollback Event** | Newest release active | f20993ee deployed but rolled back | **P2** | ⚠️ INVESTIGATE | Investigate rollback reason | Server Releases |
| 11 | **Post-Symlink Cache Clear** | Vorhanden | ✅ Vorhanden (1x) | N/A | ✅ OK | - | deploy-staging.yml:371-387 |
| 12 | **PHP-FPM OPcache Reload** | Vorhanden | ✅ Vorhanden (1x) | N/A | ✅ OK | - | deploy-staging.yml:389-431 |
| 13 | **Grace Period (15s)** | Vorhanden | ✅ Vorhanden (1x) | N/A | ✅ OK | - | deploy-staging.yml:433-437 |
| 14 | **Health Check Retry (6x)** | Vorhanden | ✅ Vorhanden (1x) | N/A | ✅ OK | - | deploy-staging.yml:439-499 |
| 15 | **Sudoers (Staging)** | Passwordless reload | ✅ Konfiguriert | N/A | ✅ OK | - | Server Sudoers |
| 16 | **Server File Structure** | 9 critical files | ✅ Alle vorhanden | N/A | ✅ OK | - | Server Release Structure |

### **Kritikalitäts-Bewertung:**

- **P0 (BLOCKER):** 4 Issues → Branch Protection (main + develop), BUILD_RUN_ID Bug, No Build for current HEAD
- **P1 (HIGH):** 3 Issues → Token Match Pending, Staging Failures, Health Endpoints 401/403
- **P2 (MEDIUM):** 3 Issues → Workflow Conflict, docs/ Ownership, Rollback Investigation

### **GO/NO-GO Decision:**

**❌ NO-GO für BEIDE (Staging & Production)**

**Begründung:**
1. **P0:** Branch Protection fehlt komplett → Force-Pushes möglich
2. **P0:** BUILD_RUN_ID Bug → Artifact-Download kann fehlschlagen
3. **P0:** Kein Build für aktuellen develop HEAD → Deployment unmöglich
4. **P1:** 100% Failure Rate bei letzten Staging Deployments
5. **P1:** Health Endpoints schlagen fehl → Smoke Tests werden fehlschlagen

---

## 5️⃣ FLOWCHART-VERIFIKATION

**Datei:** `storage/docs/backup-system/DEPLOYMENT_FLOWCHART.md`
**Status:** ✅ **KONSISTENT mit Workflows**

**Verified Sections:**
- Build Phase: 9 Pre-Bundle Checks ✅
- Staging Phase: Pre-Switch Gates, Post-Symlink Hardening, Health Checks ✅
- Production Phase: Pre-Flight, Pre-Switch Gates, Auto-Rollback ✅

**Keine Updates erforderlich** - Flowchart ist aktuell und korrekt.

---

## 6️⃣ FIX-/VALIDIERUNGSPLAN (NICHT AUSFÜHREN BIS FREIGABE)

### **PHASE 0: VORBEDINGUNGEN-CHECKLISTE**

Alle müssen ✅ sein, sonst **ABBRUCH**:

- [ ] **P0-Fix 1:** Branch Protection (main) aktiviert
- [ ] **P0-Fix 2:** Branch Protection (develop) aktiviert
- [ ] **P0-Fix 3:** BUILD_RUN_ID Bug gefixed
- [ ] **P0-Fix 4:** Build für aktuellen develop HEAD vorhanden
- [ ] **P1-Fix 1:** HEALTHCHECK_TOKEN Match verifiziert (SHA256)
- [ ] **P1-Fix 2:** Staging Failures Root Cause behoben
- [ ] **P2-Fix 1:** Workflow-Konflikt behoben
- [ ] **P2-Fix 2:** public/docs/ Ownership gefixed

---

### **FIX A: P0 — Branch Protection (main)**

**Typ:** GitHub Repository Settings (Repo Owner/Admin only)
**Status:** ⏳ GEPLANT

#### Required Checks (6):
```bash
# Via GitHub API (requires admin)
gh api repos/fabianSp77/askproai-api/branches/main/protection -X PUT --input - <<'EOF'
{
  "required_status_checks": {
    "strict": true,
    "contexts": [
      "build-frontend",
      "build-backend",
      "static-analysis",
      "run-tests",
      "create-deployment-bundle",
      "check-staging"
    ]
  },
  "enforce_admins": true,
  "required_pull_request_reviews": {
    "required_approving_review_count": 1,
    "dismiss_stale_reviews": true,
    "require_code_owner_reviews": false
  },
  "restrictions": null,
  "allow_force_pushes": false,
  "allow_deletions": false
}
EOF
```

**Akzeptanzkriterium:** `gh api repos/fabianSp77/askproai-api/branches/main/protection` returns non-null required_status_checks

---

### **FIX B: P0 — Branch Protection (develop)**

**Typ:** GitHub Repository Settings (Repo Owner/Admin only)
**Status:** ⏳ GEPLANT

```bash
# Via GitHub API (requires admin)
gh api repos/fabianSp77/askproai-api/branches/develop/protection -X PUT --input - <<'EOF'
{
  "required_status_checks": {
    "strict": true,
    "contexts": [
      "build-frontend",
      "build-backend",
      "static-analysis",
      "run-tests"
    ]
  },
  "enforce_admins": false,
  "required_pull_request_reviews": {
    "required_approving_review_count": 1,
    "dismiss_stale_reviews": true,
    "require_code_owner_reviews": false
  },
  "restrictions": null,
  "allow_force_pushes": false,
  "allow_deletions": false
}
EOF
```

**Akzeptanzkriterium:** `gh api repos/fabianSp77/askproai-api/branches/develop/protection` returns non-null required_status_checks

---

### **FIX C: P0 — BUILD_RUN_ID Null-Guard**

**Typ:** Workflow Code-Fix
**Datei:** `.github/workflows/deploy-staging.yml`
**Status:** ⏳ GEPLANT

#### Patch (Lines 171-178):

**CURRENT:**
```yaml
BUILD_RUN_ID=$(gh run list \
  --workflow "Build Artifacts" \
  --branch "${{ github.ref_name }}" \
  --json databaseId,headSha,status,conclusion \
  --limit 20 | jq -r --arg sha "$SHA" \
  '[.[] | select(.headSha==$sha and .status=="completed" and .conclusion=="success")][0].databaseId')
[ -z "$BUILD_RUN_ID" ] && { echo "No successful Build Artifacts run for SHA"; exit 1; }
BUILD_SHA="$SHA"
```

**FIXED:**
```yaml
BUILD_RUN_ID=$(gh run list \
  --workflow "Build Artifacts" \
  --branch "${{ github.ref_name }}" \
  --json databaseId,headSha,status,conclusion \
  --limit 20 | jq -r --arg sha "$SHA" \
  '[.[] | select(.headSha==$sha and .status=="completed" and .conclusion=="success")][0].databaseId // empty')
if [ -z "$BUILD_RUN_ID" ] || [ "$BUILD_RUN_ID" = "null" ]; then
  echo "❌ No successful Build Artifacts run found for SHA: $SHA"
  echo "Please ensure Build Artifacts workflow completed successfully first"
  exit 1
fi
BUILD_SHA="$SHA"
```

#### Commit Plan:
```bash
# Branch erstellen
git checkout develop
git pull origin develop
git checkout -b fix/build-run-id-null-handling

# Patch anwenden
# (Manual edit: .github/workflows/deploy-staging.yml lines 171-178)

# Commit
git add .github/workflows/deploy-staging.yml
git commit -m "fix(ci): Handle jq null return in BUILD_RUN_ID determination

- Add // empty fallback to jq expression
- Add explicit null string check
- Improve error message with troubleshooting hint

Fixes: P0 bug where jq returns string 'null' instead of empty,
bypassing [ -z ] check and causing artifact download failures.

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>"

# Push + PR
git push origin fix/build-run-id-null-handling
gh pr create --title "fix(ci): Handle jq null return in BUILD_RUN_ID" \
  --body "## Problem
BUILD_RUN_ID determination fails when jq returns string 'null' instead of empty.

## Solution
- Add \`// empty\` fallback to jq
- Add explicit null string check
- Improve error messaging

## Testing
- [ ] Verify with manual dispatch (no matching build)
- [ ] Verify with successful build
- [ ] Check logs for proper error messages

## Impact
Prevents artifact download failures during deployments.

🤖 Generated with [Claude Code](https://claude.com/claude-code)" \
  --base develop

# Merge
gh pr merge --squash --delete-branch
```

**Akzeptanzkriterium:**
- PR merged to develop
- Workflow re-run with no build → clean exit (not "null" bug)
- Workflow re-run with successful build → proper BUILD_RUN_ID

---

### **FIX D: P0 — Build für aktuellen develop HEAD**

**Typ:** Workflow Trigger
**Status:** ⏳ GEPLANT

```bash
# Trigger Build Artifacts für aktuellen develop HEAD
gh workflow run "Build Artifacts" --ref develop

# Wait for completion (3-5 min)
gh run watch $(gh run list --workflow "Build Artifacts" --limit 1 --json databaseId --jq '.[0].databaseId')

# Verify artifacts
LATEST_RUN=$(gh run list --workflow "Build Artifacts" --limit 1 --json databaseId --jq '.[0].databaseId')
gh run view $LATEST_RUN --log | grep "deployment-bundle"
```

**Akzeptanzkriterium:**
- Build Artifacts workflow completes successfully
- `deployment-bundle-{SHA}.tar.gz` artifact created (21 MB)
- SHA matches current develop HEAD

---

### **FIX E: P1 — HEALTHCHECK_TOKEN Verification**

**Typ:** Secret Verification + Update (if needed)
**Status:** ⏳ GEPLANT

#### Step 1: Hash-Vergleich
```bash
# Get staging .env hash (already have from audit)
STAGING_HASH="1ea22eac8f73552460a944cec7bb0abeea430eef01afc9620431a204cae863cd"

# Get CI secret (via test workflow run - log the hash only)
# Create temp test workflow or use manual verification

# Compare
# If MATCH → ✅ OK
# If NO-MATCH → Update GitHub Secret
```

#### Step 2: Update Secret (if mismatch)
```bash
# Get correct value from staging .env (SECURE - no logging)
ssh deploy@152.53.116.127 "grep '^HEALTHCHECK_TOKEN=' /var/www/api-gateway-staging/shared/.env/staging.env | cut -d= -f2 | tr -d '\"'" > /tmp/staging_token.txt

# Update GitHub Secret
gh secret set HEALTHCHECK_TOKEN < /tmp/staging_token.txt

# Cleanup
rm /tmp/staging_token.txt

# Verify
gh secret list | grep HEALTHCHECK_TOKEN
```

**Akzeptanzkriterium:**
- Hash-Vergleich zeigt MATCH
- Test-Deployment: Health endpoints return HTTP 200 mit Bearer

---

### **FIX F: P1 — Staging Failures Root Cause**

**Typ:** Investigation + Fix
**Status:** ⏳ INVESTIGATION REQUIRED

#### Investigation Steps:
```bash
# 1. Review last 3 failed runs
gh run view 19013942449 --log > /tmp/run1.log
gh run view 19013877383 --log > /tmp/run2.log
gh run view 19013845846 --log > /tmp/run3.log

# 2. Look for common patterns
grep -i "error\|fail\|abort" /tmp/run*.log

# 3. Check if BUILD_RUN_ID bug present
grep "BUILD_RUN_ID" /tmp/run*.log | grep -i "null"

# 4. Check health check failures
grep -i "health" /tmp/run*.log | grep -i "401\|403\|500"
```

**Expected Findings:**
- Likely related to BUILD_RUN_ID bug (P0-Fix C)
- Likely related to HEALTHCHECK_TOKEN issue (P1-Fix E)

**Akzeptanzkriterium:** After P0+P1 fixes, staging deployment succeeds

---

### **FIX G: P2 — Workflow Naming Conflict**

**Typ:** GitHub Workflow Disable
**Status:** ⏳ GEPLANT

#### Via GitHub UI:
1. Navigate to: https://github.com/fabianSp77/askproai-api/actions
2. Find workflow: "check-staging-dummy.yml" (ID: 202998568)
3. Click "..." → "Disable workflow"

**Alternative (if file still exists):**
```bash
# Check if file exists
test -f .github/workflows/check-staging-dummy.yml && echo "EXISTS" || echo "DELETED"

# If exists: Remove or rename
git checkout develop
git pull origin develop
git checkout -b fix/remove-phantom-workflow
git rm .github/workflows/check-staging-dummy.yml
git commit -m "chore(ci): Remove phantom production workflow

Resolves naming conflict where two workflows both named 'Deploy to Production'

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>"
git push origin fix/remove-phantom-workflow
gh pr create --title "chore: Remove phantom production workflow" --base develop
gh pr merge --squash --delete-branch
```

**Akzeptanzkriterium:**
- `gh workflow list | grep -i production` shows only 1 workflow
- No naming conflicts

---

### **FIX H: P2 — public/docs/ Ownership**

**Typ:** Server Permission Fix
**Status:** ⏳ GEPLANT

```bash
ssh deploy@152.53.116.127 "sudo chown -R deploy:www-data /var/www/api-gateway-staging/current/public/docs"
ssh deploy@152.53.116.127 "ls -ld /var/www/api-gateway-staging/current/public/docs"
```

**Expected Output:**
```
drwxrwxr-x 2 deploy www-data 4096 Nov  2 15:49 /var/www/api-gateway-staging/current/public/docs
```

**Akzeptanzkriterium:** Owner = deploy:www-data

---

## **PHASE 1: STAGING E2E VALIDATION** (NACH ALLEN FIXES)

**Trigger-Phrase:** `"STAGING-TEST FREIGEGEBEN"`

### Prerequisites (alle müssen ✅ sein):
- [ ] FIX A: Branch Protection main aktiviert
- [ ] FIX B: Branch Protection develop aktiviert
- [ ] FIX C: BUILD_RUN_ID Bug gefixed + merged
- [ ] FIX D: Build für develop HEAD vorhanden
- [ ] FIX E: HEALTHCHECK_TOKEN verifiziert
- [ ] FIX F: Staging Failures Root Cause behoben
- [ ] FIX G: Workflow-Konflikt behoben
- [ ] FIX H: docs/ Ownership gefixed

### Execution Steps:

#### 1. Dokumentiere aktuellen Zustand
```bash
BEFORE_SYMLINK=$(ssh deploy@152.53.116.127 "readlink -f /var/www/api-gateway-staging/current")
BEFORE_RELEASES=$(ssh deploy@152.53.116.127 "ls -1dt /var/www/api-gateway-staging/releases/* | head -3")
echo "BEFORE: $BEFORE_SYMLINK"
echo "RELEASES: $BEFORE_RELEASES"
```

#### 2. Trigger Staging Deployment
```bash
gh workflow run "Deploy to Staging" --ref develop
DEPLOY_RUN=$(gh run list --workflow "Deploy to Staging" --limit 1 --json databaseId --jq '.[0].databaseId')
echo "Deployment Run: $DEPLOY_RUN"
```

#### 3. Live-Monitor
```bash
gh run watch $DEPLOY_RUN
```

#### 4. Collect Evidence

**Expected Gates:**
- Pre-Switch Gates: ✅ 9/9 PASSED
- Migrations: ✅ Success
- Symlink Switch: ✅ Atomic
- Post-Symlink Cache Clear: ✅ Executed
- PHP-FPM Reload: ✅ Success
- Grace Period: ✅ 15s wait
- Health Checks: ✅ 3/3 HTTP 200 (within 6 attempts)
- Vite Assets: ✅ manifest.json + asset accessible

**Collect:**
```bash
# Workflow logs
gh run view $DEPLOY_RUN --log > /tmp/staging_e2e_logs.txt

# New symlink
AFTER_SYMLINK=$(ssh deploy@152.53.116.127 "readlink -f /var/www/api-gateway-staging/current")
echo "AFTER: $AFTER_SYMLINK"

# Release name
RELEASE_NAME=$(basename $AFTER_SYMLINK)
echo "Release: $RELEASE_NAME"

# Health check manual
curl -H "Authorization: Bearer $HEALTHCHECK_TOKEN" https://staging.askproai.de/health
curl -H "Authorization: Bearer $HEALTHCHECK_TOKEN" https://staging.askproai.de/api/health-check
curl https://staging.askproai.de/healthcheck.php

# Vite manifest
curl https://staging.askproai.de/build/manifest.json
```

#### 5. Generate Report

**File:** `storage/docs/backup-system/E2E_STAGING_VALIDATION_<TIMESTAMP>.md`

**Sections:**
1. Executive Summary (Success/Failure)
2. Pre-Conditions (all fixes applied)
3. Workflow Run Evidence (URL, logs)
4. Gate Results (all 9/9, pass/fail)
5. Health Checks (3/3 HTTP 200)
6. Symlink Changes (before/after)
7. Release Info (name, SHA, bundle hash)
8. Acceptance Criteria (met/not met)
9. Next Steps (Production Pre-Flight if success)

### Akzeptanzkriterien (alle müssen ✅):

- [ ] ✅ Pre-Switch Gates: 9/9 PASSED
- [ ] ✅ Migrations: No errors
- [ ] ✅ Symlink Switch: Atomic, successful
- [ ] ✅ Post-Symlink Cache Clear: Executed
- [ ] ✅ PHP-FPM Reload: Success
- [ ] ✅ Grace Period: 15s completed
- [ ] ✅ Health Checks: 3/3 HTTP 200 (within 6 attempts)
- [ ] ✅ Vite Assets: manifest.json + asset accessible
- [ ] ✅ No manual server intervention required
- [ ] ✅ Vollständige Evidence gesammelt
- [ ] ✅ Report erstellt und committed

**Bei Failure:**
- Auto-Rollback greift
- Root Cause dokumentieren
- **KEIN** Production Deploy bis Staging fix

---

## **PHASE 2: PRODUCTION PRE-FLIGHT** (READ-ONLY)

**Trigger-Phrase:** Automatisch nach Staging Success ODER Manual Review

### Execution Steps:

#### 1. Staging Stability Check
```bash
# Staging muss seit ≥1h stabil sein
curl -H "Authorization: Bearer $HEALTHCHECK_TOKEN" https://staging.askproai.de/health
# HTTP 200 expected

# Check Staging uptime (via last deployment)
gh run list --workflow "Deploy to Staging" --limit 1 --json createdAt
```

#### 2. Production Pre-Flight (Dry-Run)
```bash
# Download bundle
BUILD_SHA=$(git rev-parse develop)
gh run download <BUILD_RUN_ID> -n "deployment-bundle-$BUILD_SHA"

# Extract to temp
mkdir -p /tmp/preflight-$(date +%s)
tar -xzf deployment-bundle-$BUILD_SHA.tar.gz -C /tmp/preflight-*

# Pre-Switch Gates (9 Checks)
cd /tmp/preflight-*/
test -f public/index.php || { echo "❌ index.php missing"; exit 1; }
test -f vendor/autoload.php || { echo "❌ autoload.php missing"; exit 1; }
php -r "require 'vendor/autoload.php'; echo 'autoload-ok';"
php artisan --version

# Cleanup
cd -
rm -rf /tmp/preflight-*
```

#### 3. NGINX Config Check
```bash
ssh deploy@152.53.116.127 "sudo nginx -t"
# Expected: syntax is ok, test is successful
```

#### 4. ENV Symlink Check
```bash
ssh deploy@152.53.116.127 "test -L /var/www/api-gateway/current/.env && readlink -f /var/www/api-gateway/current/.env"
# Expected: /var/www/api-gateway/shared/.env/production.env
```

### Akzeptanzkriterien:

- [ ] ✅ Staging stabil seit ≥1h
- [ ] ✅ Pre-Flight Gates: 9/9 PASSED
- [ ] ✅ NGINX Config: Valid
- [ ] ✅ ENV Symlink: Correct
- [ ] ✅ KEINE Schreib-/Switch-Operationen

**Bei Failure:**
- **NO-GO** für Production
- Dokumentiere Root Cause
- Fix Staging first

---

## **PHASE 3: PRODUCTION DEPLOYMENT** (NACH FREIGABE)

**Trigger-Phrase:** `"PROD-DEPLOY FREIGEGEBEN"`

### Prerequisites:
- [ ] ✅ Staging E2E: Success
- [ ] ✅ Production Pre-Flight: Success
- [ ] ✅ Staging stabil seit ≥1h
- [ ] ✅ User-Freigabe vorhanden

### Execution Steps:

#### 1. Pre-Deploy Backup (Production)
```bash
ssh deploy@152.53.116.127 "/var/www/api-gateway/scripts/backup-run.sh --pre-deploy"
# Expected: App + DB + NGINX Config + SHA256
```

#### 2. Merge develop → main (oder Manual Trigger)
```bash
git checkout main
git pull origin main
git merge develop --no-ff -m "chore: Production deployment $(date +%Y-%m-%d)"
git push origin main
# → Auto-triggers Production Deploy Workflow
```

**Alternative (Manual):**
```bash
gh workflow run "Deploy to Production" --ref main
```

#### 3. Live-Monitor
```bash
PROD_RUN=$(gh run list --workflow "Deploy to Production" --limit 1 --json databaseId --jq '.[0].databaseId')
gh run watch $PROD_RUN
```

#### 4. Collect Evidence

**Expected Gates:**
- Pre-Switch Gates (Layer 3): ✅ 9/9 PASSED
- Symlink Switch: ✅ Atomic (< 1s)
- PHP-FPM Reload: ✅ Success
- Health Checks: ✅ 2/2 HTTP 200 (/health + manifest)

**Collect:**
```bash
# Workflow logs
gh run view $PROD_RUN --log > /tmp/prod_deployment_logs.txt

# New symlink
ssh deploy@152.53.116.127 "readlink -f /var/www/api-gateway/current"

# Health check
curl -H "Authorization: Bearer $HEALTHCHECK_TOKEN" https://api.askproai.de/health
curl https://api.askproai.de/build/manifest.json

# Rollback status (if applicable)
grep -i "rollback" /tmp/prod_deployment_logs.txt
```

#### 5. Generate Report

**File:** `storage/docs/backup-system/E2E_PRODUCTION_DEPLOYMENT_<TIMESTAMP>.md`

**Sections:**
1. Executive Summary
2. Pre-Flight Results
3. Deployment Run Evidence
4. Gate Results (9/9)
5. Health Checks (2/2)
6. Zero-Downtime Verification (< 1s)
7. Rollback Status (none expected)
8. Evidence Links

### Akzeptanzkriterien:

- [ ] ✅ Pre-Flight: OK
- [ ] ✅ Pre-Switch Gates: 9/9 PASSED
- [ ] ✅ Symlink Switch: Atomic
- [ ] ✅ Health Checks: 2/2 HTTP 200
- [ ] ✅ Zero-Downtime: < 1s
- [ ] ✅ Keine Rollback erforderlich
- [ ] ✅ Vollständige Evidence

**Bei Failure:**
- Auto-Rollback zu previous release
- Verify rollback: `/health` → HTTP 200
- Document Root Cause
- **STOP** Production deployments

---

## 7️⃣ AUSFÜHRUNGS-GUARDRAILS

### **Sicherheitsregeln:**

1. ✅ **Keine Ausführung ohne Freigabe-Phrase**
2. ✅ **Keine Secrets im Klartext loggen** (nur SHA256)
3. ✅ **Keine Files auf Server editieren** (außer Deploy-Steps)
4. ✅ **Bei Gate-Fail: Sofortiger Abbruch** + Root Cause + Logs
5. ✅ **Read-Only bei Audits** (keine Änderungen)
6. ✅ **Atomic Operations** (Symlink, Migrations)
7. ✅ **Auto-Rollback bei Health-Failure**

### **Abbruchkriterien:**

**Vor Staging E2E:**
- P0 Fixes nicht implementiert → **ABBRUCH**
- Token Hash mismatch → **ABBRUCH**
- Kein Build für develop HEAD → **ABBRUCH**

**Während Staging E2E:**
- Pre-Switch Gate Failure → **AUTOMATISCHER ABBRUCH**
- Health Checks 0/3 nach 6 Attempts → **AUTO-ROLLBACK**
- Manual intervention benötigt → **ABBRUCH**

**Vor Production Deploy:**
- Staging nicht stabil (< 1h) → **NO-GO**
- Pre-Flight Gates Failure → **ABBRUCH**
- Kein User-Freigabe → **ABBRUCH**

**Während Production Deploy:**
- Pre-Switch Gate Failure → **AUTOMATISCHER ABBRUCH**
- Health Checks Failure → **AUTO-ROLLBACK**

### **Verbotene Workarounds:**

❌ **Keine** ad-hoc Server-Hotfixes (direktes Editing)
❌ **Keine** manuellen Symlink-Switches ohne Workflow
❌ **Keine** Production-Deployments bei bekannten Staging-Failures
❌ **Keine** Skipping von Gates oder Health Checks
❌ **Keine** Force-Pushes zu protected branches

---

## 8️⃣ ZUSAMMENFASSUNG & NÄCHSTE SCHRITTE

### **Aktueller Status:**

- ✅ **SOLL-Prozess dokumentiert** (28 Schritte)
- ✅ **GitHub-Audit durchgeführt** (Branch Protection, Workflows, Secrets, Build-Kopplung)
- ✅ **Server-Audit durchgeführt** (Staging State, Sudoers, Health, Permissions)
- ✅ **IST↔SOLL Delta erstellt** (16 Punkte: 4x P0, 3x P1, 3x P2)
- ✅ **Flowchart verifiziert** (konsistent, keine Updates nötig)
- ✅ **Fix-Plan erstellt** (8 Fixes A-H mit Patches/Kommandos)
- ✅ **Validierungsplan erstellt** (3 Phasen: Staging E2E, Prod Pre-Flight, Prod Deploy)
- ✅ **Guardrails definiert** (Sicherheitsregeln, Abbruchkriterien)

### **GO/NO-GO Decision:**

**Staging E2E Test:** ❌ **NO-GO**
**Production Deployment:** ❌ **NO-GO**

**Grund:** 4x P0 Blocker + 3x P1 High + 3x P2 Medium

### **Erforderliche Fixes (Reihenfolge):**

**P0 (CRITICAL - must fix first):**
1. ✅ FIX A: Branch Protection (main) → 6 Required Checks
2. ✅ FIX B: Branch Protection (develop) → 4 Required Checks
3. ✅ FIX C: BUILD_RUN_ID Bug → jq `// empty` + null check
4. ✅ FIX D: Build für develop HEAD → Trigger Workflow

**P1 (HIGH - fix before E2E):**
5. ✅ FIX E: HEALTHCHECK_TOKEN → Hash-Vergleich + Update if needed
6. ✅ FIX F: Staging Failures → Root Cause (wahrscheinlich P0-Fixes lösen es)

**P2 (MEDIUM - fix before Production):**
7. ✅ FIX G: Workflow-Konflikt → Disable phantom workflow
8. ✅ FIX H: docs/ Ownership → Chown to deploy:www-data

### **Freigabe-Phrasen (warten auf User):**

- **"APPEND MISSING INFO"** + Details → Ergänze Report
- **"STAGING-TEST FREIGEGEBEN"** → Führe Staging E2E aus (nach P0+P1 Fixes!)
- **"PROD-DEPLOY FREIGEGEBEN"** → Führe Production Deploy aus (nach Staging Success!)
- **"PROD-FIX FREIGEGEBEN"** → Führe nur spezifizierte Prod-Aktion aus
- **"ABBRECHEN"** → Stoppe, liefere Status & Empfehlungen

---

## 📚 REFERENZEN

### **Dokumentation (Doku-Hub):**

- [DEPLOYMENT_HANDBUCH_FUER_DRITTE.html](https://api.askproai.de/docs/backup-system/DEPLOYMENT_HANDBUCH_FUER_DRITTE.html)
- [status-quo-deployment-prozess-2025-11-01.html](https://api.askproai.de/docs/backup-system/status-quo-deployment-prozess-2025-11-01.html)
- [E2E_DEPLOYMENT_VALIDATION_FINAL_2025-11-02_1300.html](https://api.askproai.de/docs/backup-system/E2E_DEPLOYMENT_VALIDATION_FINAL_2025-11-02_1300.html)
- [E2E_WORKFLOW_HARDENING_VALIDATION_2025-11-02_1330.html](https://api.askproai.de/docs/backup-system/E2E_WORKFLOW_HARDENING_VALIDATION_2025-11-02_1330.html)
- [DEPLOYMENT_FLOWCHART.md](https://github.com/fabianSp77/askproai-api/blob/develop/storage/docs/backup-system/DEPLOYMENT_FLOWCHART.md)

### **Workflows:**

- `.github/workflows/build-artifacts.yml` (13K)
- `.github/workflows/deploy-staging.yml` (26K)
- `.github/workflows/deploy-production.yml` (9.5K)
- `.github/workflows/staging-smoke.yml` (6.1K)

### **Audit-Reports:**

- GitHub/CI Audit: Embedded in Section 2️⃣
- Server Audit: Embedded in Section 3️⃣

---

**Report Metadata:**

- **Erstellt:** 2025-11-02 19:00 UTC
- **Autor:** Claude Code (E2E Planning & Audit)
- **Version:** 2.0
- **Format:** Markdown
- **Status:** 🔴 **PLAN-FIRST — WARTET AUF FREIGABE**

---

**⛔ STOP: NICHTS WURDE AUSGEFÜHRT**

Dieser Report ist **nur ein Plan**. Keine Workflows getriggert, keine Server-Änderungen, keine Deployments, keine Commits.

**Warte auf Freigabe-Phrase vom User.**
