# Deployment-Handbuch für AskPro AI Gateway
**Version:** 1.0
**Stand:** 2025-11-02
**Zielgruppe:** Externe Entwickler, DevOps-Teams, neue Teammitglieder

---

## Überblick

Dieses Handbuch beschreibt den vollständigen Deployment-Prozess für die AskPro AI Gateway Applikation. Der Prozess ist **vollautomatisiert** über GitHub Actions und beinhaltet mehrere Sicherheits-Gates.

### Umgebungen

| Umgebung | URL | Server | Zweck |
|----------|-----|--------|-------|
| **Staging** | https://staging.askproai.de | 152.53.116.127 | Test-Umgebung für neue Features |
| **Production** | https://api.askproai.de | 152.53.116.127 | Live-System für Endkunden |

---

## 🚀 Deployment-Flow (Übersicht)

```
Code ändern → Push to develop → Build → Tests → Staging Deploy → Tests → Production Deploy
```

### Zeitaufwand

- **Build:** ~3-5 Minuten
- **Staging Deployment:** ~2-3 Minuten
- **Production Deployment:** ~2-3 Minuten
- **Gesamt:** ~10-15 Minuten

---

## 📋 Voraussetzungen

### GitHub Repository Access

Sie benötigen:
- ✅ Push-Rechte auf das Repository `fabianSp77/askproai-api`
- ✅ Zugriff auf GitHub Actions
- ✅ Optional: `gh` CLI installiert

### Lokale Entwicklung

```bash
# Repository klonen
git clone git@github.com:fabianSp77/askproai-api.git
cd askproai-api

# Dependencies installieren
composer install
npm install
```

---

## 🔄 Deployment-Prozess Schritt-für-Schritt

### Schritt 1: Code-Änderungen vorbereiten

```bash
# Neuen Feature-Branch erstellen
git checkout develop
git pull origin develop
git checkout -b feature/meine-aenderung

# Code ändern, testen
# ...

# Commit erstellen
git add .
git commit -m "feat: Meine neue Funktion"
git push origin feature/meine-aenderung
```

### Schritt 2: Pull Request erstellen

1. Gehen Sie zu: https://github.com/fabianSp77/askproai-api/pulls
2. Klicken Sie auf "New Pull Request"
3. **Base:** `develop` ← **Compare:** `feature/meine-aenderung`
4. Titel und Beschreibung hinzufügen
5. "Create Pull Request"

**Wichtig:** Pull Requests triggern KEINE Builds (nur Dummy-Checks für Branch Protection)

### Schritt 3: Code Review & Merge

1. Code Review durchführen lassen
2. Tests prüfen (alle müssen grün sein)
3. **Merge to develop** (via Squash & Merge empfohlen)

### Schritt 4: Automatischer Build (triggert automatisch)

Nach dem Merge wird automatisch der **Build Artifacts Workflow** gestartet:

**Workflow:** `.github/workflows/build-artifacts.yml`

**Jobs:**
1. ✅ Frontend Build (Vite)
2. ✅ Backend Build (Composer)
3. ✅ Static Analysis (PHPStan)
4. ✅ Tests (Pest)
5. ✅ **Pre-Bundle Gates** (9 Checks)
6. ✅ Bundle erstellen & hochladen

**Wo sehen:**
- https://github.com/fabianSp77/askproai-api/actions
- Filter: "Build Artifacts"
- Status: Grüner Haken = Erfolgreich

**Build-Output:**
- `deployment-bundle-{SHA}.tar.gz` (ca. 21 MB)
- SHA256 Checksum für Verifikation
- Retention: 30 Tage

### Schritt 5: Staging Deployment (manuell triggern)

**Option A: Via GitHub UI**

1. Gehen Sie zu: https://github.com/fabianSp77/askproai-api/actions
2. Workflow: "Deploy to Staging"
3. "Run workflow" → Branch: `develop` → "Run workflow"

**Option B: Via CLI**

```bash
gh workflow run "Deploy to Staging" --ref develop
```

**Deployment-Schritte:**

```
1. Health Check (Staging erreichbar?)           ✅ 10s
2. Pre-Deploy Backup (App + DB)                 ✅ 30s
3. Bundle Download & Verifikation               ✅ 20s
4. Upload to Server                              ✅ 15s
5. Extract Bundle                                ✅ 5s
6. ✅ PRE-SWITCH GATE (9 Checks)                ✅ 5s
7. Run Migrations                                ✅ 10s
8. Clear Caches                                  ✅ 5s
9. Fix Permissions                               ✅ 2s
10. Switch Symlink (Atomic)                     ✅ 1s
11. Reload Services (PHP-FPM, NGINX)            ✅ 5s
12. Grace Period (15s)                          ✅ 15s
13. Health Checks (mit Retry-Logik)             ✅ 10s
```

**Gesamtdauer:** ~2-3 Minuten

**Monitoring:**
- Live-Logs: https://github.com/fabianSp77/askproai-api/actions
- Bei Fehler: **Automatischer Rollback** zur vorherigen Version

**Smoke Tests:**

Nach erfolgreichem Deployment laufen automatisch:

```bash
✅ https://staging.askproai.de/health
✅ https://staging.askproai.de/api/health-check
✅ https://staging.askproai.de/healthcheck.php
✅ https://staging.askproai.de/build/manifest.json
✅ Vite Asset verfügbar?
```

**Ergebnis:** 5/5 Tests müssen bestehen

### Schritt 6: Staging-Tests durchführen

**Manuell testen:**

```bash
# 1. Health Check
curl https://staging.askproai.de/health
# Expected: {"status":"ok","timestamp":"..."}

# 2. API testen
curl https://staging.askproai.de/api/health-check
# Expected: {"status":"healthy","environment":"staging"}

# 3. Frontend testen
# Browser: https://staging.askproai.de
# Expected: Applikation lädt korrekt
```

**Bei Problemen:**

```bash
# Logs auf Server anschauen (benötigt SSH-Zugang)
ssh deploy@152.53.116.127
tail -f /var/www/api-gateway-staging/current/storage/logs/laravel.log
```

### Schritt 7: Production Deployment (nach Freigabe)

**⚠️ WICHTIG:** Production Deployments sollten nur nach erfolgreicher Staging-Validierung durchgeführt werden!

**Trigger-Bedingung:**

Option A: **Merge `develop` → `main`**

```bash
git checkout main
git pull origin main
git merge develop
git push origin main
```

→ Production-Deployment startet **automatisch**

Option B: **Manuell triggern** (Notfall)

```bash
gh workflow run "Deploy to Production" --ref main
```

**Production-Deployment-Schritte:**

```
1. Staging Health Check (ist Staging ok?)       ✅ 10s
2. Pre-Deploy Backup (App + DB + NGINX)         ✅ 60s
3. Bundle Download & Verifikation               ✅ 20s
4. Upload to Server                              ✅ 15s
5. Extract Bundle                                ✅ 5s
6. ✅ PRE-SWITCH GATE (9 Checks)                ✅ 5s
7. Switch Symlink (Atomic)                      ✅ 1s
8. Reload PHP-FPM                                ✅ 5s
9. Health Checks                                 ✅ 10s
10. ✅ ROLLBACK bei Fehler                       ✅ 10s (wenn nötig)
```

**Gesamtdauer:** ~2-3 Minuten

**Zero-Downtime:**
- Atomic Symlink Switch (< 1 Sekunde Downtime)
- Alte Version bleibt verfügbar bis Switch
- Automatischer Rollback bei Fehler

---

## 🛡️ Sicherheits-Gates (4-Schicht-Verteidigung)

### Gate 1: Build-Time (Layer 1)

**Workflow:** `build-artifacts.yml`
**Zeitpunkt:** Vor Bundle-Erstellung
**Checks:** 9

```yaml
✅ artisan existiert
✅ composer.json existiert
✅ public/index.php existiert (CRITICAL)
✅ public/build/manifest.json existiert
✅ vendor/autoload.php existiert (CRITICAL)
✅ bootstrap/ Verzeichnis existiert
✅ config/ Verzeichnis existiert
✅ routes/ Verzeichnis existiert
✅ app/ Verzeichnis existiert
```

**Bei Fehler:** Build schlägt fehl, kein Bundle hochgeladen

### Gate 2: Staging Pre-Switch (Layer 2)

**Workflow:** `deploy-staging.yml`
**Zeitpunkt:** Nach Bundle-Extraktion, VOR Migrations
**Checks:** 9 + PHP Tests

```yaml
✅ 9 Struktur-Checks (wie Gate 1)
✅ PHP Autoload funktioniert
✅ artisan --version funktioniert
```

**Bei Fehler:** Deployment abgebrochen, Symlink NICHT gewechselt

### Gate 3: Production Pre-Switch (Layer 3)

**Workflow:** `deploy-production.yml`
**Zeitpunkt:** Nach Bundle-Extraktion, VOR Symlink-Switch
**Checks:** 9 + PHP Tests

```yaml
✅ 9 Struktur-Checks (wie Gate 1)
✅ PHP Autoload funktioniert
✅ artisan config:cache funktioniert
```

**Bei Fehler:** Production unverändert, automatischer Rollback

### Gate 4: Post-Deployment Smoke Tests (Layer 4)

**Workflow:** `deploy-production.yml` + `staging-smoke.yml`
**Zeitpunkt:** Nach Symlink-Switch
**Checks:** 2-5 Endpoints

```yaml
✅ /health returns HTTP 200
✅ /build/manifest.json verfügbar
```

**Bei Fehler:** Automatischer Rollback zur vorherigen Version

---

## 🔧 Troubleshooting

### Problem: Build schlägt fehl

**Symptom:** Build Artifacts Workflow zeigt rotes X

**Lösung:**

```bash
# 1. Workflow-Logs anschauen
https://github.com/fabianSp77/askproai-api/actions

# 2. Fehler identifizieren (häufig):
# - Composer-Fehler → composer.json prüfen
# - NPM-Fehler → package.json prüfen
# - PHPStan-Fehler → Code-Qualität verbessern
# - Pest-Fehler → Tests fixen

# 3. Lokal reproduzieren
composer install
npm run build
vendor/bin/phpstan analyze
vendor/bin/pest
```

### Problem: Staging Deployment schlägt fehl

**Symptom:** Deploy to Staging Workflow zeigt rotes X

**Häufige Ursachen:**

1. **Pre-Switch Gate Failure**
   - Symptom: "❌ FAILED: index.php missing"
   - Ursache: Bundle unvollständig
   - Lösung: Build-Workflow prüfen (Gate 1)

2. **Health Check Failure**
   - Symptom: "❌ Health check failed after 6 attempts"
   - Ursache: Applikation startet nicht korrekt
   - Lösung: Logs prüfen (`tail -f storage/logs/laravel.log`)

3. **Permissions-Fehler**
   - Symptom: "sudo: Ein Passwort ist notwendig"
   - Ursache: Passwordless sudo fehlt
   - Lösung: Kontaktieren Sie den Server-Admin

**Auto-Rollback:**

Bei Fehler wird automatisch zur vorherigen Version zurückgerollt:

```bash
✅ Rollback completed
Current symlink → releases/PREVIOUS_VERSION
```

### Problem: Production Deployment unsicher

**Frage:** Wie kann ich Production Deployment sicher durchführen?

**Best Practices:**

1. **Immer zuerst Staging testen**
   ```bash
   # 1. Deploy to Staging
   gh workflow run "Deploy to Staging" --ref develop

   # 2. Warten auf Success (2-3 min)
   # 3. Manuell testen: https://staging.askproai.de

   # 4. Erst dann Production
   gh workflow run "Deploy to Production" --ref main
   ```

2. **Smoke Tests beobachten**
   - Live-Logs verfolgen
   - Bei Fehler greift automatischer Rollback

3. **Peak-Times vermeiden**
   - Nicht während Stoßzeiten deployen
   - Optimal: Nachts oder am Wochenende

---

## 📊 Monitoring & Logs

### GitHub Actions Logs

**URL:** https://github.com/fabianSp77/askproai-api/actions

**Filter:**
- "Build Artifacts" → Build-Status
- "Deploy to Staging" → Staging Deployments
- "Deploy to Production" → Production Deployments

**Log-Level:**
- ✅ Grün = Erfolgreich
- 🟡 Gelb = In Progress
- ❌ Rot = Fehler

### Server-Logs (benötigt SSH-Zugang)

**Staging:**
```bash
ssh deploy@152.53.116.127
tail -f /var/www/api-gateway-staging/current/storage/logs/laravel.log
```

**Production:**
```bash
ssh deploy@152.53.116.127
tail -f /var/www/api-gateway/current/storage/logs/laravel.log
```

### Deployment Ledger

Alle Deployments werden protokolliert:

**Location:** `/var/www/api-gateway/backups/deployment_ledger_*.json`

**Beispiel:**
```json
{
  "timestamp": "2025-11-02T12:30:00Z",
  "action": "deploy",
  "environment": "production",
  "bundle": {
    "sha256": "0a95b3ab...",
    "commit": "4144baac",
    "run_id": "19003049369"
  },
  "result": "success",
  "preflight_checks": {
    "public/index.php": "pass",
    "vendor/autoload.php": "pass",
    "artisan_version": "pass"
  }
}
```

---

## 🔐 Zugriff & Berechtigungen

### GitHub Repository

**Benötigt:**
- GitHub Account mit Zugriff auf `fabianSp77/askproai-api`
- Rolle: Developer oder höher (für Merge-Rechte)

**Anfrage:** Kontaktieren Sie Repository-Owner

### Server-Zugriff (optional)

**SSH-Zugang für Debugging:**

```bash
# User: deploy
# Server: 152.53.116.127
# Key: Ed25519
```

**Anfrage:** Kontaktieren Sie Server-Admin

### Documentation Hub

**URL:** https://api.askproai.de/docs/backup-system

**Login:**
- Username: (siehe `.env` → `DOCS_USERNAME`)
- Password: (siehe `.env` → `DOCS_PASSWORD`)

**Inhalt:**
- Deployment-Reports
- E2E Validierungen
- Incident-Tracking
- Backup-System-Status

---

## 📚 Weiterführende Dokumentation

### Technische Details

1. **STATUS_QUO_DEPLOYMENT_PROZESS_2025-11-01.md**
   - Detaillierte IST vs. SOLL Analyse
   - Gate-System Erklärung
   - Flow-Diagramme

2. **E2E_DEPLOYMENT_VALIDATION_FINAL_2025-11-02_1300.html**
   - Validierungs-Reports
   - Test-Ergebnisse
   - Performance-Metriken

3. **PROD_FIX_BUNDLE_GATES.md**
   - Gate-Code-Implementierung
   - Testing-Strategie
   - Evidenz-Sammlung

### Workflow-Dateien

- `.github/workflows/build-artifacts.yml` - Build-Pipeline
- `.github/workflows/deploy-staging.yml` - Staging Deployment
- `.github/workflows/deploy-production.yml` - Production Deployment
- `.github/workflows/staging-smoke.yml` - Smoke Tests

---

## ❓ Häufig gestellte Fragen (FAQ)

### Kann ich direkt auf Production deployen?

**Nein.** Best Practice ist immer:
1. Develop Branch testen
2. Staging Deployment
3. Staging-Tests
4. Production Deployment

### Was passiert bei einem fehlerhaften Deployment?

**Automatischer Rollback:**
- Bei Pre-Switch Gate Failure: Gar kein Wechsel
- Bei Smoke Test Failure: Automatischer Rollback zur vorherigen Version
- Downtime: Maximal ~10 Sekunden

### Wie lange sind Backups verfügbar?

**Backups:**
- Pre-Deploy Backups: 30 Tage
- Location: `/var/www/*/backups/`
- Format: `tar.gz` mit SHA256

### Kann ich einen alten Commit deployen?

**Ja**, via manueller Workflow-Trigger:

```bash
# Zuerst Build für spezifischen Commit
gh workflow run "Build Artifacts" --ref <COMMIT_SHA>

# Dann Deployment
gh workflow run "Deploy to Staging" --ref <COMMIT_SHA>
```

### Wie sehe ich welche Version gerade deployed ist?

**Staging:**
```bash
curl https://staging.askproai.de/health
# → "version": "..."
```

**Production:**
```bash
curl https://api.askproai.de/health
# → "version": "..."
```

Oder auf Server:
```bash
ssh deploy@152.53.116.127
readlink /var/www/api-gateway/current
# → releases/20251102_115313-540bed7f
#                               ^^^^^^^^ Git SHA
```

---

## 📞 Support & Kontakt

### Bei Deployment-Problemen

1. **Check GitHub Actions Logs**
   - https://github.com/fabianSp77/askproai-api/actions

2. **Check Documentation Hub**
   - https://api.askproai.de/docs/backup-system

3. **Kontakt aufnehmen**
   - Repository Owner: fabianSp77
   - Email: fabian@askproai.de

### Incident-Meldung

Bei kritischen Production-Issues:

1. **Sofortiger Rollback:**
   ```bash
   # Via GitHub Actions
   gh workflow run "Deploy to Production" --ref main
   # → Wählt automatisch letztes erfolgreiches Deployment
   ```

2. **Incident loggen:**
   - Documentation Hub → Incident Tracking

---

## ✅ Checkliste: Deployment durchführen

**Vor dem Deployment:**

- [ ] Code-Review durchgeführt
- [ ] Tests laufen lokal grün
- [ ] Feature-Branch in `develop` gemerged
- [ ] Build Artifacts Workflow erfolgreich

**Staging Deployment:**

- [ ] "Deploy to Staging" Workflow getriggert
- [ ] Alle 9 Pre-Switch Gates bestanden
- [ ] Health Checks bestanden (5/5)
- [ ] Manuell getestet: https://staging.askproai.de

**Production Deployment:**

- [ ] Staging seit mindestens 1 Stunde stabil
- [ ] Kein Peak-Time (Stoßzeiten vermeiden)
- [ ] "Deploy to Production" Workflow getriggert
- [ ] Alle 9 Pre-Switch Gates bestanden
- [ ] Smoke Tests bestanden (2/2)
- [ ] Manuell geprüft: https://api.askproai.de

**Nach dem Deployment:**

- [ ] Monitoring für 30 Minuten beobachten
- [ ] Keine Error-Rate-Erhöhung in Logs
- [ ] Deployment im Documentation Hub dokumentiert

---

**Version:** 1.0
**Erstellt:** 2025-11-02
**Zielgruppe:** Externe Entwickler, DevOps-Teams
**Wartung:** Bitte bei Prozess-Änderungen aktualisieren
