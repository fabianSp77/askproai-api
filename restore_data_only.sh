#!/bin/bash

echo "=== AskProAI Datenwiederherstellung (nur Daten) ==="
echo ""

# Extract only INSERT statements from backup
echo "1. Extrahiere Daten aus Backup..."
grep "INSERT INTO" /var/www/api-gateway/tmp/askproai_db_2025-06-17_03-05.sql > /tmp/inserts_only.sql

# Import data with FK checks disabled
echo "2. Importiere nur die Daten..."
mysql -u root -p'V9LGz2tdR5gpDQz' askproai_db << 'EOF'
SET FOREIGN_KEY_CHECKS = 0;

-- Clear existing data first
TRUNCATE TABLE appointments;
TRUNCATE TABLE calls;
TRUNCATE TABLE customers;
TRUNCATE TABLE staff;
TRUNCATE TABLE services;
TRUNCATE TABLE branches;

-- Import data
SOURCE /tmp/inserts_only.sql;

SET FOREIGN_KEY_CHECKS = 1;
EOF

echo ""
echo "3. Prüfe wiederhergestellte Daten..."
mysql -u root -p'V9LGz2tdR5gpDQz' askproai_db -e "
SELECT 'Kunden:' as Typ, COUNT(*) as Anzahl FROM customers
UNION ALL SELECT 'Termine:', COUNT(*) FROM appointments  
UNION ALL SELECT 'Anrufe:', COUNT(*) FROM calls
UNION ALL SELECT 'Mitarbeiter:', COUNT(*) FROM staff
UNION ALL SELECT 'Filialen:', COUNT(*) FROM branches
UNION ALL SELECT 'Services:', COUNT(*) FROM services;"

echo ""
echo "4. Zeige einige wiederhergestellte Daten..."
echo ""
echo "=== Beispiel Kunden ==="
mysql -u root -p'V9LGz2tdR5gpDQz' askproai_db -e "SELECT id, name, email, phone FROM customers LIMIT 5;"

echo ""
echo "=== Beispiel Anrufe ==="  
mysql -u root -p'V9LGz2tdR5gpDQz' askproai_db -e "SELECT id, from_number, call_duration FROM calls LIMIT 5;"

# Clean up
rm -f /tmp/inserts_only.sql

echo ""
echo "✅ Datenwiederherstellung abgeschlossen!"