# AskProAI Backup System Documentation

## Status: ✅ Repariert und funktionsfähig

### Problem vom 02.08.2025
- **Fehler**: Datenbank-Backup um 02:00 Uhr fehlgeschlagen
- **Ursache**: Das alte Backup-Script fehlte (`/var/www/api-gateway/scripts/daily-backup.sh` existierte nicht)
- **Symptom**: Backup vom 04.08. war nur 11KB statt 1.3MB groß

### Lösung implementiert am 05.08.2025

## 📋 Backup-Konfiguration

### Zeitplan
- **Tägliches Backup**: 03:00 Uhr (Cron-Job aktiv)
- **Aufbewahrung**: 60 Tage (automatische Löschung älterer Backups)
- **Speicherort**: `/var/www/api-gateway/backups/`

### Was wird gesichert?

#### 1. Datenbank (askproai_db)
- Vollständiger MySQL-Dump
- Komprimiert mit gzip
- Typische Größe: ~1.2-1.3 MB
- Format: `db_backup_YYYYMMDD_HHMMSS.sql.gz`

#### 2. Anwendungsdateien
- Alle wichtigen Verzeichnisse (app/, config/, routes/, etc.)
- .env Datei
- OAuth-Keys
- Ausgeschlossen: vendor/, node_modules/, logs, cache
- Typische Größe: ~15-180 MB
- Format: `files_backup_YYYYMMDD_HHMMSS.tar.gz`

#### 3. Manifest
- JSON-Datei mit Metadaten
- Checksums für Verifizierung
- Format: `manifest_YYYYMMDD_HHMMSS.json`

## 🔧 Scripts und Tools

### 1. `/var/www/api-gateway/scripts/daily-backup.sh`
- Hauptscript für tägliche Backups
- Features:
  - Automatische 60-Tage-Rotation
  - Integritätsprüfung
  - E-Mail-Benachrichtigung bei Fehlern
  - Wöchentlicher Status-Report (Sonntags)

### 2. `/var/www/api-gateway/scripts/check-backup-health.sh`
- Health-Check für Backup-System
- Prüft letzte 7 Tage
- Zeigt fehlerhafte/fehlende Backups

### 3. Cron-Job
```bash
0 3 * * * /var/www/api-gateway/scripts/daily-backup.sh >> /var/www/api-gateway/storage/logs/backup.log 2>&1
```

## 📊 Aktuelle Backup-Übersicht

### Letzte 7 Tage (Stand: 05.08.2025)
- ✅ 05.08.2025: OK (1.2MB)
- ❌ 04.08.2025: FEHLERHAFT (11KB) - Unvollständiges Backup
- ✅ 03.08.2025: OK (1.3MB)
- ✅ 02.08.2025: OK (1.3MB)
- ✅ 01.08.2025: OK (1.3MB)
- ✅ 31.07.2025: OK (1.3MB)
- ✅ 30.07.2025: OK (1.3MB)

### Speicherplatz
- Backup-Verzeichnis: 1.5GB
- Verfügbarer Speicher: 459GB (94% frei)

## 🚀 Verwendung

### Manuelles Backup erstellen
```bash
/var/www/api-gateway/scripts/daily-backup.sh
```

### Backup-Status prüfen
```bash
/var/www/api-gateway/scripts/check-backup-health.sh
```

### Backup wiederherstellen
```bash
# Datenbank
gunzip -c /var/www/api-gateway/backups/db_backup_YYYYMMDD_HHMMSS.sql.gz | mysql -u askproai_user -p'***' askproai_db

# Dateien
tar -xzf /var/www/api-gateway/backups/files_backup_YYYYMMDD_HHMMSS.tar.gz -C /
```

## 📧 Benachrichtigungen

E-Mail-Benachrichtigungen werden gesendet an: `fabian@v2202503255565320322.happysrv.de`

- **Bei Fehlern**: Sofort
- **Status-Report**: Wöchentlich (Sonntags)

## 🔍 Monitoring

### Log-Datei
`/var/www/api-gateway/storage/logs/backup.log`

### Prüfung auf fehlerhafte Backups
```bash
# Alle Backups der letzten 7 Tage prüfen
for i in {0..6}; do
    DATE=$(date -d "$i days ago" +%Y%m%d)
    FILE=$(find /var/www/api-gateway/backups -name "db_backup_${DATE}_*.sql.gz" 2>/dev/null | head -1)
    if [ -n "$FILE" ]; then
        echo "$DATE: $(ls -lh $FILE)"
    fi
done
```

## ⚠️ Wichtige Hinweise

1. **Fehlerhafte Backups**: Das Backup vom 04.08.2025 ist unvollständig und sollte nicht zur Wiederherstellung verwendet werden
2. **Speicherplatz**: Bei 60 Tagen Aufbewahrung werden ca. 2-3GB benötigt
3. **Datenbankzugang**: Das Script verwendet hartcodierte Credentials - bei Passwortänderung muss das Script angepasst werden

## 📝 Nächste Schritte

1. ✅ Backup-System repariert
2. ✅ 60-Tage-Rotation implementiert
3. ✅ E-Mail-Benachrichtigungen konfiguriert
4. ⏳ Optional: Backup auf externen Server/S3 replizieren
5. ⏳ Optional: Monitoring-Dashboard für Backup-Status

---
*Dokumentation erstellt: 05.08.2025 18:30 Uhr*