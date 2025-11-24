# Phone-Based Cancellation & Reschedule Policy

**Date**: 2025-11-18
**Status**: Production Ready
**Impact**: Critical Security Enhancement
**Module**: Retell Function Calls (Cancel & Reschedule)

---

## Business Requirement

**Policy**: Appointments can only be cancelled or rescheduled by customers with verified phone numbers.

**Rationale**:
- **Abuse Prevention**: Prevents unauthorized cancellations from random callers
- **Identity Verification**: Ensures the person cancelling is the actual customer
- **Anonymous Booking Support**: Graceful degradation for appointments booked without phone numbers
- **Callback Integration**: Redirects unverifiable requests to manual callback workflow

---

## Implementation Overview

### Security Layers

1. **Layer 1: Anonymous Caller Detection** (Existing)
   - Detects if `call.from_number` is `anonymous`, `unknown`, `withheld`, `restricted`, or empty
   - Creates callback request immediately
   - **No changes required** - already implemented

2. **Layer 2: Customer Phone Validation** (NEW)
   - Checks if appointment's customer has a valid phone number
   - Valid means: not null, not empty, not in blocklist (`00000000`, `anonymous`, etc.)
   - If invalid → creates callback request

3. **Layer 3: Phone Number Matching** (NEW)
   - Compares caller's phone number with customer's phone number
   - Uses last 8 digits to handle different country code formats
   - If mismatch → denies cancellation and offers callback

---

## Technical Implementation

### Files Modified

- `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`
  - `handleCancellationAttempt()` (lines 4033-4276)
  - `handleRescheduleAttempt()` (lines 4284-4550+)

### Code Flow

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Anonymous Caller Check                                   │
│    IF call.from_number IN [anonymous, unknown, ...]         │
│    THEN createAnonymousCallbackRequest()                    │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. Find Appointment                                          │
│    appointment = findAppointmentFromCall(call, params)      │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. Customer Exists Check (NEW)                              │
│    IF !appointment.customer                                 │
│    THEN return error "no_customer"                          │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 4. Customer Phone Validation (NEW)                          │
│    customerPhone = customer.phone                           │
│    IF !isPhoneValid(customerPhone)                          │
│    THEN createAnonymousCallbackRequest()                    │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 5. Phone Number Matching (NEW)                              │
│    callerLast8 = substr(normalize(call.from_number), -8)    │
│    customerLast8 = substr(normalize(customer.phone), -8)    │
│    IF callerLast8 != customerLast8                          │
│    THEN return error "unauthorized" + callback offer        │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 6. Policy Check (Existing)                                  │
│    policyResult = policyEngine.canCancel(appointment)       │
│    IF allowed → proceed with cancellation                   │
│    ELSE → return denial with fee information                │
└─────────────────────────────────────────────────────────────┘
```

---

## Response Scenarios

### Scenario 1: Successful Cancellation
**Conditions**:
- Caller phone matches customer phone
- Customer has valid phone number
- Policy check passes (hours notice, quota, etc.)

**Response**:
```json
{
  "success": true,
  "status": "cancelled",
  "message": "Ihr Termin am 19.11.2025 um 14:00 Uhr wurde erfolgreich storniert.",
  "fee": 0,
  "appointment_id": 12345
}
```

### Scenario 2: Anonymous Caller
**Conditions**:
- `call.from_number` is `anonymous`, `unknown`, etc.

**Response**:
```json
{
  "success": true,
  "status": "callback_requested",
  "message": "Ich habe Ihre Anfrage notiert. Einer unserer Mitarbeiter wird sich in Kürze bei Ihnen melden.",
  "callback_request_id": 789,
  "priority": "high"
}
```

### Scenario 3: Customer Has No Phone
**Conditions**:
- Appointment exists, customer exists
- `customer.phone` is `null`, empty, or `00000000`

**Response**:
```json
{
  "success": true,
  "status": "callback_requested",
  "message": "Ich habe Ihre Anfrage notiert. Einer unserer Mitarbeiter wird sich in Kürze bei Ihnen melden.",
  "callback_request_id": 790,
  "priority": "high"
}
```

**Log Entry**:
```
🔒 Cancellation blocked: Customer has no valid phone number
{
  "appointment_id": 12345,
  "customer_id": 678,
  "customer_phone": null,
  "call_id": "call_abc123",
  "reason": "phone_verification_required"
}
```

### Scenario 4: Phone Number Mismatch
**Conditions**:
- Caller: `+49151234567890`
- Customer: `+49157654321098`
- Last 8 digits do not match

**Response**:
```json
{
  "success": false,
  "status": "unauthorized",
  "message": "Für eine Stornierung benötigen wir eine Verifikation. Dieser Termin ist auf eine andere Telefonnummer gebucht. Möchten Sie, dass wir Sie unter +49157654321098 zurückrufen, um die Stornierung zu bestätigen?",
  "callback_available": true,
  "reason": "phone_verification_failed"
}
```

**Log Entry**:
```
🚨 SECURITY: Phone mismatch - unauthorized cancellation attempt
{
  "appointment_id": 12345,
  "customer_id": 678,
  "customer_name": "Max Mustermann",
  "customer_phone_last8": "54321098",
  "caller_phone_last8": "34567890",
  "call_id": "call_abc123",
  "security_violation": "phone_mismatch"
}
```

### Scenario 5: Policy Violation (Hours Notice)
**Conditions**:
- Phone verification passes
- Appointment is in 2 hours
- Policy requires 24 hours notice

**Response**:
```json
{
  "success": false,
  "status": "denied",
  "message": "Eine Stornierung ist leider nicht mehr möglich. Sie benötigen 24 Stunden Vorlauf, aber Ihr Termin ist nur noch in 2 Stunden. Wenn Sie trotzdem stornieren möchten, fällt eine Gebühr von 20.00€ an.",
  "reason": "deadline_missed",
  "details": {
    "required_hours": 24,
    "hours_notice": 2,
    "fee_if_forced": 20.00
  }
}
```

---

## Security Considerations

### Why Last 8 Digits?

**Problem**: Phone numbers can have different formats:
- `+4915112345678` (international)
- `015112345678` (national)
- `15112345678` (local)

**Solution**: Compare last 8 digits
- Handles different country code formats
- Sufficient uniqueness (100 million combinations)
- Prevents false negatives from formatting differences

**Example**:
```php
$callerPhone = "+4915112345678";
$customerPhone = "015112345678";

// Normalize: remove non-digits
$callerNormalized = "4915112345678";  // 13 digits
$customerNormalized = "15112345678";  // 11 digits

// Last 8 digits
$callerLast8 = "12345678";
$customerLast8 = "12345678";

// Match ✓
```

### Phone Blocklist

Invalid phone values that trigger callback flow:
- `null` / empty string
- `"anonymous"`
- `"unknown"`
- `"withheld"`
- `"restricted"`
- `"00000000"` (hidden number placeholder)

### Logging Strategy

**Success Path**:
- ✅ INFO level
- Contains: appointment_id, customer_id, phone_match=true

**Security Violations**:
- 🚨 WARNING level
- Contains: last 8 digits of both numbers (not full numbers for GDPR)
- Includes security_violation field for alerting

**Errors**:
- 🚨 ERROR level
- Contains: appointment_id, customer_id (no phone numbers)

---

## Testing Strategy

### Unit Tests

```php
// Test 1: Anonymous caller → callback request
test('anonymous caller gets callback request for cancellation')

// Test 2: Customer with no phone → callback request
test('customer without phone gets callback request')

// Test 3: Phone mismatch → unauthorized
test('phone mismatch denies cancellation')

// Test 4: Phone match → proceeds to policy check
test('matching phone proceeds to policy check')

// Test 5: Different formats, same number → allowed
test('different phone formats with same number allowed')
```

### Integration Tests

```php
// Test 6: End-to-end cancellation flow
test('complete cancellation flow with phone verification')

// Test 7: Reschedule with phone verification
test('complete reschedule flow with phone verification')

// Test 8: Callback request creation
test('callback request created with correct metadata')
```

### Manual Test Scenarios

1. **Happy Path**: Call from registered number → cancel appointment
2. **Anonymous**: Call with hidden number → receive callback promise
3. **Wrong Number**: Call from different number → denied + callback offer
4. **No Phone Customer**: Existing appointment with no phone → callback flow
5. **Policy Violation**: Valid phone but quota exceeded → denied with reason

---

## Deployment Notes

### Database Requirements

**No migration required** - uses existing columns:
- `customers.phone` (already exists)
- `appointments.customer_id` (already exists)
- `callback_requests` table (already exists)

### Backward Compatibility

**Fully compatible** with existing appointments:
- Appointments with phone numbers → work as before + enhanced security
- Appointments without phone numbers → gracefully degraded to callback flow
- No breaking changes to API contracts

### Configuration

**No configuration changes required** - policy is hardcoded for security.

To customize phone validation logic:
```php
// File: RetellFunctionCallHandler.php
// Line: 4082-4083 (cancellation) and 4362-4364 (reschedule)

$isPhoneValid = !empty($customerPhone) &&
               !in_array(strtolower($customerPhone), ['anonymous', 'unknown', 'withheld', 'restricted', '00000000', '']);
```

### Monitoring

**Key Metrics to Track**:
- `phone_verification_required` count (callback requests due to no phone)
- `phone_verification_failed` count (unauthorized attempts)
- `phone_mismatch` security violations
- Callback request completion rate

**Alert Triggers**:
- Spike in `phone_mismatch` violations (potential abuse)
- High ratio of callback requests to direct cancellations (UX issue)

---

## Future Enhancements

### Considered But Not Implemented

1. **SMS Verification Code**
   - Pro: Stronger identity verification
   - Con: Adds latency, requires SMS credits
   - Decision: Callback flow is sufficient for now

2. **PIN-Based Cancellation**
   - Pro: Works without phone matching
   - Con: Customer must remember PIN
   - Decision: Phone matching is more user-friendly

3. **Email Verification**
   - Pro: Alternative to phone
   - Con: Not all customers have email
   - Decision: Phone is primary identifier for voice calls

### Recommended Next Steps

1. **Analytics Dashboard**
   - Track cancellation success rate by verification method
   - Monitor callback request completion rate
   - Identify customers with frequent verification failures

2. **Customer Portal Integration**
   - Allow customers to update their phone number
   - Show security log (who attempted to cancel)
   - Self-service verification code generation

3. **Machine Learning**
   - Detect suspicious cancellation patterns
   - Flag high-risk attempts for manual review
   - Adaptive verification based on customer trust score

---

## References

- **Implementation**: `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`
- **Callback Model**: `/var/www/api-gateway/app/Models/CallbackRequest.php`
- **Policy Engine**: `/var/www/api-gateway/app/Services/Policies/AppointmentPolicyEngine.php`
- **Related**: `APPOINTMENT_MODIFICATION_POLICY_SYSTEM.md`

---

## Changelog

**2025-11-18** - Initial Implementation
- Added customer phone validation in `handleCancellationAttempt()`
- Added phone number matching (last 8 digits)
- Added same security to `handleRescheduleAttempt()`
- Comprehensive logging for security audit trail
- Graceful fallback to callback request flow
