# 🚀 Quick Start: Webhooks in 5 Minuten

## ✅ Was bereits funktioniert

### Webhook empfängt Anrufe und speichert:
- **Anrufer-Telefonnummer**
- **Kundenname** (aus Gespräch extrahiert)
- **Gewünschtes Datum** (aus Gespräch extrahiert)
- **Gewünschte Uhrzeit** (aus Gespräch extrahiert)
- **Gesprächs-Transkript**
- **Anrufdauer und Kosten**

## 🎯 Einrichtung in 3 Schritten

### Schritt 1: Retell.ai konfigurieren
```
Webhook URL: https://api.askproai.de/api/retell/debug-webhook
Events: call_started, call_ended, call_analyzed
```

### Schritt 2: Test-Anruf machen
Rufe deine konfigurierte Nummer an und sage z.B.:
> "Ich möchte einen Termin am 20. Juni um 15 Uhr buchen"

### Schritt 3: Ergebnis prüfen
```bash
# SSH auf Server
ssh root@hosting215275.ae83d.netcup.net

# Letzte Anrufe anzeigen
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db \
  -e "SELECT id, from_number, extracted_name, extracted_date, extracted_time \
      FROM calls ORDER BY id DESC LIMIT 5;"
```

## 📊 Beispiel-Ausgabe
```
+-----+---------------+--------------------+----------------+----------------+
| id  | from_number   | extracted_name     | extracted_date | extracted_time |
+-----+---------------+--------------------+----------------+----------------+
| 122 | +491601234567 | Max Mustermann     | 2025-06-20     | 15:00          |
| 121 | +491607654321 | Anna Schmidt       | 2025-06-21     | 10:30          |
+-----+---------------+--------------------+----------------+----------------+
```

## 🔍 Live-Monitoring
```bash
cd /var/www/api-gateway
./monitor-retell-webhooks.sh
# Wähle Option 2: Show recent calls
```

## ⚠️ Wichtige Hinweise

### Was funktioniert:
✅ Anrufe werden erfasst  
✅ Kundendaten werden extrahiert  
✅ Alles wird in Datenbank gespeichert  

### Was noch NICHT funktioniert:
❌ Termine werden noch nicht in Cal.com erstellt  
❌ Filialenzuordnung muss manuell erfolgen  
❌ Keine Email-Bestätigungen  

## 🆘 Troubleshooting

### "Ich sehe keine Anrufe"
1. Prüfe Webhook-URL in Retell: `https://api.askproai.de/api/retell/debug-webhook`
2. Prüfe Logs: `tail -f /var/www/api-gateway/storage/logs/laravel.log | grep DEBUG`
3. Teste manuell:
```bash
curl -X POST https://api.askproai.de/api/retell/debug-webhook \
  -H "Content-Type: application/json" \
  -d '{"event":"call_ended","call":{"call_id":"test-123"}}'
```

### "Filiale wird nicht zugeordnet"
Das ist bekannt. Aktuell werden alle Anrufe der ersten Firma zugeordnet.
Manuelle Korrektur:
```sql
UPDATE calls SET branch_id = 'DEINE-BRANCH-UUID' WHERE id = CALL_ID;
```

## 📞 Support
Bei Problemen diese Dokumentation teilen:
- `/var/www/api-gateway/WEBHOOK_STATUS_DOKUMENTATION.md`
- `/var/www/api-gateway/WEBHOOK_CONFIGURATION_GUIDE.md`