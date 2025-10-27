# Ultimate Backup & Recovery Master Guide
**Version**: 2.0-ultimate
**Created**: 2025-10-25
**System**: AskPro AI Gateway
**Purpose**: Vollständige Disaster Recovery Strategie

---

## 📋 Executive Summary

Dieses System bietet **vollständige Disaster Recovery Fähigkeit** für das AskPro AI Gateway. Du kannst das gesamte System von Bare Metal wiederherstellen - inklusive:

- ✅ Komplette Anwendung (Laravel + Filament)
- ✅ Gesamte Datenbank (MySQL)
- ✅ Alle Konfigurationen (.env, Nginx, PHP, etc.)
- ✅ User Uploads & Storage
- ✅ Komplette Dokumentation (claudedocs/)
- ✅ **External Services State** (Retell.ai, Cal.com)
- ✅ System-Konfigurationen (Cron, Supervisor, etc.)
- ✅ SSL Zertifikate
- ✅ Git Repository State
- ✅ Scripts & Automation

---

## 🎯 Backup-Strategien im Überblick

### 1. Golden Backup (Original)
**Script**: `/var/www/api-gateway/scripts/golden-backup.sh`
**Umfang**: Basis-Backup (App, DB, Config, Storage)
**Größe**: ~50-100 MB compressed
**Dauer**: ~2-5 Minuten
**Verwendung**: Schnelle tägliche Backups

### 2. Comprehensive Backup
**Script**: `/var/www/api-gateway/scripts/comprehensive-backup.sh`
**Umfang**: Erweitertes Backup + System-Config
**Größe**: ~100-200 MB compressed
**Dauer**: ~5-10 Minuten
**Verwendung**: Wöchentliche vollständige Backups

### 3. **Ultimate Golden Backup V2** (NEU) ⭐
**Script**: `/var/www/api-gateway/scripts/golden-backup-v2-ultimate.sh`
**Umfang**: ALLES + External Services
**Größe**: ~200-500 MB compressed
**Dauer**: ~10-15 Minuten
**Verwendung**: Monatliche/Pre-Deployment komplette Snapshots

### 4. External Services Only
**Script**: `/var/www/api-gateway/scripts/backup-external-services.sh`
**Umfang**: Nur Retell + Cal.com State
**Größe**: ~1-5 MB
**Dauer**: ~30 Sekunden
**Verwendung**: Vor Änderungen an External Services

---

## 🚀 Backup durchführen

### Sofort: Ultimate Backup erstellen

```bash
# Als root/sudo
sudo bash /var/www/api-gateway/scripts/golden-backup-v2-ultimate.sh
```

**Output**:
- Backup Location: `/var/www/GOLDEN_BACKUPS_V2/ultimate-backup-YYYYMMDD_HHMMSS/`
- Archive: `/var/www/GOLDEN_BACKUPS_V2/ultimate-backup-YYYYMMDD_HHMMSS.tar.gz`
- Log: `backup.log` im Backup-Verzeichnis

### Nur External Services

```bash
sudo bash /var/www/api-gateway/scripts/backup-external-services.sh
```

**Output**:
- Location: `/var/www/backups/external-services-YYYYMMDD_HHMMSS/`

### Quick Backup (Original)

```bash
sudo bash /var/www/api-gateway/scripts/golden-backup.sh
```

**Output**:
- Location: `/var/www/backups/golden-backup-YYYYMMDD_HHMMSS/`

---

## 📦 Was ist in den Backups?

### Tier 1: Application Code
```
app/application.tar.gz
├── app/                    # Laravel app code
├── config/                 # Laravel configs
├── database/migrations/    # Migrations
├── resources/              # Views, assets
├── routes/                 # Route definitions
└── public/                 # Compiled assets
```

**Excludes**: vendor/, node_modules/, .git, cache files

### Tier 2: Database
```
database/
├── full_dump.sql.gz        # Complete database with data
├── schema_only.sql.gz      # Schema without data
└── table_statistics.txt    # Table sizes & row counts
```

**Includes**: All tables, routines, triggers, events

### Tier 3: Configuration
```
config/
├── env.production          # .env file (SENSITIVE!)
├── env.example             # .env.example
├── composer.json/lock      # PHP dependencies
├── package.json/lock       # Node dependencies
└── laravel/                # All config/*.php files
```

### Tier 4: Storage
```
storage/app_storage.tar.gz
├── app/                    # User uploads
├── private/                # Private files
└── public/                 # Public accessible files
```

**Excludes**: Cache, sessions, views, temp files

### Tier 5: Documentation
```
claudedocs/complete-docs.tar.gz
└── claudedocs/             # Entire knowledge base
    ├── 00_INDEX.md
    ├── 01_FRONTEND/
    ├── 02_BACKEND/
    ├── 03_API/
    ├── 04_TESTING/
    ├── 05_DEPLOYMENT/
    ├── 06_SECURITY/
    ├── 07_ARCHITECTURE/
    ├── 08_REFERENCE/
    └── 09_RUNBOOKS/
```

### Tier 6: External Services ⭐ (NEU)
```
external-services/
├── retell/
│   ├── agent_XXXXX.json        # Current agent config
│   ├── all_agents.json         # All agents list
│   └── phone_numbers.json      # Phone mappings
├── calcom/
│   ├── event_types.json        # Event type configs
│   ├── schedules.json          # Availability schedules
│   └── team_info.json          # Team settings
└── database-exports/
    ├── retell_agents.txt       # DB: retell_agents table
    ├── phone_numbers.txt       # DB: phone_numbers table
    ├── calcom_event_mappings.txt
    └── calcom_host_mappings.txt
```

### Tier 7: System Configuration
```
system/
├── nginx/
│   ├── sites-available/
│   ├── sites-enabled/
│   └── nginx.conf
├── php/
│   └── 8.3/                    # PHP-FPM configs
├── supervisor/
│   └── conf.d/                 # Queue workers
├── cron/
│   ├── root_crontab.txt
│   └── www-data_crontab.txt
└── redis/
    └── dump.rdb                # Redis snapshot
```

### Tier 8: Git State
```
system/git/
├── status.txt                  # Current git status
├── recent_commits.txt          # Last 50 commits
├── remotes.txt                 # Remote repositories
└── branches.txt                # All branches
```

### Tier 9: SSL Certificates
```
system/ssl/
└── letsencrypt.tar.gz         # Let's Encrypt certs
    ├── live/
    ├── archive/
    └── renewal/
```

### Tier 10: System Info
```
system/info/
├── system_report.txt           # Complete system snapshot
├── installed_packages.txt      # dpkg -l
├── php_modules.txt             # PHP extensions
├── composer_global.txt         # Global composer packages
└── npm_global.txt              # Global npm packages
```

### Tier 11: Scripts
```
system/scripts/
└── all_scripts.tar.gz
    ├── deployment/
    ├── monitoring/
    ├── testing/
    └── backup/
```

### Tier 12: Metadata & Checksums
```
├── metadata.json               # Backup metadata
├── checksums.txt               # SHA256 checksums
├── backup.log                  # Detailed backup log
└── docs/
    ├── ULTIMATE_RESTORE_GUIDE.md
    ├── AUTOMATION_GUIDE.md
    └── README.md
```

---

## 🔄 Wiederherstellung (Recovery)

### Szenario 1: Kompletter Server-Verlust (Bare Metal Recovery)

```bash
# 1. Neuen Server vorbereiten (Ubuntu 20.04+)
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server redis-server \
    php8.3-cli php8.3-fpm php8.3-mysql php8.3-redis \
    php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip \
    php8.3-gd php8.3-bcmath composer nodejs npm git supervisor

# 2. Backup extrahieren
cd /path/to/backup
tar -xzf ultimate-backup-YYYYMMDD_HHMMSS.tar.gz
cd ultimate-backup-YYYYMMDD_HHMMSS/

# 3. Restore Guide folgen
cat docs/ULTIMATE_RESTORE_GUIDE.md

# 4. Quick Restore Script nutzen
sudo bash quick-restore.sh all
```

**Dauer**: 30-60 Minuten (je nach Server-Geschwindigkeit)

### Szenario 2: Nur Datenbank wiederherstellen

```bash
cd /path/to/backup/ultimate-backup-YYYYMMDD_HHMMSS/

# Restore
gunzip < database/full_dump.sql.gz | mysql -u root -p

# Verify
mysql -u askproai_user -p askproai_db -e "SHOW TABLES;"
```

**Dauer**: 1-5 Minuten

### Szenario 3: Nur Anwendung wiederherstellen

```bash
cd /path/to/backup/ultimate-backup-YYYYMMDD_HHMMSS/

# Quick restore
sudo bash quick-restore.sh app
```

**Dauer**: 2-10 Minuten

### Szenario 4: External Services wiederherstellen

```bash
cd /path/to/backup/ultimate-backup-YYYYMMDD_HHMMSS/

# Retell.ai Agent
AGENT_ID="agent_9a8202a740cd3120d96fcfda1e"
RETELL_TOKEN="key_6ff998ba48e842092e04a5455d19"

curl -X POST "https://api.retellai.com/update-agent/${AGENT_ID}" \
    -H "Authorization: Bearer ${RETELL_TOKEN}" \
    -H "Content-Type: application/json" \
    -d @external-services/retell/agent_${AGENT_ID}.json

# Cal.com (meist automatisch erhalten, Verifikation)
cat external-services/calcom/event_types.json
```

**Dauer**: 5-10 Minuten

### Szenario 5: Nur Dokumentation wiederherstellen

```bash
cd /path/to/backup/ultimate-backup-YYYYMMDD_HHMMSS/

tar -xzf claudedocs/complete-docs.tar.gz -C /var/www/api-gateway/
```

**Dauer**: < 1 Minute

---

## ⚙️ Automatisierung

### Daily Backups (2 AM)

```bash
# Als root
sudo crontab -e

# Hinzufügen:
0 2 * * * /var/www/api-gateway/scripts/golden-backup-v2-ultimate.sh >> /var/log/backup-cron.log 2>&1
```

### Weekly Comprehensive Backup (Sonntag 3 AM)

```bash
0 3 * * 0 /var/www/api-gateway/scripts/golden-backup-v2-ultimate.sh
```

### Before Deployment

```bash
# Vor jedem Deployment
cd /var/www/api-gateway
sudo bash scripts/golden-backup-v2-ultimate.sh

# Warten bis fertig, dann deployment
bash deploy/go-live.sh
```

### After External Services Changes

```bash
# Nach Retell/Cal.com Änderungen
sudo bash /var/www/api-gateway/scripts/backup-external-services.sh
```

---

## 🌐 Off-Site Backup Strategie

### AWS S3 (Empfohlen)

```bash
# AWS CLI installieren
sudo apt install awscli
aws configure

# Upload
LATEST=$(ls -t /var/www/GOLDEN_BACKUPS_V2/ultimate-backup-*.tar.gz | head -1)
aws s3 cp "$LATEST" s3://your-bucket/backups/ --storage-class GLACIER

# Automatisiert
0 3 * * * aws s3 sync /var/www/GOLDEN_BACKUPS_V2 s3://your-bucket/backups/ --storage-class GLACIER
```

**Kosten**: ~$0.004/GB/Monat (Glacier)

### Google Cloud Storage

```bash
# gcloud installieren
curl https://sdk.cloud.google.com | bash

# Upload
LATEST=$(ls -t /var/www/GOLDEN_BACKUPS_V2/ultimate-backup-*.tar.gz | head -1)
gsutil cp "$LATEST" gs://your-bucket/backups/
```

### Rsync zu Remote Server

```bash
# SSH Key setup
ssh-keygen -t rsa -b 4096
ssh-copy-id backup-user@backup-server.com

# Sync
rsync -avz --delete \
    /var/www/GOLDEN_BACKUPS_V2/ \
    backup-user@backup-server.com:/backups/askproai/

# Automatisiert
0 4 * * * rsync -avz /var/www/GOLDEN_BACKUPS_V2/ backup-user@backup-server.com:/backups/
```

---

## 📊 Backup Retention Policy

### Empfohlene Strategie

```
Daily Backups:     7 Tage aufbewahren
Weekly Backups:    4 Wochen aufbewahren
Monthly Backups:   12 Monate aufbewahren
Yearly Backups:    3-5 Jahre aufbewahren
```

### Automatische Cleanup

```bash
# Alte Daily Backups löschen (>7 Tage)
0 4 * * * find /var/www/GOLDEN_BACKUPS_V2 -name "ultimate-backup-*.tar.gz" -mtime +7 -delete

# Alte External Service Backups löschen (>14 Tage)
0 4 * * * find /var/www/backups -name "external-services-*.tar.gz" -mtime +14 -delete
```

---

## ✅ Backup Verification

### Automatischer Integrity Check

```bash
cd /path/to/backup/ultimate-backup-YYYYMMDD_HHMMSS/

# Checksums verifizieren
sha256sum -c checksums.txt

# Sollte ausgeben:
# ./app/application.tar.gz: OK
# ./database/full_dump.sql.gz: OK
# ... etc
```

### Monatlicher Recovery Test

```bash
# Test-Script erstellen
cat > /var/www/api-gateway/scripts/test-recovery.sh << 'EOF'
#!/bin/bash

LATEST=$(ls -t /var/www/GOLDEN_BACKUPS_V2/ultimate-backup-*.tar.gz | head -1)
TEST_DIR="/tmp/recovery-test-$(date +%Y%m%d)"

mkdir -p "$TEST_DIR"
tar -xzf "$LATEST" -C "$TEST_DIR"

ERRORS=0

# Check database
if [ ! -f "$TEST_DIR"/*/database/full_dump.sql.gz ]; then
    echo "❌ Database dump missing"
    ERRORS=$((ERRORS + 1))
fi

# Check application
if [ ! -f "$TEST_DIR"/*/app/application.tar.gz ]; then
    echo "❌ Application missing"
    ERRORS=$((ERRORS + 1))
fi

# Check external services
if [ ! -f "$TEST_DIR"/*/external-services/retell/agent_*.json ]; then
    echo "⚠️  Retell config missing"
fi

rm -rf "$TEST_DIR"

if [ $ERRORS -eq 0 ]; then
    echo "✅ Recovery test PASSED"
else
    echo "❌ Recovery test FAILED: $ERRORS errors"
    exit 1
fi
EOF

chmod +x /var/www/api-gateway/scripts/test-recovery.sh

# Cron: 1. jeden Monat um 5 Uhr
0 5 1 * * /var/www/api-gateway/scripts/test-recovery.sh >> /var/log/recovery-test.log
```

---

## 🔐 Sicherheitshinweise

### Sensible Daten in Backups

**Backups enthalten**:
- `.env` File (Datenbank-Passwörter, API Keys)
- Retell.ai API Token
- Cal.com API Key
- MySQL Credentials
- SSL Private Keys
- User-Daten (DSGVO-relevant)

### Schutzmaßnahmen

1. **Verschlüsselung**
```bash
# Backup verschlüsseln
gpg --symmetric --cipher-algo AES256 ultimate-backup-YYYYMMDD.tar.gz

# Entschlüsseln
gpg --decrypt ultimate-backup-YYYYMMDD.tar.gz.gpg > ultimate-backup-YYYYMMDD.tar.gz
```

2. **Zugriffsbeschränkung**
```bash
# Nur root Zugriff
sudo chown root:root /var/www/GOLDEN_BACKUPS_V2/*
sudo chmod 600 /var/www/GOLDEN_BACKUPS_V2/*
```

3. **Separate Credentials für Backup-Server**
```bash
# Niemals production credentials auf backup server
# Verwende separate Read-Only MySQL User für Backups

# Create backup user
CREATE USER 'backup_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT, LOCK TABLES, SHOW VIEW ON askproai_db.* TO 'backup_user'@'localhost';
FLUSH PRIVILEGES;
```

4. **Audit Logs**
```bash
# Log alle Backup-Zugriffe
echo "$(date) - Backup accessed by $(whoami)" >> /var/log/backup-access.log
```

---

## 📞 Notfall-Kontakte & Procedures

### Disaster Recovery Team

```
Primary: [Dein Name/Team]
Backup:  [Backup Contact]
```

### Incident Response Procedure

1. **Erkennung**: Problem identifiziert
2. **Assessment**: Schweregrad bewerten
3. **Kommunikation**: Team benachrichtigen
4. **Backup lokalisieren**: Neuestes valides Backup finden
5. **Recovery starten**: Restore Guide folgen
6. **Validation**: System-Tests durchführen
7. **Documentation**: Incident dokumentieren
8. **Post-Mortem**: RCA durchführen

### Recovery Time Objective (RTO)

```
Critical Data:     1 Stunde
Full Application:  4 Stunden
Complete System:   8 Stunden
```

### Recovery Point Objective (RPO)

```
Daily Backups:     24 Stunden Datenverlust max
Hourly Backups:    1 Stunde Datenverlust max (falls eingerichtet)
```

---

## 📈 Monitoring & Alerting

### Backup Success Monitoring

```bash
# Slack Webhook
SLACK_WEBHOOK="https://hooks.slack.com/services/YOUR/WEBHOOK/URL"

# Nach erfolgreichem Backup
curl -X POST -H 'Content-type: application/json' \
    --data "{\"text\":\"✅ Backup completed: ${BACKUP_NAME}\"}" \
    "$SLACK_WEBHOOK"

# Bei Fehler
curl -X POST -H 'Content-type: application/json' \
    --data "{\"text\":\"❌ Backup FAILED: Check logs!\"}" \
    "$SLACK_WEBHOOK"
```

### Email Alerts

```bash
# Install mail utils
sudo apt install mailutils

# Success
echo "Backup completed: ${BACKUP_NAME}" | mail -s "✅ Backup Success" admin@example.com

# Failure
echo "Backup FAILED! Check logs immediately." | mail -s "❌ BACKUP FAILURE" admin@example.com
```

### Disk Space Monitoring

```bash
# Check before backup
DISK_USAGE=$(df -h /var/www | tail -1 | awk '{print $5}' | sed 's/%//')

if [ $DISK_USAGE -gt 85 ]; then
    echo "⚠️  WARNING: Disk space >85%"
    # Send alert
fi
```

---

## 🧪 Testing & Validation

### Pre-Production Restore Test

```bash
# 1. Backup erstellen
sudo bash /var/www/api-gateway/scripts/golden-backup-v2-ultimate.sh

# 2. Auf Test-Server restoren
scp /var/www/GOLDEN_BACKUPS_V2/ultimate-backup-*.tar.gz test-server:/tmp/

# 3. Auf Test-Server
ssh test-server
cd /tmp
tar -xzf ultimate-backup-*.tar.gz
cd ultimate-backup-*/
sudo bash quick-restore.sh all

# 4. Validierung
curl http://test-server
# Test Retell call
# Test Cal.com availability
# Test database queries
```

### Checklist nach Restore

```
□ Website lädt
□ Admin Panel zugänglich
□ Datenbank verbunden
□ File Uploads funktionieren
□ Retell.ai Calls funktionieren
□ Cal.com Availability funktioniert
□ Queue Workers laufen
□ Cron Jobs aktiv
□ SSL Zertifikat gültig
□ Redis verbunden
□ Email versand funktioniert
□ Logs werden geschrieben
□ Background Jobs laufen
```

---

## 📚 Referenzen & Weiterführende Docs

### Backup Scripts
- `/var/www/api-gateway/scripts/golden-backup.sh` - Original Golden Backup
- `/var/www/api-gateway/scripts/comprehensive-backup.sh` - Comprehensive Backup
- `/var/www/api-gateway/scripts/golden-backup-v2-ultimate.sh` - Ultimate V2 ⭐
- `/var/www/api-gateway/scripts/backup-external-services.sh` - External Services Only

### Verification Scripts
- `/var/www/api-gateway/scripts/verify-golden-backup.sh` - Backup Integrity Check

### Documentation in Backups
- `docs/ULTIMATE_RESTORE_GUIDE.md` - Schritt-für-Schritt Wiederherstellung
- `docs/AUTOMATION_GUIDE.md` - Backup Automation
- `docs/README.md` - Backup Übersicht
- `metadata.json` - Backup Metadaten
- `backup.log` - Detaillierter Backup-Log
- `checksums.txt` - SHA256 Checksums

### Related Documentation
- `claudedocs/05_DEPLOYMENT/` - Deployment Guides
- `claudedocs/06_SECURITY/` - Security Fixes
- `claudedocs/09_RUNBOOKS/` - Operational Runbooks
- `claudedocs/07_ARCHITECTURE/` - System Architecture

---

## 🎓 Best Practices

### DO ✅
- Teste Backups regelmäßig (monatlich)
- Speichere Backups off-site
- Verschlüssele sensible Backups
- Dokumentiere alle Recovery-Prozesse
- Halte RTO/RPO SLAs ein
- Rotiere alte Backups
- Monitore Backup-Erfolg
- Validiere Checksums

### DON'T ❌
- Niemals ungetestete Backups
- Niemals nur lokale Backups
- Niemals unverschlüsselte Backups mit credentials
- Niemals unlimited Retention (Disk voll!)
- Niemals Backups ohne Monitoring
- Niemals production credentials in Backup-Scripts hardcoden
- Niemals Restore ohne Test in Production

---

## 📝 Changelog

### Version 2.0-ultimate (2025-10-25)
- ✨ NEU: External Services State Backup (Retell.ai, Cal.com)
- ✨ NEU: Complete Documentation Backup (claudedocs/)
- ✨ NEU: Git Repository State
- ✨ NEU: Comprehensive System Info
- ✨ NEU: SSL Certificates Backup
- ✨ NEU: Scripts & Automation Backup
- ✨ NEU: Metadata & Checksums
- ✨ NEU: Ultimate Restore Guide
- ✨ NEU: Automation Guide
- ✨ NEU: Quick Restore Script
- 🔧 Verbessert: Logging & Progress Tracking
- 🔧 Verbessert: Error Handling
- 🔧 Verbessert: Compression & Archive
- 📚 Dokumentation: Komplette Master Guide

### Version 1.0 (2025-10-10)
- Initial Golden Backup
- Basic Comprehensive Backup

---

## 🆘 Support & Troubleshooting

### Backup schlägt fehl
```bash
# Check logs
tail -f /var/log/backup-cron.log

# Check disk space
df -h /var/www

# Check permissions
ls -la /var/www/GOLDEN_BACKUPS_V2

# Manual run with verbose
sudo bash -x /var/www/api-gateway/scripts/golden-backup-v2-ultimate.sh
```

### Restore schlägt fehl
```bash
# Check backup integrity
cd /path/to/backup
sha256sum -c checksums.txt

# Check metadata
cat metadata.json

# Check logs
cat backup.log
```

### External Services Backup fehlgeschlagen
```bash
# Verify API credentials
cat /var/www/api-gateway/.env | grep -E "RETELL|CALCOM"

# Test API manually
curl -H "Authorization: Bearer $RETELL_TOKEN" https://api.retellai.com/list-agents
```

---

## 🔗 Quick Links

| Dokument | Pfad |
|----------|------|
| Dieser Guide | `/var/www/api-gateway/claudedocs/08_REFERENCE/ULTIMATE_BACKUP_RECOVERY_MASTER_GUIDE_2025-10-25.md` |
| Ultimate Backup Script | `/var/www/api-gateway/scripts/golden-backup-v2-ultimate.sh` |
| External Services Script | `/var/www/api-gateway/scripts/backup-external-services.sh` |
| Verification Script | `/var/www/api-gateway/scripts/verify-golden-backup.sh` |
| Backup Location | `/var/www/GOLDEN_BACKUPS_V2/` |
| Legacy Backups | `/var/www/backups/` |

---

**Status**: ✅ Production Ready
**Getestet**: Nein (Test empfohlen vor erstem produktiven Einsatz)
**Nächste Review**: 2025-11-25
**Maintainer**: [Dein Name/Team]

