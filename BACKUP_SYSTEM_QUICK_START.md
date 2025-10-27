# 🚀 Backup System Quick Start Guide

**Version**: 2.0-ultimate
**Status**: ✅ Ready for Production
**Created**: 2025-10-25

---

## ⚡ Sofort-Start: Backup JETZT erstellen

```bash
# Als root/sudo
sudo bash /var/www/api-gateway/scripts/golden-backup-v2-ultimate.sh
```

**Dauer**: ~10-15 Minuten
**Output**: `/var/www/GOLDEN_BACKUPS_V2/ultimate-backup-YYYYMMDD_HHMMSS.tar.gz`

---

## 📋 Verfügbare Backup-Optionen

### 1️⃣ Ultimate Backup (EMPFOHLEN) ⭐
```bash
sudo bash /var/www/api-gateway/scripts/golden-backup-v2-ultimate.sh
```
**Was**: ALLES (App, DB, Docs, External Services, System Config, SSL)
**Wann**: Monatlich / Vor Deployments / Vor großen Änderungen
**Größe**: ~200-500 MB
**Zeit**: ~10-15 min

### 2️⃣ External Services Only
```bash
sudo bash /var/www/api-gateway/scripts/backup-external-services.sh
```
**Was**: Nur Retell.ai + Cal.com Konfigurationen
**Wann**: Vor Änderungen an Retell/Cal.com
**Größe**: ~1-5 MB
**Zeit**: ~30 Sekunden

### 3️⃣ Original Golden Backup
```bash
sudo bash /var/www/api-gateway/scripts/golden-backup.sh
```
**Was**: App, DB, Config, Storage (ohne External Services)
**Wann**: Tägliche Backups
**Größe**: ~50-100 MB
**Zeit**: ~2-5 min

### 4️⃣ Comprehensive Backup
```bash
sudo bash /var/www/api-gateway/scripts/comprehensive-backup.sh
```
**Was**: App, DB, Config, Storage, System Config
**Wann**: Wöchentliche Backups
**Größe**: ~100-200 MB
**Zeit**: ~5-10 min

---

## 🔄 Wiederherstellung (Restore)

### Vollständige System-Wiederherstellung

```bash
# 1. Backup finden
ls -lh /var/www/GOLDEN_BACKUPS_V2/

# 2. Extrahieren
cd /var/www/GOLDEN_BACKUPS_V2
tar -xzf ultimate-backup-YYYYMMDD_HHMMSS.tar.gz
cd ultimate-backup-YYYYMMDD_HHMMSS/

# 3. Guide lesen
cat docs/ULTIMATE_RESTORE_GUIDE.md

# 4. Quick Restore
sudo bash quick-restore.sh all
```

### Nur Datenbank

```bash
cd /var/www/GOLDEN_BACKUPS_V2/ultimate-backup-YYYYMMDD_HHMMSS/
sudo bash quick-restore.sh database
```

### Nur Application

```bash
cd /var/www/GOLDEN_BACKUPS_V2/ultimate-backup-YYYYMMDD_HHMMSS/
sudo bash quick-restore.sh app
```

---

## ⚙️ Automatisierung einrichten

### Daily Backup (2 Uhr nachts)

```bash
# Als root
sudo crontab -e

# Hinzufügen:
0 2 * * * /var/www/api-gateway/scripts/golden-backup-v2-ultimate.sh >> /var/log/backup-cron.log 2>&1
```

### Backup-Log überwachen

```bash
tail -f /var/log/backup-cron.log
```

---

## ✅ Backup verifizieren

```bash
# Latest Backup finden
LATEST=$(ls -t /var/www/GOLDEN_BACKUPS_V2/ultimate-backup-*.tar.gz | head -1)

# Extrahieren
tar -xzf "$LATEST" -C /tmp/

# Checksums prüfen
cd /tmp/ultimate-backup-*/
sha256sum -c checksums.txt

# Cleanup
cd /
rm -rf /tmp/ultimate-backup-*
```

---

## 📚 Komplette Dokumentation

**Master Guide**: `/var/www/api-gateway/claudedocs/08_REFERENCE/ULTIMATE_BACKUP_RECOVERY_MASTER_GUIDE_2025-10-25.md`

Enthält:
- Detaillierte Erklärungen aller Backup-Tiers
- Schritt-für-Schritt Recovery Guides
- Automation & Scheduling
- Off-Site Backup Strategien
- Security Best Practices
- Troubleshooting
- Testing & Validation

---

## 🆘 Schnelle Hilfe

### Backup schlägt fehl?
```bash
# Disk Space prüfen
df -h /var/www

# Permissions prüfen
ls -la /var/www/GOLDEN_BACKUPS_V2

# Manual mit Debug
sudo bash -x /var/www/api-gateway/scripts/golden-backup-v2-ultimate.sh
```

### Restore schlägt fehl?
```bash
# Backup Integrity prüfen
cd /path/to/backup
sha256sum -c checksums.txt

# Metadata prüfen
cat metadata.json

# Log prüfen
cat backup.log
```

---

## 📊 Backup Status prüfen

```bash
# Alle Backups anzeigen
ls -lh /var/www/GOLDEN_BACKUPS_V2/

# Neuestes Backup Info
LATEST=$(ls -t /var/www/GOLDEN_BACKUPS_V2/ultimate-backup-* | head -1)
echo "Latest: $LATEST"
du -sh "$LATEST"

# Anzahl Backups
ls /var/www/GOLDEN_BACKUPS_V2/ultimate-backup-*.tar.gz | wc -l
```

---

## 🎯 Empfohlene Backup-Strategie

```
├── Daily:    Golden Backup (täglich 2 Uhr)
├── Weekly:   Ultimate Backup (Sonntag 3 Uhr)
├── Monthly:  Ultimate Backup + Off-Site Upload
└── Pre-Deployment: Ultimate Backup (manuell)
```

**Retention**:
- Daily: 7 Tage
- Weekly: 4 Wochen
- Monthly: 12 Monate

---

## 🔐 Wichtig: Sicherheit

⚠️ **Backups enthalten sensible Daten**:
- Datenbank Passwörter
- API Keys (Retell, Cal.com)
- SSL Private Keys
- User-Daten (DSGVO)

**Maßnahmen**:
1. Verschlüssele Backups vor Off-Site Upload
2. Restriktive Permissions (chmod 600)
3. Separate Backup-User für DB
4. Audit-Logs für Zugriffe

---

**Fragen?** Siehe Master Guide: `claudedocs/08_REFERENCE/ULTIMATE_BACKUP_RECOVERY_MASTER_GUIDE_2025-10-25.md`

