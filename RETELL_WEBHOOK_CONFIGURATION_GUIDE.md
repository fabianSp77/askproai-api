# Retell.ai Webhook Konfiguration - Vollständige Anleitung

## Wichtige Erkenntnisse aus der Dokumentation

### 1. Webhook-Konfiguration erfolgt im Retell Dashboard
Es gibt **zwei Ebenen** für Webhook-Konfiguration:

#### Account-Level Webhook (Empfohlen)
- Konfiguration im Dashboard unter **"Webhooks Tab"**
- Erfasst Events für ALLE Agenten im Account
- Zentrale Verwaltung

#### Agent-Level Webhook
- Konfiguration auf der **Agent Detail Page**
- WICHTIG: Wenn Agent-Level Webhook gesetzt ist, wird Account-Level NICHT ausgelöst!

### 2. Verfügbare Events
- `call_started` - Wenn Anruf beginnt
- `call_ended` - Wenn Anruf endet (enthält alle Daten!)
- `call_analyzed` - Nach Analyse-Abschluss

### 3. Call Object Struktur (call_ended Event)

Die Dokumentation bestätigt, dass folgende Daten gesendet werden:

```json
{
  "event": "call_ended",
  "call": {
    // Basis Information
    "call_id": "string",
    "call_type": "phone_call|web_call",
    "agent_id": "string",
    "agent_version": "string",
    "call_status": "string",
    
    // Zeitstempel
    "start_timestamp": 1234567890,
    "end_timestamp": 1234567890,
    "duration_ms": 60000,
    
    // Anrufdetails
    "from_number": "+491234567890",
    "to_number": "+493083793369",
    "direction": "inbound|outbound",
    "disconnection_reason": "string",
    
    // Transkript (alle Formate!)
    "transcript": "text version",
    "transcript_object": [...], // Strukturiert mit Rollen
    "transcript_with_tool_calls": [...],
    
    // Analyse
    "call_summary": "string",
    "user_sentiment": "positive|negative|neutral",
    "call_successful": true,
    "in_voicemail": false,
    
    // Performance Metriken
    "latency": {
      "e2e": {...},
      "llm": {...},
      "tts": {...},
      "knowledge_base": {...}
    },
    
    // Kosten
    "product_costs": {...},
    "total_duration_seconds": 60,
    "combined_cost": 0.25,
    
    // URLs
    "recording_url": "https://...",
    "public_log_url": "https://...",
    
    // Zusätzliche Daten
    "metadata": {...},
    "retell_llm_dynamic_variables": {...},
    "collected_dynamic_variables": {...},
    "llm_token_usage": {...}
  }
}
```

## LÖSUNG: Konfiguration prüfen

### Schritt 1: Dashboard Login
1. Login bei https://app.retellai.com
2. Navigiere zu **"Webhooks"** Tab

### Schritt 2: Webhook URL prüfen
Die konfigurierte URL sollte sein:
```
https://api.askproai.de/api/retell/conversation-ended
```

**NICHT:**
- ~~https://api.askproai.de/api/retell/webhook~~ (Legacy)

### Schritt 3: Agent-Level Webhooks prüfen
1. Gehe zu jedem Agent
2. Prüfe ob Agent-Level Webhook gesetzt ist
3. **Falls ja**: Entfernen oder auf gleiche URL setzen wie Account-Level

### Schritt 4: Test durchführen
1. Mache einen Test-Anruf
2. Prüfe die Logs:
```bash
tail -f /var/www/api-gateway/storage/logs/laravel-*.log | grep -i "Retell Conversation Ended webhook received"
```

## Problem-Diagnose

### Wenn nur wenige Daten ankommen:
1. **Falscher Webhook Endpoint**: `/api/retell/webhook` statt `/api/retell/conversation-ended`
2. **Agent-Level Webhook überschreibt Account-Level**
3. **Alter Webhook Handler** verarbeitet nicht alle Felder

### Wenn gar keine Daten ankommen:
1. **Webhook URL falsch**
2. **Signatur-Verifikation schlägt fehl**
3. **Webhook nicht aktiviert**

## Erwartetes Ergebnis

Nach korrekter Konfiguration sollten ALLE diese Daten ankommen:
- ✅ Timestamps (start/end)
- ✅ Kosten-Breakdown
- ✅ Performance-Metriken
- ✅ Strukturierte Transkripte
- ✅ Audio URLs
- ✅ LLM Token Usage
- ✅ Disconnection Reason
- ✅ Call Summary & Sentiment

Die Infrastruktur ist bereits vorbereitet und wird automatisch alle Daten verarbeiten!