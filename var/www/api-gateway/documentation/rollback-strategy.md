# AskProAI Rollback-Strategie

Letzte Aktualisierung: 1. April 2025
Autor: [AskProAI Team]

## 1. Datenbank-Rollback

### 1.1 Wiederherstellung eines Backups

Um ein Datenbank-Backup wiederherzustellen (z.B. auf Staging oder im Notfall auf Produktion):

**WARNUNG:** Eine Wiederherstellung auf Produktion führt zu Datenverlust seit dem Backup-Zeitpunkt!

```bash
# 1. Verbinde dich mit dem Zielserver (Produktion oder Staging) per SSH

# 2. Liste verfügbare Datenbank-Backups auf
ls -lt /var/backups/askproai/db/

# 3. Wähle den gewünschten Zeitstempel des Backups (z.B. backup_db_2025-04-01_03-00-00.sql.gz)
#    Extrahiere den reinen Zeitstempel daraus (YYYY-MM-DD_HH-MM-SS)
#    Beispiel: TIMESTAMP="2025-04-01_03-00-00"

# 4. Führe das Wiederherstellungsskript aus
#    Stelle sicher, dass das Skript für die Zielumgebung (Prod/Staging) konfiguriert ist!
TIMESTAMP="YYYY-MM-DD_HH-MM-SS" # <== HIER DEN GEWÜNSCHTEN ZEITSTEMPEL EINSETZEN
sudo /var/www/api-gateway/scripts/restore.sh $TIMESTAMP
