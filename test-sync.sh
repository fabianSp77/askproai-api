#!/bin/bash

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║        Cal.com Sync Test - Live Verification                 ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

echo "📊 Schritt 1: Aktueller Status"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
mysql askproai_db -e "SELECT id, customer_id, service_id, starts_at, status, sync_origin, calcom_sync_status FROM appointments WHERE status = 'confirmed' AND calcom_v2_booking_id IS NOT NULL LIMIT 5;" | column -t
echo ""

echo "🧪 Schritt 2: Test-Anweisung"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Bitte führen Sie JETZT einen der folgenden Tests durch:"
echo ""
echo "Option A - Termin verschieben:"
echo "  1. Gehen Sie zu: https://api.askproai.de/admin/appointments"
echo "  2. Wählen Sie einen Termin mit Status 'Confirmed'"
echo "  3. Klicken Sie auf 'Verschieben' (🗓️ Icon)"
echo "  4. Ändern Sie die Zeit (z.B. +1 Stunde)"
echo "  5. Bestätigen Sie"
echo ""
echo "Option B - Termin stornieren:"
echo "  1. Gehen Sie zu: https://api.askproai.de/admin/appointments"
echo "  2. Wählen Sie einen Termin"
echo "  3. Klicken Sie auf 'Stornieren' (❌ Icon)"
echo "  4. Bestätigen Sie"
echo ""
echo "Option C - Neuen Termin erstellen:"
echo "  1. Gehen Sie zu: https://api.askproai.de/admin/appointments/create"
echo "  2. Wählen Sie Kunde und Dienstleistung"
echo "  3. Wählen Sie Zeit (morgen, 14:00 Uhr)"
echo "  4. Erstellen Sie den Termin"
echo ""
echo "⏰ Warten Sie 10 Sekunden nach der Aktion..."
read -p "Drücken Sie ENTER wenn die Aktion durchgeführt wurde..."
echo ""

echo "⏳ Warte 10 Sekunden für Job-Verarbeitung..."
sleep 10
echo ""

echo "📊 Schritt 3: Prüfe neuesten Termin"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
mysql askproai_db -e "SELECT id, status, sync_origin, calcom_sync_status, sync_job_id, updated_at FROM appointments ORDER BY updated_at DESC LIMIT 1;" | column -t
echo ""

echo "📝 Schritt 4: Prüfe Sync-Logs"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ -f /var/www/api-gateway/storage/logs/calcom.log ]; then
    echo "✅ Calcom Log existiert:"
    tail -20 /var/www/api-gateway/storage/logs/calcom.log
else
    echo "⚠️  Calcom Log noch nicht erstellt (noch keine Events gefeuert)"
fi
echo ""

echo "🔍 Schritt 5: Prüfe Queue Jobs"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
PENDING_JOBS=$(mysql askproai_db -N -e "SELECT COUNT(*) FROM jobs;")
if [ "$PENDING_JOBS" -gt 0 ]; then
    echo "⏳ Pending Jobs: $PENDING_JOBS"
    mysql askproai_db -e "SELECT id, queue, attempts, created_at FROM jobs ORDER BY id DESC LIMIT 3;" | column -t
else
    echo "✅ Keine pending Jobs (alle wurden verarbeitet)"
fi
echo ""

echo "📈 Schritt 6: Sync-Statistik"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
mysql askproai_db -e "
SELECT
    sync_origin,
    calcom_sync_status,
    COUNT(*) as anzahl
FROM appointments
WHERE sync_origin IS NOT NULL
GROUP BY sync_origin, calcom_sync_status
ORDER BY sync_origin, calcom_sync_status;
" | column -t
echo ""

echo "✅ Erfolgs-Kriterien:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1. ✅ sync_origin = 'admin' (wenn via Admin UI geändert)"
echo "2. ✅ calcom_sync_status = 'synced' oder 'pending'"
echo "3. ✅ sync_job_id ist gesetzt"
echo "4. ✅ Calcom Log zeigt: 'Dispatching Cal.com sync job'"
echo "5. ✅ Keine Fehler in den Logs"
echo ""

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║                     Test abgeschlossen                        ║"
echo "╚══════════════════════════════════════════════════════════════╝"
