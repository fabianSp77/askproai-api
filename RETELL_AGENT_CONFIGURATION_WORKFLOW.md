# Retell Agent Configuration Workflow

## Übersicht

Dieser Workflow ermöglicht es, Retell.ai Agent-Konfigurationen zu exportieren, zu modifizieren und wieder zu importieren. Der Prozess unterstützt sowohl das AskProAI-Format als auch das native Retell.ai-Format.

## Workflow-Schritte

### 1. Export aus Retell.ai

**Option A: Export über AskProAI Dashboard**
1. Navigieren Sie zu: https://api.askproai.de/admin/retell-ultimate-control-center
2. Klicken Sie auf den "Export" Button beim gewünschten Agent
3. Wählen Sie "Retell.ai Format" aus dem Dropdown
4. Die JSON-Datei wird automatisch heruntergeladen

**Option B: Direkter Export aus Retell.ai**
1. Loggen Sie sich bei https://app.retellai.com ein
2. Navigieren Sie zu Ihrem Agent
3. Nutzen Sie die Export-Funktion (falls verfügbar)

### 2. Konfiguration modifizieren

**Manuelle Bearbeitung:**
Die exportierte JSON-Datei kann mit jedem Texteditor bearbeitet werden.

**Automatisierte Bearbeitung mit CLI-Tool:**
```bash
# Basis-Bearbeitung
php artisan agent:process-config input.json --output=modified.json

# Mit Voreinstellungen für Terminbuchung
php artisan agent:process-config input.json \
    --preset=booking \
    --fix-webhooks \
    --optimize-voice \
    --enhance-prompt

# Mit benutzerdefinierten Änderungen
php artisan agent:process-config input.json \
    --fix-webhooks \
    --optimize-voice \
    --add-functions
```

**Verfügbare Optionen:**
- `--enhance-prompt`: Optimiert den Prompt für den deutschen Markt
- `--fix-webhooks`: Setzt Webhook-URL auf AskProAI
- `--optimize-voice`: Optimiert Spracheinstellungen für Deutsch
- `--add-functions`: Fügt Standard-AskProAI-Funktionen hinzu
- `--preset=booking|support|sales`: Wendet vordefinierte Einstellungen an

### 3. Import nach Retell.ai

**Option A: Import über AskProAI Dashboard**
1. Navigieren Sie zu: https://api.askproai.de/admin/retell-ultimate-control-center
2. Klicken Sie auf "Import Agent" Button
3. Wählen Sie die modifizierte JSON-Datei
4. Der Agent wird mit neuem Namen erstellt

**Option B: Direkter Import in Retell.ai**
1. Loggen Sie sich bei https://app.retellai.com ein
2. Erstellen Sie einen neuen Agent
3. Kopieren Sie die Konfiguration aus der JSON-Datei

## Konfigurationsstruktur

### Wichtige Felder

```json
{
  "agent_name": "Name des Agents",
  "voice_id": "elevenlabs-Matilda",
  "language": "de",
  "response_engine": {
    "type": "retell-llm",
    "llm_id": "...",
    "model": "gpt-4"
  },
  "webhook_settings": {
    "url": "https://api.askproai.de/api/retell/webhook",
    "listening_events": ["call_started", "call_ended", "call_analyzed"]
  },
  "general_prompt": "Agent-Anweisungen...",
  "voice_temperature": 0.7,
  "voice_speed": 1.0,
  "interruption_sensitivity": 0.7,
  "response_speed": 1000,
  "enable_backchannel": true,
  "end_call_after_silence_ms": 20000
}
```

### Voice IDs

**OpenAI Voices:**
- `openai-Alloy`, `openai-Echo`, `openai-Fable`
- `openai-Onyx`, `openai-Nova`, `openai-Shimmer`

**ElevenLabs Voices (Deutsch optimiert):**
- `elevenlabs-Matilda` (Weiblich, professionell)
- `elevenlabs-Wilhelm` (Männlich, freundlich)
- `elevenlabs-Dorothy`, `elevenlabs-Thomas`

**Deepgram Voices:**
- `deepgram-Asteria`, `deepgram-Luna`, `deepgram-Stella`

## Beispiel-Prompts

### Terminbuchung
```
Du bist ein professioneller KI-Assistent für Terminbuchungen. Deine Aufgabe ist es, Anrufer freundlich und effizient bei der Terminvereinbarung zu unterstützen.

WICHTIGE REGELN:
- Sprich IMMER auf Deutsch, niemals auf Englisch
- Verwende die Sie-Form, außer der Anrufer bietet das Du an
- Sei höflich, professionell und hilfsbereit
- Erfasse alle notwendigen Informationen für die Terminbuchung

DATENERFASSUNG:
1. Name des Anrufers
2. Telefonnummer (zur Bestätigung)
3. Gewünschte Dienstleistung
4. Bevorzugter Termin (Datum und Uhrzeit)
5. Besondere Anliegen oder Wünsche
```

### Support
```
Du bist ein hilfsbereiter KI-Support-Assistent. Deine Aufgabe ist es, Anrufern bei ihren Fragen und Problemen zu helfen.

WICHTIGE REGELN:
- Sprich IMMER auf Deutsch
- Sei geduldig und verständnisvoll
- Höre aktiv zu und stelle Rückfragen
- Biete konkrete Lösungen an
```

## Funktionen hinzufügen

### Cal.com Verfügbarkeit prüfen
```json
{
  "name": "check_availability",
  "url": "https://api.askproai.de/api/mcp/calcom/availability",
  "method": "POST",
  "description": "Verfügbare Termine prüfen",
  "speak_during_execution": true,
  "speak_during_execution_message": "Einen Moment, ich prüfe die verfügbaren Termine...",
  "speak_after_execution": true,
  "speak_after_execution_message": "Ich habe folgende Termine gefunden:",
  "parameters": [
    {"name": "date", "type": "string", "required": true},
    {"name": "service", "type": "string", "required": true}
  ]
}
```

### Termin buchen
```json
{
  "name": "book_appointment",
  "url": "https://api.askproai.de/api/mcp/calcom/booking",
  "method": "POST",
  "description": "Termin buchen",
  "speak_during_execution": true,
  "speak_during_execution_message": "Ich buche jetzt Ihren Termin...",
  "speak_after_execution": true,
  "speak_after_execution_message": "Ihr Termin wurde erfolgreich gebucht!",
  "parameters": [
    {"name": "customer_name", "type": "string", "required": true},
    {"name": "customer_phone", "type": "string", "required": true},
    {"name": "date", "type": "string", "required": true},
    {"name": "time", "type": "string", "required": true},
    {"name": "service", "type": "string", "required": true}
  ]
}
```

## Best Practices

### 1. Voice-Einstellungen für Deutsch
- **Voice Temperature**: 0.6-0.8 (natürlicher Klang)
- **Voice Speed**: 0.9-1.1 (normale Geschwindigkeit)
- **Interruption Sensitivity**: 0.6-0.8 (Balance zwischen Reaktivität und Stabilität)

### 2. Response-Einstellungen
- **Response Speed**: 800-1200ms (schnell aber nicht zu hastig)
- **Enable Backchannel**: true (für natürliche Konversation)
- **End Call After Silence**: 15000-25000ms (15-25 Sekunden)

### 3. Webhook-Konfiguration
- Immer auf AskProAI-Webhook zeigen für Tracking
- Alle Events aktivieren: `call_started`, `call_ended`, `call_analyzed`

## Troubleshooting

### Problem: Import schlägt fehl
**Lösung:**
- Prüfen Sie die JSON-Syntax
- Stellen Sie sicher, dass alle Pflichtfelder vorhanden sind
- Verwenden Sie das CLI-Tool zur Validierung

### Problem: Voice klingt unnatürlich
**Lösung:**
- Passen Sie `voice_temperature` an (höher = variabler)
- Testen Sie verschiedene Voice IDs
- Optimieren Sie `voice_speed`

### Problem: Agent unterbricht zu oft
**Lösung:**
- Reduzieren Sie `interruption_sensitivity` (z.B. auf 0.5)
- Erhöhen Sie `response_speed` leicht

### Problem: Funktionen werden nicht erkannt
**Lösung:**
- Bei `retell-llm` müssen Funktionen im LLM konfiguriert werden
- Nutzen Sie das AskProAI Dashboard für die Funktionsverwaltung

## Erweiterte Konfiguration

### Multi-Language Support
```json
{
  "language": "de",
  "voice_id": "elevenlabs-Matilda",
  "general_prompt": "WICHTIG: Sprich IMMER auf Deutsch..."
}
```

### Custom Functions
Funktionen müssen bei Verwendung von `retell-llm` über die LLM-Konfiguration hinzugefügt werden. Das CLI-Tool markiert diese mit einem `_note` Feld.

### Webhook Security
Webhook-Secrets werden beim Export automatisch entfernt und müssen nach dem Import neu konfiguriert werden.

## Zusammenfassung

Der Workflow ermöglicht flexible Anpassungen von Retell.ai Agents:

1. **Export** in beiden Formaten (AskProAI/Retell.ai)
2. **Modifikation** mit CLI-Tool oder manuell
3. **Import** mit automatischer Validierung
4. **Optimierung** für den deutschen Markt

Nutzen Sie die bereitgestellten Tools und Vorlagen für beste Ergebnisse.