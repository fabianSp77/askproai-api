# FIX: Conversational Agent - service_id Parameter fehlt

## 🔴 DAS PROBLEM

Der **Conversational Agent** (`agent_616d645570ae613e421edb98e7`) hat in der `collect_appointment_data` Funktion **KEINEN `service_id` Parameter**.

### Was passiert:
1. Agent ruft `collect_appointment_data` auf ✅
2. Retell AI filtert `service_id` raus ❌ (weil nicht in Funktionsdefinition)
3. Backend bekommt **NIE** die `service_id` ❌
4. System verwendet Default-Service ❌
5. Buchung schlägt fehl ❌

## ✅ DIE LÖSUNG

### Option 1: Automatisches Update-Script (EMPFOHLEN)

```bash
# Im Projekt-Verzeichnis ausführen
php scripts/update_conversational_agent_with_service_id.php
```

Das Script:
- ✅ Holt aktuelle Agent-Konfiguration
- ✅ Prüft ob `service_id` Parameter fehlt
- ✅ Fügt `service_id` Parameter hinzu
- ✅ Updated den Agent via Retell API

### Option 2: Manuelle Anpassung im Retell Dashboard

1. Gehe zu: https://dashboard.retellai.com/agents/agent_616d645570ae613e421edb98e7

2. Klicke auf **Functions** Tab

3. Finde die `collect_appointment_data` Funktion

4. Füge unter `parameters.properties` hinzu:

```json
"service_id": {
  "type": "integer",
  "description": "Numeric ID des gewählten Services aus list_services (z.B. 32 für 15min, 47 für 30min). WICHTIG: Immer mitgeben!"
}
```

5. Füge `"service_id"` zum `required` Array hinzu:

```json
"required": [
  "call_id",
  "name",
  "datum",
  "uhrzeit",
  "dienstleistung",
  "service_id"  <-- NEU
]
```

6. **Save** klicken

7. **Publish** klicken (falls noch nicht live)

## 🧪 NACH DEM UPDATE TESTEN

Mach einen Testanruf und prüfe die Logs:

```bash
tail -f storage/logs/laravel.log | grep -i "service"
```

Du solltest sehen:
```
✅ TIER 1: Using service_id from request
   service_id: 32 (oder 47)
   service_name: "15 Minuten Schnellberatung" (oder "30 Minuten Beratung")

🎯 FINAL SERVICE SELECTED
   selection_method: "explicit_service_id"
   calcom_event_type_id: 3664712 (oder 2563193)
```

## 📋 VOLLSTÄNDIGE FUNKTIONSDEFINITION

So sollte `collect_appointment_data` aussehen:

```json
{
  "name": "collect_appointment_data",
  "description": "Collect and verify appointment data. First call without bestaetigung to check availability, then call with bestaetigung: true to book.",
  "parameters": {
    "type": "object",
    "properties": {
      "call_id": {
        "type": "string",
        "description": "Unique call identifier (use {{call_id}})"
      },
      "service_id": {
        "type": "integer",
        "description": "Numeric ID des gewählten Services aus list_services (z.B. 32 für 15min, 47 für 30min). WICHTIG: Immer mitgeben!"
      },
      "name": {
        "type": "string",
        "description": "Customer full name"
      },
      "datum": {
        "type": "string",
        "description": "Date in DD.MM.YYYY format (e.g., \"23.10.2025\")"
      },
      "uhrzeit": {
        "type": "string",
        "description": "Time in HH:MM format, 24-hour (e.g., \"14:00\")"
      },
      "dienstleistung": {
        "type": "string",
        "description": "Service name for reference"
      },
      "bestaetigung": {
        "type": "boolean",
        "description": "Set to false to check availability, true to confirm booking"
      },
      "email": {
        "type": "string",
        "description": "Customer email (optional)"
      }
    },
    "required": [
      "call_id",
      "service_id",
      "name",
      "datum",
      "uhrzeit",
      "dienstleistung"
    ]
  }
}
```

## 🎯 WICHTIG: list_services Funktion

Stelle sicher dass der Agent auch die `list_services` Funktion hat:

```json
{
  "name": "list_services",
  "description": "Get available services for this company. Shows all services with duration and price.",
  "parameters": {
    "type": "object",
    "properties": {
      "call_id": {
        "type": "string",
        "description": "Unique call identifier (use {{call_id}})"
      }
    },
    "required": ["call_id"]
  }
}
```

## ✅ ERWARTETES VERHALTEN NACH FIX

1. Agent ruft `list_services()` auf
2. Backend sendet: `[{"id": 32, "name": "15 Min"}, {"id": 47, "name": "30 Min"}]`
3. Agent fragt: "Welcher Service? 15 oder 30 Minuten?"
4. Kunde wählt: "15 Minuten"
5. Agent ruft `collect_appointment_data(service_id=32, ...)` auf
6. Backend verwendet Service 32 ✅
7. Verfügbarkeitsprüfung funktioniert ✅
8. Buchung erfolgreich ✅

## 🆘 TROUBLESHOOTING

### Script-Fehler: "RETELLAI_API_KEY not configured"

Füge in `.env` hinzu:
```
RETELLAI_API_KEY=key_xxxxxxxxxxxxxxxxxxxxxxxxx
```

(Den API Key findest du im Retell Dashboard unter Settings → API Keys)

### Agent bekommt immer noch keine service_id

1. Prüfe ob der Agent wirklich updated wurde (Version-Nummer im Dashboard erhöht sich)
2. Prüfe ob die Änderung "Published" ist (nicht nur Draft)
3. Mach einen komplett neuen Testanruf (alte Session-Daten können gecacht sein)

### Backend verwendet immer noch Default-Service

Prüfe die Logs:
```bash
tail -f storage/logs/laravel.log | grep "service_id_from_request"
```

Wenn `null` → Agent sendet es nicht
Wenn Zahl → Backend bekommt es ✅

---

**Nach dem Fix sollte alles funktionieren! 🚀**
