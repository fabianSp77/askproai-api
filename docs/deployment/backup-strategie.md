# AskProAI Backup-Strategie

## Übersicht

Diese Dokumentation beschreibt die automatisierte Backup-Strategie für das AskProAI-System.

## Backup-Komponenten

Das Backup-System sichert folgende Komponenten:
- Datenbank (MariaDB)
- Anwendungsdateien (Laravel-Projekt)
- Konfigurationsdateien (.env, OAuth-Schlüssel, Nginx-Konfigurationen)

## Backup-Zeitplan

- Tägliche Backups um 3 Uhr morgens
- Aufbewahrungsdauer: 14 Tage (ältere Backups werden automatisch gelöscht)

## Backup-Speicherort

Alle Backups werden im Verzeichnis `/var/backups/askproai/` gespeichert, mit Unterordnern für verschiedene Komponenten:
- `/var/backups/askproai/db/` - Datenbank-Backups
- `/var/backups/askproai/files/` - Dateisystem-Backups
- `/var/backups/askproai/config/` - Konfigurationsbackups
- `/var/backups/askproai/logs/` - Backup-Logs

## Offsite-Backup

Je nach Konfiguration werden Backups zusätzlich an einen externen Speicherort übertragen:
- AWS S3 (bevorzugt)
- RSYNC zu externem Server
- FTP-Server (Fallback)

## Kompression und Archivierung

Alle Backups verwenden gzip-Kompression für optimale Speichernutzung:
- Datenbank-Backups: `.sql.gz` Format mit `mysqldump | gzip`
- Datei-Backups: `.tar.gz` Format mit `tar -czf`
- Gesamtarchiv: `.tar.gz` für Offsite-Übertragung

## Wiederherstellungsanleitung

Um ein Backup wiederherzustellen, führen Sie folgendes Kommando aus:

```bash
# Verfügbare Backups auflisten
ls -lt /var/backups/askproai/db/ | head -10

# Backup mit Timestamp wiederherstellen
sudo /var/www/api-gateway/scripts/restore.sh 2025-08-14_03-00-01
```

### Wiederherstellungsschritte:
1. **Sicherheitsabfrage**: Bestätigung mit "ja" und Timestamp-Eingabe
2. **Pre-Restore Backup**: Automatisches Backup vor Wiederherstellung
3. **Datenbank**: Entpacken mit `zcat` und Import via `mysql`
4. **Dateien**: Entpacken mit `tar -xzf` nach `/var/www/api-gateway`
5. **Services**: Automatischer Neustart von nginx und php-fpm
6. **Cache**: Automatisches Leeren aller Laravel-Caches
