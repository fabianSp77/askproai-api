# Documentation Hub - Strategie & Wartung

**Datum**: 2025-11-01
**Status**: ✅ Production-Ready
**Version**: 2.0

---

## 🎯 Strategische Ziele

Der Documentation Hub folgt einem **Drei-Schichten-Prinzip** mit intelligenter Kategorisierung und automatischer Synchronisation.

### Kernprinzipien

1. **Aktualität**: Kritische Docs sind immer up-to-date
2. **Automatisierung**: Kein manuelles Kopieren nötig
3. **Kategorisierung**: Intelligente Zuordnung basierend auf Dateinamen
4. **Wartbarkeit**: Klare Layer-Struktur für einfache Pflege

---

## 📊 Drei-Schichten-Architektur

### Layer 1: Essentials (Immer aktuell)
**Auto-Sync nach jedem Deployment**

```
EXECUTIVE_SUMMARY.md
FINAL_VALIDATION_REPORT.md
DEPLOYMENT_HARDENING_COMPLETE.md
BACKUP_AUTOMATION.md
BACKUP_NOTIFICATIONS_FINAL.md
```

**Charakteristik**:
- Werden nach jedem Production-Deployment automatisch aktualisiert
- Enthalten kritische Management-Informationen
- Müssen immer den neuesten Stand reflektieren

### Layer 2: Operational (Bei Änderung)
**Sync bei relevanten Updates**

```
DEPLOYMENT_HARDENING_SUMMARY.md
DEPLOYMENT_QUICK_START.md
DEPLOYMENT_VERIFICATION_CHECKLIST.md
EMAIL_NOTIFICATIONS_SETUP.md
SYNOLOGY_SETUP.md
MANUAL_TESTING_GUIDE_2025-10-27.md
```

**Charakteristik**:
- Operationale Anleitungen und Prozesse
- Ändern sich bei Workflow-Updates
- Sync erfolgt wenn Source-Datei neuer ist

### Layer 3: Historical (Archiv)
**Manuell gepflegt**

```
INCIDENT_REPORT_MIGRATION_FIX_2025-10-28.md
DEPLOYMENT_FIX_REPORT_2025-10-29.md
RCA-Dokumente
```

**Charakteristik**:
- Incident Reports und Root Cause Analyses
- Historische Referenz
- Werden manuell bei Bedarf hinzugefügt

---

## 🗂️ Intelligente Kategorisierung

### Automatische Kategorie-Zuweisung

Die Kategorisierung erfolgt automatisch in der API (`routes/web.php`) basierend auf Dateinamen:

```php
// Backup & PITR
if (str_contains($filename, 'BACKUP') ||
    str_contains($filename, 'PITR') ||
    str_contains($filename, 'NAS')) {
    $category = 'Backup & PITR';
}

// Deployment & Gates
elseif (str_contains($filename, 'DEPLOY') ||
        str_contains($filename, 'STAGING')) {
    $category = 'Deployment & Gates';
}

// Testing & Validation
elseif (str_contains($filename, 'TEST') ||
        str_contains($filename, 'VALIDATION')) {
    $category = 'Testing & Validation';
}

// E-Mail & Notifications
elseif (str_contains($filename, 'EMAIL') ||
        str_contains($filename, 'NOTIFICATION')) {
    $category = 'E-Mail & Notifications';
}

// Incident Reports & Fixes
elseif (str_contains($filename, 'INCIDENT') ||
        str_contains($filename, 'RCA') ||
        str_contains($filename, 'FIX')) {
    $category = 'Incident Reports & Fixes';
}
```

### Verfügbare Kategorien

| Kategorie | Icon | Beispiel-Docs | Anzahl |
|-----------|------|---------------|--------|
| Hub & Index | 🏠 | index.html, INDEX.md, status.json | 4 |
| Executive / Management | 👔 | EXECUTIVE_SUMMARY.md | 6 |
| Backup & PITR | 💾 | BACKUP_AUTOMATION.md, Zero-Loss PDF | 4 |
| Deployment & Gates | 🚀 | DEPLOYMENT_QUICK_START.md | 9 |
| Testing & Validation | 🧪 | MANUAL_TESTING_GUIDE.md | 3 |
| E-Mail & Notifications | 📧 | EMAIL_NOTIFICATIONS_SETUP.md | 2 |
| Incident Reports & Fixes | 🔥 | INCIDENT_REPORT_*.md | 1 |
| UX & Documentation | 🎨 | DOCUMENTATION_HUB_*.md | - |
| Security & Access | 🔒 | AUTH-bezogene Docs | - |

---

## 🔄 Automatisierung

### Sync-Script: `docs-sync.sh`

**Location**: `/var/www/api-gateway/scripts/docs-sync.sh`

**Features**:
- ✅ Layer 1 & 2 Auto-Sync
- ✅ Timestamp-basierte Aktualisierung (nur wenn neuer)
- ✅ Automatisches Ownership-Fix (www-data:www-data)
- ✅ Cleanup-Modus für veraltete Dateien
- ✅ Detaillierte Logs

**Verwendung**:

```bash
# Standard-Sync (nur geänderte Dateien)
cd /var/www/api-gateway
bash scripts/docs-sync.sh

# Cleanup alte/veraltete Dateien
bash scripts/docs-sync.sh --cleanup

# Force-Update (alle Dateien neu)
bash scripts/docs-sync.sh --force
```

**Output-Beispiel**:

```
🔄 Starte Dokumentations-Sync...
📋 Synchronisiere kritische Dokumente (Layer 1)...
  ✅ Aktualisiert: EXECUTIVE_SUMMARY.md
  ⏭️  Übersprungen: BACKUP_AUTOMATION.md (bereits aktuell)
  ✅ Aktualisiert: FINAL_VALIDATION_REPORT.md

📁 Synchronisiere operational Dokumente (Layer 2)...
  ✅ Aktualisiert: DEPLOYMENT_QUICK_START.md

╔══════════════════════════════════════════╗
║  Sync abgeschlossen                      ║
╚══════════════════════════════════════════╝
  ✅ Aktualisiert: 3 Dateien
  ⏭️  Übersprungen: 7 Dateien
```

### Deployment-Pipeline Integration

**Workflow**: `.github/workflows/deploy-production.yml`

**Step #11**: Sync Documentation Hub

```yaml
- name: Sync Documentation Hub
  if: success()
  run: |
    ssh -i ~/.ssh/production_key \
        ${{ env.PRODUCTION_USER }}@${{ env.PRODUCTION_HOST }} << 'EOF'

    echo "📚 Syncing Documentation Hub..."
    cd "${{ env.PRODUCTION_BASE_DIR }}/current"

    if [ -f "scripts/docs-sync.sh" ]; then
        bash scripts/docs-sync.sh
        echo "✅ Documentation Hub synchronized"
    fi
    EOF
```

**Trigger**: Nach erfolgreichen Health Checks, vor Cleanup

**Ablauf**:
1. Deployment läuft durch
2. Health Checks bestehen
3. **→ docs-sync.sh wird ausgeführt**
4. Hub ist automatisch aktuell
5. Alte Releases werden aufgeräumt

---

## 📁 Datei-Organisation

### Struktur

```
/var/www/api-gateway/
├── storage/docs/backup-system/      # Hub Storage
│   ├── index.html                   # Hub UI
│   ├── status.json                  # Live-Status
│   ├── *.md                         # Markdown Docs (30 files)
│   ├── *.html                       # HTML Docs
│   └── *.pdf                        # PDFs (919 KB)
│
├── scripts/
│   └── docs-sync.sh                 # Sync Script
│
└── .github/workflows/
    └── deploy-production.yml        # Pipeline mit Auto-Sync
```

### Aktueller Bestand

**Gesamt**: 30 Dateien (1.2 MB)
- 26 Markdown-Dateien
- 2 HTML-Dateien (index.html, deployment-release.html)
- 1 PDF (Zero-Loss-Backups-and-Deployment.pdf)
- 1 JSON (status.json)

---

## 🔧 Wartung & Best Practices

### Neue Dokumentation hinzufügen

**Option A: Automatisch (empfohlen)**
1. Datei im Root erstellen mit passender Namenskonvention
2. Zur entsprechenden Layer-Liste in `docs-sync.sh` hinzufügen
3. Nächstes Deployment synct automatisch

**Option B: Manuell**
```bash
# Datei in Hub kopieren
cp NEUE_DOKU.md storage/docs/backup-system/

# Ownership anpassen
chown www-data:www-data storage/docs/backup-system/NEUE_DOKU.md
```

### Veraltete Dokumentation entfernen

**Option A: Via Script**
```bash
bash scripts/docs-sync.sh --cleanup
```

**Option B: Manuell**
```bash
rm storage/docs/backup-system/ALTE_DATEI.md
```

### Namenskonventionen

**Format**: `[KATEGORIE]_[TYP]_[THEMA]_[DATUM?].md`

**Beispiele**:
- ✅ `DEPLOY_GUIDE_QUICK_START.md` → Deployment & Gates
- ✅ `BACKUP_SUMMARY_EXECUTIVE.md` → Backup & PITR
- ✅ `TEST_CHECKLIST_DEPLOYMENT_2025-10-30.md` → Testing & Validation
- ✅ `INCIDENT_REPORT_MIGRATION_FIX_2025-10-28.md` → Incident Reports

**Wichtig**: Konsistente Keywords nutzen für Auto-Kategorisierung!

---

## 🚀 Quick Reference

### URLs

```
Hub:        https://api.askproai.de/docs/backup-system/
API Files:  https://api.askproai.de/docs/backup-system/api/files
Status:     https://api.askproai.de/docs/backup-system/status.json
```

### Login

```
Username: fabian
Password: Qwe421as1!11
```

### Manuelle Sync

```bash
cd /var/www/api-gateway
bash scripts/docs-sync.sh
```

### Check Kategorien

```bash
curl -u "fabian:Qwe421as1!11" -s \
  https://api.askproai.de/docs/backup-system/api/files | \
  jq -r '.files | group_by(.category) |
         map({category: .[0].category, count: length})'
```

---

## 📊 Metriken & Monitoring

### KPIs

- **Sync-Frequenz**: Nach jedem Production-Deployment (~1-5x täglich)
- **Update-Zeit**: <5 Sekunden
- **Dateien pro Kategorie**: 1-9 (optimal: 3-7)
- **Hub-Größe**: ~1.2 MB (optimal: <5 MB)

### Monitoring

**Check 1: Sind kritische Docs aktuell?**
```bash
# Vergleiche Timestamps
ls -lht /var/www/api-gateway/EXECUTIVE_SUMMARY.md
ls -lht /var/www/api-gateway/storage/docs/backup-system/EXECUTIVE_SUMMARY.md
```

**Check 2: Läuft Auto-Sync?**
```bash
# Prüfe letzten Deployment-Log
gh run list --workflow=deploy-production.yml --limit=1
gh run view --log | grep "Documentation Hub"
```

**Check 3: Hub erreichbar?**
```bash
curl -u "fabian:Qwe421as1!11" -s \
  https://api.askproai.de/docs/backup-system/ | grep -q "Dokumentations-Hub"
echo $? # 0 = OK
```

---

## 🔍 Troubleshooting

### Problem: Docs werden nicht synchronisiert

**Ursache**: docs-sync.sh wird nicht ausgeführt oder hat keine Rechte

**Lösung**:
```bash
# Rechte prüfen
ls -lh /var/www/api-gateway/scripts/docs-sync.sh

# Executable setzen
chmod +x /var/www/api-gateway/scripts/docs-sync.sh

# Manuell ausführen
bash /var/www/api-gateway/scripts/docs-sync.sh
```

### Problem: Kategorien falsch

**Ursache**: Dateiname passt nicht zu Kategorisierungs-Regeln

**Lösung**: Datei umbenennen oder Regeln in `routes/web.php` anpassen

### Problem: Hub zeigt 404

**Ursache**: Nginx/Laravel Routing-Problem

**Lösung**:
```bash
# Cache clearen
php artisan route:clear
php artisan config:clear

# Nginx reload
sudo systemctl reload nginx
```

---

## 📚 Weiterführende Dokumentation

- **Deployment**: `DEPLOYMENT_HARDENING_COMPLETE.md`
- **Backup-System**: `BACKUP_AUTOMATION.md`
- **Testing**: `MANUAL_TESTING_GUIDE_2025-10-27.md`
- **Hub-Features**: `DOCUMENTATION_HUB_IMPROVEMENTS.md`

---

## ✅ Changelog

### Version 2.0 (2025-11-01)
- ✅ Drei-Schichten-Architektur implementiert
- ✅ Intelligente Kategorisierung (8 Kategorien)
- ✅ Auto-Sync Script (`docs-sync.sh`)
- ✅ Deployment-Pipeline Integration
- ✅ +10 essentielle Docs hinzugefügt
- ✅ -5 veraltete Docs entfernt
- ✅ Neue Kategorien: Testing, E-Mail, Incident Reports

### Version 1.0 (2025-10-30)
- ✅ Initiale Hub-Erstellung
- ✅ Basic Authentication
- ✅ 5 Basis-Kategorien
- ✅ 25 Dateien

---

**Maintainer**: Claude Code
**Last Updated**: 2025-11-01
**Status**: Production
**Review Cycle**: Monatlich
