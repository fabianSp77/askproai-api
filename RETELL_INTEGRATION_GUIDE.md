# Retell AI Integration Guide

## Übersicht

Die Retell.ai Integration ermöglicht es, dass eingehende Anrufe automatisch von einem KI-Agenten beantwortet werden, der Termine buchen kann.

## Aktueller Status (Stand: 23.06.2025)

### ✅ Was funktioniert:
- Webhook-Empfang von Retell.ai
- Speicherung von Anrufdaten in der `calls` Tabelle
- Multi-Tenant Zuordnung über PhoneNumberResolver
- Asynchrone Webhook-Verarbeitung mit Retry-Mechanismus

### ❌ Was NICHT funktioniert:
- Automatische Terminbuchung aus Anrufen
- Webhook-Signaturverifikation (temporär deaktiviert)
- Strukturierte Datenübergabe von Retell Agent

## Datenfluss

```
1. Kunde ruft an → Retell.ai Agent antwortet
2. Agent sammelt Termindaten (PROBLEM: Format stimmt nicht)
3. call_ended Event → Webhook an /api/retell/webhook
4. WebhookProcessor → ProcessRetellCallEndedJob
5. Job versucht Appointment zu erstellen (FEHLT: Strukturierte Daten)
```

## Hauptprobleme

### 1. Fehlende Termindaten-Struktur

Der Retell Agent muss so konfiguriert werden, dass er die `collect_appointment_data` Custom Function verwendet:

```json
{
  "name": "collect_appointment_data",
  "description": "Sammelt Termindaten vom Anrufer",
  "parameters": {
    "datum": {"type": "string", "description": "Gewünschtes Datum (z.B. 25.06.2025)"},
    "uhrzeit": {"type": "string", "description": "Gewünschte Uhrzeit (z.B. 14:30)"},
    "name": {"type": "string", "description": "Name des Kunden"},
    "telefonnummer": {"type": "string", "description": "Telefonnummer"},
    "dienstleistung": {"type": "string", "description": "Gewünschte Dienstleistung"},
    "email": {"type": "string", "description": "E-Mail-Adresse (optional)"}
  }
}
```

### 2. Webhook URL Konfiguration

Die Webhook URL muss in Retell.ai konfiguriert werden:
- URL: `https://api.askproai.de/api/retell/webhook`
- Events: `call_started`, `call_ended`, `call_analyzed`

### 3. Agent Prompt

Der Agent Prompt muss explizit anweisen, die Custom Function zu verwenden:

```
Wenn der Kunde einen Termin buchen möchte, sammle alle notwendigen Informationen und verwende die Funktion 'collect_appointment_data' mit den gesammelten Daten.
```

## Debug-Tools

### 1. Debug Retell Integration
```bash
php debug-retell-integration.php
```

Zeigt:
- Letzte Webhooks
- Letzte Anrufe
- Phone Number Konfiguration
- Retell API Verbindung

### 2. Check Agent Configuration
```bash
php check-retell-agent-config.php
```

Prüft:
- Agent Konfiguration
- Webhook URL
- Custom Functions
- Prompt Keywords

### 3. Logs analysieren
```bash
# Webhook Logs
tail -f storage/logs/laravel.log | grep -i retell

# Nur Fehler
tail -f storage/logs/laravel.log | grep -E "(ERROR|WARNING).*retell"

# Appointment Booking Versuche
tail -f storage/logs/laravel.log | grep "appointment"
```

## SQL Queries für Debugging

### Letzte Webhooks
```sql
SELECT * FROM webhook_events 
WHERE provider = 'retell' 
ORDER BY created_at DESC 
LIMIT 10;
```

### Anrufe ohne Termine
```sql
SELECT id, retell_call_id, from_number, created_at,
       JSON_EXTRACT(metadata, '$.appointment_intent_detected') as intent
FROM calls 
WHERE appointment_id IS NULL 
  AND created_at >= NOW() - INTERVAL 7 DAY
ORDER BY created_at DESC;
```

### Dynamic Variables prüfen
```sql
SELECT id, retell_call_id, 
       JSON_KEYS(retell_llm_dynamic_variables) as vars
FROM calls 
WHERE retell_llm_dynamic_variables IS NOT NULL
ORDER BY created_at DESC 
LIMIT 10;
```

## Sofortmaßnahmen

1. **Retell Agent konfigurieren**:
   - Custom Function hinzufügen
   - Prompt anpassen
   - Webhook URL setzen

2. **Monitoring aktivieren**:
   ```bash
   # Terminal 1: Logs beobachten
   tail -f storage/logs/laravel.log | grep -i retell
   
   # Terminal 2: Queue Worker
   php artisan horizon
   ```

3. **Test-Anruf durchführen**:
   - Nummer anrufen
   - Termin buchen versuchen
   - Logs prüfen

## Erweiterte Konfiguration

### MCP Server Integration

Die MCP Server können für erweiterte Funktionen genutzt werden:

```php
// Retell MCP Server Funktionen
$mcp = new RetellMCPServer();

// Anrufe importieren
$mcp->importRecentCalls(['company_id' => 1]);

// Agent Details abrufen
$mcp->getAgent(['company_id' => 1]);

// Statistiken
$mcp->getCallStats(['company_id' => 1, 'days' => 7]);
```

### Event Type Matching

Der `EventTypeMatchingService` kann Services intelligent zuordnen:

```php
$matcher = new EventTypeMatchingService();
$result = $matcher->findMatchingEventType(
    'Haarschnitt',     // Kundenanfrage
    $branch,           // Branch Objekt
    'Max Mustermann',  // Optional: Mitarbeiterwunsch
    null              // Optional: Zeitpräferenz
);
```

## Nächste Schritte

1. **Retell Agent in Retell.ai Dashboard konfigurieren**
2. **Custom Function implementieren**
3. **Webhook URL verifizieren**
4. **Test-Anrufe durchführen**
5. **Logs überwachen und debuggen**

## Support

Bei Problemen:
1. Logs prüfen (`storage/logs/laravel.log`)
2. Debug-Scripts ausführen
3. Retell.ai Support kontaktieren für Agent-Konfiguration
4. AskProAI Development Team für Backend-Issues