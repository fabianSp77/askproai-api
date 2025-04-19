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

## Wiederherstellungsanleitung

Um ein Backup wiederherzustellen, führen Sie folgendes Kommando aus:
