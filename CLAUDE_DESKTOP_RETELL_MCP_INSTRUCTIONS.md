# 🤖 Claude Desktop + Retell MCP - Agent Update Instructions

## Kopieren Sie diese Anleitung in Claude Desktop und lassen Sie Claude den Agent für Sie aktualisieren!

---

## Aufgabe für Claude Desktop:

Bitte nutze den Retell MCP um den Agent `agent_d7da9e5c49c4ccfff2526df5c1` zu aktualisieren. Hier sind die genauen Änderungen:

### 1. Agent Prompt Update

Bitte aktualisiere den `general_prompt` des Agents mit diesem Text:

```
Du bist der freundliche KI-Assistent für einen Friseursalon mit 3 Mitarbeiterinnen: Paula (ID:1), Claudia (ID:2) und Katrin (ID:3).

## Deine Hauptaufgaben:
1. Termine für Friseurdienstleistungen vereinbaren
2. Dienstleistungen und Preise erklären
3. Beratungstermine für spezielle Services vereinbaren

## Mitarbeiterauswahl:
Wenn der Kunde keinen speziellen Mitarbeiter wünscht:
- Prüfe die Verfügbarkeit aller drei Mitarbeiterinnen
- Biete die nächsten verfügbaren Termine an
- Erwähne bei wem der Termin wäre

Wenn der Kunde einen bestimmten Mitarbeiter möchte:
- Nutze nur die staff_id dieser Mitarbeiterin
- Paula = staff_id: 1
- Claudia = staff_id: 2  
- Katrin = staff_id: 3

## Dienstleistungen die Beratung benötigen:
Diese Services erfordern IMMER einen Beratungsrückruf:
- Strähnen (alle Varianten)
- Blondierung
- Balayage
- Faceframe

Für diese sage: "Das ist eine tolle Wahl! Für [Service] ist eine persönliche Beratung wichtig. Ich vereinbare gerne einen Rückruf für Sie."

## Gesprächsablauf:
1. Begrüße freundlich und frage nach dem Wunsch
2. Bei Beratungsservices → schedule_callback nutzen
3. Bei normalen Services → check_availability → book_appointment
4. Bestätige alle Termine mit allen Details
5. Verabschiede dich freundlich

## Wichtige Hinweise:
- Nutze IMMER die MCP Funktionen für alle Aktionen
- Bestätige IMMER Name und Telefonnummer
- Bei Unsicherheiten höflich nachfragen
- Sprich natürlich und freundlich auf Deutsch

## Beispiel-Dialoge:
Kunde: "Ich hätte gerne Strähnen"
Du: "Wunderbar! Strähnen sind eine tolle Wahl. Da brauchen wir eine persönliche Beratung, um das perfekte Ergebnis für Sie zu planen. Darf ich Ihnen einen Rückruf vereinbaren? Wann würde es Ihnen passen?"

Kunde: "Ich möchte einen Haarschnitt bei Paula"
Du: "Sehr gerne! Ich schaue nach freien Terminen bei Paula für einen Haarschnitt. [check_availability mit staff_id:1]"
```

### 2. Agent Settings Update

Bitte aktualisiere auch diese Einstellungen:
- `agent_name`: "Hair Salon Assistant - 3 Mitarbeiterinnen"
- `language`: "de"
- `voice_id`: "11labs-Hanna" (oder eine andere deutsche Stimme)
- `webhook_url`: "https://api.askproai.de/api/v2/hair-salon-mcp/retell-webhook"
- `responsiveness`: 1
- `interruption_sensitivity`: 1
- `enable_backchannel`: true
- `backchannel_frequency`: 0.8
- `backchannel_words`: ["ja", "genau", "verstehe", "okay", "mhm"]

### 3. Custom Functions hinzufügen

Füge diese 4 Custom Functions zum Agent hinzu:

#### Function 1: list_services
```json
{
  "name": "list_services",
  "description": "List all available hair salon services with prices",
  "url": "https://api.askproai.de/api/v2/hair-salon-mcp/mcp",
  "method": "POST",
  "headers": {
    "Content-Type": "application/json",
    "X-Company-ID": "1"
  },
  "body": {
    "jsonrpc": "2.0",
    "method": "list_services",
    "params": {
      "company_id": 1
    },
    "id": "list-{{timestamp}}"
  },
  "parameters": {
    "type": "object",
    "properties": {
      "category": {
        "type": "string",
        "description": "Optional service category filter"
      }
    }
  }
}
```

#### Function 2: check_availability
```json
{
  "name": "check_availability",
  "description": "Check available appointment slots for a service",
  "url": "https://api.askproai.de/api/v2/hair-salon-mcp/mcp",
  "method": "POST",
  "headers": {
    "Content-Type": "application/json",
    "X-Company-ID": "1"
  },
  "body": {
    "jsonrpc": "2.0",
    "method": "check_availability",
    "params": {
      "company_id": 1,
      "service_id": "{{service_id}}",
      "staff_id": "{{staff_id}}",
      "date": "{{date}}",
      "days_ahead": "{{days_ahead}}"
    },
    "id": "avail-{{timestamp}}"
  },
  "parameters": {
    "type": "object",
    "required": ["service_id"],
    "properties": {
      "service_id": {
        "type": "integer",
        "description": "ID of the service"
      },
      "staff_id": {
        "type": "integer",
        "description": "Staff member ID (1=Paula, 2=Claudia, 3=Katrin)"
      },
      "date": {
        "type": "string",
        "description": "Start date (YYYY-MM-DD)"
      },
      "days_ahead": {
        "type": "integer",
        "description": "Number of days to check ahead"
      }
    }
  }
}
```

#### Function 3: book_appointment
```json
{
  "name": "book_appointment",
  "description": "Book an appointment for a customer",
  "url": "https://api.askproai.de/api/v2/hair-salon-mcp/mcp",
  "method": "POST",
  "headers": {
    "Content-Type": "application/json",
    "X-Company-ID": "1"
  },
  "body": {
    "jsonrpc": "2.0",
    "method": "book_appointment",
    "params": {
      "company_id": 1,
      "customer_name": "{{customer_name}}",
      "customer_phone": "{{customer_phone}}",
      "service_id": "{{service_id}}",
      "staff_id": "{{staff_id}}",
      "datetime": "{{datetime}}",
      "notes": "{{notes}}"
    },
    "id": "book-{{timestamp}}"
  },
  "parameters": {
    "type": "object",
    "required": ["customer_name", "customer_phone", "service_id", "staff_id", "datetime"],
    "properties": {
      "customer_name": {
        "type": "string",
        "description": "Customer full name"
      },
      "customer_phone": {
        "type": "string",
        "description": "Customer phone number"
      },
      "service_id": {
        "type": "integer",
        "description": "ID of the service"
      },
      "staff_id": {
        "type": "integer",
        "description": "Staff member ID"
      },
      "datetime": {
        "type": "string",
        "description": "Appointment date and time (YYYY-MM-DD HH:MM)"
      },
      "notes": {
        "type": "string",
        "description": "Optional notes"
      }
    }
  }
}
```

#### Function 4: schedule_callback
```json
{
  "name": "schedule_callback",
  "description": "Schedule a callback for consultation services",
  "url": "https://api.askproai.de/api/v2/hair-salon-mcp/mcp",
  "method": "POST",
  "headers": {
    "Content-Type": "application/json",
    "X-Company-ID": "1"
  },
  "body": {
    "jsonrpc": "2.0",
    "method": "schedule_callback",
    "params": {
      "company_id": 1,
      "customer_name": "{{customer_name}}",
      "customer_phone": "{{customer_phone}}",
      "service_name": "{{service_name}}",
      "preferred_time": "{{preferred_time}}",
      "notes": "{{notes}}"
    },
    "id": "callback-{{timestamp}}"
  },
  "parameters": {
    "type": "object",
    "required": ["customer_name", "customer_phone", "service_name"],
    "properties": {
      "customer_name": {
        "type": "string",
        "description": "Customer full name"
      },
      "customer_phone": {
        "type": "string",
        "description": "Customer phone number"
      },
      "service_name": {
        "type": "string",
        "description": "Service requiring consultation"
      },
      "preferred_time": {
        "type": "string",
        "description": "Preferred callback time"
      },
      "notes": {
        "type": "string",
        "description": "Additional notes"
      }
    }
  }
}
```

### 4. MCP Configuration (@MCP Section)

Füge diese MCP Konfiguration hinzu:
- **Name**: `hair_salon_mcp`
- **URL**: `https://api.askproai.de/api/v2/hair-salon-mcp/mcp`
- **Description**: Hair Salon booking system with appointment management
- **Timeout**: `30000`
- **Headers**: 
  ```json
  {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Company-ID": "1"
  }
  ```
- **Query Parameters**:
  - `company_id`: `1`
  - `version`: `2.0`
  - `locale`: `de-DE`

---

## Bitte führe diese Updates durch und bestätige mir wenn alles erfolgreich aktualisiert wurde!

Agent ID: `agent_d7da9e5c49c4ccfff2526df5c1`
Dashboard Link: https://dashboard.retellai.com/agents/agent_d7da9e5c49c4ccfff2526df5c1