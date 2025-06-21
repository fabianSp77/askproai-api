# ✅ ASKPROAI QUICK SETUP CHECKLIST

## 🚀 IN 5 MINUTEN PRODUCTION-READY!

### 1️⃣ Datenbank-Konfiguration (2 Min)
```bash
# Als root einloggen
mysql -u root -p'V9LGz2tdR5gpDQz'

# In die richtige DB wechseln
USE askproai_db;

# Kritische Updates durchführen
UPDATE branches 
SET 
    calcom_event_type_id = 2026361,     -- Ihre Cal.com Event Type ID hier!
    retell_agent_id = 'agent_xxx',      -- Ihre Retell Agent ID hier!
    is_active = 1
WHERE id = '14b9996c-4ebe-11f0-b9c1-0ad77e7a9793';

# Verify
SELECT name, calcom_event_type_id, retell_agent_id, is_active 
FROM branches 
WHERE company_id = 1;
```

### 2️⃣ Retell.ai Konfiguration (2 Min)

#### A) In Retell Dashboard:
1. Agent öffnen
2. Webhook URL: `https://api.askproai.de/api/webhooks/retell`
3. Events aktivieren:
   - ✅ call_started
   - ✅ call_ended
   - ✅ call_analyzed

#### B) Agent Prompt Update:
```
Du bist der KI-Assistent von AskProAI.

WICHTIGE ANWEISUNGEN:
- Höflich und professionell
- Termine nur zu Geschäftszeiten (Mo-Fr 9-18 Uhr)
- Bei Verfügbarkeitsprüfung IMMER Alternativen anbieten
- Kundendaten bestätigen lassen

VERFÜGBARE SERVICES:
- Beratungsgespräch (30 Min)
- Technische Analyse (60 Min)
- Implementierung (120 Min)
```

#### C) Custom Functions → MCP Functions:
```json
{
  "name": "check_availability",
  "webhook_url": "https://api.askproai.de/api/mcp/retell/function-call",
  "description": "Prüfe verfügbare Termine",
  "parameters": {
    "date": "YYYY-MM-DD Format",
    "time": "HH:MM Format",
    "service": "Name des Services"
  }
}
```

### 3️⃣ Cal.com Konfiguration (1 Min)

1. Event Type ID finden:
   - Cal.com Dashboard → Event Types
   - URL enthält ID: `cal.com/event-types/2026361`

2. Webhook bereits konfiguriert ✅
   - URL: `https://api.askproai.de/api/webhooks/calcom`

### 4️⃣ Schnelltest

```bash
# Test 1: MCP Health Check
curl https://api.askproai.de/api/health/detailed

# Test 2: Webhook Health
curl https://api.askproai.de/api/webhooks/health

# Test 3: Retell Connection
php artisan mcp:test retell 1

# Test 4: Cal.com Connection  
php artisan mcp:test calcom 1
```

### 5️⃣ Live Monitoring

```bash
# Terminal 1: Logs beobachten
tail -f storage/logs/laravel.log | grep -E "MCP|webhook|call_"

# Terminal 2: Queue Worker
php artisan horizon

# Browser: Dashboards öffnen
open https://api.askproai.de/admin/mcp-dashboard
open https://api.askproai.de/admin/webhook-monitor
```

## 🎯 TESTANRUF DURCHFÜHREN

1. **Nummer anrufen**: +49 30 837 93 369
2. **Beispiel-Dialog**:
   - "Hallo, ich möchte einen Beratungstermin vereinbaren"
   - "Haben Sie morgen um 14 Uhr Zeit?"
   - "Mein Name ist Max Mustermann"
   - "Meine Telefonnummer ist 0171-1234567"

3. **Erwartetes Ergebnis**:
   - ✅ Verfügbarkeit wird geprüft
   - ✅ Alternative Zeiten werden angeboten
   - ✅ Termin wird gebucht
   - ✅ Bestätigung per E-Mail

## 🚨 TROUBLESHOOTING

### Problem: "No agent found"
```sql
UPDATE branches SET retell_agent_id = 'agent_xxx' WHERE company_id = 1;
```

### Problem: "Event type not found"  
```sql
UPDATE branches SET calcom_event_type_id = 2026361 WHERE company_id = 1;
```

### Problem: Webhook kommt nicht an
```bash
# Webhook manuell testen
curl -X POST https://api.askproai.de/api/test/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": true}'
```

## ✅ FERTIG!

Das System ist jetzt vollständig konfiguriert und nutzt ALLE MCP-Funktionen optimal!