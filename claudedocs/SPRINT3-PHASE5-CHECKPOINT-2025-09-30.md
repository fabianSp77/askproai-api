# Sprint 3 Phase 5: AppointmentCreationService - IN PROGRESS
**Date**: 2025-09-30 (Checkpoint)
**Priority**: HIGH
**Status**: 🔄 IN PROGRESS - Interface designed, Service implementation next

---

## Checkpoint Status

### Completed Steps
- ✅ Phase 5.1: Analysis completed
- ✅ Phase 5.2: Interface designed (13 methods, 177 lines)
- ⏳ Phase 5.3: Service implementation - NEXT STEP
- ⏸️ Phase 5.4: Tests - pending
- ⏸️ Phase 5.5: Integration - pending
- ⏸️ Phase 5.6: Documentation - pending

---

## Analysis Summary

### Scope Definition

**IN SCOPE (Phase 5)**:
- Core appointment creation logic (~330 lines)
- createAppointmentFromCallWithAlternatives() (267 lines)
- createLocalAppointmentRecord() (29 lines)
- createCalcomBookingWithAlternatives() (32 lines)
- determineServiceType() (16 lines)
- notifyCustomerAboutAlternative() (16 lines)

**OUT OF SCOPE (Future Phases)**:
- extractBookingDetailsFromTranscript() (354 lines) → Phase 6: BookingDetailsExtractor
- extractBookingDetailsFromRetellData() (72 lines) → Phase 6: BookingDetailsExtractor
- Call analysis and insights → Phase 7: CallAnalysisService

**ALREADY REFACTORED**:
- storeFailedBookingRequest() → Uses CallLifecycleService.trackFailedBooking()

### Methods Identified in RetellWebhookController

**Core Creation Methods**:
1. **createAppointmentFromCallWithAlternatives()** (lines 1294-1560)
   - 267 lines - MAIN METHOD
   - Orchestrates entire appointment creation flow
   - Handles: confidence validation, customer creation, service finding, Cal.com booking, alternatives, nested bookings

2. **createLocalAppointmentRecord()** (lines 1621-1649)
   - 29 lines
   - Creates Appointment model in database
   - Simple wrapper around Appointment::create()

3. **createCalcomBookingWithAlternatives()** (lines 1585-1616)
   - 32 lines
   - Handles nested booking appointments
   - Returns existing appointment from NestedBookingManager

4. **determineServiceType()** (lines 1565-1580)
   - 16 lines
   - Maps service name to nested booking type
   - Returns: 'coloring', 'perm', 'highlights', 'general'

5. **notifyCustomerAboutAlternative()** (lines 1668-1683)
   - 16 lines
   - TODO stub for customer notifications

**Usage Location**:
- Called in handleCallAnalyzed() (line 821)
- After booking details extraction from transcript/Retell data

---

## External Services Integration

### AppointmentAlternativeFinder
**Location**: `/app/Services/AppointmentAlternativeFinder.php`

**Purpose**: Find alternative appointment slots when desired time unavailable

**Key Methods**:
- `findAlternatives(Carbon $desiredDateTime, int $durationMinutes, int $eventTypeId): array`

**Strategies**:
1. Same day, different time
2. Next workday, same time
3. Next week, same day
4. Next available workday

**Config**:
- max_alternatives: 2
- time_window_hours: 2
- business_hours: 09:00 - 18:00

### NestedBookingManager
**Location**: `/app/Services/NestedBookingManager.php`

**Purpose**: Handle services with interruption periods (coloring, perm, highlights)

**Key Methods**:
- `createNestedBooking(array $bookingData, string $serviceType, Carbon $startTime): array`
- `supportsNesting(string $serviceType): bool`

**Supported Service Types**:
- **coloring**: 120min total (45min work, 45min break, 30min finish)
- **perm**: 150min total (60min work, 60min break, 30min finish)
- **highlights**: 180min total (90min work, 60min break, 30min finish)

### CalcomService
**Location**: `/app/Services/CalcomService.php`

**Purpose**: Integration with Cal.com booking API

**Key Methods**:
- `createBooking(array $bookingData): Response`
- Returns HTTP response with booking ID

---

## Interface Design (AppointmentCreationInterface.php)

### Created: `/app/Services/Retell/AppointmentCreationInterface.php` (177 lines)

**13 Methods Defined**:

#### 1. Core Orchestration
```php
public function createFromCall(Call $call, array $bookingDetails): ?Appointment;
public function createDirect(
    Customer $customer,
    Service $service,
    \Carbon\Carbon $startTime,
    int $durationMinutes,
    ?Call $call = null,
    bool $searchAlternatives = true
): ?Appointment;
```

#### 2. Database Operations
```php
public function createLocalRecord(
    Customer $customer,
    Service $service,
    array $bookingDetails,
    ?string $calcomBookingId = null,
    ?Call $call = null
): Appointment;
```

#### 3. Customer & Service Resolution
```php
public function ensureCustomer(Call $call): ?Customer;
public function findService(array $bookingDetails, int $companyId, ?int $branchId = null): ?Service;
```

#### 4. Cal.com Integration
```php
public function bookInCalcom(
    Customer $customer,
    Service $service,
    \Carbon\Carbon $startTime,
    int $durationMinutes,
    ?Call $call = null
): ?array;
```

#### 5. Alternative Search & Booking
```php
public function findAlternatives(
    \Carbon\Carbon $desiredTime,
    int $durationMinutes,
    int $eventTypeId
): array;

public function bookAlternative(
    array $alternatives,
    Customer $customer,
    Service $service,
    int $durationMinutes,
    Call $call,
    array &$bookingDetails
): ?array;
```

#### 6. Nested Booking Support
```php
public function createNestedBooking(
    array $bookingData,
    Service $service,
    Customer $customer,
    Call $call
): ?Appointment;

public function supportsNesting(string $serviceType): bool;
public function determineServiceType(string $serviceName): string;
```

#### 7. Validation & Notifications
```php
public function validateConfidence(array $bookingDetails): bool;
public function notifyCustomerAboutAlternative(
    Customer $customer,
    \Carbon\Carbon $requestedTime,
    \Carbon\Carbon $bookedTime,
    array $alternative
): void;
```

---

## Implementation Plan

### Next Step: Create AppointmentCreationService.php

**Dependencies to Inject**:
- CallLifecycleService (for failed booking tracking)
- ServiceSelectionService (for service finding)
- AppointmentAlternativeFinder (for alternative search)
- NestedBookingManager (for nested bookings)
- CalcomService (for Cal.com API)

**Structure**:
```php
class AppointmentCreationService implements AppointmentCreationInterface
{
    private CallLifecycleService $callLifecycle;
    private ServiceSelectionService $serviceSelector;
    private AppointmentAlternativeFinder $alternativeFinder;
    private NestedBookingManager $nestedBookingManager;
    private CalcomService $calcomService;

    // Configuration
    private const MIN_CONFIDENCE = 60;
    private const DEFAULT_DURATION = 45;

    public function __construct(
        CallLifecycleService $callLifecycle,
        ServiceSelectionService $serviceSelector,
        AppointmentAlternativeFinder $alternativeFinder,
        NestedBookingManager $nestedBookingManager,
        CalcomService $calcomService
    ) {
        // ...
    }

    // Implement 13 interface methods
}
```

**Method Implementation Order**:
1. ✅ Constructor and dependencies
2. Validation: validateConfidence()
3. Customer: ensureCustomer()
4. Service: findService(), determineServiceType()
5. Database: createLocalRecord()
6. Cal.com: bookInCalcom()
7. Alternatives: findAlternatives(), bookAlternative()
8. Nested: createNestedBooking(), supportsNesting()
9. Notifications: notifyCustomerAboutAlternative()
10. Direct: createDirect()
11. Main: createFromCall() - orchestrates all above

---

## Key Logic to Preserve

### Customer Creation Logic (from lines 1328-1399)
```php
// Get or create customer (handle anonymous calls)
$customer = $call->customer;
if (!$customer) {
    // Extract name from analysis
    $customerName = null;
    $customerPhone = $call->from_number;

    if ($call->analysis && isset($call->analysis['custom_analysis_data'])) {
        $customData = $call->analysis['custom_analysis_data'];
        $customerName = $customData['patient_full_name'] ??
                       $customData['customer_name'] ??
                       $customData['extracted_info']['customer_name'] ?? null;
    }

    // Fallback to transcript parsing
    if (!$customerName && $call->transcript) {
        $nameExtractor = new NameExtractor();
        $extractedName = $nameExtractor->extractNameFromTranscript($call->transcript);
        $customerName = $extractedName ?: 'Anonym ' . substr($customerPhone, -4);
    }

    // Find or create customer
    $customer = Customer::where('phone', $customerPhone)
        ->where('company_id', $call->company_id)
        ->first();

    if (!$customer) {
        // Get default branch
        $defaultBranch = Branch::where('company_id', $call->company_id)->first();

        $customer = Customer::create([
            'name' => $customerName,
            'phone' => $customerPhone,
            'company_id' => $call->company_id,
            'branch_id' => $call->branch_id ?? ($defaultBranch ? $defaultBranch->id : null),
            'source' => 'phone_anonymous',
            'status' => 'active',
            'notes' => 'Automatisch erstellt aus Telefonanruf'
        ]);
    }

    // Link customer to call
    $call = $this->callLifecycle->linkCustomer($call, $customer);
}
```

### Service Finding Logic (from lines 1401-1423)
```php
// Find a service from the company's team using ServiceSelectionService
$companyId = $call->company_id ?? $customer->company_id ?? 15;
$branchId = $call->branch_id ?? $customer->branch_id ?? null;

$service = $this->serviceSelector->findService(
    $bookingDetails['service'] ?? 'General Service',
    $companyId,
    $branchId
);

if (!$service) {
    Log::error('No service found for booking', [
        'service_name' => $bookingDetails['service'] ?? 'unknown',
        'company_id' => $companyId,
        'branch_id' => $branchId
    ]);
    $this->storeFailedBookingRequest($call, $bookingDetails, 'service_not_found');
    return null;
}
```

### Nested Booking Flow (from lines 1425-1452)
```php
$desiredTime = \Carbon\Carbon::parse($bookingDetails['starts_at']);
$serviceType = $this->determineServiceType($service->name);
$duration = $bookingDetails['duration_minutes'] ?? 45;

// Check if service supports nested booking
if ($this->nestedBookingManager->supportsNesting($serviceType)) {
    $nestedBooking = $this->nestedBookingManager->createNestedBooking(
        [
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'phone' => $customer->phone
        ],
        $serviceType,
        $desiredTime
    );

    if ($nestedBooking['main_booking']) {
        return $this->createCalcomBookingWithAlternatives(
            $nestedBooking['main_booking'],
            $service,
            $customer,
            $call
        );
    }
}
```

### Primary Booking Attempt (from lines 1456-1484)
```php
$bookingData = [
    'eventTypeId' => $service->calcom_event_type_id,
    'startTime' => $desiredTime->toIso8601String(),
    'endTime' => $desiredTime->copy()->addMinutes($duration)->toIso8601String(),
    'name' => $customer->name,
    'email' => $customer->email,
    'phone' => $customer->phone ?? $call->from_number ?? '+491234567890',
    'timeZone' => 'Europe/Berlin',
    'language' => 'de'
];

$response = $calcomService->createBooking($bookingData);

if ($response->successful()) {
    $appointmentData = $response->json();
    $bookingId = $appointmentData['data']['id'] ?? $appointmentData['id'] ?? null;

    if ($bookingId) {
        $call = $this->callLifecycle->trackBooking($call, $bookingDetails, true, $bookingId);
    }

    return $this->createLocalAppointmentRecord($customer, $service, $bookingDetails, $bookingId, $call);
}
```

### Alternative Search Flow (from lines 1486-1540)
```php
// Search for alternatives
$alternatives = $this->alternativeFinder->findAlternatives(
    $desiredTime,
    $duration,
    $service->calcom_event_type_id
);

if (empty($alternatives)) {
    $this->storeFailedBookingRequest($call, $bookingDetails, 'no_alternatives_found');
    return null;
}

// Try to book first alternative
$alternative = $alternatives[0];
$alternativeTime = $alternative['datetime'];

$bookingData['startTime'] = $alternativeTime->toIso8601String();
$bookingData['endTime'] = $alternativeTime->copy()->addMinutes($duration)->toIso8601String();

$response = $calcomService->createBooking($bookingData);

if ($response->successful()) {
    $appointmentData = $response->json();
    $bookingId = $appointmentData['data']['id'] ?? $appointmentData['id'] ?? null;

    if ($bookingId) {
        $bookingDetails['original_request'] = $desiredTime->format('Y-m-d H:i:s');
        $bookingDetails['booked_alternative'] = $alternativeTime->format('Y-m-d H:i:s');
        $bookingDetails['alternative_type'] = $alternative['type'];

        $call = $this->callLifecycle->trackBooking($call, $bookingDetails, true, $bookingId);
        $this->notifyCustomerAboutAlternative($customer, $desiredTime, $alternativeTime, $alternative);
    }

    return $this->createLocalAppointmentRecord($customer, $service, $bookingDetails, $bookingId, $call);
}

// All alternatives failed
$this->storeFailedBookingRequest($call, $bookingDetails, 'all_alternatives_failed');
return null;
```

---

## Testing Strategy

### Unit Tests to Write (28+ tests estimated)

**Customer Management (4 tests)**:
- ✅ it_ensures_existing_customer
- ✅ it_creates_customer_from_call_analysis
- ✅ it_creates_customer_from_transcript
- ✅ it_handles_missing_customer_info

**Service Resolution (3 tests)**:
- ✅ it_finds_service_by_name
- ✅ it_respects_branch_filtering
- ✅ it_returns_null_when_service_not_found

**Validation (2 tests)**:
- ✅ it_validates_high_confidence
- ✅ it_rejects_low_confidence

**Local Record Creation (3 tests)**:
- ✅ it_creates_local_appointment_record
- ✅ it_links_call_to_appointment
- ✅ it_stores_metadata

**Cal.com Integration (4 tests)**:
- ✅ it_books_in_calcom_successfully
- ✅ it_handles_calcom_booking_failure
- ✅ it_formats_booking_data_correctly
- ✅ it_uses_correct_timezone

**Alternative Search (4 tests)**:
- ✅ it_finds_alternatives_when_time_unavailable
- ✅ it_books_alternative_successfully
- ✅ it_updates_booking_details_with_alternative
- ✅ it_returns_null_when_no_alternatives

**Nested Booking (3 tests)**:
- ✅ it_detects_nestable_services
- ✅ it_determines_service_type_correctly
- ✅ it_creates_nested_booking_structure

**Full Flow (5 tests)**:
- ✅ it_creates_appointment_from_call_successfully
- ✅ it_creates_appointment_with_alternative
- ✅ it_handles_low_confidence_gracefully
- ✅ it_tracks_failed_booking
- ✅ it_creates_direct_appointment

---

## Files to Create

1. ✅ `/app/Services/Retell/AppointmentCreationInterface.php` (177 lines) - DONE
2. ⏳ `/app/Services/Retell/AppointmentCreationService.php` (~600 lines estimated) - NEXT
3. ⏸️ `/tests/Unit/Services/Retell/AppointmentCreationServiceTest.php` (~800 lines estimated)
4. ⏸️ Update `/app/Http/Controllers/RetellWebhookController.php` (integrate service)

---

## Integration Points

### RetellWebhookController Changes Needed

**Constructor**:
```php
// ADD
use App\Services\Retell\AppointmentCreationService;

private AppointmentCreationService $appointmentCreator;

public function __construct(
    PhoneNumberResolutionService $phoneResolver,
    ServiceSelectionService $serviceSelector,
    WebhookResponseService $responseFormatter,
    CallLifecycleService $callLifecycle,
    AppointmentCreationService $appointmentCreator  // NEW
) {
    // ...
    $this->appointmentCreator = $appointmentCreator;
}
```

**Method Replacement**:
```php
// BEFORE (line 821)
$appointment = $this->createAppointmentFromCallWithAlternatives($call, $bookingDetails);

// AFTER
$appointment = $this->appointmentCreator->createFromCall($call, $bookingDetails);
```

**Methods to Remove** (after integration):
- createAppointmentFromCallWithAlternatives() (267 lines)
- createLocalAppointmentRecord() (29 lines)
- createCalcomBookingWithAlternatives() (32 lines)
- determineServiceType() (16 lines)
- notifyCustomerAboutAlternative() (16 lines)

**Total Lines Removed**: ~360 lines

---

## Continuation Instructions

**Next Step**: Implement AppointmentCreationService.php

**Implementation Order**:
1. Create service skeleton with constructor
2. Implement simple methods first (validation, determineServiceType)
3. Implement customer/service resolution
4. Implement Cal.com booking
5. Implement alternative search/booking
6. Implement nested booking
7. Implement main createFromCall() orchestration
8. Write comprehensive tests
9. Integrate into RetellWebhookController
10. Write Phase 5 completion documentation

**Key Files to Reference**:
- `/app/Http/Controllers/RetellWebhookController.php` lines 1294-1683 (original logic)
- `/app/Services/AppointmentAlternativeFinder.php` (alternative search)
- `/app/Services/NestedBookingManager.php` (nested bookings)
- `/app/Services/CalcomService.php` (Cal.com API)

**Estimated Remaining Work**:
- Service implementation: ~600 lines
- Tests: ~800 lines
- Integration: ~20 lines modified
- Documentation: ~15KB

---

**Checkpoint Created**: 2025-09-30
**Ready for Service Implementation**: YES
**Context Preserved**: 100%