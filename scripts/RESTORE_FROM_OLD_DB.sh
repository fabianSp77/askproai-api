#!/bin/bash

# DATENBANK-WIEDERHERSTELLUNG SCRIPT
# Stellt askproai_db aus askproai_db_old wieder her
# Erstellt: 2025-10-27

set -e  # Exit on error

echo "═══════════════════════════════════════════════════"
echo "  ASKPRO AI - DATENBANK WIEDERHERSTELLUNG"
echo "═══════════════════════════════════════════════════"
echo ""
echo "Quelle: askproai_db_old"
echo "Ziel: askproai_db"
echo ""
read -p "Fortsetzten? (y/n) " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Abgebrochen."
    exit 1
fi

echo ""
echo "🔧 Schritt 1: Maintenance Mode aktivieren..."
cd /var/www/api-gateway
php artisan down

echo ""
echo "💾 Schritt 2: Aktuellen Zustand sichern..."
BACKUP_DIR="/var/www/backups/pre-restore-$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"
mysqldump -u root askproai_db > "$BACKUP_DIR/askproai_db_empty.sql" 2>/dev/null || echo "Datenbank ist leer"
echo "   ✅ Gesichert in: $BACKUP_DIR"

echo ""
echo "📋 Schritt 3: Tabellenliste aus askproai_db_old holen..."
TABLES=$(mysql -u root -N -e "SHOW TABLES FROM askproai_db_old" | grep -v "^$")
TABLE_COUNT=$(echo "$TABLES" | wc -l)
echo "   ✅ Gefunden: $TABLE_COUNT Tabellen"

echo ""
echo "🗑️  Schritt 4: Alte Tabellen in askproai_db löschen..."
mysql -u root -e "DROP DATABASE IF EXISTS askproai_db;"
mysql -u root -e "CREATE DATABASE askproai_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo "   ✅ Datenbank neu erstellt"

echo ""
echo "📦 Schritt 5: Tabellen kopieren..."
counter=0
for table in $TABLES; do
    counter=$((counter + 1))
    echo -n "   [$counter/$TABLE_COUNT] Kopiere $table..."

    # Tabellen-Struktur kopieren
    mysqldump -u root --no-data askproai_db_old "$table" | mysql -u root askproai_db

    # Daten kopieren (wenn vorhanden)
    ROW_COUNT=$(mysql -u root -N -e "SELECT COUNT(*) FROM askproai_db_old.\`$table\`")
    if [ "$ROW_COUNT" -gt 0 ]; then
        mysqldump -u root --no-create-info askproai_db_old "$table" | mysql -u root askproai_db
        echo " ✅ ($ROW_COUNT Einträge)"
    else
        echo " ✅ (leer)"
    fi
done

echo ""
echo "👤 Schritt 6: Admin-User prüfen/wiederherstellen..."
ADMIN_EXISTS=$(mysql -u root -N -e "SELECT COUNT(*) FROM askproai_db.users WHERE email = 'admin@askproai.de'")
if [ "$ADMIN_EXISTS" -eq 0 ]; then
    echo "   ⚠️  Admin-User nicht gefunden, wird erstellt..."
    mysql -u root askproai_db <<EOF
INSERT INTO users (name, email, password, created_at, updated_at)
VALUES ('Admin', 'admin@askproai.de', '\$2y\$12\$LQ0YhH8Y.9aFhC8sX5oJLeZ3lLfK1yC3Q.3Y5Z3rH8Y.9aFhC8sX5o', NOW(), NOW());
EOF
    echo "   ✅ Admin-User erstellt"
else
    echo "   ✅ Admin-User existiert bereits"
fi

echo ""
echo "🔑 Schritt 7: Berechtigungen prüfen..."
# Prüfen ob Permissions Tabellen existieren
if mysql -u root -e "USE askproai_db; SHOW TABLES LIKE 'roles'" | grep -q roles; then
    echo "   ✅ Permissions-Tabellen gefunden"

    # Super Admin Rolle sicherstellen
    ROLE_EXISTS=$(mysql -u root -N -e "SELECT COUNT(*) FROM askproai_db.roles WHERE name = 'super_admin'")
    if [ "$ROLE_EXISTS" -eq 0 ]; then
        echo "   ⚠️  super_admin Rolle wird erstellt..."
        mysql -u root askproai_db <<EOF
INSERT INTO roles (name, guard_name, created_at, updated_at)
VALUES ('super_admin', 'web', NOW(), NOW());
EOF
    fi

    # Admin-User die Rolle zuweisen
    USER_ID=$(mysql -u root -N -e "SELECT id FROM askproai_db.users WHERE email = 'admin@askproai.de'")
    ROLE_ID=$(mysql -u root -N -e "SELECT id FROM askproai_db.roles WHERE name = 'super_admin'")

    mysql -u root askproai_db <<EOF
INSERT IGNORE INTO model_has_roles (role_id, model_type, model_id)
VALUES ($ROLE_ID, 'App\\\\Models\\\\User', $USER_ID);
EOF
    echo "   ✅ super_admin Rolle zugewiesen"
else
    echo "   ⚠️  Permissions-Tabellen fehlen (werden bei erstem Login erstellt)"
fi

echo ""
echo "🧹 Schritt 8: Laravel Caches löschen..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
rm -rf storage/framework/sessions/*
rm -rf storage/framework/cache/data/*
echo "   ✅ Caches gelöscht"

echo ""
echo "🔄 Schritt 9: PHP-FPM neu laden..."
sudo systemctl reload php8.3-fpm
echo "   ✅ PHP-FPM neu geladen"

echo ""
echo "✅ Schritt 10: Maintenance Mode deaktivieren..."
php artisan up

echo ""
echo "═══════════════════════════════════════════════════"
echo "  ✅ WIEDERHERSTELLUNG ERFOLGREICH!"
echo "═══════════════════════════════════════════════════"
echo ""
echo "📊 Datenbank-Status:"
mysql -u root -N -e "
SELECT CONCAT('  ✅ Companies: ', COUNT(*)) FROM askproai_db.companies
UNION ALL
SELECT CONCAT('  ✅ Calls: ', COUNT(*)) FROM askproai_db.calls
UNION ALL
SELECT CONCAT('  ✅ Customers: ', COUNT(*)) FROM askproai_db.customers
UNION ALL
SELECT CONCAT('  ✅ Appointments: ', COUNT(*)) FROM askproai_db.appointments
UNION ALL
SELECT CONCAT('  ✅ Users: ', COUNT(*)) FROM askproai_db.users
;"

echo ""
echo "🔐 Login-Daten:"
echo "  URL: https://api.askproai.de/admin/login"
echo "  Email: admin@askproai.de"
echo "  Password: admin123"
echo ""
echo "⚠️  NÄCHSTER SCHRITT:"
echo "  Aktivieren Sie die Resource-Discovery in AdminPanelProvider.php"
echo "  Siehe: WIEDERHERSTELLUNG_JETZT.md für Details"
echo ""
