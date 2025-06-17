# Retell.ai Webhook Setup - Final

## ✅ Webhook-URL für Retell.ai Dashboard

```
https://api.askproai.de/api/retell/webhook
```

## 📋 Einrichtung im Retell.ai Dashboard

1. **Login** bei Retell.ai: https://app.retellai.com

2. **Navigation**: Settings → Webhooks (oder API → Webhooks)

3. **Neuen Webhook hinzufügen**:
   - **URL**: `https://api.askproai.de/api/retell/webhook`
   - **Secret**: `key_6ff998ba48e842092e04a5455d19` (aus .env)
   - **Events auswählen**:
     - ✅ `call_ended` (WICHTIG - enthält alle Daten)
     - ✅ `call_started` (optional - für Echtzeit-Status)
     - ✅ `call_analyzed` (optional - für AI-Insights)

4. **Speichern** und **Test** durchführen (falls verfügbar)

## 🧪 Webhook testen

Nach der Einrichtung kannst du testen:

1. **Mache einen Test-Anruf** an deine Retell.ai Nummer
2. **Warte 1-2 Minuten** nach Anrufende
3. **Prüfe die Anrufliste** im Admin-Panel

Oder prüfe direkt:
```bash
# Logs checken
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i retell

# Webhook-Tabelle prüfen
php artisan tinker
>>> \App\Models\RetellWebhook::latest()->first();
```

## ✅ Bestätigung dass alles funktioniert

- **Webhook-Endpoint**: https://api.askproai.de/api/retell/webhook ✅
- **Signatur-Verifizierung**: Aktiv mit korrektem Secret ✅
- **Queue-Worker**: Horizon läuft ✅
- **Manuelle Imports**: Funktionieren bereits ✅

Sobald der Webhook im Retell.ai Dashboard eingetragen ist, werden alle neuen Anrufe automatisch in die Anrufliste importiert!

## 🚨 Wichtige Hinweise

1. **HTTPS ist Pflicht** - ✅ (api.askproai.de hat SSL)
2. **Secret muss übereinstimmen** - ✅ (key_6ff998ba48e842092e04a5455d19)
3. **Horizon muss laufen** - Stelle sicher mit: `php artisan horizon:status`

## 📞 Support

Falls Probleme auftreten:
1. Prüfe die Logs: `tail -f storage/logs/laravel.log`
2. Teste manuellen Import: Button "Anrufe abrufen"
3. Prüfe Webhook-Status in Retell.ai Dashboard