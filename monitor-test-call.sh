#!/bin/bash

# Live Monitoring für Testanruf
echo "🔍 LIVE MONITORING GESTARTET"
echo "============================"
echo ""
echo "Überwache folgende Daten:"
echo "- Webhook-Aufrufe"
echo "- Neue Calls in der Datenbank"
echo "- Cache-Updates"
echo "- Appointments"
echo ""
echo "Drücken Sie Ctrl+C zum Beenden"
echo ""
echo "LIVE LOGS:"
echo "----------"

# Monitor logs for Retell webhooks
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -E "RETELL|webhook|collect_appointment|ProcessRetellCallEndedJob|appointment_data" --color=always