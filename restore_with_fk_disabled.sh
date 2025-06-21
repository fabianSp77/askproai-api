#!/bin/bash

echo "=== Stelle AskProAI Daten wieder her ==="
echo ""

# Backup current state
echo "1. Sichere aktuellen Zustand..."
mysqldump -u root -p'V9LGz2tdR5gpDQz' askproai_db > /var/backups/askproai/before_restore_$(date +%Y%m%d_%H%M%S).sql

# Import with foreign key checks disabled
echo "2. Importiere Backup (Foreign Keys temporär deaktiviert)..."
mysql -u root -p'V9LGz2tdR5gpDQz' askproai_db << EOF
SET FOREIGN_KEY_CHECKS = 0;
SOURCE /var/www/api-gateway/tmp/askproai_db_2025-06-17_03-05.sql;
SET FOREIGN_KEY_CHECKS = 1;
EOF

echo ""
echo "3. Prüfe wiederhergestellte Daten..."
mysql -u root -p'V9LGz2tdR5gpDQz' askproai_db -e "
SELECT 'Kunden:' as Typ, COUNT(*) as Anzahl FROM customers WHERE id > 0
UNION ALL SELECT 'Termine:', COUNT(*) FROM appointments WHERE id > 0
UNION ALL SELECT 'Anrufe:', COUNT(*) FROM calls WHERE id > 0
UNION ALL SELECT 'Mitarbeiter:', COUNT(*) FROM staff WHERE id > 0
UNION ALL SELECT 'Filialen:', COUNT(*) FROM branches WHERE id != ''
UNION ALL SELECT 'Services:', COUNT(*) FROM services WHERE id > 0
UNION ALL SELECT 'Firmen:', COUNT(*) FROM companies WHERE id > 0;"

echo ""
echo "4. Zeige Beispieldaten..."
echo ""
echo "=== Letzte 5 Kunden ==="
mysql -u root -p'V9LGz2tdR5gpDQz' askproai_db -e "SELECT id, name, email, phone FROM customers ORDER BY id DESC LIMIT 5;"

echo ""
echo "=== Letzte 5 Anrufe ==="
mysql -u root -p'V9LGz2tdR5gpDQz' askproai_db -e "SELECT id, from_number, call_duration, created_at FROM calls ORDER BY id DESC LIMIT 5;"

echo ""
echo "✅ Wiederherstellung abgeschlossen!"
echo ""
echo "Nächste Schritte:"
echo "1. php artisan config:clear"
echo "2. php artisan cache:clear"
echo "3. Prüfe im Admin Panel ob alle Daten da sind"