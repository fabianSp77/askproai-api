# Retell.ai Custom Functions Implementation Guide

## 🎯 Überblick

Wir haben ein erweitertes Custom Functions System für Retell.ai implementiert, das strukturierte Datenextraktion aus Telefongesprächen ermöglicht. Dieses System löst das Problem, dass nur 16% der Anrufe vollständige Termindaten enthalten.

## 🚀 Implementierte Custom Functions

### 1. **extract_appointment_details**
Extrahiert Termindetails aus natürlicher Sprache.

**Features:**
- Erkennt relative Datumsangaben ("morgen", "nächste Woche")
- Versteht deutsche Wochentage
- Extrahiert Zeitpräferenzen ("vormittags", "nachmittags")
- Identifiziert Service-Hinweise aus dem Gespräch
- Erkennt spezielle Kundenwünsche

**Beispiel-Aufruf:**
```json
{
  "conversation_context": "Ich hätte gerne morgen vormittags einen Termin für einen Haarschnitt",
  "customer_utterance": "morgen vormittags"
}
```

### 2. **identify_customer**
Identifiziert existierende Kunden anhand der Telefonnummer.

**Features:**
- Sucht Kunden nach Telefonnummer (mit Varianten)
- Zeigt Kundenhistorie und Präferenzen
- Berechnet Kunden-Insights (Treuekunde, No-Show-Risiko)
- Ermittelt bevorzugte Services und Mitarbeiter

**Beispiel-Response:**
```json
{
  "success": true,
  "found": true,
  "customer": {
    "id": 123,
    "name": "Max Mustermann",
    "total_appointments": 15,
    "is_vip": true
  },
  "preferences": {
    "preferred_services": ["Herrenhaarschnitt"],
    "preferred_staff": ["Maria"],
    "preferred_time": "morning"
  }
}
```

### 3. **determine_service**
Matched Service-Beschreibungen zu tatsächlichen Services.

**Features:**
- Fuzzy-Matching für Service-Namen
- Berücksichtigt Kategorien und Tags
- Gender-spezifisches Matching
- Synonym-Erkennung (z.B. "Färben" = "Colorieren")

### 4. **book_appointment**
Bucht einen Termin mit allen erfassten Daten.

**Features:**
- Vollständige Terminbuchung inkl. Cal.com Integration
- Automatische Zeitzonenkonvertierung (Berlin Zeit)
- Verfügbarkeitsprüfung
- Kundenanlage bei Neukunden
- Bestätigungsnachricht-Generierung

### 5. **book_group_appointment**
Bucht Gruppentermine für mehrere Personen.

**Features:**
- Mehrere Teilnehmer gleichzeitig
- Gruppenrabatte
- Automatische Teilnehmer-Identifikation

## 📋 Integration in Retell.ai

### Schritt 1: Agent Configuration Update

Die Custom Functions werden automatisch über den `RetellAgentProvisioner` konfiguriert:

```php
// In app/Services/Provisioning/RetellAgentProvisioner.php
private function getAgentFunctions(Branch $branch): array
{
    $functions = [];
    
    // Neue erweiterte Functions
    $functions[] = ExtractAppointmentDetailsFunction::getDefinition();
    $functions[] = IdentifyCustomerFunction::getDefinition();
    $functions[] = DetermineServiceFunction::getDefinition();
    $functions[] = AppointmentBookingFunction::getDefinition();
    
    // ... weitere Functions
}
```

### Schritt 2: Webhook Handler Configuration

Der `RetellCustomFunctionMCPServer` leitet alle Function Calls an den `CustomFunctionHandler` weiter:

```php
public function handleFunctionCall(string $functionName, array $parameters, ?string $callId = null): array
{
    // Context resolution
    // Parameter enrichment
    // Delegation to CustomFunctionHandler
}
```

### Schritt 3: Prompt Template für AI Agent

Beispiel-Prompt für den Retell.ai Agent:

```
## TERMINBUCHUNG WORKFLOW

Wenn ein Kunde einen Termin buchen möchte:

1. ZUERST: Verwende 'identify_customer' mit der Telefonnummer
   - Bei Bestandskunden: Begrüße mit Namen und frage nach üblichem Service
   - Bei Neukunden: Frage nach Namen

2. DANN: Verwende 'extract_appointment_details' um Terminwünsche zu verstehen
   - Extrahiere Datum, Zeit und Service-Wünsche aus dem Gespräch

3. OPTIONAL: Verwende 'determine_service' wenn Service unklar ist
   - Kläre ab welcher Service gemeint ist

4. PRÜFE: Verwende 'check_availability' für Verfügbarkeit

5. BUCHE: Verwende 'book_appointment' mit allen gesammelten Daten

Nutze die Funktionen in dieser Reihenfolge für optimale Ergebnisse!
```

## 🔧 Konfiguration

### Environment Variables
```env
# Retell.ai Configuration
RETELL_TOKEN=key_e973c8962e09d6a34b3b1cf386
RETELL_WEBHOOK_URL=https://api.askproai.de/api/retell/webhook
RETELL_CUSTOM_FUNCTIONS_ENABLED=true
```

### Database Requirements
- `branches` Tabelle mit `retell_agent_id`
- `services` Tabelle mit aktiven Services
- `customers` Tabelle für Kundenidentifikation
- `appointments` Tabelle für Buchungen

## 📊 Erwartete Verbesserungen

### Vorher (Baseline)
- Nur 16% der Calls haben vollständige Appointment-Daten
- Viele fehlende Felder (Service, Zeit, Kundenname)
- Keine strukturierte Datenextraktion

### Nachher (Mit Custom Functions)
- **Ziel**: 80%+ vollständige Datensätze
- Strukturierte Extraktion aller relevanten Felder
- Automatische Kundenidentifikation
- Intelligentes Service-Matching

## 🧪 Testing

### Test Custom Function Locally
```php
// Test-Skript
$handler = new CustomFunctionHandler($bookingService, $calcomService);

// Test Extract
$result = $handler->handleFunctionCall('extract_appointment_details', [
    'conversation_context' => 'Ich möchte morgen um 14 Uhr einen Haarschnitt'
]);

print_r($result);
```

### Monitor Results
```sql
-- Check appointment data completeness
SELECT 
    COUNT(*) as total_calls,
    SUM(CASE WHEN customer_name IS NOT NULL THEN 1 ELSE 0 END) as has_name,
    SUM(CASE WHEN appointment_date IS NOT NULL THEN 1 ELSE 0 END) as has_date,
    SUM(CASE WHEN appointment_time IS NOT NULL THEN 1 ELSE 0 END) as has_time,
    SUM(CASE WHEN service IS NOT NULL THEN 1 ELSE 0 END) as has_service
FROM calls
WHERE created_at >= NOW() - INTERVAL 24 HOUR;
```

## 🚨 Troubleshooting

### Problem: Functions werden nicht aufgerufen
1. Prüfe ob Agent die Functions kennt:
   ```php
   php artisan retell:check-agent {agent_id}
   ```

2. Prüfe Webhook-Logs:
   ```bash
   tail -f storage/logs/laravel.log | grep "CustomFunction"
   ```

### Problem: Falsche Datenextraktion
1. Überprüfe Prompt-Template
2. Teste Function isoliert
3. Prüfe Logs für Details

## 📈 Monitoring

### Key Metrics
- **Function Call Rate**: Wie oft werden Functions aufgerufen?
- **Success Rate**: Wie oft sind Functions erfolgreich?
- **Data Completeness**: Wie vollständig sind die extrahierten Daten?
- **Booking Conversion**: Wie viele Calls führen zu Buchungen?

### Dashboard Query
```sql
-- Custom Function Performance
SELECT 
    function_name,
    COUNT(*) as total_calls,
    SUM(CASE WHEN success = true THEN 1 ELSE 0 END) as successful,
    AVG(execution_time_ms) as avg_time_ms
FROM custom_function_logs
WHERE created_at >= NOW() - INTERVAL 7 DAY
GROUP BY function_name;
```

## 🎯 Next Steps

1. **Aktiviere Functions in Production**
   ```bash
   php artisan retell:update-agents --with-custom-functions
   ```

2. **Monitor Initial Results**
   - Beobachte Function Call Logs
   - Prüfe Datenqualität
   - Sammle Feedback

3. **Iterative Verbesserung**
   - Optimiere Prompts basierend auf Ergebnissen
   - Erweitere Functions bei Bedarf
   - Füge weitere Intelligenz hinzu

---

**Status**: ✅ Implementation Complete
**Next**: Deploy und Monitor in Production