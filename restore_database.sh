#!/bin/bash

# AskProAI Datenbank-Wiederherstellung
# Stellt die Daten vom 17. Juni wieder her

echo "=== AskProAI Datenbank-Wiederherstellung ==="
echo ""
echo "Dieses Script stellt deine verlorenen Daten wieder her!"
echo ""

# Backup der aktuellen (leeren) Datenbank
echo "1. Sichere aktuellen Zustand..."
mysqldump -u root -p'V9LGz2tdR5gpDQz' askproai_db > /var/backups/askproai/empty_state_backup_$(date +%Y%m%d_%H%M%S).sql

# Verfügbare Backups anzeigen
echo ""
echo "2. Verfügbare Backups:"
echo "----------------------"
ls -la /var/www/api-gateway/*.sql | grep -E "(dump|backup)" | tail -10

echo ""
echo "Empfohlenes Backup: /var/www/api-gateway/db_backup_before_cleanup.sql"
echo ""
read -p "Möchtest du dieses Backup wiederherstellen? (j/n) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Jj]$ ]]; then
    echo ""
    echo "3. Stelle Datenbank wieder her..."
    
    # Prüfe ob Backup existiert
    if [ -f "/var/www/api-gateway/db_backup_before_cleanup.sql" ]; then
        # Importiere das Backup
        mysql -u root -p'V9LGz2tdR5gpDQz' askproai_db < /var/www/api-gateway/db_backup_before_cleanup.sql
        
        echo ""
        echo "4. Überprüfe wiederhergestellte Daten..."
        echo ""
        
        # Zeige Daten-Statistiken
        mysql -u root -p'V9LGz2tdR5gpDQz' askproai_db -e "
        SELECT 'Kunden:' as Typ, COUNT(*) as Anzahl FROM customers
        UNION ALL
        SELECT 'Termine:', COUNT(*) FROM appointments  
        UNION ALL
        SELECT 'Anrufe:', COUNT(*) FROM calls
        UNION ALL
        SELECT 'Mitarbeiter:', COUNT(*) FROM staff
        UNION ALL
        SELECT 'Filialen:', COUNT(*) FROM branches
        UNION ALL
        SELECT 'Services:', COUNT(*) FROM services;"
        
        echo ""
        echo "✅ WIEDERHERSTELLUNG ERFOLGREICH!"
        echo ""
        echo "Wichtig: Führe jetzt diese Befehle aus:"
        echo "1. php artisan config:clear"
        echo "2. php artisan cache:clear"
        echo "3. php artisan migrate --force"
        echo ""
    else
        echo "❌ Backup-Datei nicht gefunden!"
        echo "Versuche alternatives Backup..."
        
        # Liste andere Optionen
        echo ""
        echo "Alternative Backups:"
        ls -la /var/www/api-gateway/tmp/*.sql 2>/dev/null
        ls -la /var/www/api-gateway/backup*.sql 2>/dev/null
    fi
else
    echo "Wiederherstellung abgebrochen."
fi