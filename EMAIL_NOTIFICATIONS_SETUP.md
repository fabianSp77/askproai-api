# E-Mail Backup Notifications - Setup Guide

**Status**: ⚠️ Konfiguration erforderlich
**Erstellt**: 2025-10-29
**Version**: 1.0

---

## 🎯 Überblick

State-of-the-Art E-Mail-Benachrichtigungssystem für alle Backup-Operationen mit:

- ✅ E-Mail bei jedem Backup-Run (Success & Fail)
- ✅ HTML + Plain-Text Format (responsiv, auditierbar)
- ✅ Manifest + Checksums als Attachments
- ✅ Quick-Access-Commands (ls, scp) direkt in E-Mail
- ✅ Automatische GitHub Issue-Erstellung bei Failures
- ✅ Empfänger: fabian@askproai.de, fabianspitzer@icloud.com
- ✅ Zeitzone: Europe/Berlin (CET/CEST) in allen Timestamps

---

## 📋 Komponenten

| Datei | Zweck |
|-------|-------|
| `/etc/msmtprc` | SMTP-Konfiguration (600, root-only) |
| `scripts/send-backup-notification.sh` | E-Mail-Generator mit HTML/Attachments |
| `scripts/test-backup-notifications.sh` | Test-Script für Success/Failure |
| `/var/log/msmtp.log` | SMTP-Versandlog |
| `/var/log/backup-alerts.log` | Benachrichtigungs-Log |

---

## 🔧 Setup: SMTP-Konfiguration

### Schritt 1: SMTP-Provider wählen

**Option A: Gmail / Google Workspace** (Empfohlen für einfache Einrichtung)

```bash
# 1. Generiere App-Password in Google Account
# https://myaccount.google.com/apppasswords
# Name: "AskPro AI Backup System"

# 2. Konfiguriere msmtprc
sudo nano /etc/msmtprc
```

**Konfiguration für Gmail**:
```bash
account        gmail
host           smtp.gmail.com
port           587
from           fabian@askproai.de
user           fabian@askproai.de    # Deine Gmail-Adresse
password       abcd efgh ijkl mnop   # 16-stelliges App-Password (OHNE Leerzeichen in Config!)

account default : gmail
```

**Option B: SendGrid** (Professioneller Mail-Service)

```bash
# 1. SendGrid Account erstellen: https://sendgrid.com
# 2. API Key generieren: Settings → API Keys → Create API Key

account        sendgrid
host           smtp.sendgrid.net
port           587
from           fabian@askproai.de
user           apikey               # Literal "apikey"
password       SG.xxxxxxxxxxxxxxx   # Dein SendGrid API Key

account default : sendgrid
```

**Option C: AWS SES** (Falls AWS genutzt wird)

```bash
# 1. AWS SES SMTP Credentials generieren
# Console → SES → SMTP Settings → Create My SMTP Credentials

account        ses
host           email-smtp.eu-central-1.amazonaws.com
port           587
from           fabian@askproai.de
user           AKIAXXXXXXXXXXXXXXXX  # SMTP Username
password       BxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxX  # SMTP Password

account default : ses
```

### Schritt 2: Permissions setzen

```bash
sudo chmod 600 /etc/msmtprc
ls -la /etc/msmtprc
# Muss sein: -rw------- 1 root root
```

### Schritt 3: Test-Mail senden

```bash
# Einfacher Test
echo "Test from AskPro AI Backup" | msmtp -a default fabian@askproai.de

# Mit Betreff
echo -e "Subject: Test Mail\n\nThis is a test" | msmtp -a default fabian@askproai.de
```

**Erwartete Ausgabe**: Keine Fehler, E-Mail sollte in 5-10 Sekunden ankommen.

**Bei Fehler**:
```bash
# Logs prüfen
tail -f /var/log/msmtp.log

# Häufige Fehler:
# - "authentication failed" → Passwort falsch
# - "certificate verify failed" → TLS-Problem, add: tls_starttls off
# - "Connection refused" → Port oder Host falsch
```

---

## 🧪 Testing

### Test 1: Success-Benachrichtigung

```bash
sudo /var/www/api-gateway/scripts/test-backup-notifications.sh success
```

**Erwartete E-Mail**:
- **Betreff**: `✅ Backup SUCCESS: daily (2025-10-29 22:40:00 CET)`
- **Format**: HTML (mit Fallback Plain-Text)
- **Inhalte**:
  - Tier: daily
  - Timestamp: 2025-10-29 22:40:00 CET
  - Duration: 0m 31s
  - Sizes: DB 4MB, App 138MB, System 203KB, Total 142MB
  - NAS Path: `/volume1/homes/FSAdmin/Backup/Server AskProAI/daily/2025/10/29/2240`
  - SHA256: ✓ remote == local: OK
  - Quick Access Commands (ls, scp)
- **Attachments**:
  - manifest_YYYYMMDD_HHMMSS.json
  - checksums_YYYYMMDD_HHMMSS.sha256

### Test 2: Failure-Benachrichtigung mit GitHub Issue

```bash
sudo /var/www/api-gateway/scripts/test-backup-notifications.sh failure
```

**Erwartete E-Mail**:
- **Betreff**: `❌ Backup FAILED: daily - preflight_synology (2025-10-29 22:40:00 CET)`
- **Format**: HTML (rot, kritischer Ton)
- **Inhalte**:
  - Failed Step: preflight_synology
  - Recommended Actions (3 Bulletpoints):
    - Test SSH connection: `ssh -i /root/.ssh/synology_backup_key...`
    - Check Synology DSM is running
    - Verify IP not auto-blocked
  - Log Tail (letzte 200 Zeilen)
  - Run URL (falls GitHub Actions)
- **GitHub Issue**: Automatisch erstellt mit Label "backup-failure, critical"

### Test 3: Beide Benachrichtigungen

```bash
sudo /var/www/api-gateway/scripts/test-backup-notifications.sh both
```

---

## 📧 E-Mail-Format-Beispiele

### Success-Mail (HTML)

```
Von: AskPro AI Backup System <fabian@askproai.de>
An: fabian@askproai.de, fabianspitzer@icloud.com
Betreff: ✅ Backup SUCCESS: daily (2025-10-29 22:18:24 CET)

┌─────────────────────────────────────────┐
│  AskPro AI Backup System                │
│  ✅ SUCCESS                              │
│  Backup completed successfully          │
└─────────────────────────────────────────┘

📊 Backup Details
─────────────────
Tier:       daily
Timestamp:  2025-10-29 22:18:24 CET
Duration:   0m 31s
NAS Path:   /volume1/homes/FSAdmin/.../daily/2025/10/29/2218
SHA256:     ✓ remote == local: OK

💾 Backup Sizes
───────────────
Database (compressed):  4MB
Application Files:      138MB
System State:           203KB
─────────────────────────────
Total Archive:          140MB

🚀 Quick Access Commands
────────────────────────
# List backup directory on NAS
ssh -i /root/.ssh/synology_backup_key -p 50222 \
  AskProAI@fs-cloud1977.synology.me \
  "ls -lh '/volume1/homes/FSAdmin/...'"

# Download backup to local /tmp/
scp -i /root/.ssh/synology_backup_key -P 50222 \
  "AskProAI@fs-cloud1977.synology.me:.../backup-*.tar.gz" \
  /tmp/

📎 Manifest and checksums attached
```

### Failure-Mail (HTML)

```
Von: AskPro AI Backup System <fabian@askproai.de>
An: fabian@askproai.de, fabianspitzer@icloud.com
Betreff: ❌ Backup FAILED: daily - preflight_synology (2025-10-29 22:40:00 CET)

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
• Test SSH connection: ssh -i /root/.ssh/synology_backup_key...
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

---

## 🔗 Integration mit backup-run.sh

Das Notification-Script wird automatisch von `backup-run.sh` aufgerufen (noch nicht implementiert).

**Manuelle Integration** (falls nicht automatisch):

```bash
# Am Ende von backup-run.sh erfolgreichen Backup:
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

# Bei Failure:
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

---

## 📊 Logs

### SMTP-Versand-Log
```bash
tail -f /var/log/msmtp.log
# Zeigt: SMTP-Verbindungen, Auth-Status, Fehler
```

### Backup-Alert-Log
```bash
tail -f /var/log/backup-alerts.log
# Zeigt: Erfolgreich versendete E-Mails, GitHub Issue IDs
```

### Test-Log
```bash
# Nach Test-Run:
tail -20 /var/log/backup-alerts.log
# Erwartete Ausgabe:
# [2025-10-29T22:45:00] Email sent successfully to: fabian@askproai.de,fabianspitzer@icloud.com
```

---

## 🐛 Troubleshooting

### Problem: Keine E-Mail erhalten

**Check 1: msmtp Test**
```bash
echo "Test" | msmtp -a default -d fabian@askproai.de
# -d = debug mode, zeigt Verbindungsdetails
```

**Check 2: Spam-Ordner**
- Gmail: Prüfe "Spam" und "Werbung"
- iCloud: Prüfe "Werbung"

**Check 3: SMTP-Logs**
```bash
tail -50 /var/log/msmtp.log
# Typische Fehler:
# - 535 authentication failed → Passwort falsch
# - Connection timed out → Firewall/Port-Problem
# - certificate verify failed → TLS-Problem
```

### Problem: "authentication failed" bei Gmail

**Lösung**: Verwende App-Password, NICHT normales Gmail-Passwort!

1. https://myaccount.google.com/apppasswords
2. Neues App-Password generieren
3. 16-stelligen Code in `/etc/msmtprc` eintragen (OHNE Leerzeichen)
4. Test: `echo "Test" | msmtp -a default fabian@askproai.de`

### Problem: HTML-Mail wird nicht angezeigt

**Grund**: E-Mail-Client zeigt Plain-Text-Version

**Lösung**: Das ist OK! Beide Versionen (HTML + Plain-Text) sind im MIME-Format enthalten. Der Client wählt automatisch die beste Version.

### Problem: Attachments fehlen

**Check**: Dateien existieren?
```bash
ls -la /tmp/test-manifest.json
ls -la /tmp/test-checksums.sha256
```

**Debug**: Script manuell aufrufen und Ausgabe prüfen:
```bash
/var/www/api-gateway/scripts/send-backup-notification.sh \
    success daily "$(date -Iseconds)" 31 4194304 144703488 207872 146903040 \
    "/volume1/.../daily/2025/10/29/2218" ok \
    "/tmp/test-manifest.json" "/tmp/test-checksums.sha256" "" "" local \
    > /tmp/debug-email.eml

# Prüfe EML-Datei
less /tmp/debug-email.eml
# Muss enthalten: "Content-Disposition: attachment"
```

### Problem: GitHub Issue wird nicht erstellt

**Check 1: gh CLI installiert?**
```bash
which gh
gh --version
# Falls nicht: sudo apt-get install gh
```

**Check 2: gh authentifiziert?**
```bash
gh auth status
# Falls nicht: gh auth login
```

**Check 3: Manuell testen**
```bash
gh issue create \
    --title "Test Issue from Backup System" \
    --body "This is a test" \
    --label "test"
```

---

## ✅ Acceptance Criteria - Checkliste

### Test 1: Success-Mail
- [ ] Betreff enthält: `✅ Backup SUCCESS: daily (YYYY-MM-DD HH:MM:SS CET)`
- [ ] E-Mail-Format: HTML mit responsivem Design
- [ ] Inhalte vollständig:
  - [ ] Tier (daily)
  - [ ] Timestamp (Europe/Berlin)
  - [ ] Duration (z.B. 0m 31s)
  - [ ] Sizes (DB, App, System, Total)
  - [ ] NAS Path (kein Public Link!)
  - [ ] SHA256-Status: "✓ remote == local: OK"
- [ ] Attachments vorhanden:
  - [ ] manifest_YYYYMMDD_HHMMSS.json
  - [ ] checksums_YYYYMMDD_HHMMSS.sha256
- [ ] Quick Access Commands:
  - [ ] SSH ls-Befehl mit korrektem Port (50222)
  - [ ] scp-Download-Beispiel mit korrektem Port
  - [ ] sha256sum-Verifikation
- [ ] Empfänger: Beide Adressen erhalten
  - [ ] fabian@askproai.de
  - [ ] fabianspitzer@icloud.com

### Test 2: Failure-Mail
- [ ] Betreff enthält: `❌ Backup FAILED: daily - <STEP> (YYYY-MM-DD HH:MM:SS CET)`
- [ ] E-Mail-Format: HTML in Rot/Critical-Styling
- [ ] Inhalte vollständig:
  - [ ] Failed Step angegeben
  - [ ] Recommended Actions (1-3 Bulletpoints)
  - [ ] Log Tail (letzte 200 Zeilen)
- [ ] GitHub Issue erstellt:
  - [ ] Issue vorhanden im Repository
  - [ ] Labels: "backup-failure, critical"
  - [ ] Body enthält Log Tail
- [ ] Empfänger: Beide Adressen erhalten

### Test 3: Konfiguration
- [ ] `/etc/msmtprc` existiert mit Permissions 600
- [ ] SMTP-Test erfolgreich: `echo Test | msmtp fabian@askproai.de`
- [ ] Logs werden geschrieben:
  - [ ] `/var/log/msmtp.log` enthält SMTP-Verbindungen
  - [ ] `/var/log/backup-alerts.log` enthält Benachrichtigungen

---

## 🚀 Nächste Schritte

1. **SMTP konfigurieren**: `/etc/msmtprc` mit echten Credentials
2. **Test-Mails senden**: `./scripts/test-backup-notifications.sh both`
3. **Empfang verifizieren**: Beide E-Mail-Adressen prüfen
4. **Integration**: Notification-Calls in `backup-run.sh` einbauen
5. **Production-Test**: Ersten echten Backup mit E-Mail-Benachrichtigung

---

**Dokumentation**: Vollständig
**Status**: ⚠️ Benötigt SMTP-Konfiguration
**Test-Command**: `sudo /var/www/api-gateway/scripts/test-backup-notifications.sh both`
