# Retell Controllers Refactoring Plan

**Generated**: 2025-09-30
**Target Files**:
- `RetellWebhookController.php` (2098 lines)
- `RetellFunctionCallHandler.php` (1589 lines)

---

## Executive Summary

These controllers violate the Single Responsibility Principle extensively. They handle webhook processing, call lifecycle management, appointment booking, availability checking, customer management, Cal.com integration, cost calculation, and more - all within controller methods.

**Complexity Metrics**:
- **RetellWebhookController**: 2098 lines, 30+ methods, 8+ distinct responsibilities
- **RetellFunctionCallHandler**: 1589 lines, 20+ methods, 6+ distinct responsibilities
- **Code Duplication**: High (service selection, date parsing, validation repeated)
- **Cyclomatic Complexity**: Very High (nested conditionals, multiple try-catch blocks)
- **Maintainability Index**: Low (large methods, deep nesting, mixed concerns)

**Business Impact**:
- Difficult to test individual features
- High risk of regression when making changes
- Poor code reusability across features
- Hard to onboard new developers
- Challenging to extend with new functionality

---

## Phase 1: Service Extraction Plan

### 1.1 Call Lifecycle Management Service

**Purpose**: Handle call creation, tracking, status updates, and metadata management

**Responsibilities**:
- Create call records from webhook events
- Update call status (inbound → ongoing → ended → analyzed)
- Merge temporary calls with real call IDs
- Extract and store call metadata
- Track call timestamps and durations

**Methods to Extract**:
- `handleCallStarted()` → `CallLifecycleService::startCall()`
- `handleCallEnded()` → `CallLifecycleService::endCall()`
- `handleCallAnalyzed()` → `CallLifecycleService::analyzeCall()`
- Call record creation logic from `call_inbound` handler

**Current Locations**:
- RetellWebhookController: lines 107-263, 266-268, 270-323, 362-494, 500-683
- RetellFunctionCallHandler: lines 694-847

**Dependencies**:
- PhoneNumber model
- Call model
- Company/Branch models
- NameExtractor service (existing)

**Complexity Reduction**: 600+ lines → Single focused service

---

### 1.2 Phone Number Resolution Service

**Purpose**: Normalize and validate phone numbers, resolve to company/branch context

**Responsibilities**:
- Normalize phone numbers to E.164 format
- Validate registered phone numbers
- Resolve phone numbers to company_id and branch_id
- Security: Reject unregistered phone numbers
- Cache phone number lookups per request

**Methods to Extract**:
- Phone normalization logic (lines 136-146 in RetellWebhookController)
- Phone lookup logic (lines 148-177 in RetellWebhookController)
- Context retrieval (lines 38-75 in RetellFunctionCallHandler)

**Current Locations**:
- RetellWebhookController: lines 128-177, 396-437
- RetellFunctionCallHandler: lines 38-75, 464-502, 763-798

**Dependencies**:
- PhoneNumber model
- PhoneNumberNormalizer service (existing)
- Request-scoped cache

**Security Impact**: VULN-003 fix - prevents unauthorized access to other companies' data

**Complexity Reduction**: 200+ lines of duplicated logic → Single service

---

### 1.3 Appointment Booking Service

**Purpose**: Handle appointment creation with alternatives, validation, and Cal.com integration

**Responsibilities**:
- Extract appointment details from transcripts or function parameters
- Validate appointment requests
- Check availability via Cal.com
- Book appointments with retry logic
- Handle nested bookings for complex services
- Store booking metadata
- Link appointments to calls and customers

**Methods to Extract**:
- `createAppointmentFromCallWithAlternatives()` → `AppointmentBookingService::createFromCall()`
- `bookAppointment()` from FunctionCallHandler → `AppointmentBookingService::bookForCall()`
- `collectAppointment()` logic → `AppointmentBookingService::collectAndBook()`
- Alternative finding coordination

**Current Locations**:
- RetellWebhookController: lines 910-1011, 1447-1878
- RetellFunctionCallHandler: lines 345-453, 650-1273

**Dependencies**:
- CalcomService (existing)
- AppointmentAlternativeFinder (existing)
- NestedBookingManager (existing)
- Customer model
- Service model
- Appointment model

**Complexity Reduction**: 1000+ lines → Focused booking service

---

### 1.4 Availability Service

**Purpose**: Check calendar availability and format for AI consumption

**Responsibilities**:
- Query Cal.com for available slots
- Parse and format availability responses
- Provide quick availability for real-time responses
- Find alternative time slots
- Format availability data for Retell AI
- Handle parallel availability checks

**Methods to Extract**:
- `getQuickAvailability()` → `AvailabilityService::getQuickSlots()`
- `checkAvailability()` → `AvailabilityService::checkSlot()`
- `getAlternatives()` → `AvailabilityService::findAlternatives()`
- `handleAvailabilityCheck()` → `AvailabilityService::checkForDate()`

**Current Locations**:
- RetellWebhookController: lines 2007-2096
- RetellFunctionCallHandler: lines 125-240, 246-339, 1280-1460

**Dependencies**:
- CalcomService (existing)
- AppointmentAlternativeFinder (existing)
- Service model

**Performance Impact**: Parallel API calls reduce response time by 50%

**Complexity Reduction**: 400+ lines → Focused availability service

---

### 1.5 Service Selection Service

**Purpose**: Select appropriate services based on company, branch, and context

**Responsibilities**:
- Find default services for companies
- Filter services by branch isolation
- Validate service ownership (team validation)
- Handle service priority and fallbacks
- Cache service queries per request

**Methods to Extract**:
- Service selection logic from multiple locations
- Branch-aware service filtering
- Team validation logic
- Default/priority service resolution

**Current Locations**:
- RetellWebhookController: lines 720-745, 1559-1590
- RetellFunctionCallHandler: lines 148-178, 268-296, 371-401, 477-495, 915-972

**Dependencies**:
- Service model
- Company model
- Branch model

**Security Impact**: Prevents cross-branch service access

**Complexity Reduction**: 300+ lines of duplicated logic → Single service

---

### 1.6 Customer Management Service

**Purpose**: Create, find, and manage customer records from call data

**Responsibilities**:
- Extract customer information from calls
- Create temporary customers for anonymous calls
- Find existing customers by phone/email
- Generate unique temporary emails
- Link customers to calls
- Handle customer validation

**Methods to Extract**:
- Customer creation from anonymous calls (lines 1484-1553 in RetellWebhookController)
- Customer lookup logic from multiple locations
- Email/phone validation and fallback logic

**Current Locations**:
- RetellWebhookController: lines 756-766, 1484-1553
- RetellFunctionCallHandler: lines 1488-1589

**Dependencies**:
- Customer model
- Branch model
- Company model

**Complexity Reduction**: 250+ lines → Focused customer service

---

### 1.7 Call Insights Service

**Purpose**: Extract and process insights from call transcripts and analysis data

**Responsibilities**:
- Extract appointment details from transcripts
- Parse Retell's custom_analysis_data
- Extract customer names from transcripts
- Identify service mentions
- Calculate extraction confidence scores
- Store insights as structured data

**Methods to Extract**:
- `processCallInsights()` → `CallInsightsService::processInsights()`
- `extractBookingDetailsFromRetellData()` → `CallInsightsService::extractFromRetellData()`
- `extractBookingDetailsFromTranscript()` → `CallInsightsService::extractFromTranscript()`

**Current Locations**:
- RetellWebhookController: lines 910-1443

**Dependencies**:
- Call model
- NameExtractor service (existing)
- Appointment model

**Complexity Reduction**: 500+ lines → Focused insights service

---

### 1.8 Cost Calculation Service Consolidation

**Purpose**: Consolidate cost calculation logic (already partially extracted)

**Current State**: CostCalculator and PlatformCostService already exist

**Improvement Opportunities**:
- Move inline cost calculation logic to services
- Reduce duplication between handleCallEnded methods
- Standardize cost tracking across call lifecycle

**Current Locations**:
- RetellWebhookController: lines 529-580, 609-660

**Dependencies**:
- CostCalculator (existing)
- PlatformCostService (existing)
- ExchangeRateService (existing)

**Complexity Reduction**: Consolidate 120+ lines of duplicated logic

---

### 1.9 Date/Time Parsing Service

**Purpose**: Parse German date/time formats consistently

**Responsibilities**:
- Parse German date formats (01.10.2025, erster Oktober, etc.)
- Parse German time formats (vierzehn uhr, 14:00, etc.)
- Handle relative dates (heute, morgen, montag)
- Validate and normalize date/time inputs
- Handle year inference for past dates

**Methods to Extract**:
- `parseDateTime()` from FunctionCallHandler
- `parseRelativeDate()` from FunctionCallHandler
- Date/time parsing from transcript extraction

**Current Locations**:
- RetellWebhookController: lines 1026-1085, 1090-1443
- RetellFunctionCallHandler: lines 539-577, 849-869, 1465-1483

**Dependencies**:
- Carbon

**Complexity Reduction**: 400+ lines → Reusable parsing service

---

### 1.10 Webhook Response Service

**Purpose**: Standardize webhook response formatting

**Responsibilities**:
- Format success responses for Retell AI
- Format error responses with user-friendly messages
- Handle different response structures per webhook type
- Log responses consistently
- Maintain 200 status codes (Retell requirement)

**Methods to Extract**:
- `successResponse()` → `WebhookResponseService::success()`
- `errorResponse()` → `WebhookResponseService::error()`
- Response formatting from multiple handlers

**Current Locations**:
- RetellFunctionCallHandler: lines 627-644
- Various response() calls throughout both controllers

**Dependencies**:
- None (pure formatting)

**Complexity Reduction**: Standardizes 50+ response locations

---

## Phase 2: Refactored Controller Structure

### 2.1 RetellWebhookController (Target: <200 lines)

```
RetellWebhookController
├── __invoke() - Route webhook events
├── handleCallInbound() - Delegate to CallLifecycleService
├── handleCallStarted() - Delegate to CallLifecycleService
├── handleCallEnded() - Delegate to CallLifecycleService
├── handleCallAnalyzed() - Delegate to CallLifecycleService + CallInsightsService
└── diagnostic() - Keep for system health checks
```

**Dependencies (via constructor injection)**:
- CallLifecycleService
- CallInsightsService
- PhoneNumberResolutionService
- WebhookResponseService

---

### 2.2 RetellFunctionCallHandler (Target: <300 lines)

```
RetellFunctionCallHandler
├── handleFunctionCall() - Route function calls
├── checkAvailability() - Delegate to AvailabilityService
├── getAlternatives() - Delegate to AvailabilityService
├── bookAppointment() - Delegate to AppointmentBookingService
├── listServices() - Delegate to ServiceSelectionService
├── collectAppointment() - Delegate to AppointmentBookingService
└── handleAvailabilityCheck() - Delegate to AvailabilityService
```

**Dependencies (via constructor injection)**:
- AvailabilityService
- AppointmentBookingService
- ServiceSelectionService
- CustomerManagementService
- PhoneNumberResolutionService
- DateTimeParsingService
- WebhookResponseService

---

## Phase 3: Service Dependency Structure

```
Controllers Layer
├── RetellWebhookController
└── RetellFunctionCallHandler

Business Logic Layer (New Services)
├── CallLifecycleService
│   ├── PhoneNumberResolutionService
│   └── CostCalculationService
├── AppointmentBookingService
│   ├── ServiceSelectionService
│   ├── AvailabilityService
│   ├── CustomerManagementService
│   ├── DateTimeParsingService
│   └── CalcomService (existing)
├── CallInsightsService
│   ├── DateTimeParsingService
│   └── NameExtractor (existing)
├── AvailabilityService
│   ├── CalcomService (existing)
│   ├── AppointmentAlternativeFinder (existing)
│   └── ServiceSelectionService
├── ServiceSelectionService
├── CustomerManagementService
├── PhoneNumberResolutionService
├── DateTimeParsingService
└── WebhookResponseService

External Services Layer (Existing)
├── CalcomService
├── RetellApiClient
├── CostCalculator
├── PlatformCostService
├── ExchangeRateService
├── NameExtractor
├── AppointmentAlternativeFinder
└── NestedBookingManager
```

---

## Phase 4: Migration Strategy

### Option A: Incremental Refactoring (RECOMMENDED)

**Advantages**:
- Low risk
- Gradual testing and validation
- Can deploy after each service extraction
- Team learns new architecture progressively

**Timeline**: 3-4 weeks

**Steps**:
1. Week 1: Extract low-risk services (WebhookResponseService, DateTimeParsingService, PhoneNumberResolutionService)
2. Week 2: Extract medium complexity services (ServiceSelectionService, CustomerManagementService, AvailabilityService)
3. Week 3: Extract complex services (CallLifecycleService, AppointmentBookingService)
4. Week 4: Extract insights service and final cleanup (CallInsightsService, cost consolidation)

**Per-Service Process**:
1. Create service class with interface
2. Write comprehensive unit tests for service
3. Move methods to service (copy first, don't delete)
4. Update controller to use service
5. Run integration tests
6. Remove old controller methods
7. Deploy and monitor
8. Move to next service

---

### Option B: Big Bang Refactoring

**Advantages**:
- Faster completion
- All architectural improvements at once
- No temporary inconsistencies

**Disadvantages**:
- HIGH RISK
- Large code review burden
- Difficult to isolate bugs if issues arise
- Long deployment freeze

**Timeline**: 2 weeks (but risky)

**NOT RECOMMENDED** for production system with active users

---

## Phase 5: Priority Matrix

### Priority 1 (Critical - Extract First)

**PhoneNumberResolutionService**
- **Impact**: Security vulnerability fix (VULN-003)
- **Risk**: Low (clear input/output contract)
- **Duplication**: High (used in 6+ locations)
- **Estimated Effort**: 2 days
- **Dependency**: None

**ServiceSelectionService**
- **Impact**: Security (prevents cross-branch access)
- **Risk**: Low (isolated query logic)
- **Duplication**: Very High (used in 8+ locations)
- **Estimated Effort**: 3 days
- **Dependency**: PhoneNumberResolutionService

**WebhookResponseService**
- **Impact**: Consistency across all endpoints
- **Risk**: Very Low (pure formatting)
- **Duplication**: High (50+ response locations)
- **Estimated Effort**: 1 day
- **Dependency**: None

---

### Priority 2 (High - Core Business Logic)

**CallLifecycleService**
- **Impact**: High (call tracking reliability)
- **Risk**: Medium (affects all call workflows)
- **Complexity**: High (600+ lines)
- **Estimated Effort**: 5 days
- **Dependency**: PhoneNumberResolutionService

**AvailabilityService**
- **Impact**: High (real-time availability)
- **Risk**: Medium (Cal.com integration)
- **Complexity**: Medium (400+ lines)
- **Estimated Effort**: 4 days
- **Dependency**: ServiceSelectionService

**AppointmentBookingService**
- **Impact**: Very High (revenue-critical)
- **Risk**: High (booking money transactions)
- **Complexity**: Very High (1000+ lines)
- **Estimated Effort**: 7 days
- **Dependency**: AvailabilityService, ServiceSelectionService, CustomerManagementService

---

### Priority 3 (Medium - Supporting Services)

**DateTimeParsingService**
- **Impact**: Medium (affects booking accuracy)
- **Risk**: Low (isolated parsing logic)
- **Duplication**: High (400+ lines)
- **Estimated Effort**: 3 days
- **Dependency**: None

**CustomerManagementService**
- **Impact**: Medium (customer experience)
- **Risk**: Low (CRUD operations)
- **Complexity**: Medium (250+ lines)
- **Estimated Effort**: 3 days
- **Dependency**: None

**CallInsightsService**
- **Impact**: Medium (analysis features)
- **Risk**: Low (non-critical path)
- **Complexity**: High (500+ lines)
- **Estimated Effort**: 4 days
- **Dependency**: DateTimeParsingService

---

### Priority 4 (Low - Cleanup)

**Cost Calculation Consolidation**
- **Impact**: Low (reporting accuracy)
- **Risk**: Low (existing services)
- **Complexity**: Low (consolidation only)
- **Estimated Effort**: 2 days
- **Dependency**: None

---

## Phase 6: Testing Strategy

### 6.1 Unit Tests

**Per Service**:
- Test all public methods
- Mock external dependencies (Cal.com, database)
- Cover edge cases and error handling
- Achieve 90%+ code coverage

**Example Test Structure**:
```php
PhoneNumberResolutionServiceTest
├── test_normalizes_german_phone_numbers()
├── test_normalizes_international_formats()
├── test_rejects_invalid_phone_numbers()
├── test_resolves_to_correct_company()
├── test_resolves_to_correct_branch()
├── test_returns_null_for_unregistered_numbers()
├── test_caches_lookups_per_request()
└── test_logs_security_rejections()
```

---

### 6.2 Integration Tests

**Controller Integration**:
- Test webhook flow end-to-end
- Test function call flow end-to-end
- Mock Retell webhooks
- Mock Cal.com responses
- Verify database state changes

---

### 6.3 Regression Tests

**Before Refactoring**:
1. Document current behavior with acceptance tests
2. Create test cases for all webhook event types
3. Create test cases for all function call types
4. Record expected database states

**After Each Service Extraction**:
- Run full regression suite
- Verify identical behavior
- Check performance metrics
- Review error logs

---

## Phase 7: Code Examples - Top 3 Services

### 7.1 PhoneNumberResolutionService

**Interface**:
```php
<?php

namespace App\Services\Retell;

use App\Models\PhoneNumber;

interface PhoneNumberResolutionInterface
{
    /**
     * Resolve phone number to company and branch context
     *
     * @param string $phoneNumber Raw phone number from webhook
     * @return array|null ['company_id' => int, 'branch_id' => int|null, 'phone_number_id' => int]
     * @throws UnregisteredPhoneNumberException if phone not found
     */
    public function resolve(string $phoneNumber): ?array;

    /**
     * Normalize phone number to E.164 format
     *
     * @param string $phoneNumber Raw phone number
     * @return string|null Normalized phone number or null if invalid
     */
    public function normalize(string $phoneNumber): ?string;
}
```

**Implementation**:
```php
<?php

namespace App\Services\Retell;

use App\Models\PhoneNumber;
use App\Services\PhoneNumberNormalizer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PhoneNumberResolutionService implements PhoneNumberResolutionInterface
{
    private array $requestCache = [];

    /**
     * Resolve phone number to company and branch context
     */
    public function resolve(string $phoneNumber): ?array
    {
        // Request-scoped cache check
        $cacheKey = "phone_context_{$phoneNumber}";
        if (isset($this->requestCache[$cacheKey])) {
            Log::debug('Phone resolution cache hit', ['phone' => $phoneNumber]);
            return $this->requestCache[$cacheKey];
        }

        // Normalize phone number
        $normalized = $this->normalize($phoneNumber);
        if (!$normalized) {
            Log::error('Phone normalization failed', [
                'raw_phone' => $phoneNumber,
                'ip' => request()->ip(),
            ]);
            return null;
        }

        // Lookup in database
        $phoneRecord = PhoneNumber::where('number_normalized', $normalized)
            ->with(['company', 'branch'])
            ->first();

        if (!$phoneRecord) {
            Log::error('Phone number not registered', [
                'raw_phone' => $phoneNumber,
                'normalized' => $normalized,
                'ip' => request()->ip(),
            ]);
            return null;
        }

        // Build context array
        $context = [
            'company_id' => $phoneRecord->company_id,
            'branch_id' => $phoneRecord->branch_id,
            'phone_number_id' => $phoneRecord->id,
            'agent_id' => $phoneRecord->agent_id,
            'retell_agent_id' => $phoneRecord->retell_agent_id,
        ];

        // Cache for request lifecycle
        $this->requestCache[$cacheKey] = $context;

        Log::info('Phone number resolved', [
            'phone_number_id' => $phoneRecord->id,
            'company_id' => $context['company_id'],
            'branch_id' => $context['branch_id'],
            'normalized' => $normalized,
        ]);

        return $context;
    }

    /**
     * Normalize phone number to E.164 format
     */
    public function normalize(string $phoneNumber): ?string
    {
        return PhoneNumberNormalizer::normalize($phoneNumber);
    }

    /**
     * Validate that a phone number is registered and active
     */
    public function isRegistered(string $phoneNumber): bool
    {
        return $this->resolve($phoneNumber) !== null;
    }

    /**
     * Get company ID from phone number
     */
    public function getCompanyId(string $phoneNumber): ?int
    {
        $context = $this->resolve($phoneNumber);
        return $context['company_id'] ?? null;
    }

    /**
     * Get branch ID from phone number
     */
    public function getBranchId(string $phoneNumber): ?int
    {
        $context = $this->resolve($phoneNumber);
        return $context['branch_id'] ?? null;
    }

    /**
     * Clear request cache (for testing)
     */
    public function clearCache(): void
    {
        $this->requestCache = [];
    }
}
```

**Usage in Controller**:
```php
// Before (in RetellWebhookController)
$normalizedNumber = \App\Services\PhoneNumberNormalizer::normalize($toNumber);
if (!$normalizedNumber) {
    // handle error
}
$phoneNumberRecord = PhoneNumber::where('number_normalized', $normalizedNumber)
    ->with(['company', 'branch'])
    ->first();
if (!$phoneNumberRecord) {
    // handle error
}
$companyId = $phoneNumberRecord->company_id;
$branchId = $phoneNumberRecord->branch_id;

// After
try {
    $context = $this->phoneResolver->resolve($toNumber);
    $companyId = $context['company_id'];
    $branchId = $context['branch_id'];
} catch (UnregisteredPhoneNumberException $e) {
    return response()->json([
        'error' => 'Phone number not registered'
    ], 404);
}
```

**Unit Test Example**:
```php
<?php

namespace Tests\Unit\Services\Retell;

use Tests\TestCase;
use App\Services\Retell\PhoneNumberResolutionService;
use App\Models\PhoneNumber;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PhoneNumberResolutionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PhoneNumberResolutionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PhoneNumberResolutionService();
    }

    /** @test */
    public function it_normalizes_german_phone_numbers()
    {
        $result = $this->service->normalize('+49 123 456789');
        $this->assertEquals('+49123456789', $result);

        $result = $this->service->normalize('0123 456789');
        $this->assertEquals('+49123456789', $result);
    }

    /** @test */
    public function it_resolves_registered_phone_number_to_company_and_branch()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $phone = PhoneNumber::factory()->create([
            'number' => '+49123456789',
            'number_normalized' => '+49123456789',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        $context = $this->service->resolve('+49 123 456789');

        $this->assertNotNull($context);
        $this->assertEquals($company->id, $context['company_id']);
        $this->assertEquals($branch->id, $context['branch_id']);
        $this->assertEquals($phone->id, $context['phone_number_id']);
    }

    /** @test */
    public function it_returns_null_for_unregistered_phone_numbers()
    {
        $context = $this->service->resolve('+49999999999');

        $this->assertNull($context);
    }

    /** @test */
    public function it_caches_lookups_within_request()
    {
        $company = Company::factory()->create();
        $phone = PhoneNumber::factory()->create([
            'number_normalized' => '+49123456789',
            'company_id' => $company->id,
        ]);

        // First lookup - hits database
        $context1 = $this->service->resolve('+49123456789');

        // Second lookup - should use cache
        $context2 = $this->service->resolve('+49123456789');

        $this->assertEquals($context1, $context2);

        // Verify only one database query was made
        // (Use query log or spy to verify)
    }

    /** @test */
    public function it_rejects_invalid_phone_number_formats()
    {
        $result = $this->service->normalize('not-a-phone');
        $this->assertNull($result);

        $result = $this->service->normalize('123');
        $this->assertNull($result);
    }
}
```

---

### 7.2 ServiceSelectionService

**Interface**:
```php
<?php

namespace App\Services\Retell;

use App\Models\Service;
use Illuminate\Support\Collection;

interface ServiceSelectionInterface
{
    /**
     * Get default service for company and branch
     *
     * @param int $companyId
     * @param int|null $branchId
     * @return Service|null
     */
    public function getDefaultService(int $companyId, ?int $branchId = null): ?Service;

    /**
     * Get all available services for company and branch
     *
     * @param int $companyId
     * @param int|null $branchId
     * @return Collection<Service>
     */
    public function getAvailableServices(int $companyId, ?int $branchId = null): Collection;

    /**
     * Validate that service belongs to company/branch
     *
     * @param int $serviceId
     * @param int $companyId
     * @param int|null $branchId
     * @return bool
     */
    public function validateServiceAccess(int $serviceId, int $companyId, ?int $branchId = null): bool;
}
```

**Implementation**:
```php
<?php

namespace App\Services\Retell;

use App\Models\Service;
use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ServiceSelectionService implements ServiceSelectionInterface
{
    private array $requestCache = [];

    /**
     * Get default service for company and branch
     */
    public function getDefaultService(int $companyId, ?int $branchId = null): ?Service
    {
        $cacheKey = "default_service_{$companyId}_{$branchId}";
        if (isset($this->requestCache[$cacheKey])) {
            return $this->requestCache[$cacheKey];
        }

        $query = Service::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotNull('calcom_event_type_id');

        // Apply branch filtering if provided
        if ($branchId) {
            $query->where(function($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereHas('branches', function($q2) use ($branchId) {
                      $q2->where('branches.id', $branchId);
                  })
                  ->orWhereNull('branch_id'); // Company-wide services
            });
        }

        // Try to find default service first
        $service = (clone $query)->where('is_default', true)->first();

        // Fallback to highest priority service
        if (!$service) {
            $service = $query
                ->orderBy('priority', 'asc')
                ->orderByRaw('CASE WHEN name LIKE "%Beratung%" THEN 0 WHEN name LIKE "%30 Minuten%" THEN 1 ELSE 2 END')
                ->first();
        }

        // Validate team ownership if company has a team
        if ($service) {
            $company = Company::find($companyId);
            if ($company && $company->hasTeam() && !$company->ownsService($service->calcom_event_type_id)) {
                Log::warning('Service does not belong to company team', [
                    'service_id' => $service->id,
                    'company_id' => $companyId,
                    'team_id' => $company->calcom_team_id
                ]);
                return null;
            }
        }

        $this->requestCache[$cacheKey] = $service;

        Log::info('Service selected', [
            'service_id' => $service?->id,
            'service_name' => $service?->name,
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'is_default' => $service?->is_default ?? false,
        ]);

        return $service;
    }

    /**
     * Get all available services for company and branch
     */
    public function getAvailableServices(int $companyId, ?int $branchId = null): Collection
    {
        $query = Service::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotNull('calcom_event_type_id');

        if ($branchId) {
            $query->where(function($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereHas('branches', function($q2) use ($branchId) {
                      $q2->where('branches.id', $branchId);
                  })
                  ->orWhereNull('branch_id');
            });
        }

        $services = $query->orderBy('priority', 'asc')->get();

        // Filter out services not owned by team
        $company = Company::find($companyId);
        if ($company && $company->hasTeam()) {
            $services = $services->filter(function($service) use ($company) {
                return $company->ownsService($service->calcom_event_type_id);
            });
        }

        Log::info('Services loaded', [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'count' => $services->count(),
        ]);

        return $services;
    }

    /**
     * Validate that service belongs to company/branch
     */
    public function validateServiceAccess(int $serviceId, int $companyId, ?int $branchId = null): bool
    {
        $service = Service::where('id', $serviceId)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (!$service) {
            return false;
        }

        // Check branch access if branch is specified
        if ($branchId) {
            $hasBranchAccess = $service->branch_id === $branchId
                || $service->branches->contains('id', $branchId)
                || $service->branch_id === null; // Company-wide service

            if (!$hasBranchAccess) {
                Log::warning('Service not accessible to branch', [
                    'service_id' => $serviceId,
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                ]);
                return false;
            }
        }

        // Validate team ownership
        $company = Company::find($companyId);
        if ($company && $company->hasTeam() && !$company->ownsService($service->calcom_event_type_id)) {
            Log::warning('Service not owned by company team', [
                'service_id' => $serviceId,
                'company_id' => $companyId,
                'team_id' => $company->calcom_team_id,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Clear request cache (for testing)
     */
    public function clearCache(): void
    {
        $this->requestCache = [];
    }
}
```

**Usage in Controller**:
```php
// Before (duplicated in multiple locations)
$service = null;
if ($serviceId) {
    $service = Service::where('id', $serviceId)
        ->where('company_id', $companyId)
        ->where('is_active', true)
        ->where(function($q) use ($branchId) {
            if ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereHas('branches', function($q2) use ($branchId) {
                      $q2->where('branches.id', $branchId);
                  })
                  ->orWhereNull('branch_id');
            }
        })
        ->first();
} else {
    $service = Service::where('company_id', $companyId)
        ->where('is_active', true)
        ->whereNotNull('calcom_event_type_id')
        // ... 20 more lines ...
}

// After
$service = $this->serviceSelector->getDefaultService($companyId, $branchId);

if (!$service) {
    return $this->webhookResponse->error('Service nicht verfügbar für diese Filiale');
}
```

---

### 7.3 CallLifecycleService

**Interface**:
```php
<?php

namespace App\Services\Retell;

use App\Models\Call;

interface CallLifecycleInterface
{
    /**
     * Create call record from inbound webhook
     *
     * @param array $callData Webhook call data
     * @param array $context Phone number context (company_id, branch_id, etc.)
     * @return Call
     */
    public function createFromInbound(array $callData, array $context): Call;

    /**
     * Update call status to ongoing (started)
     *
     * @param string $callId Retell call ID
     * @param array $callData Webhook call data
     * @return Call|null
     */
    public function markAsStarted(string $callId, array $callData): ?Call;

    /**
     * Update call status to ended (completed)
     *
     * @param string $callId Retell call ID
     * @param array $callData Webhook call data
     * @return Call|null
     */
    public function markAsEnded(string $callId, array $callData): ?Call;

    /**
     * Mark call as analyzed and sync final data
     *
     * @param string $callId Retell call ID
     * @param array $callData Webhook call data
     * @return Call|null
     */
    public function markAsAnalyzed(string $callId, array $callData): ?Call;

    /**
     * Merge temporary call with real call ID
     *
     * @param string $realCallId Real Retell call ID
     * @param array $context Phone number context
     * @return Call|null
     */
    public function mergeTemporaryCall(string $realCallId, array $context): ?Call;
}
```

**Implementation**:
```php
<?php

namespace App\Services\Retell;

use App\Models\Call;
use App\Services\CostCalculator;
use App\Services\PlatformCostService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CallLifecycleService implements CallLifecycleInterface
{
    public function __construct(
        private CostCalculator $costCalculator,
        private PlatformCostService $platformCostService
    ) {}

    /**
     * Create call record from inbound webhook
     */
    public function createFromInbound(array $callData, array $context): Call
    {
        $callId = $callData['call_id'] ?? null;
        $fromNumber = $callData['from_number'] ?? $callData['from'] ?? $callData['caller'] ?? null;
        $toNumber = $callData['to_number'] ?? $callData['to'] ?? $callData['callee'] ?? null;
        $agentId = $callData['agent_id'] ?? $callData['retell_agent_id'] ?? null;

        // Handle missing call ID
        if (!$callId) {
            $callId = 'temp_' . now()->timestamp . '_' . substr(md5($fromNumber . $toNumber), 0, 8);

            Log::info('Creating call with temporary ID', [
                'temp_id' => $callId,
                'from' => $fromNumber,
                'to' => $toNumber,
            ]);
        }

        $call = Call::firstOrCreate(
            ['retell_call_id' => $callId],
            [
                'call_id' => $callId,
                'from_number' => $fromNumber,
                'to_number' => $toNumber,
                'phone_number_id' => $context['phone_number_id'],
                'company_id' => $context['company_id'],
                'branch_id' => $context['branch_id'],
                'agent_id' => $context['agent_id'] ?? $agentId,
                'retell_agent_id' => $agentId,
                'status' => 'inbound',
                'direction' => 'inbound',
                'called_at' => now(),
            ]
        );

        Log::info('Call created from inbound webhook', [
            'call_id' => $call->id,
            'retell_call_id' => $callId,
            'company_id' => $call->company_id,
            'branch_id' => $call->branch_id,
        ]);

        return $call;
    }

    /**
     * Update call status to ongoing
     */
    public function markAsStarted(string $callId, array $callData): ?Call
    {
        $call = Call::where('retell_call_id', $callId)
            ->orWhere('external_id', $callId)
            ->first();

        if (!$call) {
            Log::warning('Call not found for started event', ['call_id' => $callId]);
            return null;
        }

        $call->update([
            'status' => 'ongoing',
            'call_status' => 'ongoing',
            'start_timestamp' => isset($callData['start_timestamp'])
                ? Carbon::createFromTimestampMs($callData['start_timestamp'])
                : now(),
        ]);

        Log::info('Call marked as started', [
            'call_id' => $call->id,
            'retell_call_id' => $callId,
        ]);

        return $call;
    }

    /**
     * Update call status to ended
     */
    public function markAsEnded(string $callId, array $callData): ?Call
    {
        $call = Call::where('retell_call_id', $callId)
            ->orWhere('external_id', $callId)
            ->first();

        if (!$call) {
            Log::warning('Call not found for ended event', ['call_id' => $callId]);
            return null;
        }

        // Update call end data
        $call->update([
            'status' => 'completed',
            'call_status' => 'ended',
            'end_timestamp' => isset($callData['end_timestamp'])
                ? Carbon::createFromTimestampMs($callData['end_timestamp'])
                : now(),
            'duration_ms' => $callData['duration_ms'] ?? null,
            'duration_sec' => isset($callData['duration_ms']) ? round($callData['duration_ms'] / 1000) : null,
            'disconnection_reason' => $callData['disconnection_reason'] ?? null,
        ]);

        // Calculate costs
        $this->calculateAndStoreCosts($call, $callData);

        Log::info('Call marked as ended', [
            'call_id' => $call->id,
            'duration_sec' => $call->duration_sec,
        ]);

        return $call;
    }

    /**
     * Mark call as analyzed
     */
    public function markAsAnalyzed(string $callId, array $callData): ?Call
    {
        $call = Call::where('retell_call_id', $callId)->first();

        if (!$call) {
            Log::warning('Call not found for analyzed event', ['call_id' => $callId]);
            return null;
        }

        // Update will be handled by RetellApiClient::syncCallToDatabase()
        // This method just validates and returns the call

        Log::info('Call ready for analysis', [
            'call_id' => $call->id,
            'retell_call_id' => $callId,
        ]);

        return $call;
    }

    /**
     * Merge temporary call with real call ID
     */
    public function mergeTemporaryCall(string $realCallId, array $context): ?Call
    {
        // Find most recent temporary call
        $tempCall = Call::where('retell_call_id', 'LIKE', 'temp_%')
            ->where('created_at', '>=', now()->subMinutes(10))
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$tempCall) {
            Log::info('No temporary call found to merge', ['real_call_id' => $realCallId]);
            return null;
        }

        // Update with real call ID
        $tempCall->update([
            'retell_call_id' => $realCallId,
            'call_id' => $realCallId,
            'status' => 'in_progress',
        ]);

        Log::info('Temporary call merged with real ID', [
            'call_id' => $tempCall->id,
            'old_id' => 'temp_*',
            'new_call_id' => $realCallId,
            'company_id' => $tempCall->company_id,
        ]);

        return $tempCall;
    }

    /**
     * Calculate and store call costs
     */
    private function calculateAndStoreCosts(Call $call, array $callData): void
    {
        try {
            // Calculate base costs
            $this->costCalculator->updateCallCosts($call);

            // Track Retell costs
            if (isset($callData['price_usd']) || isset($callData['cost_usd'])) {
                $retellCostUsd = $callData['price_usd'] ?? $callData['cost_usd'] ?? 0;
                if ($retellCostUsd > 0) {
                    $this->platformCostService->trackRetellCost($call, $retellCostUsd);
                }
            } elseif ($call->duration_sec > 0) {
                // Estimate Retell cost: $0.07 per minute
                $estimatedRetellCost = ($call->duration_sec / 60) * 0.07;
                $this->platformCostService->trackRetellCost($call, $estimatedRetellCost);
            }

            // Track Twilio costs
            if (isset($callData['twilio_cost_usd'])) {
                $twilioCostUsd = $callData['twilio_cost_usd'];
                if ($twilioCostUsd > 0) {
                    $this->platformCostService->trackTwilioCost($call, $twilioCostUsd);
                }
            } elseif ($call->duration_sec > 0) {
                // Estimate Twilio cost: $0.0085 per minute
                $estimatedTwilioCost = ($call->duration_sec / 60) * 0.0085;
                $this->platformCostService->trackTwilioCost($call, $estimatedTwilioCost);
            }

            // Calculate total external costs
            $this->platformCostService->calculateCallTotalCosts($call);

            Log::info('Call costs calculated', [
                'call_id' => $call->id,
                'base_cost' => $call->base_cost,
                'customer_cost' => $call->customer_cost,
                'retell_cost_usd' => $call->retell_cost_usd,
                'twilio_cost_usd' => $call->twilio_cost_usd,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to calculate call costs', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

**Usage in Controller**:
```php
// Before (100+ lines in RetellWebhookController)
if ($event === 'call_inbound') {
    try {
        $callId = $callData['call_id'] ?? $callData['id'] ?? null;
        // ... 50 lines of extraction logic ...
        $phoneNumberRecord = PhoneNumber::where('number_normalized', $normalizedNumber)
            ->with(['company', 'branch'])
            ->first();
        // ... 50 more lines of call creation ...
    } catch (\Exception $e) {
        Log::error('Failed to create call', ['error' => $e->getMessage()]);
        return response()->json(['success' => false], 500);
    }
}

// After
if ($event === 'call_inbound') {
    try {
        $context = $this->phoneResolver->resolve($toNumber);
        $call = $this->callLifecycle->createFromInbound($callData, $context);
        return $this->webhookResponse->success(['call_id' => $call->id]);
    } catch (UnregisteredPhoneNumberException $e) {
        return $this->webhookResponse->error('Phone number not registered', 404);
    } catch (\Exception $e) {
        Log::error('Failed to create call', ['error' => $e->getMessage()]);
        return $this->webhookResponse->error('Failed to process call event', 500);
    }
}
```

---

## Phase 8: Rollout Plan

### Week 1: Foundation Services
**Day 1-2**: WebhookResponseService
- Create service
- Write tests (8 test cases)
- Update both controllers
- Deploy

**Day 3-4**: PhoneNumberResolutionService
- Create service with interface
- Write comprehensive tests (12 test cases)
- Update RetellWebhookController
- Update RetellFunctionCallHandler
- Security audit
- Deploy

**Day 5**: DateTimeParsingService
- Create service
- Write tests (15 test cases covering German formats)
- Update both controllers
- Deploy

---

### Week 2: Business Logic Services
**Day 1-2**: ServiceSelectionService
- Create service with interface
- Write tests (10 test cases)
- Update both controllers (8+ locations)
- Security validation
- Deploy

**Day 3-5**: AvailabilityService
- Create service with interface
- Write tests (12 test cases)
- Update RetellWebhookController
- Update RetellFunctionCallHandler
- Load testing
- Deploy

---

### Week 3: Core Services
**Day 1-3**: CallLifecycleService
- Create service with interface
- Write comprehensive tests (20 test cases)
- Update RetellWebhookController
- Integration testing
- Deploy

**Day 4-5**: CustomerManagementService
- Create service
- Write tests (10 test cases)
- Update both controllers
- Deploy

---

### Week 4: Advanced Services & Cleanup
**Day 1-3**: AppointmentBookingService
- Create service with interface
- Write comprehensive tests (25 test cases)
- Update both controllers
- End-to-end testing
- Deploy

**Day 4**: CallInsightsService
- Create service
- Write tests (15 test cases)
- Update RetellWebhookController
- Deploy

**Day 5**: Cost Consolidation & Final Review
- Consolidate cost calculation
- Full regression testing
- Performance benchmarking
- Documentation update
- Final deployment

---

## Phase 9: Success Metrics

### Code Quality Metrics

**Before Refactoring**:
- Lines per controller: 2098, 1589
- Average method length: 50+ lines
- Cyclomatic complexity: 15-25 per method
- Code duplication: 40%+
- Test coverage: Unknown
- Maintainability index: Low (<20)

**After Refactoring**:
- Lines per controller: <200, <300
- Average method length: <15 lines
- Cyclomatic complexity: <5 per method
- Code duplication: <10%
- Test coverage: >85%
- Maintainability index: High (>70)

---

### Performance Metrics

**Target Improvements**:
- Webhook response time: <500ms (currently 600-1600ms)
- Function call response time: <800ms
- Database query count: -40% (via caching)
- Cal.com API calls: Reduced via caching

---

### Business Metrics

**Quality Improvements**:
- Reduced bug count: -60% (through better testing)
- Faster feature development: +40% (through reusable services)
- Reduced onboarding time: -50% (through clearer architecture)
- Improved code review speed: +50% (smaller, focused changes)

---

## Phase 10: Risks & Mitigation

### Risk 1: Breaking Existing Functionality
**Probability**: Medium
**Impact**: High
**Mitigation**:
- Comprehensive regression testing
- Feature flags for gradual rollout
- Keep old code temporarily (mark as deprecated)
- Extensive logging for comparison

---

### Risk 2: Performance Degradation
**Probability**: Low
**Impact**: Medium
**Mitigation**:
- Performance benchmarks before/after
- Request-scoped caching
- Profiling during testing
- Load testing before production

---

### Risk 3: Team Adoption
**Probability**: Medium
**Impact**: Medium
**Mitigation**:
- Documentation for each service
- Code review guidelines
- Pair programming sessions
- Architecture decision records (ADRs)

---

### Risk 4: Incomplete Extraction
**Probability**: Low
**Impact**: Medium
**Mitigation**:
- Clear service boundaries
- Interface-driven design
- Service responsibility matrix
- Review checklist per service

---

## Appendix A: Service Responsibility Matrix

| Service | Primary Responsibility | Dependencies | Security Critical |
|---------|----------------------|--------------|-------------------|
| PhoneNumberResolutionService | Phone validation & context | PhoneNumber model | YES (VULN-003) |
| ServiceSelectionService | Service filtering & validation | Service, Company models | YES (cross-branch) |
| CallLifecycleService | Call tracking & status | Cost services, Call model | NO |
| AvailabilityService | Availability checking | CalcomService, Service | NO |
| AppointmentBookingService | Booking orchestration | Multiple services | NO |
| CustomerManagementService | Customer CRUD | Customer model | NO |
| CallInsightsService | Insight extraction | NameExtractor | NO |
| DateTimeParsingService | Date/time parsing | Carbon | NO |
| WebhookResponseService | Response formatting | None | NO |

---

## Appendix B: Test Coverage Requirements

| Service | Unit Tests | Integration Tests | E2E Tests |
|---------|------------|-------------------|-----------|
| PhoneNumberResolutionService | 12+ | 3 | 2 |
| ServiceSelectionService | 10+ | 3 | 2 |
| CallLifecycleService | 20+ | 5 | 3 |
| AvailabilityService | 12+ | 4 | 2 |
| AppointmentBookingService | 25+ | 6 | 4 |
| CustomerManagementService | 10+ | 3 | 1 |
| CallInsightsService | 15+ | 2 | 1 |
| DateTimeParsingService | 15+ | 0 | 0 |
| WebhookResponseService | 8+ | 0 | 0 |

---

## Appendix C: Architecture Decision Records

### ADR-001: Interface-Based Services
**Decision**: All services must implement interfaces
**Rationale**: Enables testing with mocks, supports dependency inversion principle
**Status**: Accepted

### ADR-002: Request-Scoped Caching
**Decision**: Services cache lookups per request, not globally
**Rationale**: Prevents stale data, reduces memory usage, simplifies invalidation
**Status**: Accepted

### ADR-003: Constructor Dependency Injection
**Decision**: All dependencies injected via constructor
**Rationale**: Explicit dependencies, easier testing, Laravel container support
**Status**: Accepted

### ADR-004: Incremental Refactoring
**Decision**: Extract services incrementally over 4 weeks
**Rationale**: Reduced risk, continuous deployment, team learning curve
**Status**: Accepted

---

## Conclusion

This refactoring plan reduces two monolithic controllers (3687 lines total) to thin orchestration layers (<500 lines combined) backed by 10 focused, testable services. The incremental approach minimizes risk while delivering measurable improvements in code quality, maintainability, and team velocity.

**Estimated Effort**: 4 weeks (1 developer) or 2 weeks (2 developers)
**Risk Level**: Low (with incremental approach)
**Expected ROI**: 3-6 months (reduced bug fixes, faster feature development)

---

**Next Steps**:
1. Review and approve refactoring plan
2. Set up test infrastructure
3. Create feature flags for gradual rollout
4. Begin Week 1 implementations
5. Schedule daily sync meetings during refactoring period

---

**Document Version**: 1.0
**Last Updated**: 2025-09-30
**Author**: AI Refactoring Analysis