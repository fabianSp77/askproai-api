# Retell Custom Functions - Schritt-für-Schritt Anleitung

## Vorbereitung
1. Öffne den Retell Agent Editor: https://api.askproai.de/admin/retell-agent-editor?agent_id=agent_9a8202a740cd3120d96fcfda1e
2. Wähle die neueste Version (v33 oder höher)
3. Scrolle zu den Custom Functions

---

## SCHRITT 1: Prompt Update

### Im Abschnitt "General Prompt"
Suche nach "WICHTIGE ANWEISUNGEN:" oder "REGELN:" und füge DARUNTER diese Zeilen ein:

```
- NIEMALS nach der Telefonnummer fragen - die Telefonnummer ist bereits über {{caller_phone_number}} verfügbar oder verwende call_id in den Funktionen
- Bei allen Funktionsaufrufen IMMER die call_id mit {{call_id}} übergeben
- Die Telefonnummer des Anrufers ist IMMER verfügbar, niemals danach fragen
```

---

## SCHRITT 2: Function "check_customer" anpassen

### Diese Function MUSS als ERSTE im Gespräch aufgerufen werden!

**1. Klicke auf "check_customer" zum Bearbeiten**

**2. Ändere die Description zu:**
```
Prüfe ob ein Kunde im System existiert. MUSS IMMER zu Beginn des Gesprächs aufgerufen werden! Nutzt automatisch die Anrufer-Telefonnummer.
```

**3. Ändere die Parameters komplett zu:**
```json
{
  "type": "object",
  "properties": {
    "call_id": {
      "type": "string",
      "description": "Die Call ID - IMMER {{call_id}} verwenden"
    }
  },
  "required": ["call_id"]
}
```

**4. Speichere diese Function**

---

## SCHRITT 3: Function "collect_appointment_data" anpassen

**1. Klicke auf "collect_appointment_data" zum Bearbeiten**

**2. Ändere die Description zu:**
```
Sammelt alle Termindaten vom Anrufer und bucht den Termin. NIEMALS nach der Telefonnummer fragen! Die Telefonnummer wird automatisch ermittelt.
```

**3. Ändere die Parameters - WICHTIG: call_id als ERSTES Property:**
```json
{
  "type": "object",
  "properties": {
    "call_id": {
      "type": "string",
      "description": "Die Call ID - IMMER {{call_id}} verwenden"
    },
    "datum": {
      "type": "string",
      "description": "Datum des Termins (z.B. 'heute', 'morgen', 'übermorgen', '25.03.2024')"
    },
    "uhrzeit": {
      "type": "string",
      "description": "Gewünschte Uhrzeit im 24-Stunden-Format (z.B. '09:00', '14:30', '17:45')"
    },
    "name": {
      "type": "string",
      "description": "Vollständiger Name des Kunden"
    },
    "dienstleistung": {
      "type": "string",
      "description": "Gewünschte Dienstleistung oder Behandlung"
    },
    "email": {
      "type": "string",
      "description": "E-Mail-Adresse für Terminbestätigung (optional)"
    },
    "mitarbeiter_wunsch": {
      "type": "string",
      "description": "Bevorzugter Mitarbeiter (optional)"
    },
    "kundenpraeferenzen": {
      "type": "string",
      "description": "Zusätzliche Wünsche oder Anmerkungen (optional)"
    }
  },
  "required": ["call_id", "datum", "uhrzeit", "name", "dienstleistung"]
}
```

**4. ENTFERNE "telefonnummer" aus den required fields falls noch vorhanden**

**5. Speichere diese Function**

---

## SCHRITT 4: Function "cancel_appointment" anpassen

**1. Klicke auf "cancel_appointment" zum Bearbeiten**

**2. Description kann bleiben oder anpassen zu:**
```
Einen bestehenden Termin stornieren. Die Telefonnummer wird automatisch ermittelt.
```

**3. Ändere die Parameters - call_id als ERSTES:**
```json
{
  "type": "object",
  "properties": {
    "call_id": {
      "type": "string",
      "description": "Die Call ID - IMMER {{call_id}} verwenden"
    },
    "appointment_date": {
      "type": "string",
      "description": "Datum des zu stornierenden Termins"
    }
  },
  "required": ["call_id", "appointment_date"]
}
```

**4. ENTFERNE "phone_number" falls vorhanden**

**5. Speichere diese Function**

---

## SCHRITT 5: Function "reschedule_appointment" anpassen

**1. Klicke auf "reschedule_appointment" zum Bearbeiten**

**2. Description kann bleiben oder anpassen zu:**
```
Einen bestehenden Termin verschieben. Die Telefonnummer wird automatisch ermittelt.
```

**3. Ändere die Parameters - call_id als ERSTES:**
```json
{
  "type": "object",
  "properties": {
    "call_id": {
      "type": "string",
      "description": "Die Call ID - IMMER {{call_id}} verwenden"
    },
    "old_date": {
      "type": "string",
      "description": "Aktuelles Datum des Termins"
    },
    "new_date": {
      "type": "string",
      "description": "Neues Datum für den Termin"
    },
    "new_time": {
      "type": "string",
      "description": "Neue Uhrzeit für den Termin"
    }
  },
  "required": ["call_id", "old_date", "new_date", "new_time"]
}
```

**4. ENTFERNE "phone_number" falls vorhanden**

**5. Speichere diese Function**

---

## SCHRITT 6: Function "book_appointment" anpassen (falls vorhanden)

**1. Klicke auf "book_appointment" zum Bearbeiten**

**2. Ändere die Parameters - call_id statt phone_number:**
```json
{
  "type": "object",
  "properties": {
    "call_id": {
      "type": "string",
      "description": "Die Call ID - IMMER {{call_id}} verwenden"
    },
    "customer_name": {
      "type": "string",
      "description": "Name des Kunden"
    },
    "appointment_date": {
      "type": "string",
      "description": "Datum des Termins"
    },
    "appointment_time": {
      "type": "string",
      "description": "Uhrzeit des Termins"
    },
    "service_type": {
      "type": "string",
      "description": "Art der Dienstleistung"
    }
  },
  "required": ["call_id", "customer_name", "appointment_date", "appointment_time", "service_type"]
}
```

**3. ENTFERNE "phone_number" aus den required fields**

**4. Speichere diese Function**

---

## SCHRITT 7: Finale Überprüfung

1. **Speichere alle Änderungen** mit dem "Save" oder "Update" Button
2. **Prüfe nochmal** dass KEINE Function mehr "telefonnummer" oder "phone_number" in den Parameters hat
3. **Prüfe** dass ALLE relevanten Functions "call_id" als ersten Parameter haben

---

## NACH DEN ÄNDERUNGEN

Sobald du alle Änderungen gemacht hast:

1. Speichere den Agent
2. Sage mir Bescheid, damit ich die Konfiguration in unser System lade
3. Wir machen einen Testanruf

## WICHTIGE HINWEISE

- **call_id IMMER mit {{call_id}}** - die geschweiften Klammern sind wichtig!
- **KEINE telefonnummer oder phone_number Parameter** mehr verwenden
- **check_customer MUSS als erste Function** im Gespräch aufgerufen werden
- Der Agent darf **NIEMALS nach der Telefonnummer fragen**