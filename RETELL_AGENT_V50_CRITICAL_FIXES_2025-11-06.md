# Retell Agent V50 - Kritische Fixes für perfekten Datenfluss

**Date**: 2025-11-06 16:00
**Status**: 🚨 CRITICAL - 5 Major Issues
**Priority**: P0 (Production Blocker)

---

## Executive Summary

Das aktuelle Retell Agent JSON V50 hat **5 kritische Probleme**, die einen perfekten Datenfluss verhindern:

| Issue | Severity | Impact | Status |
|-------|----------|--------|--------|
| Fehlende Tools (get_alternatives, request_callback) | 🔴 CRITICAL | Kein Fallback bei Nicht-Verfügbarkeit | ❌ FEHLT |
| Two-Step Booking ungenutzt | 🔴 CRITICAL | Schlechte UX (4-5s Wartezeit) | ❌ FEHLT |
| Context Initialization fehlt | 🟡 HIGH | {{current_date}} bleibt leer | ❌ FEHLT |
| Alternative Handling incomplete | 🟡 HIGH | Alternativen nicht strukturiert | ❌ INCOMPLETE |
| Kein Fallback Route | 🟡 HIGH | User steckt fest bei Ablehnung | ❌ FEHLT |

---

## Problem 1: Fehlende CRITICAL Tools

### 1.1 get_alternatives (Feature Matrix #4)

**Status in Matrix:**
- Priority: `critical`
- Testing: `✅ getestet`
- Implementation: `✅ produktionsbereit`

**Status im Agent:**
- ❌ Tool existiert NICHT in `tools` Array
- ❌ Kein func_get_alternatives Node
- ❌ Keine Edge dorthin

**Was fehlt:**

```json
{
  "tool_id": "tool-get-alternatives",
  "timeout_ms": 10000,
  "name": "get_alternatives",
  "description": "Schlägt alternative Zeitslots vor wenn Wunschtermin nicht verfügbar",
  "type": "custom",
  "parameters": {
    "type": "object",
    "properties": {
      "service_name": {
        "type": "string",
        "description": "Service für den Alternativen gesucht werden"
      },
      "preferred_date": {
        "type": "string",
        "description": "Gewünschtes Datum (DD.MM.YYYY oder relativ)"
      },
      "preferred_time": {
        "type": "string",
        "description": "Gewünschte Uhrzeit (HH:MM)"
      },
      "call_id": {
        "type": "string",
        "description": "Call ID from system"
      }
    },
    "required": ["call_id", "service_name", "preferred_date"]
  },
  "url": "https://api.askproai.de/api/webhooks/retell/function"
}
```

**Flow Integration:**

```
func_check_availability
  → [if not available]
  → func_get_alternatives (NEW!)
  → node_present_alternatives
  → [user selects]
  → func_start_booking
```

---

### 1.2 request_callback (Feature Matrix #14)

**Status in Matrix:**
- Priority: `critical`
- Testing: `✅ getestet`
- Implementation: `✅ produktionsbereit (6 Fixes 2025-11-06)`
- Success Rate: `100%` (gerade verifiziert!)

**Status im Agent:**
- ❌ Tool existiert NICHT
- ❌ Kein Node
- ❌ Keine Fallback Route

**Was fehlt:**

```json
{
  "tool_id": "tool-request-callback",
  "timeout_ms": 10000,
  "name": "request_callback",
  "description": "Erstellt Callback-Request mit automatischer Staff-Zuweisung",
  "type": "custom",
  "parameters": {
    "type": "object",
    "properties": {
      "customer_name": {
        "type": "string",
        "description": "Name des Kunden"
      },
      "phone_number": {
        "type": "string",
        "description": "Telefonnummer (Format: +49... oder 0...)"
      },
      "reason": {
        "type": "string",
        "description": "Grund für Rückruf (z.B. 'Termin buchen', 'Beratung')"
      },
      "preferred_time": {
        "type": "string",
        "description": "Bevorzugte Rückrufzeit (optional, z.B. 'Vormittag', 'heute Nachmittag')"
      },
      "call_id": {
        "type": "string",
        "description": "Call ID from system"
      }
    },
    "required": ["call_id", "customer_name", "phone_number", "reason"]
  },
  "url": "https://api.askproai.de/api/webhooks/retell/function"
}
```

**Flow Integration:**

```
node_present_alternatives
  → [if user declines all]
  → node_offer_callback (NEW!)
  → func_request_callback (NEW!)
  → node_callback_confirmation
  → node_end
```

---

## Problem 2: Two-Step Booking existiert, wird NICHT genutzt

### Aktueller Zustand

**Tools existieren:**
```json
✅ tool-start-booking (Step 1: <500ms Validation)
✅ tool-confirm-booking (Step 2: 4-5s Booking)
```

**Aber Flow nutzt:**
```json
❌ func_book_appointment → tool-book-appointment-v17 (old single-step)
```

**Problem:**
- User wartet 4-5 Sekunden in Stille
- Kein Feedback während Cal.com Buchung
- Schlechte UX

### Fixed Flow

**Ersetze:**
```json
node_present_result → func_book_appointment
```

**Mit:**
```json
node_present_result
  → func_start_booking (instant <500ms)
  → [say: "Perfekt! Ich buche den Termin..."]
  → func_confirm_booking (background 4-5s)
  → node_booking_success
```

**Neue Nodes:**

```json
{
  "name": "Buchung starten",
  "tool_id": "tool-start-booking",
  "instruction": {
    "type": "static_text",
    "text": "Perfekt! Einen Moment, ich validiere die Daten..."
  },
  "parameter_mapping": {
    "function_name": "start_booking",
    "call_id": "{{call_id}}",
    "datetime": "{{appointment_date}} {{appointment_time}}",
    "service": "{{service_name}}",
    "customer_name": "{{customer_name}}",
    "customer_phone": "{{customer_phone}}",
    "customer_email": "{{customer_email}}"
  },
  "id": "func_start_booking",
  "type": "function",
  "speak_during_execution": true,
  "wait_for_result": true,
  "edges": [
    {
      "destination_node_id": "func_confirm_booking",
      "id": "edge_start_to_confirm",
      "transition_condition": {
        "type": "prompt",
        "prompt": "start_booking returned success"
      }
    }
  ]
}
```

```json
{
  "name": "Buchung bestätigen",
  "tool_id": "tool-confirm-booking",
  "instruction": {
    "type": "static_text",
    "text": "Ich schließe die Buchung ab..."
  },
  "parameter_mapping": {
    "function_name": "confirm_booking",
    "call_id": "{{call_id}}"
  },
  "id": "func_confirm_booking",
  "type": "function",
  "speak_during_execution": false,
  "wait_for_result": true,
  "edges": [
    {
      "destination_node_id": "node_booking_success",
      "id": "edge_confirm_to_success",
      "transition_condition": {
        "type": "prompt",
        "prompt": "Booking confirmed"
      }
    }
  ]
}
```

**Benefit:**
- User hört sofort Feedback (<500ms)
- 4-5s Wartezeit passiert während Agent spricht
- Deutlich bessere UX

---

## Problem 3: Context Initialization fehlt

### Problem

**Global Prompt referenziert:**
```
{{current_date}} → externes Backend liefert aktuelles Datum
{{current_time}} → Backend liefert aktuelle Uhrzeit
```

**Aber:**
- Tool `get_current_context` existiert ✅
- Tool wird NIE gecallt ❌
- Variablen bleiben leer ❌

### Fix

**Neuer Node nach Begrüßung:**

```json
{
  "name": "Context initialisieren",
  "tool_id": "tool-get-current-context",
  "instruction": {
    "type": "static_text",
    "text": ""
  },
  "parameter_mapping": {
    "call_id": "{{call_id}}"
  },
  "id": "func_initialize_context",
  "type": "function",
  "speak_during_execution": false,
  "wait_for_result": true,
  "edges": [
    {
      "destination_node_id": "intent_router",
      "id": "edge_init_to_intent",
      "transition_condition": {
        "type": "prompt",
        "prompt": "Context loaded"
      }
    }
  ]
}
```

**Update Edge:**
```json
// VORHER:
node_greeting → intent_router

// NACHHER:
node_greeting → func_initialize_context → intent_router
```

**Tool Response setzt:**
```json
{
  "current_date": "2025-11-06",
  "current_time": "16:00",
  "day_name": "Mittwoch",
  "week_number": 45
}
```

Diese Werte werden in {{current_date}}, {{current_time}} etc. verfügbar.

---

## Problem 4: Alternative Handling Incomplete

### Aktueller Zustand

```
func_check_availability (nur prüft Wunschtermin)
  → node_present_result (zeigt "nicht verfügbar")
  → node_extract_alternative_selection (versucht Alternativen zu extrahieren)
  → ??? (woher kommen die Alternativen?)
```

**Problem:**
- `check_availability` ist designed um JA/NEIN zu sagen
- Es gibt KEINEN dedizierten Alternative-Lookup!
- Node versucht nicht-existente Daten zu nutzen

### Fix

**Dedicated Alternative Flow:**

```json
{
  "name": "Alternativen abrufen",
  "tool_id": "tool-get-alternatives",
  "instruction": {
    "type": "static_text",
    "text": "Einen Moment, ich suche nach Alternativen..."
  },
  "parameter_mapping": {
    "call_id": "{{call_id}}",
    "service_name": "{{service_name}}",
    "preferred_date": "{{appointment_date}}",
    "preferred_time": "{{appointment_time}}"
  },
  "id": "func_get_alternatives",
  "type": "function",
  "speak_during_execution": true,
  "wait_for_result": true,
  "edges": [
    {
      "destination_node_id": "node_present_alternatives",
      "id": "edge_alternatives_to_present",
      "transition_condition": {
        "type": "prompt",
        "prompt": "Alternatives retrieved"
      }
    }
  ]
}
```

**Update node_present_result:**

```json
{
  "name": "Ergebnis zeigen",
  "edges": [
    {
      "destination_node_id": "func_start_booking",
      "id": "edge_available_to_book",
      "transition_condition": {
        "type": "prompt",
        "prompt": "Requested time IS available"
      }
    },
    {
      "destination_node_id": "func_get_alternatives",
      "id": "edge_not_available_to_alternatives",
      "transition_condition": {
        "type": "prompt",
        "prompt": "Requested time NOT available"
      }
    }
  ],
  "instruction": {
    "type": "prompt",
    "text": "WENN VERFÜGBAR:\n\"Der Termin am {{appointment_date}} um {{appointment_time}} ist verfügbar. Soll ich buchen?\"\n→ Transition zu func_start_booking\n\nWENN NICHT VERFÜGBAR:\n\"Leider ist {{appointment_date}} um {{appointment_time}} nicht verfügbar. Ich suche Alternativen...\"\n→ Transition zu func_get_alternatives"
  }
}
```

**Neue node_present_alternatives:**

```json
{
  "name": "Alternativen präsentieren",
  "edges": [
    {
      "destination_node_id": "node_extract_alternative_selection",
      "id": "edge_alternatives_to_select",
      "transition_condition": {
        "type": "prompt",
        "prompt": "User selected an alternative time"
      }
    },
    {
      "destination_node_id": "node_offer_callback",
      "id": "edge_alternatives_to_callback",
      "transition_condition": {
        "type": "prompt",
        "prompt": "User declined all alternatives OR no alternatives available"
      }
    },
    {
      "destination_node_id": "node_collect_booking_info",
      "id": "edge_alternatives_to_retry",
      "transition_condition": {
        "type": "prompt",
        "prompt": "User wants to try a completely different date/time"
      }
    }
  ],
  "id": "node_present_alternatives",
  "type": "conversation",
  "instruction": {
    "type": "prompt",
    "text": "Präsentiere die Alternativen:\n\n\"Ich habe folgende Alternativen für Sie gefunden:\n[Liste mit 2-3 Optionen]\n\nWelcher Termin passt Ihnen am besten?\n\nFalls keine passt, können wir auch einen anderen Tag probieren, oder ich kann veranlassen dass wir Sie zurückrufen.\""
  }
}
```

---

## Problem 5: Kein Fallback zu request_callback

### Fehlende Route

**Aktuell:**
```
node_present_alternatives
  → [user says "Keine passt"]
  → ??? STUCK!
```

**Was sein sollte:**
```
node_present_alternatives
  → [user declines all]
  → node_offer_callback
  → func_request_callback
  → node_callback_confirmation
```

### Neue Nodes

**node_offer_callback:**

```json
{
  "name": "Callback anbieten",
  "edges": [
    {
      "destination_node_id": "node_collect_callback_info",
      "id": "edge_offer_to_collect",
      "transition_condition": {
        "type": "prompt",
        "prompt": "User accepts callback offer (Ja, Gerne, Bitte)"
      }
    },
    {
      "destination_node_id": "node_end",
      "id": "edge_offer_to_end",
      "transition_condition": {
        "type": "prompt",
        "prompt": "User declines callback"
      }
    }
  ],
  "id": "node_offer_callback",
  "type": "conversation",
  "instruction": {
    "type": "prompt",
    "text": "Biete Callback an:\n\n\"Kein Problem! Möchten Sie, dass wir Sie zurückrufen, wenn ein passender Termin frei wird?\n\nWir könnten Sie dann kontaktieren und einen Termin direkt vereinbaren.\""
  }
}
```

**node_collect_callback_info:**

```json
{
  "name": "Callback-Daten sammeln",
  "edges": [
    {
      "destination_node_id": "func_request_callback",
      "id": "edge_collect_callback_to_func",
      "transition_condition": {
        "type": "prompt",
        "prompt": "All callback data collected: {{customer_name}} AND {{customer_phone}} AND {{callback_reason}}"
      }
    }
  ],
  "variables": [
    {
      "type": "string",
      "name": "callback_reason",
      "description": "Grund für Rückruf (z.B. 'Termin für Balayage buchen')"
    },
    {
      "type": "string",
      "name": "callback_preferred_time",
      "description": "Bevorzugte Rückrufzeit (optional)"
    }
  ],
  "id": "node_collect_callback_info",
  "type": "conversation",
  "instruction": {
    "type": "prompt",
    "text": "Sammle Callback-Informationen:\n\n**Bereits bekannt:**\n- Name: {{customer_name}}\n- Telefon: {{customer_phone}} (aus Call)\n- Service: {{service_name}}\n\n**Frage nur wenn fehlt:**\n- \"Unter welcher Nummer können wir Sie am besten erreichen?\" (wenn {{customer_phone}} fehlt)\n- \"Gibt es eine bevorzugte Zeit für den Rückruf?\" (optional)\n\n**Setze callback_reason automatisch:**\n→ \"Termin für {{service_name}} buchen\"\n\n**Transition:**\nSobald Name + Phone vorhanden → func_request_callback"
  }
}
```

**func_request_callback:**

```json
{
  "name": "Callback-Request erstellen",
  "tool_id": "tool-request-callback",
  "instruction": {
    "type": "static_text",
    "text": "Perfekt! Ich erstelle Ihre Rückruf-Anfrage..."
  },
  "parameter_mapping": {
    "call_id": "{{call_id}}",
    "customer_name": "{{customer_name}}",
    "phone_number": "{{customer_phone}}",
    "reason": "{{callback_reason}}",
    "preferred_time": "{{callback_preferred_time}}"
  },
  "id": "func_request_callback",
  "type": "function",
  "speak_during_execution": true,
  "wait_for_result": true,
  "edges": [
    {
      "destination_node_id": "node_callback_confirmation",
      "id": "edge_callback_to_confirm",
      "transition_condition": {
        "type": "prompt",
        "prompt": "Callback created successfully"
      }
    }
  ]
}
```

**node_callback_confirmation:**

```json
{
  "name": "Callback bestätigt",
  "edges": [
    {
      "destination_node_id": "node_end",
      "id": "edge_callback_confirm_to_end",
      "transition_condition": {
        "type": "prompt",
        "prompt": "Always end"
      }
    }
  ],
  "id": "node_callback_confirmation",
  "type": "conversation",
  "instruction": {
    "type": "prompt",
    "text": "Bestätige Callback:\n\n\"Wunderbar! Ihre Rückruf-Anfrage wurde erstellt und automatisch an unser Team zugewiesen.\n\nWir melden uns zeitnah bei Ihnen unter {{customer_phone}} um einen passenden Termin zu vereinbaren.\n\nVielen Dank und einen schönen Tag!\""
  }
}
```

---

## Problem 6: Parameter Mapping mit || Operator

### Problem

**Aktuell in func_book_appointment:**
```json
"uhrzeit": "{{selected_alternative_time || appointment_time}}"
```

⚠️ Der `||` Operator wird in Retell parameter_mapping möglicherweise nicht unterstützt!

### Fix

**Option 1: Variable vorher normalisieren**

Neuer Node vor func_start_booking:

```json
{
  "name": "Zeit normalisieren",
  "variables": [
    {
      "type": "string",
      "name": "final_time",
      "description": "Die finale Buchungszeit (entweder selected_alternative_time oder appointment_time)"
    }
  ],
  "id": "node_normalize_time",
  "type": "extract_dynamic_variables",
  "edges": [
    {
      "destination_node_id": "func_start_booking",
      "id": "edge_normalize_to_book",
      "transition_condition": {
        "type": "equation",
        "equations": [
          {
            "left": "final_time",
            "operator": "exists"
          }
        ]
      }
    }
  ]
}
```

Dann in func_start_booking:
```json
"datetime": "{{appointment_date}} {{final_time}}"
```

**Option 2: Separate Edges basierend auf Source**

```json
// Von node_present_result (Original-Zeit):
{
  "destination_node_id": "func_start_booking",
  "parameter_override": {
    "datetime": "{{appointment_date}} {{appointment_time}}"
  }
}

// Von node_confirm_alternative (Alternative):
{
  "destination_node_id": "func_start_booking",
  "parameter_override": {
    "datetime": "{{appointment_date}} {{selected_alternative_time}}"
  }
}
```

---

## Summary: Alle erforderlichen Changes

### 1. Tools hinzufügen (tools Array)

```json
"tools": [
  // ... existing tools ...

  // 🆕 ADD:
  {
    "tool_id": "tool-get-alternatives",
    "name": "get_alternatives",
    // ... (siehe oben)
  },

  // 🆕 ADD:
  {
    "tool_id": "tool-request-callback",
    "name": "request_callback",
    // ... (siehe oben)
  }
]
```

### 2. Nodes hinzufügen (nodes Array)

```
🆕 func_initialize_context (nach node_greeting)
🆕 func_get_alternatives (bei nicht verfügbar)
🆕 node_present_alternatives (Alternativen zeigen)
🆕 node_offer_callback (Callback anbieten)
🆕 node_collect_callback_info (Callback-Daten sammeln)
🆕 func_request_callback (Callback erstellen)
🆕 node_callback_confirmation (Callback bestätigen)
🆕 func_start_booking (Two-Step Step 1)
🆕 func_confirm_booking (Two-Step Step 2)
```

### 3. Edges ändern

```
❌ REMOVE: node_greeting → intent_router
✅ ADD:    node_greeting → func_initialize_context → intent_router

❌ REMOVE: node_present_result → func_book_appointment
✅ ADD:    node_present_result → func_start_booking → func_confirm_booking → node_booking_success

✅ ADD:    node_present_result → func_get_alternatives (wenn nicht verfügbar)
✅ ADD:    node_present_alternatives → node_offer_callback (wenn abgelehnt)
```

### 4. Deprecated Tool entfernen

```
❌ REMOVE: tool-book-appointment (v17, single-step)
```

---

## Implementation Priority

### 🔴 P0 - CRITICAL (Must-Have)

1. ✅ **Tool: request_callback hinzufügen**
   - Backend bereits gefixt und getestet (callback_id: 9 erfolgreich)
   - Flow Integration: Fallback Route erstellen

2. ✅ **Tool: get_alternatives hinzufügen**
   - Backend Service `AppointmentAlternativeFinder` existiert
   - Flow Integration: Alternative Handling komplettieren

3. ✅ **Context Initialization**
   - func_initialize_context Node erstellen
   - Edge von greeting aktualisieren

### 🟡 P1 - HIGH (Should-Have)

4. ✅ **Two-Step Booking aktivieren**
   - func_start_booking + func_confirm_booking Nodes
   - Deprecated book_appointment_v17 entfernen
   - UX deutlich verbessern

5. ✅ **Alternative Flow komplettieren**
   - node_present_alternatives erstellen
   - Edges zu callback hinzufügen

### 🟢 P2 - MEDIUM (Nice-to-Have)

6. ✅ **Parameter Mapping fixen**
   - || Operator entfernen
   - Variable normalisieren

---

## Testing Checklist

Nach Implementation alle Flows testen:

### Happy Path - Termin direkt verfügbar
```
1. User: "Herrenhaarschnitt morgen 14 Uhr"
2. func_initialize_context → {{current_date}} = "2025-11-06"
3. node_collect_booking_info → alle Daten sammeln
4. func_check_availability → ✅ verfügbar
5. func_start_booking → instant feedback
6. func_confirm_booking → booking success
7. node_booking_success → bestätigt
```

### Alternative Path - Wunschtermin nicht verfügbar
```
1. User: "Färben morgen 10 Uhr"
2. func_check_availability → ❌ nicht verfügbar
3. func_get_alternatives → [09:50, 11:30, 14:00]
4. node_present_alternatives → zeigt Optionen
5. User: "Um 14 Uhr"
6. func_start_booking → bucht 14:00
7. node_booking_success
```

### Callback Path - Keine Alternative passt
```
1. User: "Balayage Freitag 16 Uhr"
2. func_check_availability → ❌ nicht verfügbar
3. func_get_alternatives → [09:00, 10:30]
4. node_present_alternatives → zeigt Optionen
5. User: "Keine passt mir"
6. node_offer_callback → "Möchten Sie Rückruf?"
7. User: "Ja gerne"
8. node_collect_callback_info → Telefon bestätigen
9. func_request_callback → ✅ callback_id: 10
10. node_callback_confirmation → Auto-assigned
```

### Context Test - Datum-Handling
```
1. Anruf startet
2. func_initialize_context → lädt {{current_date}}
3. User: "Heute um 18 Uhr"
4. Backend nutzt {{current_date}} für "heute" → "06.11.2025"
5. func_check_availability mit korrektem Datum
```

---

## Rollback Plan

Falls Probleme auftreten:

```bash
# 1. Backup aktuelles Agent JSON
curl https://api.retellai.com/v2/agent/agent_9a8202a740cd3120d96fcfda1e \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19" \
  > agent_v50_backup_$(date +%Y%m%d_%H%M%S).json

# 2. Bei Problemen: Restore Backup
curl -X PATCH https://api.retellai.com/v2/agent/agent_9a8202a740cd3120d96fcfda1e \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19" \
  -H "Content-Type: application/json" \
  -d @agent_v50_backup_TIMESTAMP.json
```

---

## Expected Outcomes

Nach Implementation:

### Metrics
- ✅ 100% Coverage: Alle Feature Matrix Funktionen im Agent
- ✅ 0 Dead Ends: Jeder Flow hat Fallback
- ✅ <500ms Initial Feedback: Two-Step Booking
- ✅ 100% Success Rate: request_callback (verifiziert)

### User Experience
- ✅ Keine Wartezeiten ohne Feedback
- ✅ Immer eine Lösung (Termin ODER Callback)
- ✅ Natürliche Alternativen-Präsentation
- ✅ Korrektes Datum-Handling

### Technical Quality
- ✅ Alle Tools aus Backend genutzt
- ✅ Korrekte Parameter Mappings
- ✅ Saubere Edge Transitions
- ✅ Vollständige Error Handling

---

## Next Steps

1. **Review dieses Dokument** mit Team
2. **Priorität bestätigen** (empfohlen: P0 zuerst)
3. **Agent JSON updaten** mit allen Changes
4. **Deploy zu Retell.ai** via API
5. **Testing** gemäß Checklist
6. **Monitor** erste Calls mit neuer Version
7. **Iterate** basierend auf Feedback

---

**Version:** V50 Fix Analysis
**Created:** 2025-11-06 16:00
**Author:** Claude (Performance Engineer)
**Related:** RETELL_FUNCTIONS_FIX_2025-11-06.md, Feature Matrix Tab

