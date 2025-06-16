#!/bin/bash
echo ""
echo "==========================="
echo "🔎 AskProAI Retell Analyse"
echo "==========================="

echo ""
echo "1) Aktive Webhook-Endpoints:"
grep -i webhook routes/web.php routes/api.php 2>/dev/null | grep -v "#" || echo "→ Kein direkter Eintrag gefunden – evtl. via RouteServiceProvider"

echo ""
echo "2) Struktur & Unique-Keys der calls-Tabelle:"
mysql -u root -p -e "USE askproai_db; SHOW CREATE TABLE calls\G"

echo ""
echo "3) Doppelte call_ids (Duplikate) in calls:"
mysql -u root -p -e "USE askproai_db; SELECT call_id, COUNT(*) as cnt FROM calls GROUP BY call_id HAVING cnt > 1;"

echo ""
echo "4) Letzte 10 Calls (Status, Dauer, Zeit, Transkript-Länge):"
mysql -u root -p -e "USE askproai_db; SELECT id, call_id, call_status, duration_sec, created_at, updated_at, LENGTH(transcript) as transcript_len FROM calls ORDER BY id DESC LIMIT 10;"

echo ""
echo "5) Gefundene Retell-Service- und Jobklassen:"
grep -Ri 'Retell' app/Services/ app/Jobs/ 2>/dev/null | grep -E 'class |function |def ' | cut -d: -f1-2 | sort | uniq

echo ""
echo "6) Gefundene API-Versionen im Code (V1/V2):"
grep -R "retell" app/Services/ 2>/dev/null | grep -Ei '(v1|v2)' | cut -d: -f1-2 | sort | uniq

echo ""
echo "7) Neueste Webhook-Ereignisse in den Logs:"
grep "Retell Webhook empfangen" storage/logs/laravel.log 2>/dev/null | tail -20

echo ""
echo "Analyse abgeschlossen."
