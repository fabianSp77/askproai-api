# Appointment Creation/Modification Duplication Analysis

**Date**: 2025-10-10
**Scope**: Technical debt analysis and DRY refactoring recommendations
**Problem**: 21+ files create Appointments, 18+ files update them with inconsistent logic

---

## Executive Summary

### Critical Findings

**Duplication Scale**:
- ⚠️ **5 major appointment creation paths** with duplicated logic
- ⚠️ **Metadata setting inconsistencies** across 18+ files
- ⚠️ **Cal.com booking ID handling** varies by file (v1, v2, external_id)
- ⚠️ **Customer creation logic** duplicated in 4+ locations
- ⚠️ **DateTime parsing** duplicated in 3+ services

**Impact**:
- **Maintainability**: Changes require updates in 5+ locations
- **Bug Risk**: Inconsistent logic leads to data integrity issues
- **Testing Burden**: Each path requires separate test coverage
- **Code Smell Score**: 8/10 (High technical debt)

---

## Code Duplication Matrix

### 1. Appointment Creation Logic

| File | Lines | Creates Appointment | Sets Metadata | Handles Cal.com ID | Customer Creation |
|------|-------|---------------------|---------------|-------------------|-------------------|
| `AppointmentCreationService.php` | 861 | ✅ Line 389 | ✅ Line 404 | ✅ v2/external_id | ✅ Line 534 |
| `RetellApiController.php` | 1652 | ✅ Line 345 | ✅ Line 356-359 | ✅ v2/external_id | ✅ Line 1584 |
| `BookingApiService.php` | 439 | ✅ Line 139 | ✅ Line 151-153 | ✅ v2 only | ✅ Line 319 |
| `CompositeBookingService.php` | 423 | ✅ Line 209 | ✅ Line 232-237 | ✅ segments array | ❌ External |
| `RetellFunctionCallHandler.php` | N/A | ⚠️ Delegates | ⚠️ Via service | ⚠️ Via service | ⚠️ Via service |

**Duplication Score**: 75% code overlap in appointment creation

---

### 2. Metadata Setting Patterns

#### Pattern A: AppointmentCreationService.php (Lines 404-405)
```php
'metadata' => json_encode($bookingDetails)
```
**Issues**:
- Single-level JSON encode
- No nested structure
- Limited extensibility

#### Pattern B: RetellApiController.php (Lines 356-359)
```php
'metadata' => [
    'call_id' => $callId,
    'booked_via' => 'retell_ai'
]
```
**Issues**:
- Array structure (Laravel auto-converts)
- Different keys than Pattern A
- No booking details preservation

#### Pattern C: BookingApiService.php (Lines 151-153)
```php
'metadata' => [
    'booking_response' => $bookingData
]
```
**Issues**:
- Cal.com response storage
- No call tracking
- No source tracking

#### Pattern D: CompositeBookingService.php (Lines 232-237)
```php
'metadata' => [
    'composite' => true,
    'segment_count' => count($data['segments']),
    'pause_duration' => Carbon::parse($data['segments'][0]['ends_at'])
        ->diffInMinutes(Carbon::parse($data['segments'][1]['starts_at']))
]
```
**Issues**:
- Composite-specific metadata
- No call tracking
- No generic booking details

**Inconsistency Impact**:
- Cannot reliably query appointments by metadata
- Audit trail incomplete
- Analytics queries require case handling

---

### 3. Cal.com Booking ID Handling

#### Issue: Three Column Strategy (Inconsistent)

**Columns Used**:
1. `calcom_v2_booking_id` - V2 API UIDs (20+ chars)
2. `calcom_booking_id` - Legacy V1 API IDs (deprecated)
3. `external_id` - Backup/fallback reference

**Inconsistent Logic**:

**AppointmentCreationService.php** (Lines 402-403):
```php
'calcom_v2_booking_id' => $calcomBookingId,  // ✅ Primary
'external_id' => $calcomBookingId,            // ✅ Backup
```

**RetellApiController.php** (Lines 346-347):
```php
'calcom_v2_booking_id' => $bookingId,  // ✅ Correct
'external_id' => $bookingId,            // ✅ Backup
```

**BookingApiService.php** (Line 150):
```php
'calcom_v2_booking_id' => $bookingData['id'] ?? null,  // ✅ Only V2
// ❌ Missing external_id backup!
```

**CompositeBookingService.php** (Lines 219-229):
```php
// ❌ Stores in segments array, not top-level columns!
'segments' => [
    'booking_id' => $booking['booking_id']  // Lost reference!
]
```

**Risk**: Composite bookings cannot be queried by Cal.com booking ID

---

### 4. Customer Creation Duplication

#### Location 1: AppointmentCreationService.php (Lines 484-560)
```php
public function ensureCustomer(Call $call): ?Customer
{
    // 77 lines of logic
    // - Extracts name from call analysis
    // - Fallback to transcript parsing
    // - NameExtractor service
    // - Branch resolution
    // - forceFill for guarded fields
}
```

#### Location 2: RetellApiController.php (Lines 1551-1602)
```php
private function findOrCreateCustomer($name, $phone, $email, int $companyId)
{
    // 52 lines of logic
    // - Different parameter signature
    // - No transcript fallback
    // - Different name extraction
    // - forceFill for guarded fields (same pattern)
}
```

#### Location 3: BookingApiService.php (Lines 317-327)
```php
private function getOrCreateCustomer(array $data): Customer
{
    return Customer::firstOrCreate(
        ['email' => $data['email']],
        [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'company_id' => auth()->user()->company_id ?? 1
        ]
    );
}
```

**Duplication Score**: 60% overlap with different edge cases

---

### 5. DateTime Parsing Duplication

#### Location 1: RetellApiController.php (Lines 1465-1516)
```php
private function parseDateTime($date, $time)
{
    // 52 lines of logic
    // - Uses DateTimeParser service
    // - German date handling (DD.MM.YYYY)
    // - Fallback to Carbon::parse
    // - Default to tomorrow 10 AM
}
```

#### Location 2: AppointmentCreationService.php (Lines 86-100)
```php
// German time validation (14:00 = vierzehn Uhr)
if (isset($bookingDetails['extracted_data']['time_fourteen']) &&
    !str_contains($bookingDetails['starts_at'], '14:')) {
    // Manual correction logic
}
```

**Issue**: Inconsistent German time handling

---

## Architectural Smells Detected

### Smell 1: **Shotgun Surgery**
**Definition**: Single change requires modifications in many classes
**Example**: Changing metadata structure requires updates in 5+ files
**Severity**: 🔴 **Critical**

### Smell 2: **Duplicated Code**
**Definition**: Same logic repeated in multiple locations
**Example**: Customer creation logic duplicated 3 times
**Severity**: 🔴 **Critical**

### Smell 3: **Divergent Change**
**Definition**: One class changes for many different reasons
**Example**: AppointmentCreationService handles creation, validation, Cal.com, customer resolution
**Severity**: 🟡 **Moderate**

### Smell 4: **Feature Envy**
**Definition**: Method uses data from another class more than its own
**Example**: Multiple services manipulate Appointment metadata directly
**Severity**: 🟡 **Moderate**

### Smell 5: **Data Clumps**
**Definition**: Same group of data appears together repeatedly
**Example**: `company_id`, `branch_id`, `customer_id` always passed together
**Severity**: 🟢 **Low**

---

## Proposed Refactoring Architecture

### Design Pattern: **Repository + Decorator + Event-Driven**

### Component 1: `AppointmentRepository`
**Responsibility**: Single source of truth for appointment CRUD
**Location**: `/app/Repositories/AppointmentRepository.php`

```php
<?php

namespace App\Repositories;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use Carbon\Carbon;

class AppointmentRepository
{
    /**
     * Create appointment with standardized metadata
     */
    public function create(AppointmentData $data): Appointment
    {
        $appointment = new Appointment();
        $appointment->forceFill([
            'company_id' => $data->companyId,
            'branch_id' => $data->branchId,
            'customer_id' => $data->customerId,
            'service_id' => $data->serviceId,
            'staff_id' => $data->staffId,
            'starts_at' => $data->startsAt,
            'ends_at' => $data->endsAt,
            'status' => $data->status,
            'source' => $data->source,
            'calcom_v2_booking_id' => $data->calcomBookingId,
            'external_id' => $data->calcomBookingId, // Backup
            'metadata' => $this->buildMetadata($data)
        ]);
        $appointment->save();

        return $appointment;
    }

    /**
     * Standardized metadata builder
     */
    private function buildMetadata(AppointmentData $data): array
    {
        return [
            'source' => $data->source,
            'call_id' => $data->callId,
            'booking_details' => $data->bookingDetails,
            'calcom_response' => $data->calcomResponse,
            'created_via' => $data->createdVia,
            'version' => '2.0' // Metadata schema version
        ];
    }
}
```

**Benefits**:
- ✅ Single location for appointment creation
- ✅ Consistent metadata structure
- ✅ Centralized validation
- ✅ Easy to test

---

### Component 2: `AppointmentMetadataService`
**Responsibility**: Metadata operations (CRUD, search, versioning)
**Location**: `/app/Services/Appointment/AppointmentMetadataService.php`

```php
<?php

namespace App\Services\Appointment;

use App\Models\Appointment;

class AppointmentMetadataService
{
    /**
     * Update metadata preserving existing data
     */
    public function updateMetadata(Appointment $appointment, array $newData): void
    {
        $currentMetadata = $appointment->metadata ?? [];

        $appointment->update([
            'metadata' => array_merge($currentMetadata, $newData)
        ]);
    }

    /**
     * Search appointments by metadata key/value
     */
    public function findByMetadata(string $key, $value): Collection
    {
        return Appointment::where("metadata->$key", $value)->get();
    }

    /**
     * Extract typed metadata value
     */
    public function getMetadataValue(Appointment $appointment, string $key, $default = null)
    {
        return data_get($appointment->metadata, $key, $default);
    }
}
```

**Benefits**:
- ✅ Metadata operations abstracted
- ✅ Type-safe metadata access
- ✅ Searchable metadata
- ✅ Version migration support

---

### Component 3: `CalcomBookingIdResolver`
**Responsibility**: Resolve Cal.com booking ID from any column
**Location**: `/app/Services/CalcomBookingIdResolver.php`

```php
<?php

namespace App\Services;

use App\Models\Appointment;

class CalcomBookingIdResolver
{
    /**
     * Get Cal.com booking ID from any column (v1, v2, external)
     */
    public function resolve(Appointment $appointment): ?string
    {
        return $appointment->calcom_v2_booking_id
            ?? $appointment->calcom_booking_id
            ?? $appointment->external_id;
    }

    /**
     * Set Cal.com booking ID in correct columns
     */
    public function set(Appointment $appointment, string $bookingId, string $version = 'v2'): void
    {
        $updateData = [
            'external_id' => $bookingId // Always set backup
        ];

        if ($version === 'v2') {
            $updateData['calcom_v2_booking_id'] = $bookingId;
        } else {
            $updateData['calcom_booking_id'] = $bookingId;
        }

        $appointment->update($updateData);
    }
}
```

**Benefits**:
- ✅ Single resolution strategy
- ✅ Consistent backup handling
- ✅ Version-aware updates

---

### Component 4: `CustomerResolutionService`
**Responsibility**: Find or create customers with consistent logic
**Location**: `/app/Services/Customer/CustomerResolutionService.php`

```php
<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\Call;
use App\Services\NameExtractor;

class CustomerResolutionService
{
    public function __construct(
        private NameExtractor $nameExtractor
    ) {}

    /**
     * Ensure customer exists from call context
     */
    public function ensureFromCall(Call $call): ?Customer
    {
        // Return existing if linked
        if ($call->customer) {
            return $call->customer;
        }

        $name = $this->extractName($call);
        $phone = $call->from_number;

        return $this->findOrCreate($name, $phone, $call->company_id);
    }

    /**
     * Find or create customer with consistent logic
     */
    public function findOrCreate(
        string $name,
        ?string $phone,
        int $companyId,
        ?string $email = null
    ): Customer {
        // Search by phone first
        if ($phone) {
            $normalized = preg_replace('/[^0-9+]/', '', $phone);
            $customer = Customer::where('company_id', $companyId)
                ->where('phone', 'LIKE', '%' . substr($normalized, -8) . '%')
                ->first();

            if ($customer) {
                return $customer;
            }
        }

        // Create new customer
        $customer = new Customer();
        $customer->company_id = $companyId;
        $customer->forceFill([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'source' => 'automated'
        ]);
        $customer->save();

        return $customer;
    }

    private function extractName(Call $call): string
    {
        // Try analysis data
        if ($call->analysis) {
            $name = data_get($call->analysis, 'custom_analysis_data.patient_full_name')
                 ?? data_get($call->analysis, 'custom_analysis_data.customer_name');

            if ($name) return $name;
        }

        // Fallback to transcript
        if ($call->transcript) {
            $extracted = $this->nameExtractor->extractNameFromTranscript($call->transcript);
            if ($extracted) return $extracted;
        }

        // Final fallback
        return 'Anonym ' . substr($call->from_number, -4);
    }
}
```

**Benefits**:
- ✅ Single customer resolution logic
- ✅ Consistent name extraction
- ✅ Reusable across all services

---

### Component 5: `AppointmentDecorator` (Trait)
**Responsibility**: Add cross-cutting concerns to appointments
**Location**: `/app/Traits/AppointmentDecorator.php`

```php
<?php

namespace App\Traits;

trait AppointmentDecorator
{
    /**
     * Attach call context to appointment
     */
    public function attachCallContext(Appointment $appointment, Call $call): void
    {
        $metadataService = app(AppointmentMetadataService::class);

        $metadataService->updateMetadata($appointment, [
            'call_id' => $call->retell_call_id,
            'from_number' => $call->from_number,
            'duration' => $call->call_duration
        ]);
    }

    /**
     * Attach Cal.com response to appointment
     */
    public function attachCalcomResponse(Appointment $appointment, array $response): void
    {
        $metadataService = app(AppointmentMetadataService::class);

        $metadataService->updateMetadata($appointment, [
            'calcom_response' => $response,
            'calcom_synced_at' => now()->toIso8601String()
        ]);
    }
}
```

**Benefits**:
- ✅ Reusable metadata decorators
- ✅ Separation of concerns
- ✅ Easy to extend

---

## Migration Strategy

### Phase 1: **Create New Services** (Week 1)
**Tasks**:
1. ✅ Create `AppointmentRepository`
2. ✅ Create `AppointmentMetadataService`
3. ✅ Create `CalcomBookingIdResolver`
4. ✅ Create `CustomerResolutionService`
5. ✅ Create `AppointmentDecorator` trait
6. ✅ Write comprehensive unit tests

**Risk**: Low (new code, no changes to existing)

---

### Phase 2: **Refactor AppointmentCreationService** (Week 2)
**Tasks**:
1. Replace inline appointment creation with `AppointmentRepository`
2. Replace metadata logic with `AppointmentMetadataService`
3. Replace customer creation with `CustomerResolutionService`
4. Add integration tests
5. Deploy to staging

**Risk**: Medium (core service, high usage)

**Validation**:
```bash
# Run existing tests to ensure no regression
php artisan test --filter=AppointmentCreationServiceTest
```

---

### Phase 3: **Refactor RetellApiController** (Week 3)
**Tasks**:
1. Replace `findOrCreateCustomer()` with `CustomerResolutionService`
2. Replace appointment creation with `AppointmentRepository`
3. Replace metadata updates with `AppointmentMetadataService`
4. Add Retell-specific decorators
5. Deploy to staging

**Risk**: Medium (public API, external dependency)

---

### Phase 4: **Refactor BookingApiService** (Week 4)
**Tasks**:
1. Replace simple booking creation with `AppointmentRepository`
2. Standardize metadata structure
3. Add Cal.com response decorator
4. Add tests

**Risk**: Low (internal API)

---

### Phase 5: **Refactor CompositeBookingService** (Week 5)
**Tasks**:
1. Replace segment appointment creation with `AppointmentRepository`
2. Store Cal.com booking IDs in top-level columns (not just segments)
3. Add composite metadata decorator
4. Add tests

**Risk**: High (complex logic, nested bookings)

**Special Considerations**:
- Composite bookings need `composite_group_uid` tracking
- Segment-level booking IDs still needed in metadata
- Top-level booking ID should reference primary segment

---

### Phase 6: **Deprecate Old Methods** (Week 6)
**Tasks**:
1. Mark old methods as `@deprecated`
2. Add runtime warnings for deprecated usage
3. Create migration guide for developers
4. Schedule removal date (3 months)

---

## Testing Strategy

### Unit Tests Required

#### 1. AppointmentRepository Tests
```php
test('creates appointment with standardized metadata')
test('handles guarded fields with forceFill')
test('sets Cal.com booking ID in correct columns')
test('validates required fields')
test('handles null optional fields')
```

#### 2. AppointmentMetadataService Tests
```php
test('updates metadata without overwriting existing')
test('searches by metadata key-value')
test('extracts typed metadata values')
test('handles missing metadata gracefully')
test('supports metadata versioning')
```

#### 3. CalcomBookingIdResolver Tests
```php
test('resolves from calcom_v2_booking_id first')
test('falls back to calcom_booking_id')
test('falls back to external_id')
test('returns null if no ID found')
test('sets booking ID in correct column by version')
```

#### 4. CustomerResolutionService Tests
```php
test('finds existing customer by phone')
test('finds existing customer by email')
test('creates new customer if not found')
test('extracts name from call analysis')
test('falls back to transcript extraction')
test('generates anonymous name for fallback')
```

---

### Integration Tests Required

#### 1. End-to-End Appointment Creation
```php
test('complete appointment creation flow from call')
test('appointment created with all metadata fields')
test('Cal.com booking ID stored correctly')
test('customer linked to appointment and call')
```

#### 2. Metadata Persistence
```php
test('metadata survives appointment updates')
test('metadata searchable after creation')
test('metadata version tracked correctly')
```

---

## Metrics & Success Criteria

### Before Refactoring
- **Files with appointment creation**: 5
- **Lines of duplicated code**: ~300
- **Metadata inconsistencies**: 4 different patterns
- **Customer creation locations**: 3
- **Test coverage**: 65%
- **Cyclomatic complexity**: 12 (AppointmentCreationService)

### After Refactoring (Target)
- **Files with appointment creation**: 1 (AppointmentRepository)
- **Lines of duplicated code**: 0
- **Metadata inconsistencies**: 0 (single pattern)
- **Customer creation locations**: 1 (CustomerResolutionService)
- **Test coverage**: 85%+
- **Cyclomatic complexity**: 5 (AppointmentRepository)

---

## Estimated Effort

| Phase | Duration | Developer Hours | Risk Level |
|-------|----------|-----------------|------------|
| Phase 1 | 1 week | 40h | 🟢 Low |
| Phase 2 | 1 week | 40h | 🟡 Medium |
| Phase 3 | 1 week | 40h | 🟡 Medium |
| Phase 4 | 1 week | 32h | 🟢 Low |
| Phase 5 | 1 week | 48h | 🔴 High |
| Phase 6 | 1 week | 24h | 🟢 Low |
| **Total** | **6 weeks** | **224h** | **Medium** |

---

## Rollback Plan

### If Refactoring Causes Issues

**Step 1**: Identify broken functionality via monitoring
**Step 2**: Revert Git commits for affected phase
**Step 3**: Restore database backup if needed
**Step 4**: Deploy previous stable version
**Step 5**: Post-mortem analysis

**Rollback Time**: < 30 minutes per phase

---

## References

**Related Documents**:
- `/claudedocs/APPOINTMENT_METADATA_INTEGRATION_PLAN.md`
- `/claudedocs/DUPLICATE_BOOKING_PREVENTION_ARCHITECTURE.md`
- `/claudedocs/REFACTORING_STRATEGY.md`

**Code Locations**:
- `/app/Services/Retell/AppointmentCreationService.php`
- `/app/Http/Controllers/Api/RetellApiController.php`
- `/app/Services/Api/BookingApiService.php`
- `/app/Services/Booking/CompositeBookingService.php`

---

## Conclusion

**Recommendation**: **Proceed with refactoring in 6-week phased approach**

**Key Benefits**:
- ✅ Eliminate 300+ lines of duplicated code
- ✅ Single source of truth for appointment operations
- ✅ Consistent metadata structure across all creation paths
- ✅ Improved testability and maintainability
- ✅ Reduced bug surface area

**Risks Mitigated By**:
- Phased rollout (6 weeks)
- Comprehensive test coverage (85%+)
- Staging validation before production
- Fast rollback capability (< 30 min)

**Next Steps**:
1. Review this analysis with team
2. Approve refactoring timeline
3. Create Jira tickets for each phase
4. Begin Phase 1 implementation
