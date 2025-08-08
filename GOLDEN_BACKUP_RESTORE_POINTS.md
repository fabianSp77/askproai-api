# 🏆 GOLDEN BACKUP RESTORE POINTS

> **Zuletzt aktualisiert**: 2025-08-06
> **Zweck**: Sichere, verifizierte Restore Points für Notfall-Wiederherstellung

## ⚡ QUICK RESTORE

### Letztes Golden Backup wiederherstellen:
```bash
cd /var/www/backups
tar -xzf askproai-full-backup-20250805-230451.tar.gz
cd askproai-full-backup-20250805-230451
./restore-backup.sh
```

---

## 🌟 GOLDEN BACKUP #1
**Status**: ✅ VERIFIZIERT & SICHER

### 📊 Backup-Details
- **Zeitpunkt**: 2025-08-05 23:04:51 CEST
- **Datei**: `askproai-full-backup-20250805-230451.tar.gz`
- **Pfad**: `/var/www/backups/askproai-full-backup-20250805-230451.tar.gz`
- **Größe**: 17 MB (17,655,912 Bytes)
- **MD5 Checksum**: `c34f6f8071106404ff8e8b9415c06589`
- **Typ**: Vollständiges System-Backup (manuell erstellt)

### 🔍 System-Zustand bei Erstellung
- **Umgebung**: Production (https://api.askproai.de)
- **Laravel Version**: 10.x
- **PHP Version**: 8.2
- **Datenbank**: MySQL mit allen Tabellen funktionsfähig
- **Queue System**: Horizon läuft stabil
- **Kritische Features**: 
  - ✅ Admin Panel voll funktionsfähig
  - ✅ Business Portal Login funktioniert
  - ✅ Retell.ai Integration aktiv
  - ✅ Cal.com Synchronisation läuft
  - ✅ Backup-System mit E-Mail-Benachrichtigung

### 📦 Backup-Inhalt
```
askproai-full-backup-20250805-230451/
├── application/         # Kompletter Anwendungscode
│   └── app-code-*.tar.gz
├── database/            # Datenbank-Dump
│   └── askproai_db_*.sql.gz
├── config/              # Konfigurationsdateien
│   └── .env und andere configs
├── logs/                # System-Logs zum Zeitpunkt
├── BACKUP_MANIFEST.txt  # Detaillierte Inhaltsübersicht
└── restore-backup.sh    # Automatisches Restore-Script
```

### 🔧 Restore-Anleitung

#### Vollständige Wiederherstellung:
```bash
# 1. Backup extrahieren
cd /var/www/backups
tar -xzf askproai-full-backup-20250805-230451.tar.gz

# 2. In Backup-Verzeichnis wechseln
cd askproai-full-backup-20250805-230451

# 3. Restore-Script ausführen
sudo ./restore-backup.sh

# 4. Services neustarten
sudo service nginx restart
sudo service php8.2-fpm restart
php artisan horizon:terminate
php artisan horizon
```

#### Nur Datenbank wiederherstellen:
```bash
# Datenbank-Backup entpacken
gunzip -c /var/www/backups/askproai-full-backup-20250805-230451/database/askproai_db_*.sql.gz > /tmp/restore.sql

# Datenbank wiederherstellen
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db < /tmp/restore.sql

# Cache leeren
php artisan optimize:clear
```

### ✅ Verifizierung nach Restore
```bash
# 1. Datenbank-Verbindung prüfen
php artisan db:show

# 2. Admin Panel testen
curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de/admin/login
# Erwartete Antwort: 200

# 3. Business Portal testen
curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de/business/login
# Erwartete Antwort: 200

# 4. Queue System prüfen
php artisan horizon:status

# 5. Logs auf Fehler prüfen
tail -50 storage/logs/laravel.log | grep -i error
```

### 📝 Notizen
- Dieses Backup wurde nach Behebung aller kritischen Fehler erstellt
- Alle Tests liefen erfolgreich durch
- E-Mail-Benachrichtigungen waren bereits auf fabian@askproai.de konfiguriert
- Backup enthält die funktionierende `retell_ai_call_campaigns` Tabelle

---

## 📅 Weitere Restore Points

### Backup vor 20:00 Uhr (Alternative)
- **Zeitpunkt**: 2025-08-05 19:28:59
- **Pfad**: `/var/www/api-gateway/backups/2025-08-05/askproai-full-backup-20250805-192859.tar.gz`
- **Größe**: 81 MB
- **Status**: ⚠️ Größer, aber auch funktionsfähig
- **Hinweis**: Nutze dieses Backup, wenn Golden Backup #1 nicht ausreicht

---

## 🚨 WICHTIGE HINWEISE

1. **VOR jedem Restore**: Erstelle ein aktuelles Backup des jetzigen Zustands!
   ```bash
   /var/www/api-gateway/scripts/daily-backup.sh
   ```

2. **Checksum verifizieren** vor Restore:
   ```bash
   md5sum /var/www/backups/askproai-full-backup-20250805-230451.tar.gz
   # Muss sein: c34f6f8071106404ff8e8b9415c06589
   ```

3. **Monitoring** nach Restore:
   - Prüfe `/var/www/api-gateway/storage/logs/laravel.log`
   - Überwache Horizon Dashboard
   - Teste kritische Endpoints

4. **Rollback** bei Problemen:
   - Backup des vorherigen Zustands liegt in `/var/www/api-gateway/backups/`
   - Nutze das tägliche 03:00 Uhr Backup als Fallback

---

## 🔐 Zugriff für Super Admin

### Admin Portal
- URL: https://api.askproai.de/admin/backup-restore-points
- Menü: System → Backup Restore Points

### CLI Commands
```bash
# Liste alle Golden Backups
php artisan backup:list-golden

# Verifiziere Golden Backup
php artisan backup:verify-golden 1

# Markiere neues Golden Backup
php artisan backup:mark-golden /pfad/zum/backup.tar.gz
```

### Quick Access Symlink
```bash
ls -la /var/www/GOLDEN_BACKUPS/
```

---

*Dieses Dokument wird automatisch aktualisiert. Letzte manuelle Prüfung: 2025-08-06*