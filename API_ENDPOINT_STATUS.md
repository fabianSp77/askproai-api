# API ENDPOINT STATUS - 2025-11-04

## Quick Reference: All Retell Integration Endpoints

### Status Legend
- ✅ **VERIFIED** - Tested and working
- 🟢 **CONFIGURED** - Properly configured, not tested
- ⚠️ **CAUTION** - Working but with known issues
- ❌ **BROKEN** - Not functional

---

## CRITICAL ENDPOINTS (Test Call Flow)

### 1. Webhook - Call Inbound
```
POST /api/webhooks/retell
Status: ✅ VERIFIED
Middleware: retell.signature, throttle:60,1
Handler: RetellWebhookController::__invoke()
Event: call_inbound

Purpose: Receives initial call notification from Retell
Creates call record with company/branch context
```

**Request Example**:
```json
{
  "event": "call_inbound",
  "call_inbound": {
    "call_id": "call_abc123...",
    "from_number": "anonymous",
    "to_number": "+493033081738",
    "agent_id": "agent_b36ecd3927a81834b6d56ab07b"
  }
}
```

**Response**: 200 OK
**Creates**: Call record with phone context resolution

---

### 2. Function Call - Check Availability (V17)
```
POST /api/retell/v17/check-availability
Status: ✅ VERIFIED
Middleware: throttle:100,1, retell.validate.callid
Handler: RetellFunctionCallHandler::checkAvailabilityV17()

Purpose: Check appointment availability WITHOUT booking
Injects bestaetigung=false automatically
```

**Request Example**:
```json
{
  "call": {
    "call_id": "call_abc123..."
  },
  "args": {
    "datum": "05.11.2025",
    "uhrzeit": "09:00",
    "dienstleistung": "Herrenhaarschnitt",
    "name": "Max Mustermann"
  }
}
```

**Response Example**:
```json
{
  "verfuegbar": true,
  "message": "Der Termin am 05.11.2025 um 09:00 Uhr ist verfügbar.",
  "naechste_verfuegbare_termine": [
    "09:00", "09:15", "09:30", "09:45", "10:00"
  ],
  "termin_details": {
    "datum": "05.11.2025",
    "uhrzeit": "09:00",
    "dienstleistung": "Herrenhaarschnitt",
    "dauer_minuten": 45
  }
}
```

**Flow**:
1. Extract call_id from request
2. Inject bestaetigung=false
3. Call collectAppointment()
4. Query Cal.com for availability
5. Return slots to Retell agent

---

### 3. Function Call - Book Appointment (V17)
```
POST /api/retell/v17/book-appointment
Status: ✅ VERIFIED
Middleware: throttle:100,1, retell.validate.callid
Handler: RetellFunctionCallHandler::bookAppointmentV17()

Purpose: Confirm and CREATE appointment booking
Injects bestaetigung=true automatically
```

**Request Example**: Same as check-availability

**Response Example** (Success):
```json
{
  "success": true,
  "appointment_id": 123,
  "message": "Termin erfolgreich gebucht für 05.11.2025 um 09:00 Uhr",
  "bestaetigungs_details": {
    "appointment_id": 123,
    "starts_at": "2025-11-05T09:00:00+01:00",
    "ends_at": "2025-11-05T09:45:00+01:00",
    "service": "Herrenhaarschnitt",
    "customer_name": "Max Mustermann",
    "branch": "Friseur 1 Zentrale"
  }
}
```

**Flow**:
1. Extract call_id from request
2. Inject bestaetigung=true
3. Call collectAppointment()
4. Check availability
5. CREATE appointment record
6. CREATE or link customer record
7. Queue Cal.com sync job
8. Return confirmation

---

### 4. Webhook - Call Ended
```
POST /api/webhooks/retell
Status: ✅ VERIFIED
Event: call_ended
Handler: RetellWebhookController::handleCallEnded()

Purpose: Update call record with final metrics
Calculates costs, duration, success status
```

**Request Example**:
```json
{
  "event": "call_ended",
  "call": {
    "call_id": "call_abc123...",
    "duration_ms": 88000,
    "disconnection_reason": "user_hangup",
    "end_timestamp": 1730745193000
  }
}
```

**Response**: 200 OK
**Updates**: Call record with final data

---

## SUPPORTING ENDPOINTS

### 5. Webhook - Call Started
```
POST /api/webhooks/retell
Status: ✅ VERIFIED
Event: call_started
Handler: RetellWebhookController::handleCallStarted()
```

**Purpose**: Real-time call tracking, creates RetellCallSession

---

### 6. Webhook - Call Analyzed
```
POST /api/webhooks/retell
Status: ✅ VERIFIED
Event: call_analyzed
Handler: RetellWebhookController::__invoke()
```

**Purpose**: Process transcript, extract insights, link customer

---

### 7. Legacy Function Call Handler
```
POST /api/webhooks/retell/function
Status: 🟢 CONFIGURED (backward compatibility)
Middleware: throttle:100,1
Handler: RetellFunctionCallHandler::handleFunctionCall()
```

**Purpose**: Generic function call handler (legacy)
**Note**: V17 endpoints are preferred

---

### 8. Initialize Call (V4)
```
POST /api/retell/initialize-call-v4
Status: 🟢 CONFIGURED
Handler: RetellFunctionCallHandler::initializeCallV4()
```

**Purpose**: Customer identification at call start
**Used By**: Conversation Flow V4 (complex features)

---

### 9. Get Customer Appointments (V4)
```
POST /api/retell/get-appointments-v4
Status: 🟢 CONFIGURED
Handler: RetellFunctionCallHandler::getCustomerAppointmentsV4()
```

**Purpose**: Retrieve customer's upcoming appointments
**Used By**: Conversation Flow V4 (rescheduling, cancellation)

---

### 10. Cancel Appointment (V4)
```
POST /api/retell/cancel-appointment-v4
Status: 🟢 CONFIGURED
Middleware: throttle:100,1, retell.validate.callid
Handler: RetellFunctionCallHandler::cancelAppointmentV4()
```

**Purpose**: Cancel existing appointment
**Requires**: Customer authentication

---

### 11. Reschedule Appointment (V4)
```
POST /api/retell/reschedule-appointment-v4
Status: 🟢 CONFIGURED
Middleware: throttle:100,1, retell.validate.callid
Handler: RetellFunctionCallHandler::rescheduleAppointmentV4()
```

**Purpose**: Reschedule existing appointment
**Requires**: Customer authentication

---

### 12. Get Available Services (V4)
```
POST /api/retell/get-services-v4
Status: 🟢 CONFIGURED
Handler: RetellFunctionCallHandler::getAvailableServicesV4()
```

**Purpose**: List all bookable services for branch
**Returns**: Service names, durations, descriptions

---

## HEALTH CHECK ENDPOINTS

### 13. Basic Health Check
```
GET /api/health
Status: ✅ VERIFIED
Handler: HealthCheckController::basic()
```

**Response**:
```json
{
  "status": "healthy",
  "timestamp": "2025-11-04T20:15:00Z",
  "service": "askpro-api-gateway"
}
```

---

### 14. Cal.com Health Check
```
GET /api/health/calcom
Status: ✅ VERIFIED
Handler: CalcomHealthController::index()
```

**Response**:
```json
{
  "status": "healthy",
  "api_reachable": true,
  "authentication": "valid",
  "response_time_ms": 234
}
```

---

### 15. Retell Webhook Diagnostic
```
GET /api/webhooks/retell/diagnostic
Status: 🟢 CONFIGURED
Middleware: auth:sanctum
Handler: RetellWebhookController::diagnostic()
```

**Purpose**: Comprehensive system diagnostic
**Requires**: Authentication token
**Returns**: Recent calls, phone config, availability test

---

## UTILITY ENDPOINTS

### 16. Current Time (Berlin)
```
GET /api/zeitinfo
Status: ✅ VERIFIED
Middleware: throttle:100,1
```

**Response**:
```json
{
  "date": "04.11.2025",
  "time": "20:15",
  "weekday": "Montag",
  "iso_date": "2025-11-04",
  "week_number": "45"
}
```

**Purpose**: Provides current German time for Retell agent temporal context

---

### 17. Current Time Berlin (Retell Format)
```
POST /api/retell/current-time-berlin
Status: 🟢 CONFIGURED
Middleware: throttle:100,1
```

**Response**:
```json
{
  "success": true,
  "current_time": "2025-11-04T20:15:00+01:00",
  "date": "2025-11-04",
  "time": "20:15",
  "weekday": "Montag",
  "timezone": "Europe/Berlin"
}
```

---

## MIDDLEWARE REFERENCE

### retell.signature
- **Purpose**: Validates webhook signature from Retell
- **Location**: `app/Http/Middleware/ValidateRetellSignature.php`
- **Prevents**: Webhook forgery (CVSS 9.3 vulnerability)
- **Status**: ✅ ACTIVE

### retell.validate.callid
- **Purpose**: Validates call_id parameter exists
- **Location**: `app/Http/Middleware/ValidateRetellCallId.php`
- **Prevents**: Missing call context errors
- **Status**: ✅ ACTIVE

### throttle
- **Purpose**: Rate limiting
- **Limits**:
  - Webhooks: 60 requests/minute
  - Function calls: 100 requests/minute
  - Booking operations: 30-60 requests/minute

---

## ENDPOINT PRIORITY FOR TEST CALL

**CRITICAL PATH** (Must work for booking):
1. `/api/webhooks/retell` (call_inbound) - ✅
2. `/api/retell/v17/check-availability` - ✅
3. `/api/retell/v17/book-appointment` - ✅
4. `/api/webhooks/retell` (call_ended) - ✅

**SUPPORTING** (Nice to have):
5. `/api/webhooks/retell` (call_started) - ✅
6. `/api/webhooks/retell` (call_analyzed) - ✅

**MONITORING**:
7. `/api/health/calcom` - ✅
8. `/api/zeitinfo` - ✅

---

## RETELL AGENT CONFIGURATION

**Agent ID**: `agent_b36ecd3927a81834b6d56ab07b`
**Phone Number**: `+493033081738`
**Conversation Flow**: V17 (2-step booking)

**Function Definitions** (in Retell dashboard):

```javascript
// Function 1: check_availability_v17
{
  "name": "check_availability_v17",
  "url": "https://api.askpro.ai/api/retell/v17/check-availability",
  "description": "Prüft Verfügbarkeit für einen Termin OHNE zu buchen",
  "parameters": {
    "datum": "string (format: DD.MM.YYYY)",
    "uhrzeit": "string (format: HH:MM)",
    "dienstleistung": "string",
    "name": "string"
  }
}

// Function 2: book_appointment_v17
{
  "name": "book_appointment_v17",
  "url": "https://api.askpro.ai/api/retell/v17/book-appointment",
  "description": "Bucht einen Termin nachdem Verfügbarkeit bestätigt wurde",
  "parameters": {
    "datum": "string (format: DD.MM.YYYY)",
    "uhrzeit": "string (format: HH:MM)",
    "dienstleistung": "string",
    "name": "string",
    "email": "string (optional)"
  }
}
```

---

## TESTING COMMANDS

### Test Endpoint Accessibility
```bash
# Health check
curl https://api.askpro.ai/api/health

# Cal.com connectivity
curl https://api.askpro.ai/api/health/calcom

# Current time
curl https://api.askpro.ai/api/zeitinfo
```

### Monitor Test Call
```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log | grep -E "collect_appointment|check_availability|cal.com"

# Watch for errors
tail -f storage/logs/laravel.log | grep ERROR
```

### Verify Call Record
```bash
php artisan tinker --execute="
\$call = \App\Models\Call::orderBy('created_at', 'desc')->first();
echo 'Latest Call:' . PHP_EOL;
echo 'ID: ' . \$call->id . PHP_EOL;
echo 'Retell ID: ' . \$call->retell_call_id . PHP_EOL;
echo 'Phone Number ID: ' . (\$call->phone_number_id ?: 'NOT SET') . PHP_EOL;
echo 'Has Appointment: ' . (\$call->has_appointment ? 'YES' : 'NO') . PHP_EOL;
"
```

---

## KNOWN ISSUES & WORKAROUNDS

### Issue 1: phone_number_id Not Set
**Status**: ⚠️ UNDER INVESTIGATION
**Impact**: Data integrity, reporting
**Workaround**: Manual update after call creation
**Fix**: Check PhoneNumberResolutionService logging

### Issue 2: No Availability for Today
**Status**: ⚠️ BUSINESS LOGIC
**Impact**: May return "no slots" message
**Workaround**: Test with tomorrow's date
**Fix**: Configure Cal.com availability for testing hours

---

## RESPONSE TIME BENCHMARKS

Based on recent test calls:

| Endpoint | Average Response Time | Notes |
|----------|----------------------|-------|
| check_availability_v17 | 300-800ms | Includes Cal.com API call |
| book_appointment_v17 | 500-1200ms | Includes DB writes + queue |
| call_inbound webhook | 50-150ms | Just DB insert |
| call_ended webhook | 200-500ms | Includes cost calculation |

**Cal.com API**: Typically 200-400ms per request
**Database Queries**: < 50ms per operation
**Total Booking Flow**: ~2-3 seconds (check + confirm)

---

## ERROR CODES REFERENCE

| Code | Meaning | HTTP Status | Recoverable? |
|------|---------|-------------|--------------|
| `phone_number_not_found` | Unknown phone number | 404 | No |
| `service_not_found` | Service not in database | 404 | Yes (fuzzy match) |
| `availability_check_failed` | Cal.com API error | 500 | Yes (retry) |
| `no_slots_available` | No availability | 200 | Yes (alternatives) |
| `context_not_found` | Missing call context | 400 | No |
| `booking_failed` | Appointment creation failed | 500 | Yes (retry) |
| `validation_error` | Invalid input data | 422 | Yes (retry with correct data) |

---

## SECURITY CONSIDERATIONS

### Webhook Security
- ✅ Signature validation (HMAC-SHA256)
- ✅ IP whitelisting possible (not enabled)
- ✅ Rate limiting active
- ✅ HTTPS required

### API Security
- ✅ No authentication required for function calls (Retell trusted)
- ✅ Call ID validation on sensitive operations
- ✅ XSS protection via FormRequest validation
- ✅ SQL injection protected (Laravel ORM)

### Data Privacy
- ✅ GDPR-compliant logging (LogSanitizer)
- ✅ PII redaction in logs
- ✅ Secure credential storage (.env)

---

**Last Updated**: 2025-11-04 20:15:00 UTC
**Maintained By**: AskPro Engineering Team
**Version**: 1.0
