# RETELL_INTEGRATION_CRITICAL.md

> 🚨 **KRITISCH**: Wichtige Informationen zur Retell.ai Integration
> Stand: 2025-06-29

## 📊 Aktueller Status

### ✅ Was funktioniert
- **Automatischer Import**: Anrufe werden alle 15 Minuten importiert
- **Branch-Zuordnung**: Telefonnummern werden korrekt zu Company UND Branch zugeordnet
- **Zeitzonenkonvertierung**: UTC-Zeiten werden automatisch nach Berlin Zeit (+2h) konvertiert
- **Live-Anzeige**: Aktive Anrufe werden im Dashboard angezeigt
- **Automatische Bereinigung**: Alte in_progress Anrufe werden nach 15 Minuten bereinigt

### ⚠️ Bekannte Probleme
1. **Webhook-Verarbeitung**: Viele Webhooks schlagen mit Signature Verification Fehler fehl
2. **Laravel Scheduler**: Lädt nur 1 Task (knowledge:watch) - Workaround über direkte Cron-Jobs
3. **Horizon**: Muss manuell gestartet werden nach Server-Restart

## 🔧 Technische Details

### Webhook-Konfiguration
```
URL: https://api.askproai.de/api/retell/webhook
Events: call_started, call_ended, call_analyzed
Signatur: Verwendet Retell API Key (KEIN separates Secret!)
```

### Cron-Jobs (Workaround für Scheduler)
```bash
# In crontab -e eingetragen:
*/15 * * * * /usr/bin/php /var/www/api-gateway/manual-retell-import.php
*/5 * * * * /usr/bin/php /var/www/api-gateway/cleanup-stale-calls.php
```

### Wichtige Dateien
- `/var/www/api-gateway/app/Services/Webhooks/RetellWebhookHandler.php` - Webhook-Verarbeitung
- `/var/www/api-gateway/app/Jobs/ProcessRetellCallStartedJob.php` - Live-Call Erstellung
- `/var/www/api-gateway/app/Jobs/ProcessRetellCallEndedJob.php` - Call-Abschluss + UTC-Konvertierung
- `/var/www/api-gateway/app/Services/PhoneNumberResolver.php` - Company/Branch Auflösung

## 🚀 Quick Fixes

### Problem: Keine neuen Anrufe sichtbar
```bash
# 1. Horizon prüfen
php artisan horizon:status

# 2. Falls nicht läuft, starten
php artisan horizon

# 3. Manueller Import
php import-retell-calls.php
```

### Problem: Webhook-Fehler
```bash
# Webhook testen
php trigger-simple-webhook.php

# Logs prüfen
tail -f storage/logs/laravel.log | grep -i retell
```

### Problem: Falsche Zeiten
```bash
# Zeitzone prüfen
php -r "echo date_default_timezone_get() . PHP_EOL;"

# Sollte Europe/Berlin sein
# Falls nicht, in .env setzen:
# APP_TIMEZONE=Europe/Berlin
```

## 📝 Wichtige Hinweise

1. **API Key = Webhook Secret**: Retell verwendet den API Key auch für Webhook-Signaturen
2. **Phone Number Mapping**: Telefonnummern MÜSSEN in der phone_numbers Tabelle mit company_id UND branch_id existieren
3. **UTC Konvertierung**: Alle Retell-Timestamps sind UTC und werden automatisch +2h konvertiert
4. **Live-Call Timeout**: Anrufe die länger als 15 Minuten "in_progress" sind, werden automatisch bereinigt

## 🔄 Datenfluss

```
1. Kunde ruft an → Retell.ai
2. Retell sendet Webhook → /api/retell/webhook
3. Webhook erstellt Job → ProcessRetellCallStartedJob (Queue: webhooks)
4. Job erstellt Call-Record mit:
   - company_id (von Telefonnummer)
   - branch_id (von Telefonnummer)
   - UTC → Berlin Zeit konvertiert
5. Call erscheint im Dashboard (LiveCallsWidget)
6. Nach Anrufende → ProcessRetellCallEndedJob aktualisiert Status
```

## 🛠️ Debugging

### SQL-Queries für Analyse
```sql
-- Letzte Anrufe prüfen
SELECT id, retell_call_id, from_number, to_number, 
       company_id, branch_id, call_status, 
       start_timestamp, created_at
FROM calls 
ORDER BY created_at DESC 
LIMIT 10;

-- Phone Number Mapping prüfen
SELECT * FROM phone_numbers 
WHERE number LIKE '%YOUR_NUMBER%';

-- Webhook Events prüfen
SELECT * FROM webhook_events 
WHERE service = 'retell' 
ORDER BY created_at DESC 
LIMIT 10;
```

### Test-Scripts
- `test-phone-resolution.php` - Testet Phone → Company/Branch Auflösung
- `verify-webhook-status.php` - Zeigt Webhook-Verarbeitungsstatus
- `import-retell-calls.php` - Manueller Import mit Debug-Output

## Kritische Konfigurationsdateien

### 1. `.env` Variablen
```bash
# Retell.ai Configuration
RETELL_TOKEN=key_6ff998f44bb8a9bae37bb7e2c8e  # API Key
RETELL_WEBHOOK_SECRET=key_6ff998f44bb8a9bae37bb7e2c8e  # Webhook Secret (gleich wie API Key)
RETELL_BASE=https://api.retellai.com
DEFAULT_RETELL_API_KEY=key_6ff998f44bb8a9bae37bb7e2c8e
DEFAULT_RETELL_AGENT_ID=agent_9a8202a740cd3120d96fcfda1e
```

### 2. Agent Konfiguration
- Agent ID: `agent_9a8202a740cd3120d96fcfda1e`
- Agent Name: "Online: Assistent für Fabian Spitzer Rechtliches/V33"
- Voice ID: `custom_voice_191b11197fd8c3e92dab972a5a`

## Häufige Fehler & Lösungen

### Fehler: 404 bei Agent API Calls
**Ursache**: Verwendung von v2 Endpoints
**Lösung**: In `app/Services/RetellV2Service.php` alle `/v2/` Prefixe entfernen

### Fehler: Webhook Signature Invalid
**Ursache**: Falsches Secret oder Format
**Lösung**: 
1. Webhook Secret = API Key setzen
2. Signature Format: `v=timestamp,d=hmac_sha256_hash`

### Fehler: Keine Calls werden importiert
**Ursache**: Horizon läuft nicht
**Lösung**: 
```bash
php artisan horizon
# oder
php artisan queue:work --queue=webhooks,default
```

## 📞 Kontakt bei Problemen

Bei kritischen Problemen:
1. Logs sammeln: `tail -n 1000 storage/logs/laravel.log > retell-debug.log`
2. Status dokumentieren: `php verify-webhook-status.php > webhook-status.txt`
3. Horizon-Status: `php artisan horizon:status`
4. Monitor: https://api.askproai.de/retell-monitor