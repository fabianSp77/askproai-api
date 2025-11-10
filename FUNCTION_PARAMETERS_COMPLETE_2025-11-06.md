# Function Parameters - Complete Documentation

**Date**: 2025-11-06
**Purpose**: Detaillierte Parameter-Schemas für alle Retell AI Funktionen

---

## 🔧 start_booking (Fixed 2025-11-06)

### Handler
- **File**: `app/Http/Controllers/RetellFunctionCallHandler.php`
- **Line**: 1596
- **Method**: `startBooking(array $params, ?string $callId)`

### Expected Parameters

| Parameter | Type | Required | Default | Aliases | Description |
|-----------|------|----------|---------|---------|-------------|
| `customer_name` | string | ✅ Yes | - | `name` | Kundenname (Vor- und Nachname) |
| `customer_phone` | string | ✅ Yes | - | `phone` | Telefonnummer (E.164 format) |
| `service_name` | string | ✅ Yes* | - | `dienstleistung` | Service-Name (mit Synonym-Matching) |
| `service_id` | int | ⚠️ Optional | null | - | Direkte Service-ID (überschreibt name) |
| `date` | string | ✅ Yes | - | `datum` | Termin-Datum (YYYY-MM-DD oder DE format) |
| `time` | string | ✅ Yes | - | `uhrzeit` | Termin-Uhrzeit (HH:MM) |
| `customer_email` | string | ⚠️ Optional | '' | `email` | Email-Adresse |
| `duration` | int | ⚠️ Optional | 60 | - | Dauer in Minuten |
| `notes` | string | ⚠️ Optional | '' | - | Zusätzliche Notizen |

*Either `service_name` OR `service_id` required

### Date/Time Formats Supported

**Date (`date`/`datum`):**
- ISO: `2025-11-07`
- German: `07.11.2025`, `7.11.2025`
- Relative: `heute`, `morgen`, `übermorgen`
- Weekdays: `Montag`, `nächsten Freitag`

**Time (`time`/`uhrzeit`):**
- 24h: `10:00`, `14:30`, `09:15`
- Smart inference: `10 Uhr` → infers date if missing

### Response Format

**Success (HTTP 200):**
```json
{
  "success": true,
  "data": {
    "status": "validating",
    "next_action": "confirm_booking",
    "service_name": "Herrenhaarschnitt",
    "appointment_time": "2025-11-07T10:00:00+01:00"
  },
  "message": "Ich prüfe jetzt die Verfügbarkeit für Herrenhaarschnitt am Freitag, 07. November um 10:00 Uhr."
}
```

**Error (HTTP 200):**
```json
{
  "success": false,
  "error": "Dieser Service ist leider nicht verfügbar"
}
```

### Test Command
```bash
curl -X POST "https://api.askproai.de/api/webhooks/retell/function" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "start_booking",
    "args": {
      "service_name": "Herrenhaarschnitt",
      "date": "2025-11-07",
      "time": "10:00",
      "customer_name": "Max Mustermann",
      "customer_phone": "+4915123456789"
    },
    "call": {"call_id": "call_test_123"}
  }'
```

---

## 🔍 find_next_available (Fixed 2025-11-06)

### Handler
- **File**: `app/Http/Controllers/RetellFunctionCallHandler.php`
- **Line**: 4915
- **Method**: `handleFindNextAvailable(array $params, ?string $callId)`

### Expected Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `service_name` | string | ⚠️ Optional* | - | Service-Name (mit Synonym-Matching) |
| `service_id` | int | ⚠️ Optional* | - | Direkte Service-ID |
| `after` | string | ⚠️ Optional | now() | Startpunkt der Suche (ISO datetime) |
| `search_days` | int | ⚠️ Optional | 14 | Anzahl Tage voraus suchen (max 30) |

*Either `service_name` OR `service_id` required

### Response Format

**Success (HTTP 200):**
```json
{
  "success": true,
  "next_available": "2025-11-08T14:00:00+01:00",
  "service_name": "Herrenhaarschnitt",
  "formatted_date": "Freitag, 08. November um 14:00 Uhr"
}
```

**No Slots (HTTP 200):**
```json
{
  "success": false,
  "message": "Leider sind in den nächsten 14 Tagen keine freien Termine verfügbar"
}
```

**Error (HTTP 200):**
```json
{
  "success": false,
  "message": "Anrufkontext nicht gefunden"
}
```

### Test Command
```bash
curl -X POST "https://api.askproai.de/api/webhooks/retell/function" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "find_next_available",
    "args": {
      "service_name": "Herrenhaarschnitt",
      "search_days": 7
    },
    "call": {"call_id": "call_test_456"}
  }'
```

---

## 📊 Implementation Details

### start_booking Flow

1. **Get Call Context** (Line 1605)
   - Resolves company_id, branch_id from call_id
   - Falls back to TEST MODE if not found

2. **Parse DateTime** (Line 1625)
   - Uses `DateTimeParser` service
   - Handles German formats, relative dates
   - Rejects past times

3. **Extract Customer Data** (Lines 1636-1642)
   - Supports multiple parameter names (aliases)
   - Validates required fields

4. **Service Selection** (Lines 1644-1656)
   - Checks cached service_id first (pinned service)
   - Falls back to service_id or service_name lookup
   - Uses default service if none specified

5. **Validation & Caching** (Lines 1672-1697)
   - Stores validated data in Redis (5min TTL)
   - Cache key: `booking_prep:{call_id}`

6. **Response** (Lines 1701-1713)
   - Returns immediate status update (<500ms)
   - User hears feedback while availability check runs async

### find_next_available Flow

1. **Get Call Context** (Line 1924)
   - Resolves company_id from call_id
   - Returns error if call not found

2. **Service Lookup** (Lines 1933-1942)
   - Prioritizes service_name with LIKE matching
   - Falls back to direct service_id lookup

3. **Parse Start Time** (Lines 1952-1962)
   - Parses optional `after` parameter
   - Defaults to now() if not provided

4. **Search Slots** (Lines 1965-1968)
   - Uses `SmartAppointmentFinder` service
   - Searches up to `search_days` ahead (default: 14)
   - Queries Cal.com API for availability

5. **Response** (Lines 1970-1990)
   - Returns first available slot if found
   - Returns user-friendly message if no slots

---

## 🔗 Related Files

### Core Services Used

**start_booking:**
- `app/Services/Retell/DateTimeParser.php` - Date parsing
- `app/Services/Retell/ServiceSelectionService.php` - Service lookup
- `app/Services/Retell/WebhookResponseService.php` - Response formatting

**find_next_available:**
- `app/Services/CallLifecycleService.php` - Call context
- `app/Services/Appointments/SmartAppointmentFinder.php` - Slot finding
- `app/Models/Service.php` - Service model

### Documentation Files
- `FUNCTION_500_ERRORS_FIXED_2025-11-06.md` - Bugfixes
- `agent-v50-interactive-complete.html` - Interactive docs

---

## ✅ Status

**Both functions fully documented with:**
- ✅ Complete parameter schemas
- ✅ Expected formats & validation rules
- ✅ Response formats (success + error cases)
- ✅ Test commands
- ✅ Implementation flow diagrams
