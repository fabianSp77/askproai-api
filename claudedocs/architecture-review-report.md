# Architecture Review Report
**Date:** 2025-09-30
**System:** Multi-Tenant Laravel API Gateway
**Current Scale:** 14 phone numbers, 8 companies
**Target Scale:** 100+ companies, 500+ phone numbers

---

## Executive Summary

The API Gateway demonstrates solid multi-tenant fundamentals with webhook-driven architecture connecting Retell.ai and Cal.com. However, significant scalability challenges exist in controller complexity, service layer organization, and horizontal scaling readiness. Critical path to 100+ companies requires immediate architectural refactoring.

**Risk Level:** MODERATE-HIGH
**Scalability Readiness:** 60%
**Recommended Timeline:** 6-12 sprints for full re-architecture

---

## Current State Assessment

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    External Systems                          │
├─────────────────────────────────────────────────────────────┤
│  Retell.ai      →  Webhooks  →  Laravel API Gateway        │
│  (AI Phone)         (Events)                                 │
│                                                              │
│  Cal.com        ←  REST API  ←  Laravel API Gateway        │
│  (Calendar)         (Booking)                                │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│              Laravel API Gateway (Current)                   │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Controllers (Fat Controllers - Anti-pattern)               │
│  ├─ RetellWebhookController (2091 lines!)                  │
│  ├─ RetellFunctionCallHandler (1583 lines)                 │
│  └─ CalcomBookingController (mixed responsibilities)       │
│                                                              │
│  Services (Fragmented - 30+ services)                       │
│  ├─ CalcomService, CalcomV2Service (duplication)           │
│  ├─ RetellService, RetellV2Service, RetellV1Service        │
│  ├─ AppointmentAlternativeFinder                            │
│  ├─ NestedBookingManager                                    │
│  ├─ CostCalculator, PlatformCostService                     │
│  └─ 20+ other services (unclear boundaries)                │
│                                                              │
│  Models (Anemic Domain Model)                               │
│  ├─ Call (91 fillable fields, minimal logic)               │
│  ├─ Company (encryption logic only)                         │
│  ├─ Customer, Service, Branch, PhoneNumber                 │
│  └─ Relationships only, no domain logic                    │
│                                                              │
│  Data Layer                                                  │
│  └─ SQLite (Single DB - No Sharding Strategy)              │
└─────────────────────────────────────────────────────────────┘
```

### Technology Stack Evaluation

| Component | Technology | Assessment | Scale Readiness |
|-----------|-----------|------------|-----------------|
| Framework | Laravel 11.31 | ✅ Modern, well-maintained | High |
| Database | SQLite | ⚠️ Not production-grade | LOW |
| Queue | Database Queue | ⚠️ Single point of failure | MEDIUM |
| Cache | Database Cache | ⚠️ No distributed caching | LOW |
| Session | Database Session | ⚠️ Not horizontally scalable | LOW |
| Admin UI | Filament 3.3 | ✅ Excellent for CRUD | High |
| Phone | Twilio SDK 8.8 | ✅ Enterprise-grade | High |
| PDF | DomPDF 3.1 | ✅ Adequate | Medium |
| Permissions | Spatie Permissions 6.21 | ✅ Industry standard | High |

**Critical Issues:**
1. SQLite is not suitable for production multi-tenant systems at scale
2. Database-backed queue/cache will become bottleneck at 100+ companies
3. No Redis/Memcached for distributed caching
4. No message queue (RabbitMQ/SQS) for async processing

---

## Architecture Analysis

### 1. Controller Layer - CRITICAL ISSUES

#### RetellWebhookController.php (2091 lines)

**Violations:**
- **Single Responsibility Principle:** Handles 5+ distinct responsibilities
  - Webhook validation and logging
  - Call lifecycle management (inbound, started, ended, analyzed)
  - Appointment extraction and booking
  - Alternative appointment finding
  - Cost calculation and platform cost tracking

**Code Smells:**
```php
// Lines 106-262: call_inbound handling (156 lines in one method)
// Lines 264-494: call_started handling with availability injection
// Lines 499-683: call_ended with cost calculation
// Lines 909-1010: processCallInsights (nested logic)
// Lines 1446-1742: createAppointmentFromCallWithAlternatives (296 lines!)
```

**Impact:**
- Difficult to test individual concerns
- High cognitive load for developers
- Merge conflicts in team environment
- Impossible to optimize individual paths

**Recommendation:** Break into 7 service classes:
- `WebhookValidationService`
- `CallLifecycleService`
- `AppointmentExtractionService`
- `AppointmentBookingService`
- `CostCalculationService`
- `TranscriptAnalysisService`
- `CustomerDataService`

#### RetellFunctionCallHandler.php (1583 lines)

**Violations:**
- Mixed concerns: availability checking, booking, service listing
- Branch isolation logic duplicated across 4 methods (lines 120-180, 240-300, 340-410, 452-527)
- Hard-coded service IDs (lines 1343-1350)
- 300-line methods (collectAppointment: 925 lines)

**Critical Security Issue:**
```php
// Lines 1343-1350: Hard-coded service selection
if ($companyId == 15) {
    $service = Service::where('id', 45)->first(); // BRITTLE
} elseif ($companyId == 1) {
    $service = Service::where('id', 40)->first(); // BRITTLE
}
```

**Recommendation:** Extract to domain services:
- `BranchContextResolver` (centralize call context logic)
- `AvailabilityChecker`
- `AppointmentBooker`
- `ServiceSelector` (replace hard-coded IDs with configuration)

---

### 2. Service Layer - FRAGMENTATION

**Current Services (30+ files):**
```
CalcomService.php
CalcomV2Service.php         ← Version duplication
CalcomServiceInterface.php  ← Empty interface
RetellService.php
RetellV2Service.php          ← Version duplication
RetellV1Service.php          ← Legacy?
RetellAIService.php          ← What's the difference?
AppointmentAlternativeFinder.php
NestedBookingManager.php
CostCalculator.php
PlatformCostService.php
ExchangeRateService.php
NameExtractor.php
PhoneNumberNormalizer.php
... (17+ more services)
```

**Problems:**
1. **No Clear Boundaries:** 30+ services without clear domain separation
2. **Version Proliferation:** V1, V2 services coexist without deprecation strategy
3. **Naming Inconsistency:** `*Service`, `*Manager`, `*Finder`, `*Calculator`
4. **Empty Abstractions:** `CalendarServiceInterface` with no implementations
5. **Direct Instantiation:** `new AppointmentAlternativeFinder()` instead of DI

**Service Dependency Graph (Inferred):**
```
RetellWebhookController
  ├─> RetellApiClient
  ├─> CalcomService
  ├─> AppointmentAlternativeFinder ──> CalcomService (circular?)
  ├─> NestedBookingManager
  ├─> CostCalculator
  ├─> PlatformCostService ──> ExchangeRateService
  ├─> NameExtractor
  └─> PhoneNumberNormalizer

RetellFunctionCallHandler
  ├─> AppointmentAlternativeFinder (duplicate instantiation)
  └─> CalcomService (duplicate instantiation)
```

**Recommendation:** Consolidate into 6 domain-bounded services:
```
Domain/Call/
  ├─ CallLifecycleService
  ├─ CallAnalysisService
  └─ CallCostService

Domain/Appointment/
  ├─ AppointmentBookingService
  ├─ AvailabilityService
  └─ AlternativeFindingService

Domain/Integration/
  ├─ RetellIntegrationService (unified)
  ├─ CalcomIntegrationService (unified)
  └─ WebhookProcessingService

Domain/Customer/
  ├─ CustomerIdentificationService
  └─ CustomerDataService

Domain/Billing/
  ├─ CostCalculationService
  └─ InvoicingService

Domain/Notification/
  └─ NotificationService
```

---

### 3. Domain Model - ANEMIC PATTERN

**Call Model (163 lines):**
```php
// 91 fillable fields - Fat data model
protected $fillable = [
    'external_id', 'customer_id', 'kunde_id', 'phone_number_id',
    'branch_id', 'agent_id', 'retell_agent_id', 'retell_call_id',
    // ... 83 more fields
];

// Only relationships - NO business logic
public function customer(): BelongsTo { ... }
public function company(): BelongsTo { ... }
// No domain methods like calculateCost(), extractAppointment(), etc.
```

**Company Model (264 lines):**
```php
// 80+ fillable fields
// Only encryption logic and relationships
// Missing: validateTeamAccess(), syncTeamEventTypes() implementation
// Team methods call external services instead of encapsulating logic
```

**Problems:**
1. **No Domain Logic:** Models are pure data containers
2. **Business Rules in Controllers:** All logic in 2000-line controllers
3. **No Value Objects:** Phone numbers, money, timestamps as primitives
4. **No Validation:** All validation in controllers/form requests
5. **No Invariants:** No protection of business rules

**Recommendation:** Rich Domain Model
```php
// Example: Call Aggregate Root
class Call extends Model {
    // Value Objects
    public function duration(): Duration { ... }
    public function cost(): Money { ... }
    public function phoneNumber(): PhoneNumber { ... }

    // Domain Logic
    public function markAsCompleted(DisconnectionReason $reason): void { ... }
    public function calculateTotalCost(ExchangeRate $rate): Money { ... }
    public function extractAppointmentRequest(): ?AppointmentRequest { ... }

    // Business Rule Enforcement
    public function convertToAppointment(Appointment $apt): void {
        if (!$this->isCompleted()) {
            throw new InvalidStateException();
        }
        // ...
    }
}
```

---

### 4. Multi-Tenant Isolation Analysis

**Current Implementation:**

✅ **Strengths:**
- Company-scoped phone numbers with validation (lines 136-163)
- Branch-level service filtering (RetellFunctionCallHandler lines 143-171)
- Encrypted API keys per company
- Team-based Cal.com event type validation

⚠️ **Weaknesses:**
- No database-level tenant isolation (shared tables)
- Inconsistent tenant context propagation
- Hard-coded fallbacks to company_id=1 removed (good) but default to 15 (line 1527)
- Branch context resolution repeated in 4+ places

**Security Concerns:**
```php
// RetellFunctionCallHandler.php:1527
$companyId = $customer->company_id ?? 15; // DEFAULT TENANT?

// RetellWebhookController.php:398-604
$companyId = 1; // Default company - VULN if phone lookup fails
```

**Recommendation:**
1. Implement `TenantScopedModel` trait for automatic scoping
2. Centralize tenant resolution in middleware
3. Remove all hard-coded tenant fallbacks
4. Add database-level RLS (Row Level Security) if using PostgreSQL

---

### 5. Scalability Concerns

#### Horizontal Scaling Readiness: 40%

| Aspect | Status | Blocker |
|--------|--------|---------|
| Stateless Application | ⚠️ Partial | Database sessions prevent scale-out |
| Database Sharding | ❌ No | Single SQLite file |
| Load Balancer Ready | ✅ Yes | Webhooks are stateless |
| Async Processing | ⚠️ Basic | DB queue not production-grade |
| Cache Distribution | ❌ No | Database cache not shared |
| File Storage | ⚠️ Local | No S3/CDN integration |

#### Database Bottlenecks

**Current Schema Analysis:**
- `calls` table: 91 columns (excessive, needs normalization)
- No partitioning strategy
- No read replicas
- No connection pooling configuration

**Growth Projection:**
```
Current: 8 companies × 50 calls/day = 400 calls/day
Target:  100 companies × 50 calls/day = 5,000 calls/day
         × 365 days = 1.8M calls/year

SQLite limitations:
- Max database size: ~140TB (theoretical)
- Concurrent writes: 1 writer (BOTTLENECK)
- Recommended max DB size: 1-2GB (practical)
```

**Recommendation:** Migrate to PostgreSQL with:
- Partitioning on `calls` table by month/company
- Read replicas for analytics
- Connection pooling (PgBouncer)
- Consider TimescaleDB for time-series call data

#### Service Decomposition Candidates

**Microservice Extraction Priority:**

1. **Call Processing Service** (HIGH PRIORITY)
   - Handles: Retell webhook processing, transcript analysis
   - Why: CPU-intensive, can scale independently
   - Technology: Separate Laravel app + RabbitMQ

2. **Appointment Booking Service** (HIGH PRIORITY)
   - Handles: Cal.com integration, availability checking
   - Why: External API dependency, needs circuit breaker
   - Technology: Separate service + Redis cache

3. **Notification Service** (MEDIUM PRIORITY)
   - Handles: SMS, Email, Push notifications
   - Why: Can be reused across applications
   - Technology: Microservice + SNS/SQS

**Not Recommended for Extraction (Yet):**
- Customer Management (too coupled to core domain)
- Billing Service (complex financial transactions)
- Admin UI (Filament tightly integrated)

---

### 6. Code Organization Analysis

#### Directory Structure
```
app/
├─ Http/Controllers/
│  ├─ RetellWebhookController.php (2091 lines) ← FAT
│  └─ RetellFunctionCallHandler.php (1583 lines) ← FAT
│
├─ Services/ (30+ files, no subfolders) ← FLAT
│  ├─ CalcomService.php
│  ├─ CalcomV2Service.php
│  ├─ RetellService.php
│  └─ ... 27 more services
│
├─ Models/ (Anemic, no domain logic)
│
└─ Filament/Resources/ (Well-organized) ✅
```

**Problems:**
1. No domain-driven directory structure
2. Services folder is flat (30+ files in one directory)
3. No clear separation of core vs. infrastructure
4. Controllers contain domain logic

**Recommended Structure (DDD-inspired):**
```
app/
├─ Domain/
│  ├─ Call/
│  │  ├─ Models/Call.php
│  │  ├─ Services/CallLifecycleService.php
│  │  ├─ ValueObjects/Duration.php
│  │  └─ Events/CallCompleted.php
│  │
│  ├─ Appointment/
│  │  ├─ Models/Appointment.php
│  │  ├─ Services/BookingService.php
│  │  └─ ValueObjects/TimeSlot.php
│  │
│  └─ Customer/
│     └─ ...
│
├─ Infrastructure/
│  ├─ Integration/
│  │  ├─ Retell/RetellClient.php
│  │  └─ Calcom/CalcomClient.php
│  │
│  └─ Persistence/
│     └─ Repositories/
│
├─ Application/
│  ├─ UseCases/
│  │  ├─ ProcessInboundCall.php
│  │  └─ BookAppointment.php
│  │
│  └─ DTOs/
│
└─ Presentation/
   ├─ Http/Controllers/ (Thin controllers)
   └─ Filament/
```

---

### 7. Integration Patterns Analysis

#### Webhook Reliability

**Current Implementation:**
```php
// RetellWebhookController.php:42-57
$webhookEvent = $this->logWebhookEvent($request, 'retell', $data);
// Logging only, no retry mechanism

// No exponential backoff
// No dead-letter queue
// No idempotency key validation
```

**Missing Patterns:**
1. **Idempotency:** No duplicate webhook protection
2. **Retry Logic:** No retry on transient failures
3. **Circuit Breaker:** No protection against Cal.com API failures
4. **Webhook Verification:** Basic logging only

**Recommendation:**
```php
class WebhookProcessingService {
    public function process(WebhookEvent $event): void {
        // 1. Idempotency check
        if ($this->isDuplicate($event->id)) {
            return;
        }

        // 2. Circuit breaker for external APIs
        $this->circuitBreaker->execute(function() use ($event) {
            $this->handler->handle($event);
        });

        // 3. Retry on failure
        try {
            $this->handler->handle($event);
        } catch (TransientException $e) {
            dispatch(new RetryWebhookJob($event))
                ->delay(now()->addMinutes(5));
        }

        // 4. Dead letter queue
        catch (PermanentException $e) {
            $this->deadLetterQueue->push($event);
        }
    }
}
```

#### Cal.com API Integration

**Current Issues:**
- No connection pooling for HTTP client
- No request timeout configuration visible
- No rate limiting protection
- Synchronous blocking calls in webhook handlers

**Recommendation:**
- Use Laravel HTTP facade with retry middleware
- Implement exponential backoff: `retry(3, fn() => $api->call(), 100)`
- Add circuit breaker: `Circuit::for('calcom')->run($callback)`
- Cache availability responses: `Cache::remember("availability:{$date}", 300, ...)`

---

## Technical Debt Inventory

### Critical (Sprint 2-3)

| Issue | Impact | Effort | Priority |
|-------|--------|--------|----------|
| SQLite → PostgreSQL migration | Production blocker | 3 sprints | P0 |
| RetellWebhookController refactoring | Maintainability | 2 sprints | P0 |
| Service layer consolidation | Code quality | 2 sprints | P0 |
| Implement idempotent webhooks | Data integrity | 1 sprint | P1 |
| Branch context centralization | Security | 1 sprint | P1 |

### High (Sprint 4-6)

| Issue | Impact | Effort | Priority |
|-------|--------|--------|----------|
| Database queue → Redis queue | Scalability | 1 sprint | P1 |
| Implement caching layer (Redis) | Performance | 1 sprint | P1 |
| Domain model enrichment | Code quality | 2 sprints | P2 |
| Circuit breaker for Cal.com | Reliability | 1 sprint | P2 |
| Service interface contracts | Testing | 1 sprint | P2 |

### Medium (Sprint 7-12)

| Issue | Impact | Effort | Priority |
|-------|--------|--------|----------|
| Microservice extraction (Call Processing) | Scalability | 3 sprints | P2 |
| Event-driven architecture | Extensibility | 2 sprints | P3 |
| Database partitioning | Performance | 2 sprints | P3 |
| DDD refactoring (full) | Maintainability | 4 sprints | P3 |
| API Gateway pattern | Architecture | 2 sprints | P3 |

---

## Scalability Roadmap

### Phase 1: Quick Improvements (Sprint 2-3)

**Goal:** Support 25-50 companies with minimal risk

1. **Database Migration** (3 weeks)
   - SQLite → PostgreSQL with pgBouncer
   - Database connection pooling
   - Read replica for analytics
   - Estimated effort: 40 hours

2. **Queue Infrastructure** (1 week)
   - Database queue → Redis queue
   - Configure horizon for monitoring
   - Async webhook processing
   - Estimated effort: 16 hours

3. **Webhook Idempotency** (1 week)
   - Add `idempotency_key` to webhooks table
   - Implement duplicate detection
   - Estimated effort: 12 hours

4. **Branch Context Middleware** (1 week)
   - Extract `BranchContextResolver` service
   - Add `TenantScope` middleware
   - Remove hard-coded tenant fallbacks
   - Estimated effort: 16 hours

**Outcome:** Production-ready infrastructure for 50 companies

### Phase 2: Refactoring (Sprint 4-6)

**Goal:** Support 50-75 companies with maintainable code

1. **Controller Decomposition** (2 weeks)
   - Break RetellWebhookController into 7 services
   - Implement service interfaces
   - Add service provider bindings
   - Estimated effort: 40 hours

2. **Service Layer Consolidation** (2 weeks)
   - Merge V1/V2 services with version strategy
   - Organize services by domain
   - Add service contracts
   - Estimated effort: 32 hours

3. **Caching Layer** (1 week)
   - Implement Redis caching
   - Cache Cal.com availability responses
   - Add cache invalidation strategy
   - Estimated effort: 16 hours

4. **Circuit Breaker Implementation** (1 week)
   - Add circuit breaker for Cal.com API
   - Implement fallback strategies
   - Add monitoring/alerts
   - Estimated effort: 12 hours

**Outcome:** Maintainable codebase supporting 75 companies

### Phase 3: Re-architecture (Sprint 7-12)

**Goal:** Support 100+ companies with microservices

1. **Event-Driven Architecture** (2 weeks)
   - Implement domain events
   - Add event bus (RabbitMQ)
   - Convert sync calls to async events
   - Estimated effort: 32 hours

2. **Call Processing Microservice** (3 weeks)
   - Extract call processing logic
   - Deploy as separate service
   - API communication layer
   - Estimated effort: 60 hours

3. **Database Partitioning** (2 weeks)
   - Partition calls table by month
   - Partition by company (sharding strategy)
   - Migration scripts
   - Estimated effort: 32 hours

4. **Rich Domain Model** (3 weeks)
   - Implement value objects
   - Add domain logic to models
   - Implement aggregates
   - Estimated effort: 48 hours

**Outcome:** Scalable architecture supporting 100+ companies

---

## Design Patterns to Implement

### 1. Repository Pattern (Priority: HIGH)

**Problem:** Direct Eloquent queries scattered across controllers

**Solution:**
```php
interface CallRepositoryInterface {
    public function findByRetellId(string $id): ?Call;
    public function findActiveByCompany(Company $company): Collection;
    public function findWithAppointments(): Collection;
}

class EloquentCallRepository implements CallRepositoryInterface {
    public function findByRetellId(string $id): ?Call {
        return Call::where('retell_call_id', $id)
            ->with(['customer', 'phoneNumber'])
            ->first();
    }
}
```

**Benefits:**
- Testable (mock repository in tests)
- Database abstraction (easier migration)
- Query optimization in one place

### 2. Service Layer Pattern (Priority: HIGH)

**Problem:** Business logic in controllers

**Solution:**
```php
class AppointmentBookingService {
    public function __construct(
        private CalcomIntegration $calcom,
        private AvailabilityChecker $availability,
        private NotificationService $notifier
    ) {}

    public function bookAppointment(
        AppointmentRequest $request,
        Call $call
    ): BookingResult {
        // 1. Check availability
        $slot = $this->availability->findSlot($request);

        // 2. Create booking
        $booking = $this->calcom->createBooking($slot);

        // 3. Link to call
        $call->linkAppointment($booking);

        // 4. Notify customer
        $this->notifier->sendConfirmation($booking);

        return new BookingResult($booking);
    }
}
```

### 3. Circuit Breaker Pattern (Priority: HIGH)

**Problem:** No protection against external API failures

**Solution:**
```php
use Illuminate\Support\Facades\Http;

class CircuitBreakerCalcomClient {
    public function createBooking(array $data): Response {
        return Circuit::for('calcom')
            ->maxFailures(5)
            ->retryAfterSeconds(60)
            ->run(
                try: fn() => Http::retry(3, 100)
                    ->post("https://cal.com/api/bookings", $data),
                fallback: fn() => $this->queueForLater($data)
            );
    }
}
```

### 4. Strategy Pattern (Priority: MEDIUM)

**Problem:** Hard-coded service selection logic

**Solution:**
```php
interface ServiceSelectionStrategy {
    public function selectService(Company $company): Service;
}

class DefaultServiceStrategy implements ServiceSelectionStrategy {
    public function selectService(Company $company): Service {
        return $company->services()
            ->where('is_default', true)
            ->firstOrFail();
    }
}

class PriorityServiceStrategy implements ServiceSelectionStrategy {
    public function selectService(Company $company): Service {
        return $company->services()
            ->orderBy('priority')
            ->firstOrFail();
    }
}
```

### 5. Event Sourcing (Priority: LOW - Future)

**Problem:** No audit trail of call state changes

**Solution:**
```php
// Events
class CallStarted extends DomainEvent {
    public function __construct(
        public readonly CallId $callId,
        public readonly PhoneNumber $from,
        public readonly DateTime $occurredAt
    ) {}
}

// Event Store
class CallAggregate {
    private array $events = [];

    public function startCall(PhoneNumber $from): void {
        $this->recordEvent(new CallStarted(
            $this->id,
            $from,
            now()
        ));
    }

    public function apply(CallStarted $event): void {
        $this->status = 'ongoing';
        $this->startedAt = $event->occurredAt;
    }
}
```

---

## Architecture Decision Records (ADRs)

### ADR-001: Migrate from SQLite to PostgreSQL

**Status:** PROPOSED
**Date:** 2025-09-30
**Context:**
SQLite is inadequate for production multi-tenant system with 100+ companies and 5000+ calls/day due to single-writer limitation.

**Decision:**
Migrate to PostgreSQL 15+ with:
- PgBouncer for connection pooling
- Read replicas for analytics
- Partitioning on `calls` table

**Consequences:**
- Positive: Horizontal read scaling, ACID compliance, better concurrency
- Negative: 3-sprint migration effort, hosting cost increase
- Neutral: Requires schema migration, query optimization

**Alternatives Considered:**
- MySQL: Rejected (PostgreSQL superior JSON support for `analysis` fields)
- MongoDB: Rejected (ACID requirements, complex relationships)

---

### ADR-002: Implement Repository Pattern

**Status:** PROPOSED
**Date:** 2025-09-30
**Context:**
Direct Eloquent queries in controllers create tight coupling and prevent testing.

**Decision:**
Introduce repository layer with interfaces:
- `CallRepositoryInterface`
- `CompanyRepositoryInterface`
- `AppointmentRepositoryInterface`

**Consequences:**
- Positive: Testability, database abstraction, query optimization
- Negative: Additional abstraction layer, slight complexity increase
- Neutral: Migration effort for existing queries

---

### ADR-003: Extract Call Processing Service

**Status:** PROPOSED (FUTURE)
**Date:** 2025-09-30
**Context:**
Webhook processing is CPU-intensive and blocks HTTP workers.

**Decision:**
Extract call processing into separate microservice:
- Communication: RabbitMQ message bus
- Scaling: Independent horizontal scaling
- Tech: Laravel 11 + Octane for performance

**Consequences:**
- Positive: Independent scaling, better resource utilization
- Negative: Distributed system complexity, network latency
- Neutral: Requires DevOps infrastructure

**Alternatives Considered:**
- Queue workers only: Rejected (insufficient for 100+ companies)
- AWS Lambda: Rejected (cold start issues for real-time webhooks)

---

### ADR-004: Adopt Circuit Breaker for Cal.com API

**Status:** PROPOSED
**Date:** 2025-09-30
**Context:**
Cal.com API failures cause cascading failures and poor user experience.

**Decision:**
Implement circuit breaker with:
- 5 failures threshold
- 60-second recovery window
- Fallback to queue-based retry

**Consequences:**
- Positive: System stability, graceful degradation
- Negative: Delayed bookings during outages
- Neutral: Requires monitoring infrastructure

---

## Security Analysis

### Current Security Posture

✅ **Strengths:**
- Encrypted API keys (AES-256)
- Phone number validation before lookup
- Branch-level isolation in function calls
- Removed company_id=1 fallback (VULN-003 fix evident)

⚠️ **Weaknesses:**
- No rate limiting on webhook endpoints
- No IP whitelist for Retell webhooks
- Default company fallback (company_id=15) still exists
- No webhook signature verification

🔴 **Critical Vulnerabilities:**
1. **Webhook Authenticity:** No cryptographic signature verification
2. **Rate Limiting:** Webhook endpoints unprotected from DoS
3. **SQL Injection Risk:** Raw queries in some services (need audit)

### Recommendations

1. **Webhook Security** (Sprint 2)
```php
class VerifyRetellWebhookSignature {
    public function handle(Request $request, Closure $next) {
        $signature = $request->header('X-Retell-Signature');
        $payload = $request->getContent();

        $expected = hash_hmac('sha256', $payload, config('retell.webhook_secret'));

        if (!hash_equals($expected, $signature)) {
            abort(401, 'Invalid webhook signature');
        }

        return $next($request);
    }
}
```

2. **Rate Limiting** (Sprint 2)
```php
// routes/api.php
Route::post('/webhooks/retell', [RetellWebhookController::class, '__invoke'])
    ->middleware('throttle:120,1'); // 120 requests/minute per IP
```

3. **Tenant Isolation Validation** (Sprint 3)
```php
trait EnforcesTenantScope {
    protected static function bootEnforcesTenantScope() {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if ($tenant = request()->get('tenant')) {
                $builder->where('company_id', $tenant->id);
            }
        });
    }
}
```

---

## Performance Optimization Targets

### Current Performance Metrics (Estimated)

| Metric | Current | Target (100 companies) | Gap |
|--------|---------|------------------------|-----|
| Webhook response time | 200-500ms | <200ms | 60% |
| Availability check | 1-2s | <500ms | 75% |
| Booking creation | 2-3s | <1s | 67% |
| Database connections | 10-20 | 100+ (pooled) | 400% |
| Concurrent calls | 5-10 | 100+ | 900% |

### Optimization Strategies

1. **Caching Layer** (Sprint 4)
```php
class CachedCalcomService {
    public function getAvailability(EventTypeId $id, Date $date): Slots {
        return Cache::remember(
            "availability:{$id}:{$date}",
            now()->addMinutes(5),
            fn() => $this->calcom->getAvailability($id, $date)
        );
    }
}
```

2. **Database Query Optimization** (Sprint 5)
```php
// Before: N+1 query problem
$calls = Call::all();
foreach ($calls as $call) {
    $company = $call->company; // N queries
}

// After: Eager loading
$calls = Call::with(['company', 'customer', 'phoneNumber'])->get();
```

3. **Async Webhook Processing** (Sprint 3)
```php
// Before: Synchronous blocking
public function __invoke(Request $request) {
    $this->processWebhook($request->all());
    return response()->json(['success' => true]);
}

// After: Queue-based
public function __invoke(Request $request) {
    ProcessWebhookJob::dispatch($request->all());
    return response()->json(['accepted' => true], 202);
}
```

---

## Testing Strategy

### Current Test Coverage: UNKNOWN (No tests visible)

**Recommendations:**

1. **Unit Tests** (Sprint 4-6)
```php
class AppointmentBookingServiceTest extends TestCase {
    public function test_books_appointment_when_slot_available() {
        $service = new AppointmentBookingService(
            calcom: $this->mock(CalcomIntegration::class),
            availability: $this->mock(AvailabilityChecker::class)
        );

        $result = $service->bookAppointment($request, $call);

        $this->assertTrue($result->isSuccess());
    }
}
```

2. **Integration Tests** (Sprint 5-6)
```php
class WebhookIntegrationTest extends TestCase {
    public function test_retell_webhook_creates_call_record() {
        $response = $this->postJson('/webhooks/retell', [
            'event' => 'call_inbound',
            'call_inbound' => ['call_id' => 'test123']
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('calls', ['retell_call_id' => 'test123']);
    }
}
```

3. **Load Tests** (Sprint 7-8)
```bash
# Artillery.io config
scenarios:
  - name: "Webhook Load Test"
    flow:
      - post:
          url: "/webhooks/retell"
          json:
            event: "call_started"

arrival_rate: 50 # 50 webhooks/second
duration: 300    # 5 minutes
```

---

## Monitoring & Observability

### Required Metrics (Sprint 3-4)

1. **Business Metrics**
   - Calls per company per day
   - Appointment conversion rate
   - Average call duration
   - Cost per call

2. **Technical Metrics**
   - Webhook processing time (P95, P99)
   - Cal.com API response time
   - Database query time
   - Queue job processing time
   - Error rate by endpoint

3. **Infrastructure Metrics**
   - Database connections (active/idle)
   - Redis memory usage
   - Queue depth
   - Worker utilization

### Recommended Tools

```yaml
Application Monitoring:
  - Laravel Telescope: Already installed ✅
  - New Relic / Datadog: For production APM

Log Management:
  - Laravel Log: File-based (current)
  - LogStash + ElasticSearch: For 100+ companies

Alerting:
  - PagerDuty: For critical errors
  - Slack Webhooks: For business metrics
```

---

## Cost Projection

### Infrastructure Costs (Monthly)

| Component | Current | 25 Companies | 100 Companies |
|-----------|---------|--------------|---------------|
| Application Servers | $50 | $200 | $800 |
| PostgreSQL Database | $0 | $100 | $400 |
| Redis Cache | $0 | $50 | $150 |
| RabbitMQ | $0 | $50 | $150 |
| Load Balancer | $0 | $50 | $50 |
| **Total** | **$50** | **$450** | **$1,550** |

### Development Costs (One-time)

| Phase | Effort (Weeks) | Cost (Blended Rate) |
|-------|----------------|---------------------|
| Phase 1 (Infrastructure) | 6 weeks | $30,000 |
| Phase 2 (Refactoring) | 6 weeks | $30,000 |
| Phase 3 (Re-architecture) | 10 weeks | $50,000 |
| **Total** | **22 weeks** | **$110,000** |

---

## Conclusion & Recommendations

### Critical Path to 100+ Companies

**MUST DO (Next 3 Sprints):**
1. ✅ Migrate SQLite → PostgreSQL (Sprint 2-3)
2. ✅ Implement webhook idempotency (Sprint 2)
3. ✅ Refactor RetellWebhookController (Sprint 2-3)
4. ✅ Add Redis caching (Sprint 3)

**SHOULD DO (Sprint 4-6):**
5. ✅ Consolidate service layer
6. ✅ Implement circuit breaker
7. ✅ Add comprehensive tests
8. ✅ Enrich domain models

**NICE TO HAVE (Sprint 7-12):**
9. ⚪ Extract microservices
10. ⚪ Event-driven architecture
11. ⚪ Database partitioning

### Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Database scalability wall | HIGH | CRITICAL | Immediate PostgreSQL migration |
| Code maintainability crisis | MEDIUM | HIGH | Controller refactoring Sprint 2-3 |
| Cal.com API outages | MEDIUM | MEDIUM | Circuit breaker implementation |
| Webhook processing delays | LOW | MEDIUM | Async queue processing |
| Multi-tenant data leakage | LOW | CRITICAL | Security audit + tenant scopes |

### Go/No-Go Criteria for 100+ Companies

✅ **GO Criteria:**
- PostgreSQL migration complete
- Webhook processing <200ms P95
- Circuit breaker implemented
- Test coverage >70%
- Load test passed (100 concurrent calls)

❌ **NO-GO Criteria:**
- Still using SQLite
- Fat controllers (>500 lines) remain
- No caching layer
- No circuit breaker

---

**Document Version:** 1.0
**Author:** Architecture Review Team
**Next Review:** After Phase 1 completion (Sprint 3)