#!/bin/bash

# Demo Screenshot Creation Script
# Erstellt: 16.07.2025, 19:15 Uhr

echo "📸 Erstelle Demo-Screenshots für morgen..."
echo "=========================================="

# Create screenshot directory
SCREENSHOT_DIR="$HOME/Desktop/demo-screenshots-2025-07-16"
mkdir -p "$SCREENSHOT_DIR"

echo "✅ Screenshot-Verzeichnis erstellt: $SCREENSHOT_DIR"
echo ""

# URLs für Screenshots
ADMIN_URL="https://api.askproai.de/admin"
BUSINESS_URL="https://api.askproai.de/business"
KUNDENVERWALTUNG_URL="https://api.askproai.de/admin/kundenverwaltung"

echo "📌 Bitte folgende Schritte manuell durchführen:"
echo ""
echo "1. Browser öffnen (Chrome/Firefox)"
echo "2. Browser-Fenster maximieren"
echo "3. Zoom auf 100% stellen"
echo "4. Bookmarks ausblenden"
echo "5. Extensions deaktivieren"
echo ""

echo "📝 Screenshot-Liste:"
echo "==================="
echo ""
echo "Screenshot 1: Admin Login"
echo "- URL: $ADMIN_URL"
echo "- Filename: 01_admin_login.png"
echo "- ⏸️  Drücke Enter wenn bereit..."
read

echo "Screenshot 2: Admin Dashboard (nach Login als demo@askproai.de)"
echo "- Multi-Company Widget muss sichtbar sein!"
echo "- Filename: 02_admin_dashboard_multicompany.png"
echo "- ⏸️  Drücke Enter wenn bereit..."
read

echo "Screenshot 3: Kundenverwaltung"
echo "- URL: $KUNDENVERWALTUNG_URL"
echo "- Filename: 03_kundenverwaltung_overview.png"
echo "- ⏸️  Drücke Enter wenn bereit..."
read

echo "Screenshot 4: Company Details (TechPartner GmbH)"
echo "- Klick auf TechPartner in der Liste"
echo "- Filename: 04_company_details_reseller.png"
echo "- ⏸️  Drücke Enter wenn bereit..."
read

echo "Screenshot 5: Portal Switch Button"
echo "- Hover über 'Portal öffnen' Button"
echo "- Filename: 05_portal_switch_button.png"
echo "- ⏸️  Drücke Enter wenn bereit..."
read

echo "Screenshot 6: Client Portal Dashboard"
echo "- Nach Switch zu Dr. Schmidt"
echo "- URL: $BUSINESS_URL"
echo "- Filename: 06_client_portal_dashboard.png"
echo "- ⏸️  Drücke Enter wenn bereit..."
read

echo "Screenshot 7: Client Calls List"
echo "- Calls-Seite im Business Portal"
echo "- Filename: 07_client_calls_list.png"
echo "- ⏸️  Drücke Enter wenn bereit..."
read

echo "Screenshot 8: Balance Management"
echo "- Zurück ins Admin Portal"
echo "- PrepaidBalances Seite"
echo "- Filename: 08_balance_management.png"
echo "- ⏸️  Drücke Enter wenn bereit..."
read

echo ""
echo "✅ Screenshot-Checkliste abgeschlossen!"
echo ""
echo "📁 Bitte speichere alle Screenshots in:"
echo "   $SCREENSHOT_DIR"
echo ""
echo "💾 Backup-Empfehlung:"
echo "   1. Google Drive/Dropbox Upload"
echo "   2. USB-Stick Kopie"
echo "   3. Email an dich selbst"
echo ""
echo "🎯 Fertig für die Demo morgen!"