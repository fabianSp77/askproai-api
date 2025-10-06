# 🚨 KRITISCH: Retell AI Function Konfiguration

## ⚠️ WICHTIG: Diese Konfiguration MUSS in Retell AI eingetragen werden!

### 1. Function: `collect_appointment_data`

**GENAU SO in Retell Dashboard eintragen:**

```json
{
  "name": "collect_appointment_data",
  "description": "Sammelt Termindaten und prüft Verfügbarkeit",
  "webhook_url": "https://api.askproai.de/api/webhooks/retell/collect-appointment",
  "parameters": {
    "type": "object",
    "properties": {
      "datum": {
        "type": "string",
        "description": "Datum im Format TT.MM.JJJJ oder TT.MM"
      },
      "uhrzeit": {
        "type": "string",
        "description": "Uhrzeit im Format HH:MM oder nur HH"
      },
      "name": {
        "type": "string",
        "description": "Name des Kunden"
      },
      "dienstleistung": {
        "type": "string",
        "description": "Art der Dienstleistung (z.B. Beratungstermin)"
      },
      "call_id": {
        "type": "string",
        "description": "Die Retell Call ID"
      }
    },
    "required": ["datum", "uhrzeit", "name"]
  }
}
```

### 2. Response Handling

Die Function gibt verschiedene Status zurück:

#### `status: "available"`
```json
{
  "status": "available",
  "message": "Der Termin am 01.10.2025 um 16:00 ist verfügbar. Soll ich den Termin für Sie buchen?"
}
```
→ KI sollte Bestätigung einholen

#### `status: "unavailable"`
```json
{
  "status": "unavailable",
  "message": "Der Termin um 14:00 Uhr ist leider nicht verfügbar. Ich kann Ihnen folgende Alternativen anbieten: 1. am gleichen Tag, 12:00 Uhr 2. am gleichen Tag, 16:00 Uhr",
  "alternatives": [...]
}
```
→ KI sollte Alternativen anbieten

#### `status: "error"`
```json
{
  "status": "error",
  "message": "Ich kann die Verfügbarkeit momentan nicht prüfen."
}
```
→ KI sollte um Geduld bitten

### 3. Prompt für Retell Agent

**FÜGEN SIE DIES ZU IHREM AGENT PROMPT HINZU:**

```
Wenn ein Kunde nach einem Termin fragt:

1. IMMER zuerst die "collect_appointment_data" Function aufrufen
2. Bei status="unavailable": Die Alternativen aus der message vorlesen
3. Bei status="available": Um Bestätigung bitten bevor du buchst
4. NIEMALS sagen "Termin gebucht" ohne echte Bestätigung

Beispiel-Dialog:
Kunde: "Ich möchte einen Termin am 1. Oktober um 14 Uhr"
Du: [rufe collect_appointment_data auf]
System: status=unavailable mit Alternativen
Du: "Der Termin um 14 Uhr ist leider nicht verfügbar. Ich kann Ihnen aber 12 Uhr oder 16 Uhr anbieten. Was passt Ihnen besser?"
```

### 4. Test-Szenario

**Testen Sie mit diesem Dialog:**

1. "Ich möchte einen Termin am 1. Oktober um 14 Uhr"
   → Sollte Alternativen anbieten
2. "Dann nehme ich 16 Uhr"
   → Sollte bestätigen und buchen

### 5. Monitoring

Prüfen Sie nach jedem Anruf:
```bash
curl https://api.askproai.de/api/webhooks/retell/diagnostic | jq '.'
```

### ⚠️ KRITISCHE PUNKTE:

1. **URL MUSS GENAU SO SEIN:** `https://api.askproai.de/api/webhooks/retell/collect-appointment`
2. **call_id MUSS mitgesendet werden**
3. **Status-Handling ist PFLICHT**
4. **NIEMALS "gebucht" sagen ohne Bestätigung**

## 🔴 SOFORT UMSETZEN!

Diese Konfiguration ist KRITISCH für die Funktion. Ohne sie:
- Sagt die KI "gebucht" aber nichts passiert
- Kunden werden belogen
- Keine echten Termine werden erstellt

**DEADLINE: SOFORT**