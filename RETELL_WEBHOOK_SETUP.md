# Retell.ai Webhook Setup

## Problem: "Es werden keine Anrufe eingespielt"

### Ursachen
1. **Fehlende Webhook-Registrierung**: Die Webhook-URL muss bei Retell.ai registriert sein
2. **Fehlende API Keys**: Company hatte keinen `retell_api_key` gesetzt
3. **Queue Worker**: Horizon muss laufen, um Jobs zu verarbeiten

### Lösung

#### 1. Webhook-URL bei Retell.ai registrieren

Die Webhook-URL muss im Retell.ai Dashboard konfiguriert werden:

```
https://yourdomain.com/api/retell/webhook
```

**Wichtig**: 
- Die URL muss öffentlich erreichbar sein
- HTTPS ist erforderlich
- Die Signatur-Verifizierung ist aktiviert (`verify.retell.signature` Middleware)

#### 2. API Key Setup

```bash
# In .env setzen:
RETELL_TOKEN=key_6ff998ba48e842092e04a5455d19
RETELL_WEBHOOK_SECRET=key_6ff998ba48e842092e04a5455d19
RETELL_BASE=https://api.retellai.com
```

#### 3. Manuelle Anruf-Synchronisation

Falls Webhooks verpasst wurden, können Anrufe manuell importiert werden:

**Option A: Über die UI**
- Gehe zur Anrufliste
- Klicke auf "Anrufe abrufen"
- Bestätige den Import

**Option B: Über die Kommandozeile**
```bash
php fix-retell-import.php
```

#### 4. Queue Worker sicherstellen

```bash
# Horizon muss laufen:
php artisan horizon

# Oder einzelner Worker:
php artisan queue:work --queue=webhooks
```

### Debugging

#### Prüfen ob Webhooks ankommen:
```bash
# Logs prüfen
tail -f storage/logs/laravel.log | grep -i retell

# Webhook-Tabelle prüfen
php artisan tinker
>>> \App\Models\RetellWebhook::latest()->take(5)->get();
```

#### API-Verbindung testen:
```bash
php test-retell-api.php
```

### Automatischer Flow

1. **Anruf bei Retell.ai** → 
2. **Webhook an `/api/retell/webhook`** → 
3. **Signatur-Verifizierung** → 
4. **Job in Queue** → 
5. **ProcessRetellCallEndedJob** → 
6. **Call in Datenbank**

### Konfiguration in Retell.ai

Im Retell.ai Dashboard unter "Webhooks" folgende Events aktivieren:
- `call_ended` (wichtigste Event für vollständige Daten)
- `call_started` (optional, für Echtzeit-Tracking)
- `call_analyzed` (optional, für AI-Insights)

Webhook Secret im Dashboard generieren und in `.env` als `RETELL_WEBHOOK_SECRET` eintragen.