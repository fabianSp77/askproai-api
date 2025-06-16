# Retell.ai Datenstruktur-Analyse

## Zusammenfassung
Diese Analyse vergleicht die aktuelle Implementierung der Call-Datenstruktur in AskProAI mit den verfügbaren Daten aus der Retell.ai API-Dokumentation.

## 1. Aktuelle Datenbankstruktur (Calls-Tabelle)

### Vorhandene Felder in der Calls-Tabelle:
```php
// Aus den Migrationen und Call Model identifiziert:
- id
- call_id
- external_id
- retell_call_id (unique)
- caller
- from_number
- to_number
- call_status
- call_successful (boolean)
- call_type
- duration_sec
- duration_minutes
- cost
- cost_cents
- transcript
- transcription_id
- recording_url
- audio_url
- video_url
- analysis (JSON)
- webhook_data (JSON)
- raw (JSON)
- metadata (JSON)
- details (JSON)
- tags (JSON)
- sentiment
- notes
- customer_id
- appointment_id
- agent_id
- company_id (tenant_id)
- branch_id
- staff_id
- created_at
- updated_at
```

## 2. Retell.ai Webhook Datenfelder (laut API-Dokumentation)

### Webhook Event: "call_ended"
```json
{
  "event": "call_ended",
  "call": {
    // Basis-Informationen
    "call_type": "phone_call|web_call",
    "call_id": "uuid",
    "agent_id": "agent_uuid",
    "call_status": "registered|ongoing|ended|error",
    
    // Telefonnummern (bei phone_call)
    "from_number": "+491234567890",
    "to_number": "+498901234567",
    "direction": "inbound|outbound",
    
    // Zeitstempel
    "start_timestamp": 1234567890,
    "end_timestamp": 1234567890,
    
    // Anrufbeendigung
    "disconnection_reason": "user_hangup|agent_hangup|call_transfer|...",
    
    // Gesprächsdaten
    "transcript": "Vollständiges Transkript...",
    "transcript_object": [
      {"role": "agent", "content": "..."},
      {"role": "user", "content": "..."}
    ],
    "transcript_with_tool_calls": [...],
    
    // Custom Daten
    "metadata": {},
    "retell_llm_dynamic_variables": {},
    
    // Datenschutz
    "opt_out_sensitive_data_storage": false,
    
    // Zusätzliche Felder (via API abrufbar)
    "duration_ms": 120000,
    "recording_url": "https://...",
    "public_log_url": "https://...",
    "latency": {
      "p50": 123,
      "p90": 234,
      "p95": 345,
      "p99": 456,
      "max": 567,
      "min": 89,
      "num": 100
    },
    "call_cost": {
      "total_cost": 0.123,
      "transcription_cost": 0.01,
      "llm_cost": 0.05,
      "synthesis_cost": 0.063,
      "vapi_cost": 0.0
    },
    "llm_token_usage": {
      "total_tokens": 1234,
      "prompt_tokens": 800,
      "completion_tokens": 434
    }
  }
}
```

## 3. Fehlende Datenfelder

### Kritische fehlende Felder:
1. **start_timestamp** / **end_timestamp** - Präzise Zeitstempel für Anrufbeginn/-ende
2. **disconnection_reason** - Wichtig für Qualitätsanalyse
3. **transcript_object** - Strukturiertes Transkript mit Rollen
4. **transcript_with_tool_calls** - Tool/Function Call Tracking
5. **direction** (inbound/outbound) - Anrufrichtung
6. **retell_llm_dynamic_variables** - Dynamische Variablen aus dem Gespräch
7. **opt_out_sensitive_data_storage** - Datenschutz-Flag

### Nützliche Performance-Metriken:
8. **duration_ms** - Genauere Dauer in Millisekunden
9. **public_log_url** - Link zu öffentlichen Logs
10. **latency** (Objekt) - Detaillierte Latenz-Metriken:
    - p50, p90, p95, p99 Perzentile
    - min/max Werte
    - Anzahl Messungen

### Kostenanalyse-Felder:
11. **call_cost** (Objekt) - Detaillierte Kostenaufschlüsselung:
    - total_cost
    - transcription_cost
    - llm_cost  
    - synthesis_cost
    - vapi_cost

### LLM-Nutzungsstatistiken:
12. **llm_token_usage** (Objekt):
    - total_tokens
    - prompt_tokens
    - completion_tokens

## 4. Implementierungslücken

### ProcessRetellWebhookJob:
- Speichert nur grundlegende Felder
- Keine Verarbeitung der erweiterten Metriken
- Fehlende Speicherung von Performance-Daten
- Keine Kostenaufschlüsselung

### RetellConversationEndedController:
- Sehr minimale Implementierung
- Nutzt nur call_id, tmp_call_id und duration
- Ignoriert alle anderen wertvollen Daten

### Call Model:
- Hat Felder für einige Daten (raw, webhook_data, analysis)
- Aber keine strukturierte Extraktion der Retell-spezifischen Felder
- Einfache Sentiment-Analyse statt Nutzung der Retell-Daten

## 5. Empfehlungen

### Datenbank-Erweiterungen:
```sql
ALTER TABLE calls ADD COLUMN start_timestamp BIGINT NULL;
ALTER TABLE calls ADD COLUMN end_timestamp BIGINT NULL;
ALTER TABLE calls ADD COLUMN disconnection_reason VARCHAR(100) NULL;
ALTER TABLE calls ADD COLUMN direction VARCHAR(20) NULL;
ALTER TABLE calls ADD COLUMN transcript_object JSON NULL;
ALTER TABLE calls ADD COLUMN transcript_with_tools JSON NULL;
ALTER TABLE calls ADD COLUMN latency_metrics JSON NULL;
ALTER TABLE calls ADD COLUMN cost_breakdown JSON NULL;
ALTER TABLE calls ADD COLUMN llm_usage JSON NULL;
ALTER TABLE calls ADD COLUMN public_log_url VARCHAR(500) NULL;
ALTER TABLE calls ADD COLUMN retell_dynamic_variables JSON NULL;
ALTER TABLE calls ADD COLUMN opt_out_sensitive_data BOOLEAN DEFAULT FALSE;
```

### Webhook-Verarbeitung verbessern:
1. RetellConversationEndedController sollte alle Felder verarbeiten
2. ProcessRetellWebhookJob sollte erweitert werden für:
   - Strukturierte Transkript-Speicherung
   - Performance-Metriken Extraktion
   - Kostenanalyse
   - Tool-Call Tracking

### Neue Services erstellen:
1. **RetellAnalyticsService** - Für Performance-Auswertung
2. **CallCostCalculatorService** - Für Kostenanalyse
3. **TranscriptAnalyzerService** - Für erweiterte Transkript-Analyse

### Monitoring & Reporting:
- Dashboard-Widgets für Latenz-Metriken
- Kostenübersicht pro Tenant
- Disconnection-Reason Analyse
- LLM-Token-Nutzung Tracking

## 6. Prioritäten

### Sofort umsetzen:
1. Timestamps und disconnection_reason speichern
2. Vollständige webhook_data Speicherung sicherstellen
3. Cost breakdown extrahieren und speichern

### Kurzfristig (1-2 Wochen):
4. Strukturiertes Transkript implementieren
5. Latenz-Metriken Dashboard
6. Erweiterte Webhook-Verarbeitung

### Mittelfristig (1 Monat):
7. Analytics Service aufbauen
8. Tool-Call Tracking
9. Erweiterte Reporting-Features

Diese Analyse zeigt, dass AskProAI aktuell nur einen Bruchteil der verfügbaren Retell.ai Daten nutzt. Die Implementierung der fehlenden Felder würde deutlich bessere Einblicke in Anrufqualität, Performance und Kosten ermöglichen.