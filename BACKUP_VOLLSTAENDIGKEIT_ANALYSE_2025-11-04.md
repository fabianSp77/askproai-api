# Backup-Vollständigkeits-Analyse
**Datum:** 2025-11-04
**Erstellt für:** AskPro AI Gateway
**Zweck:** Sicherstellung 100% vollständiger Wiederherstellbarkeit

---

## 🔍 Status Quo: Was wird AKTUELL gesichert?

### ✅ IM BACKUP ENTHALTEN (223 MB):

#### 1. Datenbank (~180-200 MB komprimiert)
- ✅ Vollständiger mysqldump von `askproai_db`
- ✅ Mit PITR (Point-in-Time Recovery) Support
- ✅ Binlog-Position für Disaster Recovery
- ✅ Routines, Events, Triggers

#### 2. Application Code (~20-30 MB)
- ✅ Alle PHP-Dateien: `app/`, `routes/`, `config/`, `database/`
- ✅ `.env` Datei (KRITISCH für Wiederherstellung)
- ✅ `composer.json`, `composer.lock` (Dependency-Locks)
- ✅ `package.json`, `package-lock.json`
- ✅ `storage/app/` (User Uploads, Exports: ~4.8 MB)
- ✅ `public/` inkl. `public/build/` (Frontend Assets: ~16 MB)

#### 3. System State (~80 KB)
- ✅ Nginx Site Configs (`/etc/nginx/sites-available/`)
- ✅ Crontab
- ✅ SSH Keys
- ✅ System-Info (PHP Version, Laravel Version, Disk Usage)

---

## ❌ NICHT IM BACKUP (363 MB):

### 1. vendor/ (196 MB)
**Was:** Alle Composer Dependencies (Laravel, Filament, Libraries)
**Wiederherstellung:** `composer install` (benötigt Internet + Packagist)

**RISIKEN:**
- ⚠️ **Packagist Down**: Wenn Packagist nicht erreichbar ist
- ⚠️ **Veraltete Pakete gelöscht**: Alte Package-Versionen können von Packagist entfernt werden
- ⚠️ **Private Packages**: Falls Sie private Repos nutzen (auth required)
- ⚠️ **Zeitverlust**: composer install dauert 2-5 Minuten
- 🔴 **KRITISCH bei Disaster Recovery**: Ohne Internet KEINE Wiederherstellung möglich!

### 2. node_modules/ (167 MB)
**Was:** Alle NPM Dependencies (Alpine.js, FullCalendar, Flowbite, etc.)
**Wiederherstellung:** `npm install` (benötigt Internet + NPM Registry)

**RISIKEN:**
- ⚠️ **NPM Registry Down**: Wenn NPM nicht erreichbar ist
- ⚠️ **Package Unpublished**: Packages können von NPM entfernt werden
- ⚠️ **Zeitverlust**: npm install dauert 1-3 Minuten
- 🟡 **WENIGER KRITISCH**: `public/build/` ist gesichert (Pre-built Assets vorhanden)

### 3. Cache/Temp Directories (KORREKT AUSGESCHLOSSEN)
- ✅ `storage/framework/cache/` - Kann neu generiert werden
- ✅ `storage/framework/sessions/` - Temporär, nicht nötig
- ✅ `storage/framework/views/` - Blade Cache, wird neu generiert
- ✅ `storage/logs/*.log` - Nicht für Recovery nötig
- ✅ `.git/` - Code ist in Git Repository

---

## 📊 Größen-Vergleich

| Backup-Strategie | Größe | Wiederherstellung | Internet benötigt? |
|------------------|-------|-------------------|-------------------|
| **AKTUELL (ohne vendor/node_modules)** | 223 MB | 5-10 Minuten | ✅ JA (composer + npm) |
| **VOLLSTÄNDIG (mit vendor/node_modules)** | 586 MB (~600 MB) | 2-3 Minuten | ❌ NEIN |

---

## 🎯 Risiko-Bewertung: Kann das System VOLLSTÄNDIG wiederhergestellt werden?

### Szenario 1: Normale Wiederherstellung (Internet verfügbar)
**Status:** ✅ JA, vollständig wiederherstellbar

**Schritte:**
1. Backup extrahieren → 223 MB
2. Datenbank restore → `mysql < database.sql.gz`
3. Dependencies installieren → `composer install` + `npm install`
4. Cache regenerieren → `php artisan optimize`
5. System läuft → ~10 Minuten

**Risiko:** 🟢 NIEDRIG

---

### Szenario 2: Disaster Recovery (Kein Internet / Notfall)
**Status:** 🔴 UNVOLLSTÄNDIG wiederherstellbar

**Problem:**
- ❌ Ohne Internet: `composer install` SCHLÄGT FEHL
- ❌ Ohne `vendor/`: Laravel läuft NICHT
- ❌ System ist DOWN bis Internet verfügbar

**Risiko:** 🔴 HOCH - System kann nicht offline wiederhergestellt werden!

---

### Szenario 3: Packagist/NPM Probleme
**Status:** 🟡 POTENZIELL PROBLEMATISCH

**Problem:**
- ⚠️ Package-Version nicht mehr verfügbar
- ⚠️ Private Packages benötigen Auth
- ⚠️ Lange Wartezeiten bei langsamer Verbindung

**Risiko:** 🟡 MITTEL - Verzögerungen möglich

---

## ✅ EMPFEHLUNG: 2-Tier Backup-Strategie

### Option A: Standard-Backups (täglich, 223 MB)
**Häufigkeit:** 3x täglich (03:00, 11:00, 19:00)
**Inhalt:** OHNE vendor/node_modules
**Zweck:** Schnelle tägliche Sicherungen
**Retention:** 14 Tage

**Vorteile:**
- ✅ Schneller Upload (223 MB)
- ✅ Weniger Speicherplatz
- ✅ Reicht für normale Wiederherstellungen

---

### Option B: Vollständige Backups (wöchentlich, 586 MB)
**Häufigkeit:** 1x wöchentlich (Sonntag 02:00) + vor Deployments
**Inhalt:** MIT vendor/ + node_modules/
**Zweck:** Disaster Recovery, Offline-Wiederherstellung
**Retention:** 6 Monate

**Vorteile:**
- ✅ 100% offline wiederherstellbar
- ✅ Keine externen Dependencies
- ✅ Schnellere Wiederherstellung (keine composer/npm install)

**Nachteile:**
- ⚠️ 2.6x größer (586 vs 223 MB)
- ⚠️ Längerer Upload zur Synology

---

### Option C: Smart-Backup (EMPFOHLEN)
**Strategie:**
1. **Täglich (3x)**: Standard-Backup ohne Dependencies (223 MB)
2. **Wöchentlich (1x Sonntag)**: Vollständig mit Dependencies (586 MB)
3. **Vor Deployment**: Vollständig mit Dependencies (586 MB)

**Vorteile:**
- ✅ Beste Balance zwischen Geschwindigkeit und Sicherheit
- ✅ Disaster Recovery möglich (wöchentliche Full-Backups)
- ✅ Schnelle tägliche Sicherungen
- ✅ Überschaubarer Speicherbedarf

**Speicherbedarf (30 Tage):**
- Täglich: 14 × 223 MB = 3.1 GB
- Wöchentlich: 4 × 586 MB = 2.3 GB
- **GESAMT:** ~5.4 GB

---

## 🔧 Implementierungs-Optionen

### Option 1: Separate Full-Backup Funktion
```bash
# Neue Funktion in backup-run.sh
backup_application_full() {
    # Backup MIT vendor/node_modules
    tar -czf "$app_file" \
        -C "$PROJECT_ROOT" \
        --exclude="storage/framework/cache" \
        --exclude="storage/framework/sessions" \
        --exclude="storage/framework/views" \
        --exclude="storage/logs/*.log" \
        --exclude=".git" \
        . # OHNE --exclude vendor und node_modules
}
```

### Option 2: Env-Variable für Full-Backup
```bash
# In backup-run.sh vor backup_application():
if [ "${FULL_BACKUP:-false}" = "true" ]; then
    backup_application_full
else
    backup_application  # Standard (ohne vendor/node_modules)
fi
```

### Option 3: Separates Wochenend-Script
```bash
# scripts/backup-run-full.sh
# Ruft backup-run.sh mit FULL_BACKUP=true auf
```

---

## 📅 Vorgeschlagener Cron-Schedule

```bash
# AKTUELL:
0 3,11,19 * * * /var/www/api-gateway/scripts/backup-run.sh

# NEU - Smart Strategy:
# Täglich Standard (Mo-Sa)
0 3,11,19 * * 1-6 /var/www/api-gateway/scripts/backup-run.sh

# Sonntag: Vollständiges Backup
0 2 * * 0 FULL_BACKUP=true /var/www/api-gateway/scripts/backup-run.sh

# Vor jedem Deployment (manuell):
# FULL_BACKUP=true ./scripts/backup-run.sh
```

---

## 🎯 Zusammenfassung

### AKTUELLER Stand:
- ✅ Datenbank: VOLLSTÄNDIG
- ✅ Code: VOLLSTÄNDIG
- ✅ User-Daten: VOLLSTÄNDIG
- ❌ Dependencies: FEHLEN (vendor/, node_modules/)

### RISIKO:
- 🟢 Mit Internet: System vollständig wiederherstellbar
- 🔴 Ohne Internet: System NICHT wiederherstellbar (vendor/ fehlt)

### EMPFEHLUNG:
**Option C (Smart-Backup) implementieren:**
- Täglich: Standard-Backups (223 MB, schnell)
- Wöchentlich: Full-Backups (586 MB, vollständig)
- Deployment: Full-Backup (Pre-Deploy Safety)

**Ergebnis:**
- ✅ 100% Offline-Wiederherstellbarkeit
- ✅ Minimaler Overhead (~5.4 GB statt 3.1 GB)
- ✅ Keine externen Abhängigkeiten für Recovery

---

## ❓ Entscheidung erforderlich

Welche Option möchten Sie implementieren?

1. **Alles vollständig (586 MB, 3x täglich)** → Maximale Sicherheit, mehr Speicher
2. **Smart-Backup (Mix aus Standard + Full)** → Balance (EMPFOHLEN)
3. **Status Quo beibehalten** → Risiko bei Offline-Recovery akzeptieren

---

**Nächste Schritte nach Entscheidung:**
1. backup-run.sh anpassen
2. Crontab aktualisieren
3. Test-Backup durchführen
4. Wiederherstellungs-Test (inkl. Full-Backup ohne Internet)
