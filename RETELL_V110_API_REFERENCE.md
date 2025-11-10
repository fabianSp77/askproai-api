# Retell Agent V110 - API Reference

**Version:** V110
**Base URL:** `https://api.askproai.de`
**Alle Endpoints:** 11 Custom Functions

---

## Table of Contents

1. [get_current_context](#1-get_current_context)
2. [check_customer](#2-check_customer) ⭐ NEU
3. [check_availability_v17](#3-check_availability_v17)
4. [get_alternatives](#4-get_alternatives)
5. [start_booking](#5-start_booking)
6. [confirm_booking](#6-confirm_booking)
7. [get_customer_appointments](#7-get_customer_appointments)
8. [cancel_appointment](#8-cancel_appointment)
9. [reschedule_appointment](#9-reschedule_appointment)
10. [get_available_services](#10-get_available_services)
11. [request_callback](#11-request_callback)

---

## 1. get_current_context

**Purpose:** Holt aktuelles Datum, Uhrzeit und Wochentag für temporalen Kontext

**Endpoint:** `POST /api/webhooks/retell/current-context`

**When to call:** Zu Beginn JEDES Anrufs (automatisch via func_initialize_context)

### Request

```json
{
  "call_id": "call_abc123xyz"
}
```

### Response

```json
{
  "success": true,
  "current_date": "2025-11-10",
  "current_time": "14:30",
  "day_name": "Sonntag",
  "formatted_date": "Sonntag, den 10. November",
  "timestamp": "2025-11-10T14:30:00+01:00"
}
```

### Variables Set

- `{{current_date}}` → "2025-11-10"
- `{{current_time}}` → "14:30"
- `{{day_name}}` → "Sonntag"

### Timeout

5000ms (5 seconds)

### Error Handling

**If error:** Agent kann nicht mit relativen Zeitangaben arbeiten ("morgen", "übermorgen")

---

## 2. check_customer ⭐

**Purpose:** Identifiziert Kunde via Telefonnummer, liefert Historie und Service-Vorhersage

**Endpoint:** `POST /api/webhooks/retell/check-customer`

**When to call:** Nach get_current_context (automatisch via func_check_customer)

### Request

```json
{
  "call_id": "call_abc123xyz"
}
```

**Note:** Backend extrahiert `from_number` aus Call Context via call_id

### Response (Customer Found)

```json
{
  "found": true,
  "customer_name": "Max Müller",
  "customer_phone": "+491234567890",
  "customer_email": "max@example.com",
  "predicted_service": "Herrenhaarschnitt",
  "service_confidence": 0.85,
  "preferred_staff": "Maria",
  "staff_confidence": 0.90,
  "total_appointments": 12,
  "last_appointment_at": "2025-10-15T10:00:00Z"
}
```

### Response (Customer Not Found)

```json
{
  "found": false
}
```

### Variables Set

- `{{customer_name}}` → "Max Müller" (if found)
- `{{customer_phone}}` → "+491234567890" (if found)
- `{{customer_email}}` → "max@example.com" (if found)
- `{{predicted_service}}` → "Herrenhaarschnitt" (if confidence >= 0.8)
- `{{service_confidence}}` → 0.85
- `{{preferred_staff}}` → "Maria" (if confidence >= 0.8)

### Confidence Thresholds

| Confidence | Usage |
|------------|-------|
| >= 0.8 | Service automatically used (no question) |
| 0.5 - 0.79 | Service suggested as option |
| < 0.5 | Service NOT used (ask customer) |

### Timeout

5000ms (5 seconds)

### Error Handling

**If error:** `{"found": false}` → Agent treats as new customer

---

## 3. check_availability_v17

**Purpose:** Prüft exakte Verfügbarkeit + liefert bis zu 2 Alternativen wenn nicht verfügbar

**Endpoint:** `POST /api/webhooks/retell/function`

**When to call:** Sobald service_name + appointment_date + appointment_time vorhanden

### Request

```json
{
  "call_id": "call_abc123xyz",
  "name": "Max Müller",
  "dienstleistung": "Herrenhaarschnitt",
  "datum": "2025-11-11",
  "uhrzeit": "10:00"
}
```

### Response (Available)

```json
{
  "success": true,
  "available": true,
  "requested_time": "2025-11-11T10:00:00Z",
  "staff": "Maria",
  "duration_minutes": 30,
  "price": 25.00
}
```

### Response (Not Available with Alternatives)

```json
{
  "success": true,
  "available": false,
  "requested_time": "2025-11-11T10:00:00Z",
  "alternatives": [
    {
      "time": "2025-11-11T09:45:00Z",
      "formatted_time": "9 Uhr 45",
      "staff": "Maria",
      "reason": "same_day",
      "distance_minutes": -15,
      "near_match": true
    },
    {
      "time": "2025-11-11T10:15:00Z",
      "formatted_time": "10 Uhr 15",
      "staff": "Julia",
      "reason": "same_day",
      "distance_minutes": 15,
      "near_match": true
    }
  ]
}
```

### Near-Match Logic

```
distance_minutes = alternative_time - requested_time (in minutes)

IF abs(distance_minutes) <= 30:
  near_match = true
  → Agent uses POSITIVE framing: "kann Ihnen anbieten"

ELSE:
  near_match = false
  → Agent uses NEUTRAL framing: "ist leider nicht verfügbar"
```

### Alternatives Sorting

1. **Same Day + Near-Match** (±30 min)
2. **Same Day + Far-Match** (>30 min)
3. **Same Time + Different Day**
4. **Next Available Slot**

### Timeout

15000ms (15 seconds)

### Error Handling

**If timeout:** Agent transitions to node_booking_failed → Callback Flow

---

## 4. get_alternatives

**Purpose:** Holt weitere Alternativen wenn erste 2 vom User abgelehnt wurden

**Endpoint:** `POST /api/webhooks/retell/function`

**When to call:** User sagt "Weitere Vorschläge" oder "Mehr Optionen"

### Request

```json
{
  "call_id": "call_abc123xyz",
  "service_name": "Herrenhaarschnitt",
  "preferred_date": "2025-11-11",
  "preferred_time": "10:00"
}
```

### Response

```json
{
  "success": true,
  "alternatives": [
    {
      "time": "2025-11-11T14:00:00Z",
      "formatted_time": "14 Uhr",
      "staff": "Maria",
      "reason": "same_day",
      "distance_minutes": 240,
      "near_match": false
    },
    {
      "time": "2025-11-12T10:00:00Z",
      "formatted_time": "10 Uhr",
      "staff": "Julia",
      "reason": "same_time",
      "distance_minutes": 1440,
      "near_match": false
    }
  ]
}
```

### Timeout

10000ms (10 seconds)

### Error Handling

**If no more alternatives:** Return empty array `[]` → Agent offers callback

---

## 5. start_booking

**Purpose:** Step 1 of Two-Step Booking - Validiert Daten + cached für 5 Minuten

**Endpoint:** `POST /api/webhooks/retell/function`

**When to call:** Nach User-Bestätigung "Ja bitte buchen"

### Request

```json
{
  "call_id": "call_abc123xyz",
  "function_name": "start_booking",
  "customer_name": "Max Müller",
  "service": "Herrenhaarschnitt",
  "datetime": "11.11.2025 10:00",
  "customer_phone": "+491234567890",
  "customer_email": "max@example.com"
}
```

### Response (Success)

```json
{
  "success": true,
  "status": "validating",
  "booking_token": "tok_abc123",
  "cached_until": "2025-11-10T14:35:00Z",
  "validation": {
    "booking_notice_ok": true,
    "service_exists": true,
    "time_valid": true,
    "contact_provided": true
  }
}
```

### Response (Validation Error)

```json
{
  "success": false,
  "status": "validation_error",
  "error_code": "BOOKING_NOTICE_VIOLATION",
  "error_message": "Termine müssen mindestens 15 Minuten im Voraus gebucht werden",
  "next_valid_slot": "2025-11-10T15:00:00Z"
}
```

### Validation Rules

| Rule | Description | Error Code |
|------|-------------|------------|
| Booking Notice | >= 15 min vorher | BOOKING_NOTICE_VIOLATION |
| Service Exists | Service in DB vorhanden | SERVICE_NOT_FOUND |
| Time Valid | HH:MM Format | INVALID_TIME_FORMAT |
| Contact | Phone OR Email | CONTACT_REQUIRED |
| Name | Not empty | NAME_REQUIRED |

### Timeout

5000ms (5 seconds) - FAST validation only

### Error Handling

**If error:** Agent transitions to node_booking_validation_failed → Asks for correction

---

## 6. confirm_booking

**Purpose:** Step 2 of Two-Step Booking - Führt Cal.com Buchung aus (slow operation)

**Endpoint:** `POST /api/webhooks/retell/function`

**When to call:** IMMEDIATELY after start_booking returns success

### Request

```json
{
  "call_id": "call_abc123xyz",
  "function_name": "confirm_booking"
}
```

**Note:** Backend retrieves cached booking data via call_id

### Response (Success)

```json
{
  "success": true,
  "appointment_id": "apt_123",
  "booking_uid": "cal_abc123xyz",
  "appointment_date": "2025-11-11",
  "appointment_time": "10:00",
  "staff_name": "Maria",
  "confirmation_sent": true,
  "confirmation_channels": ["email", "sms"]
}
```

### Response (Failure)

```json
{
  "success": false,
  "error_code": "CAL_TIMEOUT",
  "error_message": "Cal.com API nicht erreichbar",
  "retry_possible": true
}
```

### Variables Set

- `{{appointment_id}}` → "apt_123"
- `{{booking_uid}}` → "cal_abc123xyz"

### Timeout

30000ms (30 seconds) - Cal.com can be slow

### Error Handling

**If error:** Agent transitions to node_booking_failed → Callback Flow with phone collection

---

## 7. get_customer_appointments

**Purpose:** Ruft alle zukünftigen Termine des Kunden ab

**Endpoint:** `POST /api/webhooks/retell/function`

**When to call:** User fragt "Wann ist mein nächster Termin?" oder "Welche Termine habe ich?"

### Request

```json
{
  "call_id": "call_abc123xyz"
}
```

**Note:** Backend identifiziert Customer via from_number

### Response (Appointments Found)

```json
{
  "success": true,
  "appointments": [
    {
      "appointment_id": "apt_123",
      "date": "2025-11-15",
      "formatted_date": "Freitag, den 15. November",
      "time": "10:00",
      "formatted_time": "10 Uhr",
      "service": "Herrenhaarschnitt",
      "staff": "Maria",
      "duration_minutes": 30,
      "can_cancel": true,
      "can_reschedule": true
    },
    {
      "appointment_id": "apt_124",
      "date": "2025-12-01",
      "formatted_date": "Sonntag, den 1. Dezember",
      "time": "14:30",
      "formatted_time": "14 Uhr 30",
      "service": "Färben",
      "staff": "Julia",
      "duration_minutes": 90,
      "can_cancel": true,
      "can_reschedule": true
    }
  ],
  "total_count": 2
}
```

### Response (No Appointments)

```json
{
  "success": true,
  "appointments": [],
  "total_count": 0
}
```

### Sorting

Chronologically ascending (next appointment first)

### Timeout

15000ms (15 seconds)

### Error Handling

**If error:** Agent says "Entschuldigung, ich kann Ihre Termine gerade nicht abrufen"

---

## 8. cancel_appointment

**Purpose:** Storniert einen bestehenden Termin

**Endpoint:** `POST /api/webhooks/retell/function`

**When to call:** Nach User-Bestätigung "Ja, bitte absagen"

### Request (with appointment_id)

```json
{
  "call_id": "call_abc123xyz",
  "appointment_id": "apt_123"
}
```

### Request (without appointment_id - auto-detect)

```json
{
  "call_id": "call_abc123xyz",
  "datum": "2025-11-15",
  "uhrzeit": "10:00"
}
```

### Response (Success)

```json
{
  "success": true,
  "cancelled_appointment": {
    "appointment_id": "apt_123",
    "date": "2025-11-15",
    "time": "10:00",
    "service": "Herrenhaarschnitt"
  },
  "cancellation_sent": true,
  "cancellation_channels": ["email", "sms"]
}
```

### Response (Failure)

```json
{
  "success": false,
  "error_code": "APPOINTMENT_NOT_FOUND",
  "error_message": "Kein Termin für das angegebene Datum gefunden"
}
```

### Timeout

15000ms (15 seconds)

### Error Handling

**If error:** Agent transitions to node_cancel_failed → Callback Flow

---

## 9. reschedule_appointment

**Purpose:** Verschiebt einen bestehenden Termin auf neues Datum/Uhrzeit

**Endpoint:** `POST /api/webhooks/retell/function`

**When to call:** Nach User gibt neues Datum/Zeit an

### Request

```json
{
  "call_id": "call_abc123xyz",
  "appointment_id": "apt_123",
  "new_datum": "2025-11-20",
  "new_uhrzeit": "14:00"
}
```

### Response (Success)

```json
{
  "success": true,
  "old_appointment": {
    "date": "2025-11-15",
    "time": "10:00"
  },
  "new_appointment": {
    "date": "2025-11-20",
    "time": "14:00",
    "staff": "Maria",
    "service": "Herrenhaarschnitt"
  },
  "confirmation_sent": true
}
```

### Response (New Time Not Available)

```json
{
  "success": false,
  "error_code": "TIME_NOT_AVAILABLE",
  "alternatives": [
    {
      "time": "2025-11-20T14:30:00Z",
      "formatted_time": "14 Uhr 30"
    }
  ]
}
```

### Timeout

15000ms (15 seconds)

### Error Handling

**If error:** Agent transitions to node_reschedule_failed → Offers alternatives

---

## 10. get_available_services

**Purpose:** Listet alle verfügbaren Services mit Preisen und Dauer

**Endpoint:** `POST /api/webhooks/retell/function`

**When to call:** User fragt "Was bieten Sie an?" oder "Was kostet X?"

### Request

```json
{
  "call_id": "call_abc123xyz"
}
```

### Response

```json
{
  "success": true,
  "services": [
    {
      "service_id": "svc_123",
      "name": "Herrenhaarschnitt",
      "description": "Waschen, Schneiden, Föhnen",
      "duration_minutes": 30,
      "price": 25.00,
      "currency": "EUR",
      "popular": true
    },
    {
      "service_id": "svc_124",
      "name": "Damenhaarschnitt",
      "description": "Waschen, Schneiden, Föhnen",
      "duration_minutes": 45,
      "price": 35.00,
      "currency": "EUR",
      "popular": true
    },
    {
      "service_id": "svc_125",
      "name": "Färben",
      "description": "Komplettfärbung mit Pflege",
      "duration_minutes": 90,
      "price": 65.00,
      "currency": "EUR",
      "popular": false
    }
  ],
  "total_count": 3
}
```

### Sorting

1. **popular: true** first
2. Alphabetically by name

### Timeout

15000ms (15 seconds)

### Agent Behavior

**Maximal 5 Services nennen**, dann:
"Möchten Sie mehr hören?" → Bei Ja: Weitere 5 nennen

---

## 11. request_callback

**Purpose:** Erstellt Callback-Request mit Multi-Channel Benachrichtigung an Staff

**Endpoint:** `POST /api/webhooks/retell/function`

**When to call:** Bei technischen Fehlern ODER User möchte Callback

### Request

```json
{
  "call_id": "call_abc123xyz",
  "customer_name": "Max Müller",
  "phone_number": "+491234567890",
  "reason": "Termin buchen - Cal.com Timeout"
}
```

### Response

```json
{
  "success": true,
  "callback_id": "cb_123",
  "assigned_to_staff": "Maria",
  "notification_sent": true,
  "notification_channels": ["email", "sms", "whatsapp", "portal"],
  "estimated_callback_within_minutes": 30,
  "sms_sent_to_customer": true
}
```

### Notification Content (Staff)

**Email:**
```
Betreff: Rückruf-Anfrage von Max Müller

Kunde: Max Müller
Telefon: +491234567890
Grund: Termin buchen - Cal.com Timeout
Erstellt: 10.11.2025 14:30 Uhr

Bitte innerhalb von 30 Minuten zurückrufen.
```

**SMS:**
```
Rückruf: Max Müller
Tel: +491234567890
Grund: Termin buchen
Bitte zurückrufen.
```

### Notification Content (Customer)

**SMS:**
```
Vielen Dank! Wir rufen Sie unter +491234567890 innerhalb der nächsten 30 Minuten zurück.

- Ihr Friseur 1 Team
```

### Timeout

10000ms (10 seconds)

### Error Handling

**If error:** Agent apologizes and asks user to call back later

---

## Error Codes Reference

### General Errors

| Code | Description | Recovery |
|------|-------------|----------|
| AUTH_ERROR | Invalid call_id | Retry with correct call_id |
| TIMEOUT | Function timeout | Trigger callback flow |
| SERVER_ERROR | Backend 500 | Trigger callback flow |

### Booking Errors

| Code | Description | Recovery |
|------|-------------|----------|
| BOOKING_NOTICE_VIOLATION | Too short notice | Suggest next valid slot |
| SERVICE_NOT_FOUND | Invalid service name | Show available services |
| TIME_NOT_AVAILABLE | Slot already booked | Show alternatives |
| INVALID_TIME_FORMAT | Wrong time format | Ask again with example |
| CONTACT_REQUIRED | No phone/email | Ask for contact |
| NAME_REQUIRED | Missing name | Ask for name |

### Appointment Errors

| Code | Description | Recovery |
|------|-------------|----------|
| APPOINTMENT_NOT_FOUND | No matching appointment | List all appointments |
| CANNOT_CANCEL | Too close to appointment | Offer to call salon |
| CANNOT_RESCHEDULE | Appointment locked | Offer to call salon |

---

## Rate Limits

| Endpoint | Limit | Window |
|----------|-------|--------|
| get_current_context | 100 req/min | Per IP |
| check_customer | 100 req/min | Per IP |
| check_availability_v17 | 60 req/min | Per IP |
| All booking functions | 30 req/min | Per IP |

**If exceeded:** 429 Too Many Requests → Trigger callback flow

---

## Authentication

All requests require Retell signature verification via `X-Retell-Signature` header.

**Backend Verification:**
```php
use App\Services\RetellApiClient;

$signature = $request->header('X-Retell-Signature');
$payload = $request->getContent();

if (!RetellApiClient::verifySignature($signature, $payload)) {
    return response()->json(['error' => 'Invalid signature'], 401);
}
```

---

## Testing

**Postman Collection:** `tests/postman/Retell_V110_API.postman_collection.json`

**Quick Test Script:**
```bash
# Test all endpoints
./tests/scripts/test_retell_api_v110.sh

# Test specific endpoint
curl -X POST "https://api.askproai.de/api/webhooks/retell/check-customer" \
  -H "Content-Type: application/json" \
  -H "X-Retell-Signature: test_signature" \
  -d '{"call_id": "test_123"}' \
  | jq '.'
```

---

**Version:** V110 API Reference
**Last Updated:** 2025-11-10
**Total Endpoints:** 11
**Coverage:** 100%
