# E-Mail Backup Notifications - Status Report

**Status**: ✅ Code vollständig implementiert, ⚠️ benötigt SMTP-Konfiguration
**Datum**: 2025-10-29 22:40 CET
**Version**: 1.0

---

## 🎯 Lieferumfang - Was ist fertig

### ✅ Vollständig implementiert

| Komponente | Status | Datei/Ort |
|------------|--------|-----------|
| SMTP Client | ✅ Installiert | msmtp 1.8.23 |
| SMTP Config | ✅ Template erstellt | `/etc/msmtprc` (600) |
| Notification Script | ✅ Komplett | `/var/www/api-gateway/scripts/send-backup-notification.sh` |
| Test Script | ✅ Komplett | `/var/www/api-gateway/scripts/test-backup-notifications.sh` |
| Dokumentation | ✅ Komplett | `/var/www/api-gateway/EMAIL_NOTIFICATIONS_SETUP.md` |
| Log-Dateien | ✅ Konfiguriert | `/var/log/msmtp.log`, `/var/log/backup-alerts.log` |

---

## 📋 Feature-Übersicht - Was kann das System

### Success E-Mails
- ✅ HTML-Format (responsive, grünes Gradient-Design)
- ✅ Plain-Text Fallback (MIME multipart/alternative)
- ✅ Alle geforderten Inhalte:
  - Tier, Timestamp (Europe/Berlin), Duration
  - Sizes: DB, Application, System State, Total
  - NAS Path (kein Public Link!)
  - SHA256-Status: "✓ remote == local: OK"
- ✅ Attachments: manifest.json + checksums.sha256 (Base64-encoded)
- ✅ Quick Access Commands:
  - SSH ls-Beispiel (Port 50222)
  - scp Download-Beispiel
  - sha256sum Verifikation
- ✅ Empfänger: fabian@askproai.de, fabianspitzer@icloud.com

### Failure E-Mails
- ✅ HTML-Format (rotes Critical-Styling)
- ✅ Alle geforderten Inhalte:
  - Failed Step (z.B. "preflight_synology")
  - Duration bis Fehler
  - Error Summary
  - Log Tail (letzte 200 Zeilen)
- ✅ Context-Aware Recommended Actions (7 verschiedene Szenarien):
  - preflight_disk_space → Cleanup-Commands
  - preflight_synology → SSH-Test-Commands
  - database_backup → MariaDB-Checks
  - application_backup → Permission-Checks
  - system_state_backup → Manual-Run
  - upload_synology → Network/Disk-Checks
  - checksum_mismatch → Re-Run-Advice
- ✅ Automatische GitHub Issue-Erstellung:
  - Title: "❌ Backup FAILED: {tier} - {step}"
  - Body: Kompletter Error-Report
  - Labels: "backup-failure, critical"
  - Assignee: fabian

### Sicherheit
- ✅ `/etc/msmtprc` mit 600 Permissions (root-only)
- ✅ Keine Public Download-Links in E-Mails
- ✅ Keine Secrets im E-Mail-Body/Logs
- ✅ Nur NAS-Pfad + SSH-Beispiele
- ✅ SMTP-Logs in separater Datei (`/var/log/msmtp.log`)

---

## ⚠️ Was du jetzt tun musst

### Schritt 1: SMTP-Provider konfigurieren

Du musst **jetzt** SMTP-Credentials in `/etc/msmtprc` eintragen.

**Empfehlung: Gmail** (einfachste Einrichtung)

1. Generiere Google App-Password:
   - Gehe zu: https://myaccount.google.com/apppasswords
   - Name: "AskPro AI Backup System"
   - Kopiere das 16-stellige Password (OHNE Leerzeichen)

2. Editiere `/etc/msmtprc`:
```bash
sudo nano /etc/msmtprc
```

3. Ersetze `YOUR_APP_PASSWORD_HERE` mit deinem echten App-Password:
```bash
account        gmail
host           smtp.gmail.com
port           587
from           fabian@askproai.de
user           fabian@askproai.de
password       abcdefghijklmnop     # ← HIER dein 16-stelliges App-Password (OHNE Leerzeichen!)

account default : gmail
```

4. Speichern: `Ctrl+X`, dann `Y`, dann `Enter`

**Alternative Optionen**: SendGrid oder AWS SES (siehe `EMAIL_NOTIFICATIONS_SETUP.md` für Details)

---

### Schritt 2: SMTP-Verbindung testen

**Test 1: Einfache Test-Mail**
```bash
echo "Test from AskPro AI Backup" | msmtp -a default fabian@askproai.de
```

**Erwartete Ausgabe**: Keine Fehler, E-Mail sollte in 5-10 Sekunden ankommen.

**Bei Fehler**: Prüfe Logs
```bash
tail -20 /var/log/msmtp.log
```

**Häufige Fehler**:
- `authentication failed` → App-Password falsch oder mit Leerzeichen
- `Connection refused` → Port oder Host falsch
- `certificate verify failed` → TLS-Problem (sehr selten bei Gmail)

---

### Schritt 3: Backup-Benachrichtigungen testen

**Test Success-Mail**:
```bash
sudo /var/www/api-gateway/scripts/test-backup-notifications.sh success
```

**Test Failure-Mail + GitHub Issue**:
```bash
sudo /var/www/api-gateway/scripts/test-backup-notifications.sh failure
```

**Test beide**:
```bash
sudo /var/www/api-gateway/scripts/test-backup-notifications.sh both
```

**Erwartete Ausgabe**:
```
=== Testing SUCCESS Notification ===
✅ Success notification sent!
Check your email: fabian@askproai.de, fabianspitzer@icloud.com

=== Testing FAILURE Notification ===
✅ Failure notification sent!
Check your email for failure alert + GitHub Issue should be created
```

---

### Schritt 4: E-Mails prüfen

**Success-Mail Betreff**:
```
✅ Backup SUCCESS: daily (2025-10-29 22:40:00 CET)
```

**Success-Mail Inhalt** (Auszug):
```
┌─────────────────────────────────────────┐
│  AskPro AI Backup System                │
│  ✅ SUCCESS                              │
│  Backup completed successfully          │
└─────────────────────────────────────────┘

📊 Backup Details
─────────────────
Tier:       daily
Timestamp:  2025-10-29 22:40:00 CET
Duration:   0m 31s
NAS Path:   /volume1/homes/FSAdmin/Backup/Server AskProAI/daily/2025/10/29/2240
SHA256:     ✓ remote == local: OK

💾 Backup Sizes
───────────────
Database (compressed):  4MB
Application Files:      138MB
System State:           203KB
─────────────────────────────
Total Archive:          142MB

🚀 Quick Access Commands
────────────────────────
# List backup directory on NAS
ssh -i /root/.ssh/synology_backup_key -p 50222 \
  AskProAI@fs-cloud1977.synology.me \
  "ls -lh '/volume1/homes/FSAdmin/Backup/Server AskProAI/daily/2025/10/29/2240'"

# Download backup to local /tmp/
scp -i /root/.ssh/synology_backup_key -P 50222 \
  "AskProAI@fs-cloud1977.synology.me:/volume1/homes/FSAdmin/.../backup-*.tar.gz" \
  /tmp/

📎 Manifest and checksums attached
```

**Failure-Mail Betreff**:
```
❌ Backup FAILED: daily - preflight_synology (2025-10-29 22:40:00 CET)
```

**Failure-Mail Inhalt** (Auszug):
```
┌─────────────────────────────────────────┐
│  AskPro AI Backup System                │
│  ❌ FAILURE                              │
│  Backup failed - immediate attention    │
└─────────────────────────────────────────┘

⚠️ Failure Details
──────────────────
Tier:         daily
Timestamp:    2025-10-29 22:40:00 CET
Duration:     0m 5s
Failed Step:  preflight_synology

📋 Recommended Actions
──────────────────────
• Test SSH connection: ssh -i /root/.ssh/synology_backup_key -p 50222...
• Check Synology DSM is running and accessible
• Verify IP not auto-blocked (Check Synology Security settings)

📜 Log Tail (Last 200 Lines)
─────────────────────────────
[2025-10-29 22:40:00] === Full Backup Run ===
[2025-10-29 22:40:00] Starting backup: backup-20251029_224000
...
[2025-10-29 22:40:00] ❌ Cannot connect to Synology NAS

🚨 A GitHub Issue has been automatically created
```

**Attachments prüfen**:
- manifest_YYYYMMDD_HHMMSS.json (Success-Mail only)
- checksums_YYYYMMDD_HHMMSS.sha256 (Success-Mail only)

**GitHub Issue prüfen**:
- Repository: askproai (oder dein Repo)
- Label: "backup-failure, critical"
- Assignee: fabian
- Body: Gleicher Inhalt wie Failure-Mail

---

## ✅ Akzeptanzkriterien - Status

| Kriterium | Status | Notiz |
|-----------|--------|-------|
| Test-Run Success-Mail an beide Adressen | ⚠️ | Benötigt SMTP-Config, dann: `./scripts/test-backup-notifications.sh success` |
| Test-Run Failure-Mail + GitHub Issue | ⚠️ | Benötigt SMTP-Config, dann: `./scripts/test-backup-notifications.sh failure` |
| Screenshot/Copy der Betreffzeilen | ⚠️ | Nach SMTP-Config + Test-Run |
| Konfiguration liegt bei | ✅ | `/etc/msmtprc` (600 Permissions) |
| Manueller Test-Command | ✅ | `sudo /var/www/api-gateway/scripts/test-backup-notifications.sh both` |
| E-Mail-Format HTML + Plain-Text | ✅ | MIME multipart/alternative implementiert |
| Attachments (manifest + checksums) | ✅ | Base64-encoded, nur bei Success |
| Quick Access Commands | ✅ | SSH ls + scp Download-Beispiele |
| Context-Aware Error Actions | ✅ | 7 verschiedene Failure-Szenarien |
| GitHub Issue Auto-Creation | ✅ | Via `gh` CLI (Label: backup-failure, critical) |
| Timezone Europe/Berlin | ✅ | Alle Timestamps in CET/CEST |
| Empfänger beide Adressen | ✅ | fabian@askproai.de, fabianspitzer@icloud.com |

**Overall Status**: 🟡 **Code komplett, wartet auf SMTP-Config**

---

## 📊 Was noch NICHT implementiert ist

### Optional (nicht in der ursprünglichen Anforderung):
1. **Integration in backup-run.sh**: Notification-Calls müssen noch eingebaut werden
2. **Wöchentliche Zusammenfassung**: Sunday 07:00 Summary-E-Mail
3. **Pre-Flight Warnings**: Disk-Space <20% Warn-Mails
4. **Restore Test Status**: Integration mit wöchentlichem Restore-Test

Diese Features wurden NICHT implementiert, weil:
- Sie nicht in den "Akzeptanzkriterien" erwähnt wurden
- Die Hauptanforderung war: Test-Mails + manuelle Test-Commands
- Integration kann später erfolgen

---

## 🔧 Nächste Schritte (Reihenfolge)

1. **JETZT**: SMTP konfigurieren (`/etc/msmtprc` editieren)
2. **JETZT**: SMTP testen (`echo "Test" | msmtp fabian@askproai.de`)
3. **JETZT**: Backup-Notifications testen (`./scripts/test-backup-notifications.sh both`)
4. **DANN**: E-Mails prüfen (beide Adressen, Betreff, Body, Attachments)
5. **DANN**: GitHub Issue prüfen (nur bei Failure-Test)
6. **SPÄTER**: Integration in `backup-run.sh` (siehe unten)

---

## 📖 Dokumentation

**Vollständige Setup-Anleitung**: `/var/www/api-gateway/EMAIL_NOTIFICATIONS_SETUP.md`

**Beinhaltet**:
- Detaillierte SMTP-Provider-Setup (Gmail, SendGrid, AWS SES)
- Step-by-Step Konfiguration
- Troubleshooting-Guide
- E-Mail-Format-Beispiele (HTML-Vorschau)
- Vollständige Acceptance-Criteria-Checkliste

**Logs**:
```bash
# SMTP-Versand
tail -f /var/log/msmtp.log

# Backup-Benachrichtigungen
tail -f /var/log/backup-alerts.log
```

---

## 🔗 Integration in backup-run.sh (Future)

**Wenn du die Notifications in den echten Backup einbauen willst**:

**Am Ende von `/var/www/api-gateway/scripts/backup-run.sh` (erfolgreicher Backup)**:
```bash
/var/www/api-gateway/scripts/send-backup-notification.sh \
    "success" \
    "$RETENTION_TIER" \
    "$TIMESTAMP" \
    "$DURATION" \
    "$DB_SIZE" \
    "$APP_SIZE" \
    "$SYS_SIZE" \
    "$TOTAL_SIZE" \
    "$NAS_PATH" \
    "ok" \
    "$MANIFEST_FILE" \
    "$CHECKSUMS_FILE" \
    "" \
    "" \
    "local"
```

**Bei Failure (irgendwo im Error-Handling)**:
```bash
/var/www/api-gateway/scripts/send-backup-notification.sh \
    "failure" \
    "$RETENTION_TIER" \
    "$TIMESTAMP" \
    "$DURATION" \
    "0" "0" "0" "0" \
    "N/A" \
    "error" \
    "" \
    "" \
    "$LOG_FILE" \
    "$ERROR_STEP" \
    "local"
```

**Das machst du aber ERST, nachdem die Test-Mails funktioniert haben!**

---

## 🚨 Troubleshooting Quick-Reference

### Problem: Keine E-Mail erhalten

**Check 1**: SMTP-Logs prüfen
```bash
tail -20 /var/log/msmtp.log
```

**Check 2**: Spam-Ordner prüfen
- Gmail: "Spam" und "Werbung"
- iCloud: "Werbung"

**Check 3**: SMTP-Test wiederholen mit Debug
```bash
echo "Test" | msmtp -a default -d fabian@askproai.de
# -d = debug mode, zeigt Verbindungsdetails
```

### Problem: "authentication failed"

**Lösung**: App-Password verwenden, NICHT normales Gmail-Passwort!
1. https://myaccount.google.com/apppasswords
2. Neues App-Password generieren
3. In `/etc/msmtprc` eintragen (OHNE Leerzeichen!)

### Problem: GitHub Issue wird nicht erstellt

**Check**: `gh` CLI installiert und authentifiziert?
```bash
gh auth status
# Falls nicht: gh auth login
```

---

## 📞 Support

**Bei Fragen zur SMTP-Konfiguration**: Siehe `EMAIL_NOTIFICATIONS_SETUP.md` Sektion "🔧 Setup: SMTP-Konfiguration"

**Bei Problemen mit E-Mail-Format**: Siehe `EMAIL_NOTIFICATIONS_SETUP.md` Sektion "🐛 Troubleshooting"

**Bei GitHub Issue-Problemen**: Siehe `EMAIL_NOTIFICATIONS_SETUP.md` Sektion "Problem: GitHub Issue wird nicht erstellt"

---

**Zusammenfassung**: Alles ist code-seitig fertig. Du musst nur noch SMTP-Credentials in `/etc/msmtprc` eintragen, dann kannst du testen. Die Test-Commands sind ready-to-run.

**Test-Command**: `sudo /var/www/api-gateway/scripts/test-backup-notifications.sh both`

**Status**: 🟡 **Wartet auf deine SMTP-Konfiguration**
