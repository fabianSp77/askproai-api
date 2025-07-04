# Retell Field Mapping Analysis - 2025-06-29

## 🔍 Kritische Erkenntnisse aus den Retell-Daten

### 1. **Felder die Retell nutzt (aus deiner Übersicht)**

#### Standard Call Fields:
- **Time** → start_timestamp
- **Duration** → duration_sec (DISKREPANZ GEFUNDEN!)
- **Channel Type** → call_type (immer "phone_call")
- **Cost** → cost
- **Session ID** → call_id / retell_call_id
- **End Reason** → disconnection_reason
- **Session Status** → call_status
- **User Sentiment** → sentiment
- **Agent ID** → agent_id / retell_agent_id
- **Agent Version** → agent_version (FEHLT IN UNSEREM SYSTEM!)
- **From** → from_number
- **To** → to_number
- **Session Outcome** → session_outcome (FEHLT IN UNSEREM SYSTEM!)
- **End to End Latency** → latency_metrics

#### Custom Variables (Termindaten):
- **appointment_date_time** → Kombiniertes Datum/Zeit
- **patient_full_name** / **caller_full_name** → name
- **caller_phone** / **telefonnummer__anrufer** → phone_number
- **reason_for_visit** → dienstleistung
- **additional_notes** / **information__anruf** → notes
- **health_insurance_company** → NEUES FELD
- **appointment_made** → Boolean für erfolgreiche Buchung
- **_datum__termin** → datum_termin
- **_uhrzeit__termin** → uhrzeit_termin
- **_email** → email
- **_zusammenfassung__anruf** → summary

### 2. **🚨 KRITISCHES PROBLEM: Audio-Dauer Diskrepanz**

Du hast recht - es gibt eine Diskrepanz zwischen:
- **Retell Dashboard**: 0:54 (54 Sekunden)
- **Unser System**: Möglicherweise andere Werte

Das deutet auf ein Problem bei der Dauer-Berechnung hin!

### 3. **Fehlende Felder in unserem System**

```sql
-- Diese Spalten fehlen in der calls Tabelle:
ALTER TABLE calls ADD COLUMN agent_version VARCHAR(255) AFTER retell_agent_id;
ALTER TABLE calls ADD COLUMN session_outcome VARCHAR(50) AFTER call_status;
ALTER TABLE calls ADD COLUMN health_insurance_company VARCHAR(255) AFTER email;
ALTER TABLE calls ADD COLUMN appointment_made BOOLEAN DEFAULT FALSE AFTER appointment_id;
ALTER TABLE calls ADD COLUMN reason_for_visit TEXT AFTER dienstleistung;
ALTER TABLE calls ADD COLUMN end_to_end_latency INT AFTER latency_metrics;
```

### 4. **Dynamic Variables Mapping Problem**

Die Daten zeigen, dass Retell verschiedene Formate für Dynamic Variables nutzt:
- Mit Unterstrich: `_datum__termin`, `_uhrzeit__termin`
- Ohne Unterstrich: `appointment_date_time`, `patient_full_name`
- Template Variables: `{{caller_phone_number}}`

### 5. **Erfolgreiche vs. Nicht-Erfolgreiche Anrufe**

**Successful** (appointment_made = true):
- Haben vollständige Termindaten
- Session Outcome = "Successful"
- Meist "agent hangup" (Agent beendet nach Bestätigung)

**Unsuccessful**:
- Fehlende oder Template-Daten ({{caller_phone_number}})
- Session Outcome = "Unsuccessful"
- Meist "user hangup" (Kunde legt auf)

## 📋 Sofort-Maßnahmen

### 1. Audio-Dauer Fix

```php
// In ProcessRetellCallEndedJobFixed.php
// Zeile 108-113 ersetzen:
if (isset($callData['end_timestamp']) && isset($callData['start_timestamp'])) {
    // Berechne Dauer in Sekunden
    $duration = ($callData['end_timestamp'] - $callData['start_timestamp']) / 1000;
    $call->duration_sec = round($duration);
    
    // Zusätzlich: Speichere die Original-Millisekunden
    $call->duration_ms = $callData['end_timestamp'] - $callData['start_timestamp'];
}

// Oder nutze Retell's call_length direkt:
if (isset($callData['call_analysis']['call_length'])) {
    $call->duration_sec = $callData['call_analysis']['call_length'];
}
```

### 2. Dynamic Variables Parser

```php
private function parseDynamicVariables($dynamicVars) {
    $normalized = [];
    
    foreach ($dynamicVars as $key => $value) {
        // Entferne führende Unterstriche
        $cleanKey = ltrim($key, '_');
        
        // Ersetze doppelte Unterstriche
        $cleanKey = str_replace('__', '_', $cleanKey);
        
        // Map zu unseren Feldnamen
        $fieldMap = [
            'datum_termin' => 'datum_termin',
            'uhrzeit_termin' => 'uhrzeit_termin',
            'appointment_date_time' => 'appointment_datetime',
            'patient_full_name' => 'name',
            'caller_full_name' => 'name',
            'telefonnummer_anrufer' => 'phone_number',
            'caller_phone' => 'phone_number',
            'reason_for_visit' => 'dienstleistung',
            'zusammenfassung_anruf' => 'summary',
            'information_anruf' => 'notes'
        ];
        
        if (isset($fieldMap[$cleanKey])) {
            $normalized[$fieldMap[$cleanKey]] = $value;
        } else {
            $normalized[$cleanKey] = $value;
        }
    }
    
    return $normalized;
}
```

### 3. Webhook Handler Update

```php
// In RetellWebhookHandler.php
private function processCallEnded($data) {
    $callData = $data['call'];
    
    // Extrahiere ALLE Felder
    $call->agent_version = $callData['agent_version'] ?? null;
    $call->session_outcome = $callData['session_outcome'] ?? 'unknown';
    $call->end_to_end_latency = $callData['end_to_end_latency'] ?? null;
    
    // Parse Dynamic Variables richtig
    if (isset($callData['retell_llm_dynamic_variables'])) {
        $parsed = $this->parseDynamicVariables($callData['retell_llm_dynamic_variables']);
        
        // Setze appointment_made basierend auf Daten
        $call->appointment_made = !empty($parsed['datum_termin']) && 
                                 !empty($parsed['uhrzeit_termin']) &&
                                 !str_contains($parsed['phone_number'] ?? '', '{{');
    }
}
```

## 🎯 Nächste Schritte

1. **Datenbank-Migration erstellen** für fehlende Felder
2. **Audio-Dauer Berechnung fixen** 
3. **Dynamic Variables Parser** implementieren
4. **Retell Agent Prompt** anpassen für konsistente Variable-Namen
5. **Test mit echten Anrufen** durchführen

## 💡 Wichtige Erkenntnis

Die Daten zeigen, dass der Retell Agent bereits versucht, Termine zu buchen! Die Dynamic Variables werden gefüllt, aber:
- Inkonsistente Feldnamen (mit/ohne Unterstriche)
- Template-Variablen werden nicht ersetzt ({{caller_phone_number}})
- appointment_made Flag fehlt für Tracking