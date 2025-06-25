# Retell.ai Custom Functions - Übersicht

## 🎯 Implementierte Custom Functions

### 1. **collect_appointment_data** ✅ (Bereits aktiv)
- **Endpoint**: `https://api.askproai.de/api/retell/collect-appointment`
- **Zweck**: Sammelt Termindaten während des Anrufs
- **Parameter**:
  - `datum` (string, required)
  - `uhrzeit` (string, required)
  - `name` (string, required)
  - `telefonnummer` (string, required)
  - `dienstleistung` (string, required)
  - `email` (string, optional)
  - `mitarbeiter_wunsch` (string, optional)
  - `kundenpraeferenzen` (string, optional)

### 2. **identify_customer** 🆕
- **Endpoint**: `https://api.askproai.de/api/retell/identify-customer`
- **Zweck**: Identifiziert Bestandskunden anhand Telefonnummer
- **Parameter**:
  - `phone_number` (string, required)
  - `call_id` (string, optional)
- **Rückgabe**:
  - Kundendaten inkl. VIP-Status, Präferenzen, Historie
  - Personalisierte Begrüßung

### 3. **save_customer_preference** 🆕
- **Endpoint**: `https://api.askproai.de/api/retell/save-preference`
- **Zweck**: Speichert Kundenpräferenzen während des Anrufs
- **Parameter**:
  - `customer_id` (integer, required)
  - `preference_type` (string, required)
  - `preference_key` (string, required)
  - `preference_value` (any, required)

### 4. **apply_vip_benefits** 🆕
- **Endpoint**: `https://api.askproai.de/api/retell/apply-vip-benefits`
- **Zweck**: Wendet VIP-Vorteile auf Buchungen an
- **Parameter**:
  - `customer_id` (integer, required)
  - `booking_data` (object, required)
- **Vorteile**:
  - Bronze: Flexible Stornierung
  - Silver: +5% Rabatt, Prioritätsbuchung
  - Gold: +10% Rabatt, Exklusive Slots
  - Platinum: +15% Rabatt, Persönlicher Account Manager

### 5. **transfer_to_fabian** 🆕
- **Endpoint**: `https://api.askproai.de/api/retell/transfer-to-fabian`
- **Zweck**: Direkte Weiterleitung zu Fabian Spitzer (+491604366218)
- **Parameter**:
  - `call_id` (string, optional)
  - `reason` (string, optional)
  - `customer_name` (string, optional)
  - `topic` (string, optional)
- **Action**: Blind Transfer mit Wartemusik

### 6. **check_transfer_availability** 🆕
- **Endpoint**: `https://api.askproai.de/api/retell/check-transfer-availability`
- **Zweck**: Prüft Verfügbarkeit für Weiterleitung
- **Rückgabe**:
  - `available` (boolean)
  - `reason` (string)
  - `alternative_options` (object)

### 7. **schedule_callback** 🆕
- **Endpoint**: `https://api.askproai.de/api/retell/schedule-callback`
- **Zweck**: Plant einen Rückruf
- **Parameter**:
  - `customer_name` (string, required)
  - `phone_number` (string, required)
  - `preferred_time` (string, required) - z.B. "morgen 14 Uhr"
  - `reason` (string, optional)
  - `notes` (string, optional)
- **Features**:
  - Intelligente Zeit-Erkennung
  - Prioritäts-Management
  - Automatische Benachrichtigung

### 8. **handle_urgent_transfer** 🆕
- **Endpoint**: `https://api.askproai.de/api/retell/handle-urgent-transfer`
- **Zweck**: Behandelt dringende Weiterleitungsanfragen
- **Parameter**:
  - `reason` (string, required)
  - `urgency` (string, optional)
- **Logik**:
  - Emergency: Sofortige Weiterleitung
  - High: Prioritäts-Callback innerhalb 30 Min
  - Normal: Standard-Behandlung

## 📝 Beispiel Agent-Prompt für neue Functions

```
## Verfügbare Custom Functions

1. **identify_customer**: Nutze diese Funktion zu Beginn des Anrufs mit {{caller_phone_number}}
   - Wenn Kunde erkannt: Verwende die personalisierte Begrüßung
   - Wenn VIP-Kunde: Erwähne die besonderen Vorteile

2. **collect_appointment_data**: Für Terminbuchungen (bereits bekannt)

3. **transfer_to_fabian**: Wenn der Kunde ausdrücklich nach Herrn Spitzer fragt oder bei komplexen Anfragen
   - Sage: "Ich verbinde Sie gerne direkt mit Herrn Spitzer."

4. **schedule_callback**: Wenn Weiterleitung nicht möglich oder Kunde Rückruf bevorzugt
   - Frage nach bevorzugter Zeit
   - Bestätige den geplanten Rückruf

5. **save_customer_preference**: Speichere erkannte Präferenzen
   - Zeit-Präferenzen: "Ich merke mir, dass Sie nachmittags bevorzugen"
   - Mitarbeiter-Präferenzen: "Ich notiere, dass Sie gerne bei [Name] Termine haben"

## Beispiel-Dialog mit neuen Functions

Retell: [Anruf startet]
→ CALL: identify_customer(phone_number: "{{caller_phone_number}}")
← RESPONSE: { customer_found: true, customer_name: "Herr Schmidt", vip_status: "gold", personalized_greeting: "Guten Tag, Herr Schmidt! Schön, von unserem Gold-Kunden zu hören..." }

Agent: "Guten Tag, Herr Schmidt! Schön, von unserem Gold-Kunden zu hören. Als Gold-Kunde genießen Sie 10% Rabatt und Zugang zu exklusiven Terminen. Wie kann ich Ihnen heute helfen?"

Kunde: "Ich hätte gerne einen Termin bei Frau Müller, am liebsten nachmittags."

Agent: "Sehr gerne! Ich sehe, Sie bevorzugen Termine bei Frau Müller am Nachmittag."
→ CALL: save_customer_preference(customer_id: 123, preference_type: "staff", preference_key: "preferred_staff", preference_value: "Frau Müller")
→ CALL: save_customer_preference(customer_id: 123, preference_type: "time", preference_key: "preferred_time", preference_value: "afternoon")

[... Terminbuchung läuft ...]

Kunde: "Könnte ich vielleicht direkt mit Herrn Spitzer sprechen?"

Agent: "Selbstverständlich! Ich verbinde Sie gerne direkt mit Herrn Spitzer. Einen Moment bitte."
→ CALL: transfer_to_fabian(reason: "direct_request", customer_name: "Herr Schmidt", topic: "Terminvereinbarung")
```

## 🔧 Integration in Retell.ai Dashboard

1. Navigiere zu deinem Agent
2. Gehe zu "Functions" oder "Custom Functions"
3. Füge jede Function mit folgenden Details hinzu:

### Beispiel für `identify_customer`:
```json
{
  "name": "identify_customer",
  "description": "Identifiziert Bestandskunden und lädt deren Präferenzen",
  "endpoint": "https://api.askproai.de/api/retell/identify-customer",
  "method": "POST",
  "headers": {
    "Content-Type": "application/json"
  },
  "parameters": [
    {
      "name": "phone_number",
      "type": "string",
      "description": "Telefonnummer des Anrufers",
      "required": true
    },
    {
      "name": "call_id",
      "type": "string", 
      "description": "Retell Call ID",
      "required": false
    }
  ]
}
```

## 🚀 Aktivierung & Testing

1. **Functions im Agent aktivieren**
2. **Agent-Prompt aktualisieren** mit obigen Beispielen
3. **Testanrufe durchführen**:
   - Bestandskunde anrufen → Personalisierte Begrüßung
   - Nach Fabian fragen → Weiterleitung
   - Rückruf vereinbaren → Callback-Planung
   - Als VIP-Kunde buchen → Rabatte anwenden

## 📊 Monitoring

- Alle Function-Calls werden geloggt in `/storage/logs/laravel.log`
- Customer Interactions in `customer_interactions` Tabelle
- Callback Requests in `callback_requests` Tabelle
- VIP-Status Updates in `customers` Tabelle