#!/usr/bin/env bash
set -Eeuo pipefail

# Trap für automatischen Rollback bei Fehler
trap 'handle_error' ERR

handle_error() {
    echo ""
    echo "======================================================"
    echo "❌ FEHLER ERKANNT → AUTOMATISCHER ROLLBACK"
    echo "======================================================"
    echo ""
    echo "Fehler in Zeile $BASH_LINENO: $BASH_COMMAND"
    echo ""
    echo "Starte Rollback..."
    bash /var/www/api-gateway/tests/rollback-flags.sh
    echo ""
    echo "======================================================"
    echo "⚠️  ROLLBACK ABGESCHLOSSEN"
    echo "======================================================"
    echo ""
    echo "System wurde auf sicheren Zustand zurückgesetzt."
    echo "Prüfe Logs für Details: tail -f storage/logs/laravel.log"
    echo ""
    exit 1
}

echo "======================================================"
echo "GO-LIVE MIT AUTO-ROLLBACK ABSICHERUNG"
echo "======================================================"
echo ""
echo "Bei Fehler wird automatisch ein Rollback durchgeführt."
echo ""

# Hauptskript ausführen
/var/www/api-gateway/deploy/go-live.sh

echo ""
echo "======================================================"
echo "✅ GO-LIVE ERFOLGREICH OHNE FEHLER"
echo "======================================================"
echo ""