# Integration der Verfügbarkeitsprüfung in collect_appointment_data

## Option 1: Direkt in collect_appointment_data (EMPFOHLEN)

### 1. JSON Schema für collect_appointment_data erweitern:

```json
{
  "type": "object",
  "properties": {
    "datum": {
      "type": "string",
      "description": "Das gewünschte Datum für den Termin"
    },
    "name": {
      "type": "string",
      "description": "Name des Kunden"
    },
    "telefonnummer": {
      "type": "string",
      "description": "Telefonnummer des Kunden"
    },
    "dienstleistung": {
      "type": "string",
      "description": "Gewünschte Dienstleistung"
    },
    "uhrzeit": {
      "type": "string",
      "description": "Gewünschte Uhrzeit"
    },
    "email": {
      "type": "string",
      "description": "E-Mail-Adresse des Kunden"
    },
    "kundenpraeferenzen": {
      "type": "string",
      "description": "Zeitliche Präferenzen (z.B. 'nur vormittags', 'donnerstags 16-19 Uhr')"
    }
  },
  "required": ["datum", "name", "telefonnummer", "uhrzeit"]
}
```

### 2. Prompt-Anpassung (Minimal):

Fügen Sie zu Ihrem bestehenden Prompt nur diese Zeilen hinzu:

```
## Terminbuchung mit Präferenzen

Wenn der Kunde zeitliche Einschränkungen nennt (z.B. "nur vormittags", "donnerstags", "ab 16 Uhr"), 
erfasse diese im Feld `kundenpraeferenzen` der collect_appointment_data Funktion.

Beispiele für Kundenpräferenzen:
- "Ich kann nur vormittags"
- "Bei mir geht es nur donnerstags"
- "Ich habe Zeit zwischen 16 und 19 Uhr"
- "Montags oder mittwochs nachmittags"
```

### 3. So funktioniert es:

**Kunde:** "Ich bräuchte einen Termin, aber ich kann nur donnerstags nachmittags."

**Agent erfasst:**
```json
{
  "datum": "20.06.2025",
  "uhrzeit": "15:00",
  "name": "Max Mustermann",
  "telefonnummer": "0123456789",
  "kundenpraeferenzen": "nur donnerstags nachmittags"
}
```

**System:**
- Prüft automatisch ob 20.06. ein Donnerstag ist
- Prüft ob 15:00 Uhr verfügbar ist
- Wenn nicht: Sucht nächste Donnerstage nachmittags
- Bucht den Termin oder schlägt Alternativen vor

## Option 2: Mit Verfügbarkeitsprüfung VOR der Buchung

Wenn Sie möchten, dass der Agent IMMER erst prüft bevor er bucht, können Sie einen zweistufigen Prozess verwenden:

### Schritt 1: Custom Function für Verfügbarkeitsprüfung

```json
{
  "name": "check_appointment_availability",
  "description": "Prüft ob ein Termin verfügbar ist",
  "parameters": {
    "type": "object",
    "properties": {
      "datum": {"type": "string"},
      "uhrzeit": {"type": "string"},
      "kundenpraeferenzen": {"type": "string"}
    }
  }
}
```

### Schritt 2: Webhook für Echtzeit-Antwort

URL: `https://ihre-domain.de/api/retell/function-call`

Der Webhook antwortet sofort mit:
```json
{
  "verfuegbar": true/false,
  "alternative_termine": "Donnerstag 15:30 Uhr oder Donnerstag 16:00 Uhr",
  "nachricht": "Der Termin ist verfügbar" 
}
```

## Was ist der Unterschied?

**Option 1 (collect_appointment_data erweitert):**
- ✅ Einfacher - nur JSON Schema anpassen
- ✅ Agent sammelt alle Daten in einem Schritt
- ✅ System prüft nach dem Anruf und bucht wenn möglich
- ❌ Keine Echtzeit-Rückmeldung während des Anrufs

**Option 2 (Zweistufig mit Custom Function):**
- ✅ Echtzeit-Verfügbarkeitsprüfung während des Anrufs
- ✅ Agent kann sofort Alternativen anbieten
- ❌ Komplexerer Prompt
- ❌ Zusätzliche Custom Function nötig

## Empfehlung

Starten Sie mit **Option 1** - das ist einfacher und für die meisten Fälle ausreichend. 

Die Kundenpräferenzen werden trotzdem berücksichtigt und das System findet automatisch passende Alternativen, nur eben nach dem Anruf statt währenddessen.

## Test ohne Retell.ai

```bash
# Testen Sie die Terminbuchung mit Präferenzen
curl -X POST http://localhost:8000/api/retell/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "event": "call_ended",
    "call": {
      "call_id": "test_123",
      "retell_llm_dynamic_variables": {
        "datum": "20.06.2025",
        "uhrzeit": "15:00",
        "name": "Test Kunde",
        "telefonnummer": "0123456789",
        "kundenpraeferenzen": "nur donnerstags nachmittags"
      }
    }
  }'
```