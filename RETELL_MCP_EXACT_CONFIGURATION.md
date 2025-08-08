# Retell.ai MCP - Exakte Konfiguration für Hair Salon

## 📋 Felder im Retell Dashboard (basierend auf Ihrem Screenshot)

### 1️⃣ **Name**
```
hair_salon_mcp
```

### 2️⃣ **URL**
```
https://api.askproai.de/api/v2/hair-salon-mcp/mcp
```

### 3️⃣ **Description**
```
Hair Salon booking system with appointment management, service catalog, and consultation callbacks for complex treatments
```

### 4️⃣ **Headers** (JSON Format)
```json
{
  "Content-Type": "application/json",
  "Accept": "application/json",
  "X-Company-ID": "1",
  "X-MCP-Version": "2.0"
}
```

### 5️⃣ **Query Parameters** (Key-Value Pairs)
| Key | Value |
|-----|-------|
| company_id | 1 |
| version | 2.0 |
| locale | de-DE |

### 6️⃣ **Timeout (ms)**
```
30000
```

### 7️⃣ **Available Tools/Methods**

Da Retell.ai die MCP Tools automatisch erkennt, müssen diese im Response Format richtig definiert sein. Unser MCP Server gibt diese Tools zurück:

#### Tool 1: list_services
```json
{
  "name": "list_services",
  "description": "Alle verfügbaren Friseur-Dienstleistungen mit Preisen und Dauer anzeigen",
  "input_schema": {
    "type": "object",
    "properties": {
      "category": {
        "type": "string",
        "description": "Optional: Filter nach Kategorie (schnitt, färbung, strähnen)",
        "enum": ["schnitt", "färbung", "strähnen", "dauerwelle"]
      }
    }
  }
}
```

#### Tool 2: check_availability
```json
{
  "name": "check_availability",
  "description": "Verfügbare Termine für eine Dienstleistung prüfen",
  "input_schema": {
    "type": "object",
    "properties": {
      "service_id": {
        "type": "integer",
        "description": "ID der gewählten Dienstleistung"
      },
      "staff_id": {
        "type": "integer",
        "description": "Optional: Bestimmter Mitarbeiter (1=Paula, 2=Claudia, 3=Katrin)"
      },
      "date": {
        "type": "string",
        "description": "Startdatum (YYYY-MM-DD)"
      },
      "days_ahead": {
        "type": "integer",
        "description": "Anzahl Tage voraus prüfen",
        "default": 3
      }
    },
    "required": ["service_id", "date"]
  }
}
```

#### Tool 3: book_appointment
```json
{
  "name": "book_appointment",
  "description": "Termin für Friseur-Dienstleistung buchen",
  "input_schema": {
    "type": "object",
    "properties": {
      "customer_name": {
        "type": "string",
        "description": "Vollständiger Name des Kunden"
      },
      "customer_phone": {
        "type": "string",
        "description": "Telefonnummer (mit +49)"
      },
      "customer_email": {
        "type": "string",
        "description": "E-Mail für Bestätigung"
      },
      "service_id": {
        "type": "integer",
        "description": "ID der Dienstleistung"
      },
      "staff_id": {
        "type": "integer",
        "description": "ID des Mitarbeiters"
      },
      "datetime": {
        "type": "string",
        "description": "Termin (YYYY-MM-DD HH:mm)"
      },
      "notes": {
        "type": "string",
        "description": "Zusätzliche Notizen"
      }
    },
    "required": ["customer_name", "customer_phone", "service_id", "staff_id", "datetime"]
  }
}
```

#### Tool 4: schedule_callback
```json
{
  "name": "schedule_callback",
  "description": "Beratungsrückruf für Strähnen, Blondierung oder Balayage vereinbaren",
  "input_schema": {
    "type": "object",
    "properties": {
      "customer_name": {
        "type": "string",
        "description": "Name des Kunden"
      },
      "customer_phone": {
        "type": "string",
        "description": "Rückrufnummer"
      },
      "service_name": {
        "type": "string",
        "description": "Gewünschte Dienstleistung"
      },
      "preferred_time": {
        "type": "string",
        "description": "Bevorzugte Rückrufzeit"
      },
      "notes": {
        "type": "string",
        "description": "Beratungswünsche"
      }
    },
    "required": ["customer_name", "customer_phone", "service_name"]
  }
}
```

## 🔄 MCP Protocol Format

Unser MCP Server erwartet Requests in diesem Format:

```json
{
  "jsonrpc": "2.0",
  "method": "list_services",
  "params": {
    "company_id": 1,
    "category": "schnitt"
  },
  "id": "unique-request-id"
}
```

Und antwortet mit:

```json
{
  "jsonrpc": "2.0",
  "result": {
    "services": [
      {
        "id": 1,
        "name": "Waschen, schneiden, föhnen (Damen)",
        "duration": 60,
        "price": 45,
        "requires_consultation": false
      }
    ]
  },
  "id": "unique-request-id"
}
```

## ✅ Schritt-für-Schritt Anleitung

1. **Öffnen Sie den Agent im Retell Dashboard**
   - https://dashboard.retellai.com/agents/agent_d7da9e5c49c4ccfff2526df5c1

2. **Navigieren Sie zum MCP Bereich**
   - Scrollen Sie zu "Model Context Protocol (MCP)"
   - Klicken Sie auf "Add MCP"

3. **Füllen Sie die Felder aus:**
   - **Name**: `hair_salon_mcp`
   - **URL**: `https://api.askproai.de/api/v2/hair-salon-mcp/mcp`
   - **Description**: (siehe oben)
   - **Headers**: (JSON von oben kopieren)
   - **Query Parameters**: company_id=1, version=2.0, locale=de-DE
   - **Timeout**: 30000

4. **Speichern Sie die Konfiguration**

5. **Testen Sie die Integration**

## 🧪 Test Commands

Nach der Konfiguration können Sie testen:

```bash
# Test ob MCP erreichbar ist
curl -X POST https://api.askproai.de/api/v2/hair-salon-mcp/mcp \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "method": "list_services",
    "params": {"company_id": 1},
    "id": "test-1"
  }'
```

## 📞 Test-Anrufe

Nach der Konfiguration testen Sie mit diesen Szenarien:

1. **"Ich möchte einen Haarschnitt buchen"**
   - Sollte `list_services` → `check_availability` → `book_appointment` aufrufen

2. **"Ich hätte gerne Strähnen"**
   - Sollte `schedule_callback` aufrufen (Beratung erforderlich)

3. **"Was kostet eine Dauerwelle?"**
   - Sollte `list_services` mit category="dauerwelle" aufrufen

## 📊 Monitoring

```bash
# Live Logs verfolgen
tail -f storage/logs/laravel.log | grep "MCP Bridge"

# Nur MCP Requests
tail -f storage/logs/laravel.log | grep "Retell MCP Request"
```

## ⚠️ Wichtige Hinweise

1. **Der MCP läuft PARALLEL zu Cal.com** - beide Systeme funktionieren
2. **Authentication ist OPTIONAL** - für erste Tests ohne Auth
3. **Company ID 1** ist der Standard-Testsalon
4. **Tools werden automatisch erkannt** - Retell.ai liest die Tool-Definitionen aus dem MCP

## 🔴 Falls es nicht funktioniert

1. **Check Health Endpoint:**
   ```
   https://api.askproai.de/api/v2/hair-salon-mcp/mcp/health
   ```

2. **Check Logs:**
   ```bash
   tail -100 storage/logs/laravel.log | grep -i error
   ```

3. **Test Direct Call:**
   ```bash
   curl -X POST https://api.askproai.de/api/v2/hair-salon-mcp/mcp/list_services \
     -H "Content-Type: application/json" \
     -H "X-Company-ID: 1" \
     -d '{"method":"list_services","params":{"company_id":1}}'
   ```

---

**Status:** ✅ Backend Ready
**Next:** Füllen Sie diese Werte im Retell Dashboard aus