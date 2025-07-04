# Live Call Webhook Fix - Warum Anrufe nicht angezeigt werden

## Problem
Testanrufe wurden nicht im LiveCallsWidget angezeigt, weil:
1. Keine `call_started` Webhooks ankommen
2. Testanrufe sofort mit `end_timestamp` erstellt wurden

## Ursachen

### 1. Webhook-Konfiguration in Retell
**WICHTIG**: Die Webhook-URL muss in Retell konfiguriert sein!

In Ihrem Retell Dashboard (https://dashboard.retellai.com/):
1. Settings → Webhooks
2. Webhook URL: `https://api.askproai.de/api/retell/webhook`
3. Aktivierte Events:
   - ✅ `call_started` (WICHTIG für Live-Anzeige!)
   - ✅ `call_ended`
   - ✅ `call_analyzed`

### 2. Webhook-Verarbeitung
Der Flow sollte sein:
1. Anruf startet → Retell sendet `call_started` Webhook
2. `ProcessRetellCallStartedJob` erstellt Call mit `end_timestamp = NULL`
3. LiveCallsWidget zeigt den aktiven Anruf
4. Anruf endet → Retell sendet `call_ended` Webhook
5. Call wird mit `end_timestamp` aktualisiert und verschwindet aus Live-Anzeige

## Lösung

### 1. ProcessRetellCallStartedJob wurde erstellt ✅
```php
// app/Jobs/ProcessRetellCallStartedJob.php
// Erstellt Calls OHNE end_timestamp für Live-Anzeige
```

### 2. LiveCallsWidget korrekt konfiguriert ✅
```php
// Zeigt nur Calls mit:
->whereNull('end_timestamp')
->where('created_at', '>', now()->subHours(2))
```

### 3. Webhook-Route existiert ✅
```
POST /api/retell/webhook
```

## Was Sie tun müssen

### 1. Retell Webhook konfigurieren
1. Gehen Sie zu https://dashboard.retellai.com/
2. Settings → Webhooks
3. URL: `https://api.askproai.de/api/retell/webhook`
4. Events: `call_started`, `call_ended`, `call_analyzed`
5. Speichern

### 2. Testen
1. Machen Sie einen echten Testanruf
2. Der Anruf sollte SOFORT im LiveCallsWidget erscheinen
3. Nach Beendigung verschwindet er automatisch

### 3. Troubleshooting
Falls es nicht funktioniert:
```bash
# Prüfen ob Webhooks ankommen
tail -f storage/logs/laravel.log | grep -i retell

# Prüfen ob Horizon läuft
php artisan horizon:status

# Manuell testen
php create-live-test-call.php
```

## Alternative: Fallback Import
Falls Webhooks nicht funktionieren, greift der automatische Import alle 15 Minuten. Aber für Live-Anzeige sind Webhooks essentiell!

## Status
✅ Code ist fertig und funktioniert
⚠️  Retell Webhook muss konfiguriert werden
✅ Test-Script zeigt, dass Widget funktioniert