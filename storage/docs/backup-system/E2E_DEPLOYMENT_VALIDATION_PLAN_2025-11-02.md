# E2E DEPLOYMENT VALIDATION PLAN — STAGING ➜ PRODUCTION

**Version:** 1.0
**Erstellt:** 2025-11-02 19:00 UTC
**Autor:** Claude Code (Automated Planning)
**Status:** 🟡 **PLAN ZUERST — NICHTS AUSGEFÜHRT**
**Zweck:** Vollständige E2E-Validierung mit SOLL↔IST-Abgleich, Flowchart, Validierungsplan

---

## ⚠️ WICHTIG: STOP-BEDINGUNG

**Dieser Report ist NUR ein Plan.** NICHTS wurde getriggert, NICHTS geändert, NICHTS auf Production deployed.

**Ausführung erfolgt ERST nach expliziter Freigabe:**
- **"STAGING-TEST FREIGEGEBEN"** → Führt Staging E2E aus
- **"PROD-DEPLOY FREIGEGEBEN"** → Führt Production Deploy aus
- **"PROD-FIX FREIGEGEBEN"** → Führt nur spezifizierte Prod-Aktion aus

---

## 1️⃣ SOLL-ZUSAMMENFASSUNG (aus Dokumentation)

Basierend auf Analyse von 5 Doku-Dateien (STATUS_QUO, HANDBUCH, E2E_VALIDATION_FINAL, E2E_WORKFLOW_HARDENING, PROD_FIX_BUNDLE_GATES):

### **BUILD-PHASE** (3-5 min)

1. **Frontend + Backend Build**
   - **Aktion:** Vite Build + Composer Install (--no-dev --optimize-autoloader)
   - **Quelle:** DEPLOYMENT_HANDBUCH Zeilen 101-104
   - **Dauer:** ~3-5 Minuten
   - **Abbruch:** Compilation error → Workflow fails

2. **Static Analysis + Tests**
   - **Aktion:** PHPStan Level 5 + Pest Tests (MariaDB)
   - **Quelle:** STATUS_QUO Zeilen 32-33
   - **Dauer:** Teil der Build-Phase
   - **Abbruch:** Test failure → Workflow fails

3. **Pre-Bundle Gates (Layer 1) — 9 Checks**
   - **Aktion:** artisan, composer.json, **public/index.php**, **vendor/autoload.php**, directories
   - **Quelle:** PROD_FIX Zeilen 64-78, .github/workflows/build-artifacts.yml:321-350
   - **Dauer:** ~5-10 Sekunden
   - **Abbruch:** Fehlende kritische Datei → Build fails, kein Artifact

4. **Bundle Creation + Upload**
   - **Aktion:** `deployment-bundle-{SHA}.tar.gz`, SHA256 Checksum, 30 Tage Retention
   - **Quelle:** DEPLOYMENT_HANDBUCH Zeilen 113-117
   - **Dauer:** ~1-2 Minuten
   - **Abbruch:** Upload error → Workflow fails

### **STAGING-DEPLOY** (2-3 min)

5. **Health Check (Pre-Deploy)**
   - **Aktion:** staging.askproai.de erreichbar?
   - **Quelle:** DEPLOYMENT_HANDBUCH Zeile 135
   - **Dauer:** ~10 Sekunden
   - **Abbruch:** Staging down → Deploy aborted

6. **Pre-Deploy Backup**
   - **Aktion:** App Backup (tar.gz) + DB Backup (mysqldump) + SHA256
   - **Quelle:** DEPLOYMENT_HANDBUCH Zeile 136
   - **Dauer:** ~30 Sekunden
   - **Abbruch:** Backup error → Deploy aborted

7. **Bundle Download + SHA256 Verify**
   - **Aktion:** Download Artifact, Checksum verify
   - **Quelle:** DEPLOYMENT_HANDBUCH Zeilen 137-138, deploy-staging.yml:215-234
   - **Dauer:** ~20 Sekunden
   - **Abbruch:** Checksum mismatch → Deploy aborted

8. **Upload + Extract Bundle**
   - **Aktion:** Upload to Server, Extract to `/releases/{TIMESTAMP}-{SHA}`
   - **Quelle:** deploy-staging.yml:249-275
   - **Dauer:** ~15-20 Sekunden
   - **Abbruch:** Disk full → Deploy aborted

9. **Pre-Switch Gates (Layer 2) — 9 Checks + PHP Tests**
   - **Aktion:** VOR Migrations: 9 Struktur-Checks + `php -r "require 'vendor/autoload.php'"` + `php artisan --version`
   - **Quelle:** PROD_FIX Zeilen 100-118, deploy-staging.yml:277-329
   - **Dauer:** ~5-15 Sekunden
   - **Abbruch:** Gate failure → Deploy aborted, Symlink NICHT gewechselt

10. **Run Migrations**
    - **Aktion:** `php artisan migrate --force` (NACH Pre-Switch Gate)
    - **Quelle:** deploy-staging.yml:331-339
    - **Dauer:** ~4-10 Sekunden
    - **Abbruch:** Migration error → Auto-Rollback triggered

11. **Clear Caches (Pre-Symlink)**
    - **Aktion:** Laravel Caches (config, route, view, cache, optimize)
    - **Quelle:** deploy-staging.yml:341-354
    - **Dauer:** ~5-6 Sekunden
    - **Abbruch:** Non-critical, continue

12. **Switch Symlink (Atomic)**
    - **Aktion:** `ln -snf {RELEASE} /var/www/api-gateway-staging/current`
    - **Quelle:** deploy-staging.yml:359-369
    - **Dauer:** < 1 Sekunde (atomic)
    - **Abbruch:** Symlink error → Auto-Rollback

13. **Post-Symlink Cache Clear**
    - **Aktion:** Clear ALL caches NACH Symlink (config, route, view, cache)
    - **Quelle:** E2E_WORKFLOW Zeilen 195-200, deploy-staging.yml:371-387
    - **Dauer:** ~6-8 Sekunden
    - **Abbruch:** Non-critical, continue to health checks

14. **Reload PHP-FPM (OPcache)**
    - **Aktion:** `sudo -n service php8.3-fpm reload` (passwordless)
    - **Quelle:** E2E_WORKFLOW Zeilen 200, deploy-staging.yml:389-431
    - **Dauer:** ~4-5 Sekunden
    - **Abbruch:** Non-critical (warning logged)

15. **Grace Period**
    - **Aktion:** 15 Sekunden Warten für Cache-Propagation
    - **Quelle:** deploy-staging.yml:433-437
    - **Dauer:** 15 Sekunden
    - **Abbruch:** N/A (fixed delay)

16. **Health Checks (Retry Logic)**
    - **Aktion:** 6 Attempts, 5s Intervall: `/health` (Bearer), `/api/health-check` (Bearer), `/healthcheck.php`
    - **Quelle:** E2E_VALIDATION Zeilen 88-135, deploy-staging.yml:439-500
    - **Dauer:** ~10-30 Sekunden (max 6 × 5s)
    - **Abbruch:** All attempts fail → Auto-Rollback triggered

17. **Vite Asset Validation**
    - **Aktion:** `/build/manifest.json` + Asset verfügbar?
    - **Quelle:** deploy-staging.yml:502-525
    - **Dauer:** ~5 Sekunden
    - **Abbruch:** Missing assets → Deploy fails

### **PRODUCTION-DEPLOY** (nach Freigabe, 2-3 min)

18. **Staging Health Check**
    - **Aktion:** Staging seit mindestens 1h stabil?
    - **Quelle:** deploy-production.yml:34-44
    - **Dauer:** ~10 Sekunden
    - **Abbruch:** Staging unhealthy → Prod deploy aborted

19. **Pre-Deploy Backup (Production)**
    - **Aktion:** App + DB + **NGINX Config** + SHA256
    - **Quelle:** deploy-production.yml:54-80
    - **Dauer:** ~60 Sekunden
    - **Abbruch:** Backup error → Deploy aborted

20. **Pre-Switch Gates (Layer 3)**
    - **Aktion:** 9 Checks + PHP Autoload + `artisan config:cache` (VOR Symlink)
    - **Quelle:** PROD_FIX Zeilen 125-143, deploy-production.yml:142-174
    - **Dauer:** ~5 Sekunden
    - **Abbruch:** Gate failure → Production unverändert

21. **Switch Symlink (Atomic)**
    - **Aktion:** `ln -sfn {RELEASE} /var/www/api-gateway/current`
    - **Quelle:** deploy-production.yml:185-187
    - **Dauer:** < 1 Sekunde
    - **Abbruch:** Symlink error → Auto-Rollback

22. **Reload PHP-FPM**
    - **Aktion:** `sudo systemctl reload php8.3-fpm`
    - **Quelle:** deploy-production.yml:189-192
    - **Dauer:** ~5 Sekunden
    - **Abbruch:** Reload error → Auto-Rollback

23. **Health Checks (Production)**
    - **Aktion:** `/health` (HTTP 200), `/build/manifest.json` verfügbar
    - **Quelle:** deploy-production.yml:208-230
    - **Dauer:** ~10 Sekunden
    - **Abbruch:** Health check fails → Auto-Rollback

24. **Auto-Rollback (bei Failure)**
    - **Aktion:** Symlink switch to previous release, Verify rollback health
    - **Quelle:** E2E_VALIDATION Zeilen 221-235, deploy-production.yml:235-272
    - **Dauer:** ~10 Sekunden
    - **Abbruch:** N/A (recovery mechanism)

---

## 2️⃣ GITHUB-AUDIT (IST-ZUSTAND)

### **Branch Protection**

**Main Branch:**
```json
{
  "required_status_checks": null,
  "enforce_admins": null,
  "required_pull_request_reviews": null
}
```
⚠️ **ISSUE:** Main branch hat **KEINE** Required Checks!
**SOLL:** 6 Checks (build-frontend, build-backend, static-analysis, run-tests, create-deployment-bundle, check-staging)

**Develop Branch:**
```
⚠️ Develop branch not protected
```
**SOLL:** ≥4 Build-Checks, "Require up-to-date", Reviews aktiv

### **Workflows Vorhanden**

| Workflow | Datei | Größe | Status |
|----------|-------|-------|--------|
| Build Artifacts | build-artifacts.yml | 13K | ✅ Existiert |
| Deploy to Staging | deploy-staging.yml | 26K | ✅ Existiert |
| Deploy to Production | deploy-production.yml | 9.5K | ✅ Existiert |
| Staging Smoke | staging-smoke.yml | 6.1K | ✅ Existiert |
| **Weitere** | backup-daily.yml, etc. | Various | 13 workflows total |

### **Recent Workflow Runs**

**Build Artifacts:**
- Run 19015290501 (SHA 62584375): ✅ success (2025-11-02 16:52:41Z)
- Run 19015290114 (SHA 62584375): ✅ success (2025-11-02 16:52:39Z)
- Run 19015263191 (SHA c99bbb21): ✅ success (2025-11-02 16:49:58Z)

**Deploy to Staging:**
- Run 19013942449 (SHA f20993ee): ❌ failure (2025-11-02 14:54:51Z)
- Run 19013877383 (SHA f0959baf): ❌ failure (2025-11-02 14:48:20Z)
- Run 19013845846 (SHA f0959baf): ❌ failure (2025-11-02 14:45:12Z)

⚠️ **ISSUE:** Letzten 3 Staging Deployments sind fehlgeschlagen!

**Deploy to Production:**
```
could not resolve to a unique workflow; found: check-staging-dummy.yml deploy-production.yml
```
⚠️ **ISSUE:** Zwei Production Workflows gefunden (naming conflict?)

### **Build→Deploy Artefakte-Kopplung**

**BUILD_RUN_ID Ermittlung** (deploy-staging.yml:171-177):
```yaml
BUILD_RUN_ID=$(gh run list \
  --workflow "Build Artifacts" \
  --branch "${{ github.ref_name }}" \
  --json databaseId,headSha,status,conclusion \
  --limit 20 | jq -r --arg sha "$SHA" \
  '[.[] | select(.headSha==$sha and .status=="completed" and .conclusion=="success")][0].databaseId')
[ -z "$BUILD_RUN_ID" ] && { echo "No successful Build Artifacts run for SHA"; exit 1; }
```

⚠️ **P0 BUG:** Kein Guard gegen `jq` returning string `"null"`!
- **Issue:** `jq -r '...[0].databaseId'` returns `"null"` (string) wenn kein Match
- **Impact:** `[ -z "$BUILD_RUN_ID" ]` ist FALSE für string `"null"` → Artifact download fails
- **Fix Required:** `jq -r '...[0].databaseId // empty'` ODER `[ "$BUILD_RUN_ID" = "null" ]` check

**Artifact Download** (deploy-staging.yml:215-221):
```yaml
uses: actions/download-artifact@v4
with:
  name: deployment-bundle-${{ env.BUILD_SHA }}
  path: ./bundle/
  github-token: ${{ secrets.GITHUB_TOKEN }}
  run-id: ${{ env.BUILD_RUN_ID }}  # ← Verwendet ermittelte BUILD_RUN_ID
```
✅ Korrekte Kopplung via `run-id` Parameter

### **Workflow Hardening Features**

✅ **Post-Symlink Cache Clear:** 1 Vorkommen (deploy-staging.yml)
✅ **PHP-FPM OPcache Reload:** 1 Vorkommen (deploy-staging.yml)
✅ **Grace Period (15s):** 1 Vorkommen (deploy-staging.yml)
✅ **Health Check Retry (6 attempts):** 1 Vorkommen (deploy-staging.yml)

**Quelle:** E2E_WORKFLOW_HARDENING_VALIDATION Zeilen 180-216

---

## 3️⃣ SERVER-AUDIT (IST-ZUSTAND)

### **Staging Current State**

**Current Symlink:**
```
/var/www/api-gateway-staging/releases/20251102_154900-f0959baf
```

**Last 2 Releases:**
```
/var/www/api-gateway-staging/releases/20251102_155523-f20993ee (newer, NOT current)
/var/www/api-gateway-staging/releases/20251102_154900-f0959baf (current)
```

⚠️ **OBSERVATION:** Neueste Release (f20993ee) ist NICHT aktiv → Deployment failed und rollback zu f0959baf

**Current Release Structure:**
```
✅ public/index.php exists
✅ vendor/autoload.php exists
```

### **HEALTHCHECK_TOKEN**

**Staging .env:**
```
SHA256: 67fbd6319dbc2db758a1c0f0ec07d4cf0ec9fc73d3c77c88ea1d8f05831bff8d
Token Length: 43 chars
✅ Token found in staging .env
```

**GitHub Secret:**
- Name: `HEALTHCHECK_TOKEN`
- ⚠️ **Manual Verification Required:** GitHub Secret SHA256 muss mit Staging .env matchen!
- **Verification Method:** In Workflow-Logs sichtbar (Existenz), aber kein Klartext

**Hash-Vergleich Status:** ⏳ **PENDING** (manuelle Verifikation erforderlich)

### **Sudoers (Staging)**

**Deploy User Permissions:**
```
✅ (root) NOPASSWD: /usr/bin/systemctl reload nginx
✅ (root) NOPASSWD: /usr/sbin/service nginx reload
✅ (root) NOPASSWD: /usr/sbin/service php*-fpm reload
```

**Sudoers Config Check:**
```
sudo: Zum Lesen des Passworts ist ein Terminal erforderlich
```
⚠️ **Note:** `sudo visudo -c` benötigt TTY, aber Permissions sind konfiguriert (siehe sudo -l Output)

**Assessment:** ✅ Passwordless sudo für Service-Reloads ist korrekt konfiguriert

---

## 4️⃣ IST↔SOLL DELTA & RISIKOBEWERTUNG

| # | Thema | SOLL | IST | Impact | Status | Empfohlener Fix |
|---|-------|------|-----|--------|--------|-----------------|
| 1 | **Branch Protection (main)** | 6 Required Checks | null (keine Protection) | **P0** | ❌ CRITICAL | Branch Protection aktivieren mit 6 Checks |
| 2 | **Branch Protection (develop)** | ≥4 Build-Checks, Reviews | nicht protected | **P1** | ❌ HIGH | Branch Protection für develop |
| 3 | **BUILD_RUN_ID null-Guard** | `jq // empty` ODER null-Check | nur `[ -z ]` Check | **P0** | ❌ CRITICAL | Fix: `jq -r '...[0].databaseId // empty'` |
| 4 | **HEALTHCHECK_TOKEN Match** | CI Secret = Staging .env (sha256) | ⏳ Manual Verify | **P1** | ⚠️ PENDING | Hash-Vergleich durchführen |
| 5 | **Staging Deployments** | Last 3 sollten success sein | Last 3 = failure | **P1** | ❌ HIGH | Root Cause der Failures beheben |
| 6 | **Production Workflow Conflict** | 1 eindeutiger Workflow | 2 Workflows gefunden | **P2** | ⚠️ MEDIUM | check-staging-dummy.yml umbenennen/entfernen |
| 7 | **Post-Symlink Cache Clear** | Vorhanden | ✅ Vorhanden (1x) | N/A | ✅ OK | - |
| 8 | **PHP-FPM OPcache Reload** | Vorhanden | ✅ Vorhanden (1x) | N/A | ✅ OK | - |
| 9 | **Grace Period (15s)** | Vorhanden | ✅ Vorhanden (1x) | N/A | ✅ OK | - |
| 10 | **Health Check Retry (6x)** | Vorhanden | ✅ Vorhanden (1x) | N/A | ✅ OK | - |
| 11 | **Sudoers (Staging)** | Passwordless reload | ✅ Konfiguriert | N/A | ✅ OK | - |
| 12 | **Server File Structure** | index.php + autoload.php | ✅ Beide vorhanden | N/A | ✅ OK | - |

### **Kritikalitäts-Bewertung:**

- **P0 (BLOCKER):** 2 Issues → Branch Protection (main) + BUILD_RUN_ID Bug
- **P1 (HIGH):** 3 Issues → Branch Protection (develop) + Token Verification + Staging Failures
- **P2 (MEDIUM):** 1 Issue → Production Workflow Conflict

### **GO/NO-GO Decision:**

**❌ NO-GO für Production Deployment**

**Begründung:**
1. P0: BUILD_RUN_ID Bug kann Artifact-Download verhindern (Race Condition)
2. P0: Main branch ohne Protection → versehentliche Force-Pushes möglich
3. P1: Staging Deployments schlagen fehl → Root Cause unklar
4. P1: Token Match nicht verifiziert → Health Checks könnten fehlschlagen

**Staging E2E Test:** ⚠️ **CONDITIONAL GO** (nach P0-Fixes)

---

## 5️⃣ FLOWCHART

**Datei:** `storage/docs/backup-system/DEPLOYMENT_FLOWCHART.md`
**Commit:** `ad3cb8d3947c52d15f6162bdc4b56832cb3158e1`
**Branch:** `develop`
**Status:** ✅ Committed

**URL:** [DEPLOYMENT_FLOWCHART.md](https://github.com/fabianSp77/askproai-api/blob/develop/storage/docs/backup-system/DEPLOYMENT_FLOWCHART.md)

**Verlinkt in Handbuch:** ⏳ TODO (siehe TEIL 1 Aufgaben)

---

## 6️⃣ VALIDIERUNGSPLAN (DETAILLIERT, NICHT AUSFÜHREN)

### **VORBEDINGUNGEN-CHECKLISTE**

Alle müssen ✅ sein, sonst **ABBRUCH**:

- [ ] **Branch Protection (main):** 6 Required Checks aktiviert
- [ ] **Branch Protection (develop):** ≥4 Build-Checks aktiviert
- [ ] **BUILD_RUN_ID Bug Fix:** `jq // empty` implementiert ODER null-Check hinzugefügt
- [ ] **HEALTHCHECK_TOKEN Verification:** CI Secret SHA256 = Staging .env SHA256 (MATCH)
- [ ] **Build Artifact vorhanden:** Aktueller develop-Commit hat erfolgreichen Build
- [ ] **Sudoers OK:** `sudo -l` zeigt reload-Permissions
- [ ] **SSH Reachability:** `ssh deploy@152.53.116.127` funktioniert
- [ ] **Staging Failures Resolved:** Root Cause der letzten 3 Failures behoben

### **STAGING E2E** (nach "STAGING-TEST FREIGEGEBEN")

#### **Phase 1: Vorbereitung**

1. **Aktuellen Zustand dokumentieren:**
   ```bash
   readlink -f /var/www/api-gateway-staging/current
   ls -1dt /var/www/api-gateway-staging/releases/* | head -3
   ```

2. **Build triggern** (falls nötig):
   ```bash
   gh workflow run "Build Artifacts" --ref develop
   ```

3. **Warten auf Build Success:**
   - Expected: ~3-5 Minuten
   - Verify: `gh run list --workflow "Build Artifacts" --limit 1`
   - Expected Artifacts: `deployment-bundle-{SHA}.tar.gz`

#### **Phase 2: Staging Deployment**

4. **Deploy to Staging triggern:**
   ```bash
   gh workflow run "Deploy to Staging" --ref develop
   ```

5. **Live-Monitoring:**
   ```bash
   gh run watch <RUN_ID>
   ```

#### **Phase 3: Gate-Verifikation**

6. **Pre-Switch Gates (erwartetes Log-Output):**
   ```
   🔎 Verifying release structure before migrations...
   ✅ All pre-switch gates PASSED
   Release structure verified:
   -rw-r--r-- 1 deploy deploy 1.2K ... index.php
   -rw-r--r-- 1 deploy deploy  748 ... autoload.php
   ```

7. **Migrations:**
   ```
   php artisan migrate --force
   (expected: no errors)
   ```

8. **Symlink Switch:**
   ```
   ✅ Symlink switched to new release
   ```

9. **Post-Switch Cache Clear:**
   ```
   🧹 Clearing all Laravel caches after symlink switch...
   ✅ All Laravel caches cleared
   ```

10. **PHP-FPM Reload:**
    ```
    🔄 Reloading PHP-FPM to force OPcache clear...
    ✅ PHP-FPM reloaded - OPcache cleared
    ```

11. **Grace Period:**
    ```
    ⏳ Waiting 15 seconds for cache clearing to propagate...
    ✅ Grace period complete
    ```

12. **Health Checks (3/3 erwartetes Output):**
    ```
    🏥 Running comprehensive post-deploy health checks with retry logic...

    Attempt 1/6: /health -> HTTP 200
      ✅ /health: HTTP 200

    Attempt 1/6: /api/health-check -> HTTP 200
      ✅ /api/health-check: HTTP 200

    ✅ All Laravel health endpoints passed

    /healthcheck.php -> HTTP 200
    ✅ Public healthcheck.php passed
    ```

13. **Vite Assets:**
    ```
    ✅ Vite manifest.json is valid
    ✅ Vite assets are accessible
    ```

#### **Phase 4: Evidence Collection**

14. **Sammeln:**
    - Run URL: `https://github.com/fabianSp77/askproai-api/actions/runs/<RUN_ID>`
    - Log-Snippets: Pre-Switch Gates, Health Checks, alle ✅
    - Current Symlink: `readlink -f /var/www/api-gateway-staging/current`
    - Release Name: `20251102_HHMMSS-{SHA:0:8}`
    - Bundle SHA256: aus Workflow-Logs
    - Deployment Ledger: Download Artifact `deployment-ledger-<RUN_ID>`

#### **Phase 5: Akzeptanzkriterien**

**Alle müssen erfüllt sein:**
- ✅ Pre-Switch Gates: 9/9 PASSED
- ✅ Health Checks: 3/3 HTTP 200 (innerhalb 6 Attempts)
- ✅ Vite Assets: manifest.json + Asset accessible
- ✅ Kein manueller Server-Eingriff erforderlich
- ✅ Vollständige Evidence vorhanden (Logs, Ledger, Symlink)

**Bei Failure:**
- Auto-Rollback greift
- Root Cause dokumentieren
- **KEIN** Production Deploy bis Staging fix

---

### **PRODUCTION PRE-FLIGHT** (read-only, NICHT AUSFÜHREN)

#### **Checks:**

1. **Bundle extrahieren** (zu temp release):
   ```bash
   ssh deploy@152.53.116.127 "mkdir -p /tmp/preflight-{TS} && tar -xzf /tmp/bundle.tar.gz -C /tmp/preflight-{TS}"
   ```

2. **Pre-Switch Gates** (9 Checks):
   ```bash
   test -f public/index.php || exit 1
   test -f vendor/autoload.php || exit 1
   php -r "require 'vendor/autoload.php'; echo 'autoload-ok';"
   php artisan --version
   ```

3. **NGINX Config Check:**
   ```bash
   nginx -t
   ```

4. **ENV-Symlink:**
   ```bash
   test -L .env && readlink -f .env
   ```

5. **Migrations Status:**
   ```bash
   php artisan migrate:status --env=production
   ```

6. **Cleanup:**
   ```bash
   rm -rf /tmp/preflight-{TS}
   ```

**Erwartetes Ergebnis:**
- ✅ Alle 9 Gates PASS
- ✅ NGINX Config valid
- ✅ Keine offenen Migrations
- ❌ **Keine** Schreib-/Switch-Operationen!

---

### **PRODUCTION DEPLOY ENTWURF** (nur Plan, NICHT AUSFÜHREN)

#### **Nach "PROD-DEPLOY FREIGEGEBEN":**

1. **Vorbedingungen:**
   - Staging seit ≥1h stabil
   - Pre-Flight erfolgreich
   - User-Freigabe vorhanden

2. **Backup (Production):**
   ```bash
   /var/www/api-gateway/scripts/backup-run.sh --pre-deploy
   ```
   Expected: App + DB + NGINX Config + SHA256

3. **Deploy via Workflow:**
   ```bash
   gh workflow run "Deploy to Production" --ref main
   ```

4. **Gate-Verifikation:**
   - Pre-Switch Gates (Layer 3): 9 Checks + PHP Tests
   - Expected: ✅ All PASSED

5. **Symlink Switch:**
   ```bash
   ln -sfn {RELEASE} /var/www/api-gateway/current
   ```
   Expected: Atomic, < 1s Downtime

6. **Service Reload:**
   ```bash
   sudo systemctl reload php8.3-fpm
   ```

7. **Health Checks (2/2):**
   - `/health` → HTTP 200
   - `/build/manifest.json` → verfügbar

8. **Bei Failure:**
   - Auto-Rollback zu previous release
   - Verify rollback: `/health` → HTTP 200
   - Document Root Cause

#### **Evidence:**
- Run URL
- Log-Snippets
- Current Symlink
- Health Check Results
- Rollback Status (falls applicable)

#### **Abbruchkriterien:**
- Staging unhealthy → NO-GO
- Pre-Switch Gates Failure → Abort
- Health Checks Failure → Auto-Rollback
- Manual intervention required → Abort

#### **Akzeptanzkriterien (Production):**
- ✅ Pre-Flight OK
- ✅ Pre-Switch Gates 9/9 PASSED
- ✅ Health Checks 2/2 HTTP 200
- ✅ Zero-Downtime (< 1s)
- ✅ Keine Rollback erforderlich
- ✅ Vollständige Evidence

---

## 7️⃣ RISIKEN & ABBRUCHKRITERIEN

### **P0 Risiken (BLOCKER)**

| Risiko | Impact | Mitigation | Status |
|--------|--------|------------|--------|
| **BUILD_RUN_ID "null" Bug** | Artifact download fails (Race Condition) | Fix: `jq // empty` ODER null-Check | ❌ OFFEN |
| **Main Branch unprotected** | Versehentliche Force-Pushes möglich | Branch Protection aktivieren | ❌ OFFEN |

### **P1 Risiken (HIGH)**

| Risiko | Impact | Mitigation | Status |
|--------|--------|------------|--------|
| **Token Mismatch** | Health Checks return 401/403 | Hash-Vergleich vor Deploy | ⏳ VERIFY |
| **Staging Failures** | Prod Deploy basiert auf instabilem Staging | Root Cause beheben | ❌ OFFEN |

### **P2 Risiken (MEDIUM)**

| Risiko | Impact | Mitigation | Status |
|--------|--------|------------|--------|
| **Workflow Naming Conflict** | Falsche Workflow-Ausführung | Rename/Remove check-staging-dummy.yml | ⚠️ OFFEN |
| **Caches/OPcache nicht cleared** | Alte Code-Ausführung | Post-Switch Cache Clear + PHP-FPM Reload | ✅ MITIGIERT |

### **Abbruchkriterien**

**Vor Staging E2E:**
- P0 Fixes nicht implementiert → **ABBRUCH**
- Token Hash mismatch → **ABBRUCH**
- SSH nicht erreichbar → **ABBRUCH**

**Während Staging E2E:**
- Pre-Switch Gate Failure → **AUTOMATISCHER ABBRUCH**
- Health Checks 0/3 nach 6 Attempts → **AUTO-ROLLBACK**

**Vor Production Deploy:**
- Staging nicht stabil (< 1h) → **NO-GO**
- Pre-Flight Gates Failure → **ABBRUCH**
- Kein User-Freigabe → **ABBRUCH**

**Während Production Deploy:**
- Pre-Switch Gate Failure → **AUTOMATISCHER ABBRUCH**
- Health Checks Failure → **AUTO-ROLLBACK**

### **Workarounds: VERBOTEN**

❌ **Keine** ad-hoc Server-Hotfixes (direktes Editing von Dateien)
❌ **Keine** manuellen Symlink-Switches ohne Workflow
❌ **Keine** Production-Deployments bei bekannten Staging-Failures
❌ **Keine** Skipping von Gates oder Health Checks

---

## 8️⃣ ZUSAMMENFASSUNG & NEXT STEPS

### **Aktueller Status**

- ✅ **SOLL-Prozess dokumentiert** (22 Schritte, vollständig)
- ✅ **Flowchart erstellt & committed** (ad3cb8d3)
- ✅ **GitHub-Audit durchgeführt** (Workflows, Runs, Branch Protection)
- ✅ **Server-Audit durchgeführt** (Staging State, Sudoers, Token)
- ✅ **IST↔SOLL Delta erstellt** (12 Punkte, 2x P0, 3x P1, 1x P2)
- ✅ **Validierungsplan erstellt** (detailliert, nicht ausgeführt)
- ✅ **Risiken dokumentiert** (P0, P1, P2 mit Mitigation)

### **GO/NO-GO Decision**

**Production Deployment:** ❌ **NO-GO**
- Grund: 2x P0 Blocker + 3x P1 High + Staging Failures

**Staging E2E Test:** ⚠️ **CONDITIONAL GO**
- Voraussetzung: P0 Fixes implementiert + Token verified

### **Erforderliche Fixes (vor Staging E2E)**

1. **P0: BUILD_RUN_ID Bug Fix**
   ```yaml
   # In deploy-staging.yml Zeile 176:
   BUILD_RUN_ID=$(... | jq -r --arg sha "$SHA" \
     '[.[] | select(.headSha==$sha and .status=="completed" and .conclusion=="success")][0].databaseId // empty')
   # UND Zeile 177:
   [ -z "$BUILD_RUN_ID" ] || [ "$BUILD_RUN_ID" = "null" ] && { echo "No successful Build"; exit 1; }
   ```

2. **P0: Branch Protection (main)**
   ```
   Required Checks:
   - build-frontend
   - build-backend
   - static-analysis
   - run-tests
   - create-deployment-bundle
   - check-staging
   ```

3. **P1: Token Verification**
   ```bash
   # Compare hashes:
   echo -n "$CI_SECRET" | sha256sum
   # vs
   ssh deploy@152.53.116.127 "grep HEALTHCHECK_TOKEN .env | sha256sum"
   # Must MATCH!
   ```

4. **P1: Staging Failures Root Cause**
   - Analyse der letzten 3 failed runs
   - Fix implementieren
   - Verify mit Test-Deploy

### **Freigabe-Phrasen**

Warte auf eine der folgenden Phrasen:

- **"APPEND MISSING INFO"** + Details → Ergänze Report
- **"STAGING-TEST FREIGEGEBEN"** → Führe Staging E2E aus (nach P0-Fixes!)
- **"PROD-DEPLOY FREIGEGEBEN"** → Führe Production Deploy aus (nach Staging Success!)
- **"PROD-FIX FREIGEGEBEN"** → Führe nur spezifizierte Prod-Aktion aus
- **"ABBRECHEN"** → Stoppe, liefere Status & Empfehlungen

---

## 📚 REFERENZEN

### **Dokumentation (Doku-Hub)**

- [DEPLOYMENT_HANDBUCH_FUER_DRITTE.html](https://api.askproai.de/docs/backup-system/DEPLOYMENT_HANDBUCH_FUER_DRITTE.html)
- [status-quo-deployment-prozess-2025-11-01.html](https://api.askproai.de/docs/backup-system/status-quo-deployment-prozess-2025-11-01.html)
- [E2E_DEPLOYMENT_VALIDATION_FINAL_2025-11-02_1300.html](https://api.askproai.de/docs/backup-system/E2E_DEPLOYMENT_VALIDATION_FINAL_2025-11-02_1300.html)
- [E2E_WORKFLOW_HARDENING_VALIDATION_2025-11-02_1330.html](https://api.askproai.de/docs/backup-system/E2E_WORKFLOW_HARDENING_VALIDATION_2025-11-02_1330.html)
- [PROD_FIX_BUNDLE_GATES.md](https://github.com/fabianSp77/askproai-api/blob/develop/storage/docs/backup-system/PROD_FIX_BUNDLE_GATES.md)

### **Workflows**

- `.github/workflows/build-artifacts.yml`
- `.github/workflows/deploy-staging.yml`
- `.github/workflows/deploy-production.yml`
- `.github/workflows/staging-smoke.yml`

### **Flowchart**

- [DEPLOYMENT_FLOWCHART.md](https://github.com/fabianSp77/askproai-api/blob/develop/storage/docs/backup-system/DEPLOYMENT_FLOWCHART.md) (Commit: ad3cb8d3)

---

**Report Metadata**

- **Erstellt:** 2025-11-02 19:00 UTC
- **Autor:** Claude Code (Automated E2E Planning)
- **Version:** 1.0
- **Format:** Markdown
- **Status:** 🔴 **PLAN ZUERST — WARTET AUF FREIGABE**

---

**⛔ STOP: NICHTS WURDE AUSGEFÜHRT**

Dieser Report ist **nur ein Plan**. Keine Workflows getriggert, keine Server-Änderungen, keine Production-Deployments.

**Warte auf Freigabe-Phrase vom User.**
