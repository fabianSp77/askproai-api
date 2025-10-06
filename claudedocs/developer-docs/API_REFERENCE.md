# AskProAI Appointment Management System - API Reference

**Last Updated:** 2025-10-02
**API Version:** v1
**Document Status:** Technical Reference

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Retell Function Call API](#retell-function-call-api)
4. [Service Layer API](#service-layer-api)
5. [Response Formats](#response-formats)
6. [Error Handling](#error-handling)
7. [Rate Limiting](#rate-limiting)
8. [Examples](#examples)

---

## Overview

The AskProAI API provides webhook endpoints for Retell AI voice assistant integration and programmatic access to appointment management services.

### Base URL

```
Production:  https://askproai.de/api
Development: http://localhost:8000/api
```

### Content Types

- **Request:** `application/json`
- **Response:** `application/json`

### API Versioning

Currently using URL versioning:
- `/api/v1/...` (future)
- `/api/...` (current, unversioned)

---

## Authentication

### Retell Webhook Authentication

Retell AI webhooks use custom signature validation for security.

**Headers:**

```http
X-Retell-Signature: sha256=abc123def456...
X-Retell-Timestamp: 1696248000
Content-Type: application/json
```

**Verification Process:**

```php
// Location: app/Http/Middleware/VerifyRetellSignature.php

1. Extract signature from X-Retell-Signature header
2. Get raw request body
3. Compute HMAC-SHA256(secret, timestamp + body)
4. Compare computed signature with provided signature
5. Check timestamp freshness (< 5 minutes old)
```

**Signature Calculation:**

```php
$payload = $timestamp . $rawBody;
$computedSignature = hash_hmac('sha256', $payload, config('services.retell.webhook_secret'));

if (!hash_equals($providedSignature, $computedSignature)) {
    abort(401, 'Invalid signature');
}
```

**Environment Configuration:**

```env
RETELL_WEBHOOK_SECRET=your_webhook_secret_here
```

### Internal API Authentication

**Bearer Token Authentication:**

```http
Authorization: Bearer {token}
```

**Laravel Sanctum:**

```php
// Generate token
$token = $user->createToken('api-token')->plainTextToken;

// Usage
Route::middleware('auth:sanctum')->group(function () {
    // Protected routes
});
```

---

## Retell Function Call API

### Endpoint

```http
POST /api/webhooks/retell/function-call
```

**Handler:** `RetellFunctionCallHandler::handleFunctionCall()`

**Location:** `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`

### Request Format

```json
{
  "function_name": "string",
  "call_id": "string",
  "agent_id": "string",
  "parameters": {
    // Function-specific parameters
  }
}
```

### Supported Functions

#### 1. cancel_appointment

**Purpose:** Cancel an existing appointment with policy enforcement

**Parameters:**

```json
{
  "function_name": "cancel_appointment",
  "call_id": "call_abc123",
  "parameters": {
    "appointment_date": "2025-10-05",  // YYYY-MM-DD
    "reason": "Zeitlicher Konflikt"     // Optional
  }
}
```

**Success Response (200):**

```json
{
  "success": true,
  "status": "cancelled",
  "message": "Ihr Termin wurde erfolgreich storniert.",
  "appointment": {
    "id": 123,
    "starts_at": "2025-10-05T09:00:00Z",
    "service_name": "Beratungsgespräch",
    "cancelled_at": "2025-10-02T10:30:00Z"
  },
  "fee": 0.0,
  "modification_id": 456
}
```

**Policy Violation Response (200):**

```json
{
  "success": false,
  "error": "policy_violation",
  "message": "Cancellation requires 24 hours notice. Only 12 hours remain.",
  "details": {
    "hours_notice": 12,
    "required_hours": 24,
    "fee_if_forced": 10.0
  }
}
```

**Flow:**

```php
// 1. Load call context (company_id, branch_id, customer_id)
$context = $this->getCallContext($callId);

// 2. Find appointment by date + customer
$appointment = Appointment::where('customer_id', $context['customer_id'])
    ->whereDate('starts_at', $params['appointment_date'])
    ->whereIn('status', ['pending', 'confirmed'])
    ->firstOrFail();

// 3. Check policy
$policyResult = $this->policyEngine->canCancel($appointment);

if (!$policyResult->allowed) {
    return response()->json([
        'success' => false,
        'error' => 'policy_violation',
        'message' => $policyResult->reason,
        'details' => $policyResult->details
    ]);
}

// 4. Cancel appointment
$appointment->update([
    'status' => 'cancelled',
    'cancelled_at' => now()
]);

// 5. Track modification
AppointmentModification::create([
    'appointment_id' => $appointment->id,
    'customer_id' => $appointment->customer_id,
    'modification_type' => 'cancel',
    'within_policy' => true,
    'fee_charged' => $policyResult->fee,
    'reason' => $params['reason'] ?? null
]);

// 6. Return success
return response()->json([
    'success' => true,
    'status' => 'cancelled',
    'fee' => $policyResult->fee
]);
```

#### 2. reschedule_appointment

**Purpose:** Change appointment time with policy enforcement

**Parameters:**

```json
{
  "function_name": "reschedule_appointment",
  "call_id": "call_def456",
  "parameters": {
    "appointment_date": "2025-10-05",
    "new_date": "2025-10-08",
    "new_time": "14:00",
    "reason": "Besserer Zeitpunkt"
  }
}
```

**Success Response (200):**

```json
{
  "success": true,
  "status": "rescheduled",
  "message": "Ihr Termin wurde erfolgreich verschoben.",
  "appointment": {
    "id": 123,
    "old_time": "2025-10-05T09:00:00Z",
    "new_time": "2025-10-08T14:00:00Z",
    "service_name": "Beratungsgespräch"
  },
  "fee": 0.0,
  "modification_id": 457
}
```

**No Availability Response (200):**

```json
{
  "success": false,
  "error": "no_availability",
  "message": "Der gewünschte Termin ist nicht verfügbar.",
  "alternatives": [
    {
      "date": "2025-10-08",
      "time": "15:00",
      "available": true
    },
    {
      "date": "2025-10-09",
      "time": "09:00",
      "available": true
    }
  ]
}
```

**Flow:**

```php
// 1. Find existing appointment
$appointment = $this->findAppointment($params, $callId);

// 2. Check reschedule policy
$policyResult = $this->policyEngine->canReschedule($appointment);

if (!$policyResult->allowed) {
    return $this->responseFormatter->error($policyResult->reason);
}

// 3. Check new time availability
$newDateTime = Carbon::parse("{$params['new_date']} {$params['new_time']}");
$available = $this->checkAvailability($appointment->service, $newDateTime);

if (!$available) {
    // Find alternatives
    $alternatives = $this->finder->findInTimeWindow(
        $appointment->service,
        $newDateTime,
        $newDateTime->copy()->addWeek()
    );

    return response()->json([
        'success' => false,
        'error' => 'no_availability',
        'alternatives' => $alternatives
    ]);
}

// 4. Update appointment
$oldTime = $appointment->starts_at;
$appointment->update([
    'starts_at' => $newDateTime,
    'ends_at' => $newDateTime->copy()->addMinutes($appointment->duration)
]);

// 5. Track modification
AppointmentModification::create([
    'appointment_id' => $appointment->id,
    'customer_id' => $appointment->customer_id,
    'modification_type' => 'reschedule',
    'within_policy' => true,
    'fee_charged' => $policyResult->fee,
    'original_start_time' => $oldTime,
    'new_start_time' => $newDateTime
]);

return $this->responseFormatter->success([
    'status' => 'rescheduled',
    'old_time' => $oldTime,
    'new_time' => $newDateTime,
    'fee' => $policyResult->fee
]);
```

#### 3. request_callback

**Purpose:** Create callback request when immediate booking isn't possible

**Parameters:**

```json
{
  "function_name": "request_callback",
  "call_id": "call_ghi789",
  "parameters": {
    "customer_name": "Max Mustermann",
    "phone": "+49123456789",
    "preferred_time": "morgens",        // Optional: "morgens", "mittags", "nachmittags"
    "priority": "high",                 // Optional: "normal", "high", "urgent"
    "service_id": 123,                  // Optional
    "notes": "Bitte auf Deutsch zurückrufen"  // Optional
  }
}
```

**Success Response (200):**

```json
{
  "success": true,
  "status": "callback_requested",
  "message": "Wir rufen Sie innerhalb von 4 Stunden zurück.",
  "callback": {
    "id": 789,
    "phone_number": "+49123456789",
    "customer_name": "Max Mustermann",
    "priority": "high",
    "status": "assigned",
    "assigned_to": "Hans Müller",
    "expires_at": "2025-10-02T14:30:00Z",
    "estimated_callback_time": "innerhalb von 4 Stunden"
  }
}
```

**Flow:**

```php
// 1. Extract call context
$context = $this->getCallContext($callId);

// 2. Parse preferred time window
$timeWindow = $this->parsePreferredTime($params['preferred_time'] ?? null);

// 3. Create callback request
$callback = $this->callbackService->createRequest([
    'customer_name' => $params['customer_name'],
    'phone_number' => $params['phone'],
    'branch_id' => $context['branch_id'],
    'service_id' => $params['service_id'] ?? null,
    'priority' => $params['priority'] ?? 'normal',
    'preferred_time_window' => $timeWindow,
    'notes' => $params['notes'] ?? null,
    'metadata' => [
        'call_id' => $callId,
        'created_via' => 'retell_ai'
    ]
]);

// Auto-assignment happens in createRequest() for high/urgent priority

// 4. Return success with SLA info
$estimatedTime = $this->getEstimatedCallbackTime($callback->priority);

return response()->json([
    'success' => true,
    'status' => 'callback_requested',
    'message' => "Wir rufen Sie {$estimatedTime} zurück.",
    'callback' => [
        'id' => $callback->id,
        'priority' => $callback->priority,
        'status' => $callback->status,
        'assigned_to' => $callback->assignedTo->name ?? null,
        'expires_at' => $callback->expires_at->toIso8601String()
    ]
]);
```

**Preferred Time Window Mapping:**

```php
private function parsePreferredTime(?string $preference): ?array
{
    $today = Carbon::today();

    return match($preference) {
        'morgens' => [
            'start' => $today->copy()->setHour(8),
            'end' => $today->copy()->setHour(12)
        ],
        'mittags' => [
            'start' => $today->copy()->setHour(12),
            'end' => $today->copy()->setHour(14)
        ],
        'nachmittags' => [
            'start' => $today->copy()->setHour(14),
            'end' => $today->copy()->setHour(18)
        ],
        default => null
    };
}
```

#### 4. find_next_available

**Purpose:** Find the next available appointment slot for a service

**Parameters:**

```json
{
  "function_name": "find_next_available",
  "call_id": "call_jkl012",
  "parameters": {
    "service_id": 123,
    "after_date": "2025-10-02",  // Optional, defaults to now
    "search_days": 14             // Optional, defaults to 14
  }
}
```

**Success Response (200):**

```json
{
  "success": true,
  "next_available": {
    "date": "2025-10-05",
    "time": "09:00",
    "datetime": "2025-10-05T09:00:00Z",
    "day_of_week": "Freitag"
  },
  "service": {
    "id": 123,
    "name": "Beratungsgespräch",
    "duration": 60
  }
}
```

**No Availability Response (200):**

```json
{
  "success": false,
  "error": "no_availability",
  "message": "Keine freien Termine in den nächsten 14 Tagen gefunden.",
  "searched_period": {
    "from": "2025-10-02",
    "to": "2025-10-16",
    "days": 14
  }
}
```

**Flow:**

```php
// 1. Load call context
$context = $this->getCallContext($callId);

// 2. Get service (with branch validation)
$service = Service::where('id', $params['service_id'])
    ->where('company_id', $context['company_id'])
    ->where('branch_id', $context['branch_id'])
    ->firstOrFail();

// 3. Parse search parameters
$after = isset($params['after_date'])
    ? Carbon::parse($params['after_date'])
    : Carbon::now();
$searchDays = $params['search_days'] ?? 14;

// 4. Find next available slot (with caching)
$nextSlot = $this->finder->findNextAvailable($service, $after, $searchDays);

if (!$nextSlot) {
    return response()->json([
        'success' => false,
        'error' => 'no_availability',
        'message' => "Keine freien Termine in den nächsten {$searchDays} Tagen gefunden.",
        'searched_period' => [
            'from' => $after->format('Y-m-d'),
            'to' => $after->copy()->addDays($searchDays)->format('Y-m-d'),
            'days' => $searchDays
        ]
    ]);
}

// 5. Format response
return response()->json([
    'success' => true,
    'next_available' => [
        'date' => $nextSlot->format('Y-m-d'),
        'time' => $nextSlot->format('H:i'),
        'datetime' => $nextSlot->toIso8601String(),
        'day_of_week' => $nextSlot->translatedFormat('l')  // German day name
    ],
    'service' => [
        'id' => $service->id,
        'name' => $service->name,
        'duration' => $service->duration
    ]
]);
```

#### 5. check_availability

**Purpose:** Check if a specific date/time is available

**Parameters:**

```json
{
  "function_name": "check_availability",
  "call_id": "call_mno345",
  "parameters": {
    "date": "2025-10-05",
    "time": "14:00",
    "service_id": 123,
    "duration": 60  // Optional, defaults to service duration
  }
}
```

**Available Response (200):**

```json
{
  "success": true,
  "available": true,
  "message": "Ja, 14:00 Uhr ist noch frei.",
  "requested_time": "2025-10-05T14:00:00Z",
  "alternatives": []
}
```

**Not Available with Alternatives Response (200):**

```json
{
  "success": true,
  "available": false,
  "message": "Dieser Termin ist leider nicht verfügbar. Alternativen: Freitag 15:00 Uhr oder Montag 09:00 Uhr.",
  "requested_time": "2025-10-05T14:00:00Z",
  "alternatives": [
    {
      "date": "2025-10-05",
      "time": "15:00",
      "datetime": "2025-10-05T15:00:00Z",
      "day_of_week": "Freitag"
    },
    {
      "date": "2025-10-08",
      "time": "09:00",
      "datetime": "2025-10-08T09:00:00Z",
      "day_of_week": "Montag"
    }
  ]
}
```

**Flow:**

```php
// 1. Parse requested datetime
$requestedDate = Carbon::parse("{$params['date']} {$params['time']}");

// 2. Check exact availability (1-hour window)
$startTime = $requestedDate->copy()->startOfHour();
$endTime = $requestedDate->copy()->endOfHour();

$response = $this->calcomClient->getAvailableSlots(
    $service->calcom_event_type_id,
    $startTime,
    $endTime
);

$slots = $response->json()['data']['slots'] ?? [];
$isAvailable = in_array($requestedDate->toIso8601String(), $slots);

if ($isAvailable) {
    return response()->json([
        'success' => true,
        'available' => true,
        'message' => "Ja, {$requestedDate->format('H:i')} Uhr ist noch frei.",
        'requested_time' => $requestedDate->toIso8601String()
    ]);
}

// 3. Find alternatives automatically
$alternatives = $this->finder->findInTimeWindow(
    $service,
    $requestedDate->copy()->startOfDay(),
    $requestedDate->copy()->addWeek()
);

// 4. Format alternatives for natural speech
$alternativeText = $this->formatAlternativesForSpeech($alternatives->take(3));

return response()->json([
    'success' => true,
    'available' => false,
    'message' => "Dieser Termin ist leider nicht verfügbar. {$alternativeText}",
    'requested_time' => $requestedDate->toIso8601String(),
    'alternatives' => $this->formatAlternativesArray($alternatives->take(3))
]);
```

#### 6. book_appointment

**Purpose:** Create a new appointment booking

**Parameters:**

```json
{
  "function_name": "book_appointment",
  "call_id": "call_pqr678",
  "parameters": {
    "date": "2025-10-05",
    "time": "14:00",
    "service_id": 123,
    "customer_name": "Max Mustermann",
    "phone": "+49123456789",
    "email": "max@example.com",  // Optional
    "notes": "Ersttermin"         // Optional
  }
}
```

**Success Response (200):**

```json
{
  "success": true,
  "status": "booked",
  "message": "Ihr Termin wurde erfolgreich gebucht.",
  "appointment": {
    "id": 123,
    "starts_at": "2025-10-05T14:00:00Z",
    "ends_at": "2025-10-05T15:00:00Z",
    "service_name": "Beratungsgespräch",
    "staff_name": "Hans Müller",
    "calcom_booking_uid": "cal_abc123def"
  },
  "customer": {
    "id": 456,
    "name": "Max Mustermann",
    "phone": "+49123456789"
  }
}
```

---

## Service Layer API

### AppointmentPolicyEngine

**Location:** `/var/www/api-gateway/app/Services/Policies/AppointmentPolicyEngine.php`

#### canCancel()

**Purpose:** Check if appointment can be cancelled based on policy

**Signature:**

```php
public function canCancel(Appointment $appointment, ?Carbon $now = null): PolicyResult
```

**Usage:**

```php
$policyEngine = app(AppointmentPolicyEngine::class);
$result = $policyEngine->canCancel($appointment);

if ($result->allowed) {
    echo "Can cancel. Fee: €{$result->fee}";
    // Details: hours_notice, required_hours, policy
} else {
    echo "Cannot cancel: {$result->reason}";
    // Details: hours_notice, required_hours, fee_if_forced
}
```

**PolicyResult Object:**

```php
class PolicyResult
{
    public readonly bool $allowed;
    public readonly ?string $reason;
    public readonly float $fee;
    public readonly array $details;
}
```

#### canReschedule()

**Purpose:** Check if appointment can be rescheduled based on policy

**Signature:**

```php
public function canReschedule(Appointment $appointment, ?Carbon $now = null): PolicyResult
```

**Usage:**

```php
$result = $policyEngine->canReschedule($appointment);

if (!$result->allowed) {
    Log::warning('Reschedule denied', [
        'appointment_id' => $appointment->id,
        'reason' => $result->reason,
        'details' => $result->details
    ]);
}
```

#### calculateFee()

**Purpose:** Calculate modification fee based on notice period

**Signature:**

```php
public function calculateFee(
    Appointment $appointment,
    string $modificationType,  // 'cancellation' or 'reschedule'
    ?float $hoursNotice = null
): float
```

**Usage:**

```php
// Calculate fee for cancellation with 13 hours notice
$fee = $policyEngine->calculateFee($appointment, 'cancellation', 13.0);
// Returns: 10.0 (based on 24-48h tier in default policy)

// Calculate without specifying hours (uses current time)
$fee = $policyEngine->calculateFee($appointment, 'reschedule');
```

**Fee Calculation Logic:**

```php
// 1. Check for fixed fee in policy
if (isset($policy['fee'])) {
    return (float) $policy['fee'];
}

// 2. Check for tiered fees
if (isset($policy['fee_tiers'])) {
    // Example: [
    //   ['min_hours' => 48, 'fee' => 0],
    //   ['min_hours' => 24, 'fee' => 10],
    //   ['min_hours' => 0, 'fee' => 25]
    // ]
    // 13 hours notice → 10€ fee (24-48h tier)
    return $this->calculateTieredFee($hoursNotice, $policy['fee_tiers']);
}

// 3. Check for percentage-based fee
if (isset($policy['fee_percentage']) && $appointment->price) {
    $percentage = (float) $policy['fee_percentage'];
    return round(($appointment->price * $percentage) / 100, 2);
}

// 4. Default tiers (no policy)
return $this->calculateTieredFee($hoursNotice, [
    ['min_hours' => 48, 'fee' => 0.0],
    ['min_hours' => 24, 'fee' => 10.0],
    ['min_hours' => 0, 'fee' => 15.0]
]);
```

#### getRemainingModifications()

**Purpose:** Get customer's remaining quota for cancellations/reschedules

**Signature:**

```php
public function getRemainingModifications(Customer $customer, string $type): int
```

**Usage:**

```php
$remainingCancellations = $policyEngine->getRemainingModifications($customer, 'cancel');
// Returns: 2 (if max is 3 and 1 used this month)

$remainingReschedules = $policyEngine->getRemainingModifications($customer, 'reschedule');
// Returns: PHP_INT_MAX (if no limit configured)
```

### SmartAppointmentFinder

**Location:** `/var/www/api-gateway/app/Services/Appointments/SmartAppointmentFinder.php`

#### findNextAvailable()

**Purpose:** Find next available appointment slot with intelligent caching

**Signature:**

```php
public function findNextAvailable(
    Service $service,
    ?Carbon $after = null,
    int $searchDays = 14
): ?Carbon
```

**Usage:**

```php
$finder = new SmartAppointmentFinder($company);

// Find next slot from now
$nextSlot = $finder->findNextAvailable($service);
// Returns: Carbon instance or null

// Find next slot after specific date
$afterDate = Carbon::parse('2025-10-05');
$nextSlot = $finder->findNextAvailable($service, $afterDate, 30);
// Searches 30 days from 2025-10-05
```

**Caching:**

```php
// Cache key format
'appointment_finder:next_available:service_123:start_2025-10-02-09-00:end_14'

// TTL: 45 seconds (based on Cal.com research)
// Cache hit rate: ~85% in production
```

#### findInTimeWindow()

**Purpose:** Find all available slots in a specific time range

**Signature:**

```php
public function findInTimeWindow(
    Service $service,
    Carbon $start,
    Carbon $end
): Collection
```

**Usage:**

```php
// Find all slots next week
$start = Carbon::parse('2025-10-07 00:00:00');
$end = Carbon::parse('2025-10-13 23:59:59');

$slots = $finder->findInTimeWindow($service, $start, $end);
// Returns: Collection of Carbon instances

// Example: Display slots
foreach ($slots as $slot) {
    echo $slot->format('l, d.m.Y H:i') . "\n";
    // Output: Montag, 07.10.2025 09:00
}
```

**Exception Handling:**

```php
try {
    $slots = $finder->findInTimeWindow($service, $start, $end);
} catch (\InvalidArgumentException $e) {
    // Thrown if end <= start
    echo "Invalid time window: {$e->getMessage()}";
}
```

#### clearCache()

**Purpose:** Invalidate cached availability for a service

**Signature:**

```php
public function clearCache(Service $service): void
```

**Usage:**

```php
// After creating/cancelling appointment
$appointment = Appointment::create([...]);
$finder->clearCache($appointment->service);

// Observer pattern (automatic)
class AppointmentObserver
{
    public function created(Appointment $appointment)
    {
        $finder = new SmartAppointmentFinder();
        $finder->clearCache($appointment->service);
    }
}
```

### CallbackManagementService

**Location:** `/var/www/api-gateway/app/Services/Appointments/CallbackManagementService.php`

#### createRequest()

**Purpose:** Create callback request with automatic assignment

**Signature:**

```php
public function createRequest(array $data): CallbackRequest
```

**Required Fields:**

```php
[
    'customer_name' => 'Max Mustermann',
    'phone_number' => '+49123456789',
    'branch_id' => 'uuid-string',
]
```

**Optional Fields:**

```php
[
    'customer_id' => 123,  // Link to existing customer
    'service_id' => 456,   // Requested service
    'staff_id' => 'uuid',  // Preferred staff
    'priority' => 'high',  // normal/high/urgent
    'preferred_time_window' => ['start' => '...', 'end' => '...'],
    'notes' => 'Customer notes',
    'metadata' => ['call_id' => 'retell_123']
]
```

**Usage:**

```php
$callbackService = app(CallbackManagementService::class);

$callback = $callbackService->createRequest([
    'customer_name' => 'Max Mustermann',
    'phone_number' => '+49123456789',
    'branch_id' => $branch->id,
    'priority' => CallbackRequest::PRIORITY_HIGH,
    'metadata' => [
        'call_id' => $callId,
        'source' => 'retell_ai'
    ]
]);

// Auto-assignment happens automatically for high/urgent priority
echo "Assigned to: {$callback->assignedTo->name}";
echo "Expires at: {$callback->expires_at->diffForHumans()}";
```

#### escalate()

**Purpose:** Escalate overdue callback to different staff member

**Signature:**

```php
public function escalate(CallbackRequest $request, string $reason): CallbackEscalation
```

**Usage:**

```php
// Detect overdue callbacks
$overdueCallbacks = CallbackRequest::overdue()
    ->where('branch_id', $branch->id)
    ->get();

foreach ($overdueCallbacks as $callback) {
    $escalation = $callbackService->escalate($callback, 'sla_breach');

    Log::warning('Callback escalated', [
        'callback_id' => $callback->id,
        'from' => $escalation->escalatedFrom->name,
        'to' => $escalation->escalatedTo->name,
        'reason' => $escalation->escalation_reason
    ]);
}
```

**Escalation Reasons:**

```php
'sla_breach'          // Exceeded expires_at deadline
'customer_request'    // Customer asked for escalation
'staff_unavailable'   // Original staff no longer available
'quality_concern'     // Quality issue with original assignment
'manual'              // Manual escalation by manager
```

---

## Response Formats

### Success Response Structure

```json
{
  "success": true,
  "status": "string",
  "message": "string",
  "data": {
    // Function-specific data
  }
}
```

### Error Response Structure

```json
{
  "success": false,
  "error": "error_code",
  "message": "Human-readable error message",
  "details": {
    // Additional error context
  }
}
```

### Error Codes

| Code | Description | HTTP Status | Retryable |
|------|-------------|-------------|-----------|
| `policy_violation` | Policy rule prevents operation | 200 | No |
| `no_availability` | No slots available | 200 | Yes (later) |
| `appointment_not_found` | Appointment doesn't exist | 404 | No |
| `invalid_parameters` | Missing/invalid parameters | 400 | No |
| `authentication_failed` | Invalid signature/token | 401 | No |
| `rate_limit_exceeded` | Too many requests | 429 | Yes (backoff) |
| `service_unavailable` | External service down | 503 | Yes (retry) |
| `internal_error` | Unexpected server error | 500 | Yes (retry) |

---

## Error Handling

### Client-Side Error Handling

```javascript
// Retell AI function call error handling
async function handleRetellResponse(response) {
    const data = await response.json();

    if (!data.success) {
        switch (data.error) {
            case 'policy_violation':
                return `Leider ist das nicht möglich: ${data.message}`;

            case 'no_availability':
                const alternatives = formatAlternatives(data.alternatives);
                return `Dieser Termin ist nicht verfügbar. ${alternatives}`;

            case 'appointment_not_found':
                return 'Ich konnte Ihren Termin nicht finden. Bitte nennen Sie mir das Datum.';

            default:
                return 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.';
        }
    }

    return formatSuccessMessage(data);
}
```

### Server-Side Error Handling

```php
// Controller error handling pattern
try {
    $result = $this->policyEngine->canCancel($appointment);

    if (!$result->allowed) {
        return response()->json([
            'success' => false,
            'error' => 'policy_violation',
            'message' => $result->reason,
            'details' => $result->details
        ], 200);  // Still 200 for controlled business logic errors
    }

    // Process cancellation...

} catch (ModelNotFoundException $e) {
    return response()->json([
        'success' => false,
        'error' => 'appointment_not_found',
        'message' => 'Termin wurde nicht gefunden.'
    ], 404);

} catch (\Exception $e) {
    Log::error('Cancellation failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    return response()->json([
        'success' => false,
        'error' => 'internal_error',
        'message' => 'Ein interner Fehler ist aufgetreten.'
    ], 500);
}
```

### Logging Standards

```php
// Success logging
Log::info('✅ Appointment cancelled', [
    'appointment_id' => $appointment->id,
    'customer_id' => $appointment->customer_id,
    'fee_charged' => $fee,
    'within_policy' => true
]);

// Warning logging (policy violation)
Log::warning('⚠️ Cancellation blocked by policy', [
    'appointment_id' => $appointment->id,
    'reason' => $result->reason,
    'hours_notice' => $result->details['hours_notice'],
    'required_hours' => $result->details['required_hours']
]);

// Error logging
Log::error('❌ Failed to cancel appointment', [
    'appointment_id' => $appointment->id,
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString()
]);
```

---

## Rate Limiting

### Cal.com API Rate Limiting

**Implementation:** `/var/www/api-gateway/app/Services/CalcomApiRateLimiter.php`

**Strategy:** Adaptive exponential backoff based on response headers

```php
class CalcomApiRateLimiter
{
    private int $maxRequestsPerMinute = 60;
    private int $requestCount = 0;
    private Carbon $windowStart;

    public function canMakeRequest(): bool
    {
        $this->resetWindowIfNeeded();

        return $this->requestCount < $this->maxRequestsPerMinute;
    }

    public function waitForAvailability(): void
    {
        $elapsed = now()->diffInSeconds($this->windowStart);
        $remaining = 60 - $elapsed;

        if ($remaining > 0) {
            sleep($remaining);
            $this->resetWindow();
        }
    }

    public function incrementRequestCount(): void
    {
        $this->requestCount++;
    }
}
```

**Header-Based Adaptation:**

```php
// In SmartAppointmentFinder::adaptToRateLimitHeaders()

$remaining = $headers['X-RateLimit-Remaining'][0] ?? null;

if ($remaining !== null && (int)$remaining < 10) {
    Log::warning('⚠️ Cal.com rate limit approaching', [
        'remaining' => $remaining
    ]);

    // Exponential backoff for remaining < 5
    if ((int)$remaining < 5) {
        $backoffSeconds = pow(2, 5 - (int)$remaining);
        // remaining=4 → 2^1=2s
        // remaining=3 → 2^2=4s
        // remaining=2 → 2^3=8s
        // remaining=1 → 2^4=16s
        sleep($backoffSeconds);
    }
}

// Handle 429 Too Many Requests
if ($response->status() === 429) {
    $retryAfter = $headers['Retry-After'][0] ?? 60;
    sleep((int)$retryAfter);
}
```

### Retell Webhook Rate Limiting

**No rate limiting applied** - Retell webhooks are trusted and time-sensitive.

**Concurrency Handling:**
- Async processing for heavy operations
- Database transactions for data integrity
- Queue-based notification delivery

---

## Examples

### Complete Cancellation Flow

```php
// 1. Retell webhook received
POST /api/webhooks/retell/function-call
{
  "function_name": "cancel_appointment",
  "call_id": "call_test_001",
  "parameters": {
    "appointment_date": "2025-10-05",
    "reason": "Zeitlicher Konflikt"
  }
}

// 2. Handler processes request
class RetellFunctionCallHandler
{
    public function handleCancellationAttempt($params, $callId)
    {
        // Get call context
        $context = $this->getCallContext($callId);

        // Find appointment
        $appointment = Appointment::where('customer_id', $context['customer_id'])
            ->whereDate('starts_at', $params['appointment_date'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->firstOrFail();

        // Check policy
        $result = $this->policyEngine->canCancel($appointment);

        if (!$result->allowed) {
            return response()->json([
                'success' => false,
                'error' => 'policy_violation',
                'message' => $result->reason,
                'details' => $result->details
            ]);
        }

        // Cancel appointment
        DB::transaction(function () use ($appointment, $result, $params) {
            $appointment->update([
                'status' => 'cancelled',
                'cancelled_at' => now()
            ]);

            AppointmentModification::create([
                'appointment_id' => $appointment->id,
                'customer_id' => $appointment->customer_id,
                'modification_type' => 'cancel',
                'within_policy' => true,
                'fee_charged' => $result->fee,
                'reason' => $params['reason'] ?? null
            ]);
        });

        return response()->json([
            'success' => true,
            'status' => 'cancelled',
            'message' => 'Ihr Termin wurde erfolgreich storniert.',
            'fee' => $result->fee
        ]);
    }
}
```

### Callback Escalation Workflow

```php
// Background job: CheckOverdueCallbacks

class CheckOverdueCallbacks
{
    public function handle()
    {
        $overdueCallbacks = CallbackRequest::overdue()
            ->with(['assignedTo', 'branch'])
            ->get();

        foreach ($overdueCallbacks as $callback) {
            $this->handleOverdueCallback($callback);
        }
    }

    private function handleOverdueCallback(CallbackRequest $callback)
    {
        Log::warning('Overdue callback detected', [
            'callback_id' => $callback->id,
            'customer' => $callback->customer_name,
            'assigned_to' => $callback->assignedTo->name ?? 'Unassigned',
            'expired_at' => $callback->expires_at->toIso8601String(),
            'overdue_by' => $callback->expires_at->diffForHumans(null, true)
        ]);

        // Escalate
        $callbackService = app(CallbackManagementService::class);
        $escalation = $callbackService->escalate($callback, 'sla_breach');

        // Notify new assignee
        Notification::send($escalation->escalatedTo, new CallbackEscalatedNotification($callback));

        // Update callback status if needed
        if ($callback->status === CallbackRequest::STATUS_PENDING) {
            $callback->markContacted();  // Move to contacted to prevent re-escalation
        }
    }
}
```

### Policy Configuration Example

```php
// Create company-wide cancellation policy

use App\Services\Policies\PolicyConfigurationService;

$policyService = app(PolicyConfigurationService::class);

$policy = $policyService->setPolicy(
    $company,
    'cancellation',
    [
        'hours_before' => 24,
        'max_cancellations_per_month' => 3,
        'fee_tiers' => [
            ['min_hours' => 48, 'fee' => 0],    // >48h: free
            ['min_hours' => 24, 'fee' => 10],   // 24-48h: 10€
            ['min_hours' => 12, 'fee' => 20],   // 12-24h: 20€
            ['min_hours' => 0, 'fee' => 30]     // <12h: 30€
        ]
    ],
    false  // is_override = false (not overriding parent)
);

// Create branch-specific override (more lenient)

$branchPolicy = $policyService->setPolicy(
    $branch,
    'cancellation',
    [
        'hours_before' => 12,  // Only 12 hours required
        'max_cancellations_per_month' => 5,  // More quota
        'fee_tiers' => [
            ['min_hours' => 24, 'fee' => 0],
            ['min_hours' => 0, 'fee' => 15]  // Flat 15€ for <24h
        ]
    ],
    true  // is_override = true (overrides company policy)
);

// Test policy resolution
$appointment = Appointment::where('branch_id', $branch->id)->first();
$result = $policyEngine->canCancel($appointment);

// Uses branch policy (12h requirement, not 24h)
```

---

## Appendix: Quick Reference

### Common HTTP Status Codes

```
200 OK                 - Success (including business logic errors)
400 Bad Request        - Invalid parameters
401 Unauthorized       - Authentication failed
404 Not Found          - Resource doesn't exist
429 Too Many Requests  - Rate limit exceeded
500 Internal Error     - Unexpected server error
503 Service Unavailable - External service down
```

### Policy Types

```php
PolicyConfiguration::POLICY_TYPE_CANCELLATION = 'cancellation'
PolicyConfiguration::POLICY_TYPE_RESCHEDULE = 'reschedule'
PolicyConfiguration::POLICY_TYPE_RECURRING = 'recurring'
```

### Callback Priorities

```php
CallbackRequest::PRIORITY_NORMAL = 'normal'   // 24h SLA
CallbackRequest::PRIORITY_HIGH = 'high'       // 4h SLA
CallbackRequest::PRIORITY_URGENT = 'urgent'   // 2h SLA
```

### Callback Statuses

```php
CallbackRequest::STATUS_PENDING = 'pending'       // Created, not assigned
CallbackRequest::STATUS_ASSIGNED = 'assigned'     // Assigned to staff
CallbackRequest::STATUS_CONTACTED = 'contacted'   // Customer contacted
CallbackRequest::STATUS_COMPLETED = 'completed'   // Successfully handled
CallbackRequest::STATUS_EXPIRED = 'expired'       // Exceeded SLA
CallbackRequest::STATUS_CANCELLED = 'cancelled'   // Cancelled by customer
```

### Appointment Statuses

```php
'pending'     // Created, awaiting confirmation
'confirmed'   // Confirmed by customer/system
'completed'   // Appointment finished
'cancelled'   // Cancelled by customer/staff
'no_show'     // Customer didn't show up
```

---

## Support

**Documentation Issues:** Open an issue in the repository
**API Questions:** Contact the development team
**Production Incidents:** Check `/var/www/api-gateway/storage/logs/laravel.log`
