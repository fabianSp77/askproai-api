# AskProAI Appointment Management System - Architecture Documentation

**Last Updated:** 2025-10-02
**System Version:** Laravel 11
**Document Status:** Technical Reference

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Component Architecture](#component-architecture)
3. [Data Flow](#data-flow)
4. [Database Schema](#database-schema)
5. [Design Patterns](#design-patterns)
6. [Integration Points](#integration-points)
7. [Caching Strategy](#caching-strategy)
8. [Security Architecture](#security-architecture)

---

## System Overview

AskProAI is a voice-enabled appointment management system that integrates three core components:

- **Retell AI**: Voice assistant for natural language phone interactions
- **Cal.com API**: Appointment scheduling and availability management
- **Laravel Backend**: Business logic, policy enforcement, and data persistence

### System Capabilities

1. **Voice-Driven Booking**: Natural German language appointment scheduling via phone
2. **Policy Enforcement**: Hierarchical cancellation/reschedule policies with fee calculation
3. **Smart Availability**: Intelligent slot finding with caching and rate limiting
4. **Callback Management**: Auto-assignment and escalation workflows for failed bookings
5. **Multi-Tenant**: Company → Branch → Service → Staff hierarchy

### Technology Stack

```
Voice Layer:     Retell AI (WebRTC)
API Gateway:     Laravel 11 (PHP 8.2+)
Database:        MySQL 8.0
Scheduling:      Cal.com v2 API
Cache:           Redis (Laravel Cache)
Queue:           Redis (Laravel Queue)
Testing:         PHPUnit, MySQL test database
```

---

## Component Architecture

### High-Level Architecture

```
┌─────────────────┐
│   Retell AI     │ Voice interactions (German language)
│  Voice Agent    │
└────────┬────────┘
         │ HTTPS Webhooks
         │ (function_call events)
         ▼
┌────────────────────────────────────────────────────┐
│          Laravel API Gateway (Port 8000)           │
├────────────────────────────────────────────────────┤
│                                                    │
│  ┌──────────────────────────────────────────┐    │
│  │  RetellFunctionCallHandler               │    │
│  │  /api/webhooks/retell/function-call      │    │
│  └──────────┬───────────────────────────────┘    │
│             │                                     │
│             ├─► cancel_appointment                │
│             ├─► reschedule_appointment            │
│             ├─► request_callback                  │
│             ├─► find_next_available               │
│             └─► book_appointment                  │
│                                                    │
│  ┌────────────────────────────────────────────┐  │
│  │         Service Layer                      │  │
│  ├────────────────────────────────────────────┤  │
│  │                                            │  │
│  │  AppointmentPolicyEngine ◄─────┐          │  │
│  │  - canCancel()                 │          │  │
│  │  - canReschedule()             │          │  │
│  │  - calculateFee()              │          │  │
│  │                                │          │  │
│  │  PolicyConfigurationService    │          │  │
│  │  - resolvePolicy() ◄───────────┘          │  │
│  │  - Cache management (5min TTL)            │  │
│  │                                            │  │
│  │  SmartAppointmentFinder                   │  │
│  │  - findNextAvailable()                    │  │
│  │  - Rate limiting + caching (45s TTL)      │  │
│  │                                            │  │
│  │  CallbackManagementService                │  │
│  │  - createRequest()                        │  │
│  │  - Auto-assignment                        │  │
│  │  - Escalation workflows                   │  │
│  └────────────────────────────────────────────┘  │
│                                                    │
│  ┌────────────────────────────────────────────┐  │
│  │         External Integrations              │  │
│  ├────────────────────────────────────────────┤  │
│  │  CalcomV2Client                            │  │
│  │  - getAvailableSlots()                     │  │
│  │  - createBooking()                         │  │
│  │  - Rate limiter integration                │  │
│  └────────────────────────────────────────────┘  │
│                                                    │
└────────────────────────────────────────────────────┘
         │
         ▼
┌────────────────────────────────────────────────────┐
│              MySQL Database                        │
├────────────────────────────────────────────────────┤
│  Companies → Branches → Services → Staff           │
│  Customers                                         │
│  Appointments                                      │
│  PolicyConfigurations (polymorphic)                │
│  CallbackRequests → CallbackEscalations            │
│  AppointmentModifications                          │
│  AppointmentModificationStats                      │
└────────────────────────────────────────────────────┘
```

### Service Layer Components

#### 1. AppointmentPolicyEngine

**Location:** `/var/www/api-gateway/app/Services/Policies/AppointmentPolicyEngine.php`

**Responsibilities:**
- Policy evaluation for cancellation/reschedule operations
- Fee calculation based on notice period and policy configuration
- Quota tracking and enforcement
- Hierarchical policy resolution

**Key Methods:**

```php
// Check if appointment can be cancelled
public function canCancel(Appointment $appointment, ?Carbon $now = null): PolicyResult

// Check if appointment can be rescheduled
public function canReschedule(Appointment $appointment, ?Carbon $now = null): PolicyResult

// Calculate modification fee based on policy
public function calculateFee(Appointment $appointment, string $modificationType, ?float $hoursNotice = null): float

// Get remaining quota for customer
public function getRemainingModifications(Customer $customer, string $type): int
```

**Policy Resolution Hierarchy:**
```
1. Staff policy (most specific)
   ↓
2. Service policy
   ↓
3. Branch policy
   ↓
4. Company policy (fallback)
   ↓
5. Default (allow with 0 fee)
```

**Business Rules:**

1. **Cancellation:**
   - Must meet `hours_before` deadline
   - Must not exceed `max_cancellations_per_month` quota
   - Fee calculated based on tiered structure or percentage

2. **Reschedule:**
   - Must meet `hours_before` deadline
   - Must not exceed `max_reschedules_per_appointment` limit
   - Fee calculated based on notice period

**Fee Calculation Logic:**

```php
// Default tiered structure (if no policy)
$defaultTiers = [
    ['min_hours' => 48, 'fee' => 0.0],    // >48h: free
    ['min_hours' => 24, 'fee' => 10.0],   // 24-48h: 10€
    ['min_hours' => 0, 'fee' => 15.0],    // <24h: 15€
];
```

#### 2. PolicyConfigurationService

**Location:** `/var/www/api-gateway/app/Services/Policies/PolicyConfigurationService.php`

**Responsibilities:**
- Policy configuration CRUD operations
- Hierarchical resolution with parent entity traversal
- Cache management (5-minute TTL)
- Batch policy resolution for performance

**Key Methods:**

```php
// Resolve policy with caching
public function resolvePolicy(Model $entity, string $policyType): ?array

// Batch resolve for multiple entities (optimized)
public function resolveBatch(Collection $entities, string $policyType): array

// Warm cache proactively
public function warmCache(Model $entity, ?array $policyTypes = null): int

// Clear cache after updates
public function clearCache(Model $entity, ?string $policyType = null): void

// CRUD operations
public function setPolicy(Model $entity, string $policyType, array $config, bool $isOverride = false): PolicyConfiguration
public function deletePolicy(Model $entity, string $policyType): bool
```

**Caching Strategy:**

```php
// Cache key format
'policy_config_Company_123_cancellation'
'policy_config_Branch_456_reschedule'

// TTL: 300 seconds (5 minutes)
private const CACHE_TTL = 300;
```

**Parent Traversal:**

```php
// Staff → Branch → Company
// Service → Branch → Company
// Branch → Company
// Company → null
```

#### 3. SmartAppointmentFinder

**Location:** `/var/www/api-gateway/app/Services/Appointments/SmartAppointmentFinder.php`

**Responsibilities:**
- Next available slot discovery
- Time window availability search
- Intelligent caching (45s TTL based on Cal.com research)
- Adaptive rate limiting with exponential backoff

**Key Methods:**

```php
// Find next available slot from a starting point
public function findNextAvailable(Service $service, ?Carbon $after = null, int $searchDays = 14): ?Carbon

// Find all slots in a specific time window
public function findInTimeWindow(Service $service, Carbon $start, Carbon $end): Collection

// Cache invalidation
public function clearCache(Service $service): void
```

**Cal.com Integration:**

```php
// API call with rate limiting
protected function fetchAvailableSlots(Service $service, Carbon $start, Carbon $end): Collection
{
    // 1. Check rate limiter
    if (!$this->rateLimiter->canMakeRequest()) {
        $this->rateLimiter->waitForAvailability();
    }

    // 2. Make API call
    $response = $this->calcomClient->getAvailableSlots(
        $service->calcom_event_type_id,
        $start,
        $end
    );

    // 3. Increment counter
    $this->rateLimiter->incrementRequestCount();

    // 4. Adapt to response headers
    $this->adaptToRateLimitHeaders($response);

    return $this->parseSlots($response->json());
}
```

**Caching Strategy:**

```php
// Cache TTL: 45 seconds (Cal.com data freshness research)
protected const CACHE_TTL = 45;

// Cache key format
'appointment_finder:next_available:service_123:start_2025-10-02-09-00:end_14'
'appointment_finder:time_window:service_123:start_2025-10-02-09-00:end_2025-10-09-17-00'
```

**Rate Limiting:**

```php
// Adaptive exponential backoff based on X-RateLimit-Remaining header
if ((int)$remaining < 5) {
    $backoffSeconds = pow(2, 5 - (int)$remaining);
    sleep($backoffSeconds);
}

// Handle 429 Too Many Requests
if ($response->status() === 429) {
    $retryAfter = $headers['Retry-After'][0] ?? 60;
    sleep((int)$retryAfter);
}
```

#### 4. CallbackManagementService

**Location:** `/var/www/api-gateway/app/Services/Appointments/CallbackManagementService.php`

**Responsibilities:**
- Callback request lifecycle management
- Auto-assignment to staff based on workload and expertise
- Contact attempt tracking
- Escalation workflows for overdue callbacks

**Key Methods:**

```php
// Create new callback request with auto-assignment
public function createRequest(array $data): CallbackRequest

// Manual assignment
public function assignToStaff(CallbackRequest $request, Staff $staff): void

// Contact tracking
public function markContacted(CallbackRequest $request): void
public function markCompleted(CallbackRequest $request, string $notes): void

// Escalation
public function escalate(CallbackRequest $request, string $reason): CallbackEscalation

// Query overdue callbacks
public function getOverdueCallbacks(Branch $branch): Collection
```

**Auto-Assignment Strategy:**

```php
// Priority order:
// 1. Preferred staff (if specified and available)
if ($callback->staff_id) {
    $staff = Staff::find($callback->staff_id);
    if ($staff && $staff->is_active) return $staff;
}

// 2. Staff with service expertise (least loaded among experts)
if ($callback->service_id) {
    $expertStaff = Staff::where('branch_id', $callback->branch_id)
        ->where('is_active', true)
        ->whereHas('services', fn($q) => $q->where('services.id', $callback->service_id))
        ->withCount('callbackRequests')
        ->orderBy('callback_requests_count', 'asc')
        ->first();
    if ($expertStaff) return $expertStaff;
}

// 3. Least loaded staff in branch
return Staff::where('branch_id', $callback->branch_id)
    ->where('is_active', true)
    ->withCount('callbackRequests')
    ->orderBy('callback_requests_count', 'asc')
    ->first();
```

**Expiration Calculation:**

```php
// Priority-based expiration
$hours = match ($priority) {
    CallbackRequest::PRIORITY_URGENT => 2,   // 2 hours
    CallbackRequest::PRIORITY_HIGH => 4,     // 4 hours
    default => 24,                            // 24 hours (normal)
};

return Carbon::now()->addHours($hours);
```

---

## Data Flow

### User Journey 1: Appointment Cancellation

```
1. Customer calls → Retell AI answers
2. Customer: "Ich möchte meinen Termin am Freitag stornieren"
3. Retell → POST /api/webhooks/retell/function-call
   {
     "function_name": "cancel_appointment",
     "call_id": "call_abc123",
     "parameters": {
       "appointment_date": "2025-10-05",
       "reason": "Zeitlicher Konflikt"
     }
   }

4. RetellFunctionCallHandler::handleCancellationAttempt()
   ├─► Load call context (company_id, branch_id, customer_id)
   ├─► Find appointment by date + customer
   ├─► AppointmentPolicyEngine::canCancel()
   │   ├─► PolicyConfigurationService::resolvePolicy() [cached]
   │   ├─► Check hours_before deadline
   │   ├─► Check max_cancellations_per_month quota
   │   └─► Calculate fee (if applicable)
   │
   ├─► If allowed:
   │   ├─► Update appointment.status = 'cancelled'
   │   ├─► AppointmentModification::create() [tracking]
   │   └─► Return success to Retell
   │
   └─► If denied:
       └─► Return error with policy reason

5. Retell AI speaks: "Ihr Termin wurde storniert. Gebühr: 0€"
```

### User Journey 2: Next Available Slot Search

```
1. Customer: "Wann haben Sie den nächsten freien Termin?"
2. Retell → POST /api/webhooks/retell/function-call
   {
     "function_name": "find_next_available",
     "call_id": "call_xyz789",
     "parameters": {
       "service_id": 123,
       "after_date": "2025-10-02"
     }
   }

3. RetellFunctionCallHandler::handleFindNextAvailable()
   ├─► Load call context
   ├─► Get service by ID (branch validation)
   ├─► SmartAppointmentFinder::findNextAvailable()
   │   ├─► Check cache [45s TTL]
   │   │   └─► Cache HIT → Return cached slot
   │   │
   │   ├─► Cache MISS:
   │   │   ├─► CalcomApiRateLimiter::canMakeRequest()
   │   │   ├─► CalcomV2Client::getAvailableSlots()
   │   │   ├─► Parse response slots
   │   │   ├─► Cache result [45s]
   │   │   └─► Return first slot
   │   │
   │   └─► Handle rate limiting
   │       └─► Exponential backoff if needed
   │
   └─► Return slot to Retell

4. Retell AI speaks: "Der nächste freie Termin ist am Freitag, 5. Oktober um 9 Uhr"
```

### User Journey 3: Callback Request (No Availability)

```
1. Customer: "Ich brauche einen Termin, aber Ihre Zeiten passen nicht"
2. Retell → POST /api/webhooks/retell/function-call
   {
     "function_name": "request_callback",
     "call_id": "call_def456",
     "parameters": {
       "customer_name": "Max Mustermann",
       "phone": "+49123456789",
       "preferred_time": "morgens",
       "priority": "high"
     }
   }

3. RetellFunctionCallHandler::handleCallbackRequest()
   ├─► Load call context
   ├─► CallbackManagementService::createRequest()
   │   ├─► Set defaults (status: pending, expires_at: +4h for high priority)
   │   ├─► CallbackRequest::create()
   │   ├─► Fire CallbackRequested event
   │   │
   │   └─► Auto-assignment:
   │       ├─► Check if should auto-assign (high/urgent priority)
   │       ├─► findBestStaff()
   │       │   ├─► Try preferred staff
   │       │   ├─► Try service experts (least loaded)
   │       │   └─► Fallback: least loaded in branch
   │       │
   │       └─► callback.assign(staff)
   │           ├─► assigned_to = staff.id
   │           ├─► status = 'assigned'
   │           └─► assigned_at = now()
   │
   └─► Return callback_id to Retell

4. Retell AI speaks: "Wir rufen Sie innerhalb von 4 Stunden zurück"

--- Later, if callback becomes overdue ---

5. Background Job detects overdue callback
   ├─► CallbackManagementService::escalate()
   │   ├─► Find different staff (escalation target)
   │   ├─► CallbackEscalation::create()
   │   │   ├─► escalation_reason: 'sla_breach'
   │   │   ├─► escalated_from: original_staff_id
   │   │   └─► escalated_to: new_staff_id
   │   │
   │   ├─► callback.assign(newStaff) [re-assign]
   │   └─► Fire CallbackEscalated event
   │
   └─► Notification sent to new staff member
```

---

## Database Schema

### Core Tables

#### appointments

```sql
CREATE TABLE appointments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id CHAR(36) NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    branch_id CHAR(36) NOT NULL,
    service_id BIGINT UNSIGNED NULL,
    staff_id CHAR(36) NULL,

    -- Timing
    starts_at TIMESTAMP NOT NULL,
    ends_at TIMESTAMP NOT NULL,

    -- Status
    status ENUM('pending','confirmed','completed','cancelled','no_show') DEFAULT 'pending',
    cancelled_at TIMESTAMP NULL,

    -- Recurring
    is_recurring BOOLEAN DEFAULT FALSE,
    recurring_group_id VARCHAR(255) NULL,

    -- Cal.com integration
    calcom_booking_id INT NULL,
    calcom_booking_uid VARCHAR(255) NULL,

    -- Metadata
    notes TEXT NULL,
    metadata JSON NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    INDEX idx_customer_status (customer_id, status),
    INDEX idx_branch_date (branch_id, starts_at),
    INDEX idx_staff_date (staff_id, starts_at),
    INDEX idx_recurring (recurring_group_id),
    INDEX idx_calcom (calcom_booking_uid)
);
```

#### policy_configurations

**Polymorphic relationship:** Can attach to Company, Branch, Service, or Staff

```sql
CREATE TABLE policy_configurations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Polymorphic relationship
    configurable_type VARCHAR(255) NOT NULL,  -- 'App\Models\Company'
    configurable_id VARCHAR(255) NOT NULL,    -- UUID or BIGINT

    -- Policy categorization
    policy_type ENUM('cancellation','reschedule','recurring') NOT NULL,

    -- Flexible JSON configuration
    config JSON NOT NULL,
    /*
    Example configs:

    Cancellation:
    {
        "hours_before": 24,
        "max_cancellations_per_month": 3,
        "fee_tiers": [
            {"min_hours": 24, "fee": 0},
            {"min_hours": 12, "fee": 10},
            {"min_hours": 0, "fee": 25}
        ]
    }

    Reschedule:
    {
        "hours_before": 12,
        "max_reschedules_per_appointment": 2,
        "fee_percentage": 25
    }

    Recurring:
    {
        "allow_partial_cancel": true,
        "require_full_series_notice": false
    }
    */

    -- Inheritance control
    is_override BOOLEAN DEFAULT FALSE,
    overrides_id BIGINT UNSIGNED NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    INDEX idx_polymorphic_config (configurable_type, configurable_id),
    INDEX idx_policy_type (policy_type),
    INDEX idx_override_chain (is_override, overrides_id),
    UNIQUE KEY unique_policy_per_entity (configurable_type, configurable_id, policy_type, deleted_at),

    FOREIGN KEY (overrides_id) REFERENCES policy_configurations(id) ON DELETE SET NULL
);
```

#### callback_requests

```sql
CREATE TABLE callback_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Relationships
    customer_id BIGINT UNSIGNED NULL,
    branch_id CHAR(36) NOT NULL,
    service_id BIGINT UNSIGNED NULL,
    staff_id CHAR(36) NULL,  -- Preferred staff

    -- Contact info
    phone_number VARCHAR(50) NOT NULL,  -- E.164 format
    customer_name VARCHAR(255) NOT NULL,

    -- Scheduling
    preferred_time_window JSON NULL,
    /*
    {
        "start": "2025-10-02 09:00",
        "end": "2025-10-02 17:00"
    }
    */

    -- Priority and status
    priority ENUM('normal','high','urgent') DEFAULT 'normal',
    status ENUM('pending','assigned','contacted','completed','expired','cancelled') DEFAULT 'pending',

    -- Assignment tracking
    assigned_to CHAR(36) NULL,  -- Staff handling callback
    assigned_at TIMESTAMP NULL,
    contacted_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NOT NULL,  -- SLA deadline

    -- Notes
    notes TEXT NULL,
    metadata JSON NULL,  -- Retell call data

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    INDEX idx_status_priority_expires (status, priority, expires_at),
    INDEX idx_assigned_status (assigned_to, status),
    INDEX idx_customer (customer_id),
    INDEX idx_branch (branch_id),

    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES staff(id) ON DELETE SET NULL
);
```

#### callback_escalations

```sql
CREATE TABLE callback_escalations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    callback_request_id BIGINT UNSIGNED NOT NULL,

    -- Escalation tracking
    escalation_reason VARCHAR(255) NOT NULL,  -- 'sla_breach', 'customer_request', etc.
    escalated_from CHAR(36) NULL,  -- Original staff
    escalated_to CHAR(36) NULL,    -- New staff
    escalated_at TIMESTAMP NOT NULL,

    -- Resolution
    resolved BOOLEAN DEFAULT FALSE,
    resolved_at TIMESTAMP NULL,
    resolution_notes TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_callback (callback_request_id),
    INDEX idx_unresolved (resolved, escalated_at),

    FOREIGN KEY (callback_request_id) REFERENCES callback_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (escalated_from) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (escalated_to) REFERENCES staff(id) ON DELETE SET NULL
);
```

#### appointment_modifications

**Tracks all cancellation/reschedule operations for analytics and auditing**

```sql
CREATE TABLE appointment_modifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    appointment_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,

    -- Modification details
    modification_type ENUM('cancel','reschedule') NOT NULL,
    within_policy BOOLEAN NOT NULL,
    fee_charged DECIMAL(10,2) DEFAULT 0.00,

    -- Context
    reason VARCHAR(500) NULL,
    modified_by_type VARCHAR(50) NULL,  -- 'customer', 'staff', 'system', 'api'
    modified_by_id VARCHAR(255) NULL,

    -- Reschedule specifics
    original_start_time TIMESTAMP NULL,
    new_start_time TIMESTAMP NULL,

    metadata JSON NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_appointment (appointment_id),
    INDEX idx_customer (customer_id),
    INDEX idx_type_policy (modification_type, within_policy),
    INDEX idx_fee (fee_charged),

    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);
```

#### appointment_modification_stats

**Materialized statistics for fast quota checks (rolling 30-day windows)**

```sql
CREATE TABLE appointment_modification_stats (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    customer_id BIGINT UNSIGNED NOT NULL,
    stat_type VARCHAR(50) NOT NULL,  -- 'cancellation_count', 'reschedule_count'
    count INT UNSIGNED NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,

    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_customer_type (customer_id, stat_type),
    INDEX idx_period (period_end),
    UNIQUE KEY unique_customer_stat_period (customer_id, stat_type, period_start),

    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);
```

### Entity Relationships

```
companies (1) ──────┬─────► branches (N)
                    │
                    └─────► services (N)

branches (1) ───────┬─────► staff (N)
                    │
                    ├─────► services (N)
                    │
                    └─────► callback_requests (N)

services (1) ───────┬─────► appointments (N)
                    │
                    └─────► callback_requests (N)

staff (1) ──────────┬─────► appointments (N)
                    │
                    ├─────► callback_requests (N) [preferred]
                    │
                    └─────► callback_requests (N) [assigned_to]

customers (1) ──────┬─────► appointments (N)
                    │
                    ├─────► callback_requests (N)
                    │
                    ├─────► appointment_modifications (N)
                    │
                    └─────► appointment_modification_stats (N)

policy_configurations (*) ──► [polymorphic to Company|Branch|Service|Staff]

callback_requests (1) ──────► callback_escalations (N)
```

---

## Design Patterns

### 1. Service Layer Pattern

**Purpose:** Encapsulate business logic separate from controllers and models

**Implementation:**
- Controllers handle HTTP concerns only
- Services contain business logic and orchestration
- Models are data containers with relationships

**Example:**

```php
// Controller (thin)
class RetellFunctionCallHandler extends Controller
{
    public function handleCancellationAttempt(array $params, string $callId)
    {
        $appointment = $this->findAppointment($params, $callId);

        // Delegate to service
        $result = $this->policyEngine->canCancel($appointment);

        if ($result->allowed) {
            $appointment->cancel();
            return $this->responseFormatter->success($result);
        }

        return $this->responseFormatter->error($result->reason);
    }
}

// Service (business logic)
class AppointmentPolicyEngine
{
    public function canCancel(Appointment $appointment): PolicyResult
    {
        $policy = $this->resolvePolicy($appointment, 'cancellation');
        $hoursNotice = now()->diffInHours($appointment->starts_at);

        // Business rules
        if ($hoursNotice < $policy['hours_before']) {
            return PolicyResult::deny("Insufficient notice");
        }

        // More complex logic...
        return PolicyResult::allow();
    }
}
```

### 2. Strategy Pattern (Policy Resolution)

**Purpose:** Select algorithm (policy) dynamically based on entity hierarchy

**Implementation:**

```php
class PolicyConfigurationService
{
    // Strategy selection at runtime
    public function resolvePolicy(Model $entity, string $policyType): ?array
    {
        // Check entity's own policy
        $policy = $this->getEntityPolicy($entity, $policyType);
        if ($policy) return $policy;

        // Traverse to parent
        $parent = $this->getParentEntity($entity);
        if ($parent) return $this->resolvePolicy($parent, $policyType);

        return null; // No policy found
    }

    // Strategy mapping
    private function getParentEntity(Model $entity): ?Model
    {
        return match (get_class($entity)) {
            'App\Models\Staff' => $entity->branch,
            'App\Models\Service' => $entity->branch,
            'App\Models\Branch' => $entity->company,
            'App\Models\Company' => null,
            default => null,
        };
    }
}
```

### 3. Value Object Pattern

**Purpose:** Immutable objects representing policy evaluation results

**Implementation:**

```php
class PolicyResult
{
    public function __construct(
        public readonly bool $allowed,
        public readonly ?string $reason = null,
        public readonly float $fee = 0.0,
        public readonly array $details = []
    ) {}

    public static function allow(float $fee = 0.0, array $details = []): self
    {
        return new self(true, null, $fee, $details);
    }

    public static function deny(string $reason, array $details = []): self
    {
        return new self(false, $reason, 0.0, $details);
    }
}
```

### 4. Repository Pattern (Implicit via Eloquent)

Laravel's Eloquent ORM provides repository-like functionality:

```php
// Scope-based queries (reusable)
class CallbackRequest extends Model
{
    public function scopeOverdue($query)
    {
        return $query->where('expires_at', '<', Carbon::now())
                     ->whereNotIn('status', ['completed', 'expired', 'cancelled']);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }
}

// Usage
$overdueHighPriority = CallbackRequest::overdue()
    ->byPriority('high')
    ->get();
```

### 5. Factory Pattern (Laravel Factories)

**Purpose:** Generate test data consistently

```php
// tests/database/factories/AppointmentFactory.php
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'starts_at' => Carbon::now()->addDays(3),
            'ends_at' => Carbon::now()->addDays(3)->addHour(),
            'status' => 'confirmed',
        ];
    }

    // State modifiers
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now(),
        ]);
    }
}
```

---

## Integration Points

### 1. Retell AI Webhooks

**Endpoint:** `POST /api/webhooks/retell/function-call`

**Authentication:** Custom signature validation (see SECURITY.md)

**Request Format:**

```json
{
  "function_name": "cancel_appointment",
  "call_id": "call_abc123def456",
  "agent_id": "agent_789xyz",
  "parameters": {
    "appointment_date": "2025-10-05",
    "reason": "Zeitlicher Konflikt"
  }
}
```

**Supported Functions:**

| Function Name | Purpose | Parameters |
|---------------|---------|------------|
| `check_availability` | Check specific date/time | `date`, `time`, `service_id` |
| `find_next_available` | Find next free slot | `service_id`, `after_date` |
| `book_appointment` | Create new appointment | `date`, `time`, `service_id`, `customer_name`, `phone` |
| `cancel_appointment` | Cancel existing appointment | `appointment_date`, `reason` |
| `reschedule_appointment` | Change appointment time | `appointment_date`, `new_date`, `new_time` |
| `request_callback` | Create callback request | `customer_name`, `phone`, `preferred_time`, `priority` |

### 2. Cal.com API v2

**Base URL:** `https://api.cal.com/v2`

**Authentication:** Bearer token (per company)

**Key Endpoints Used:**

```
GET  /slots/available
POST /bookings
GET  /bookings/{uid}
PATCH /bookings/{uid}
DELETE /bookings/{uid}
```

**Rate Limiting:**
- Default: 100 requests/minute
- Monitored via `X-RateLimit-Remaining` header
- Exponential backoff when remaining < 5

**Caching Strategy:**
- Availability queries: 45 seconds TTL
- Booking details: No cache (real-time)

### 3. Events and Listeners

**Events Fired:**

```php
// Callback lifecycle
event(new CallbackRequested($callback));
event(new CallbackAssigned($callback, $staff));
event(new CallbackEscalated($callback, $reason, $fromStaff, $toStaff));
event(new CallbackCompleted($callback));

// Appointment modifications
event(new AppointmentCancelled($appointment, $fee));
event(new AppointmentRescheduled($appointment, $oldTime, $newTime));
```

**Potential Listeners:**

```php
// Email notifications
SendCallbackAssignmentEmail
SendEscalationNotification
SendCancellationConfirmation

// Analytics
LogModificationMetrics
UpdateCustomerStats

// External integrations
SyncToCalendar
NotifySlackChannel
```

---

## Caching Strategy

### Cache Layers

```
┌─────────────────────────────────────────────────┐
│  L1: Request-Scoped Cache (Array)               │
│  TTL: Single request                            │
│  Usage: Call context, duplicate prevention      │
└─────────────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────┐
│  L2: Redis Cache (Laravel Cache)                │
│  TTL: 45s (availability), 5min (policies)       │
│  Usage: Cal.com slots, policy configs           │
└─────────────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────┐
│  L3: MySQL Query Cache                          │
│  TTL: Automatic (MySQL managed)                 │
│  Usage: Frequent queries with same params       │
└─────────────────────────────────────────────────┘
```

### Cache Keys and TTLs

| Component | Cache Key Pattern | TTL | Invalidation Trigger |
|-----------|------------------|-----|---------------------|
| Appointment Availability | `appointment_finder:*:service_{id}:*` | 45s | Booking created/cancelled |
| Policy Configuration | `policy_config_{Entity}_{id}_{type}` | 5min | Policy updated/deleted |
| Call Context | Request-scoped array | Request | End of request |
| Cal.com Rate Limit | `calcom_rate_limit:{company_id}` | 60s | Time-based reset |

### Cache Invalidation

```php
// Appointment created/cancelled → Clear availability cache
class AppointmentObserver
{
    public function created(Appointment $appointment)
    {
        $finder = new SmartAppointmentFinder();
        $finder->clearCache($appointment->service);
    }
}

// Policy updated → Clear policy cache
class PolicyConfigurationService
{
    public function setPolicy(Model $entity, string $type, array $config)
    {
        PolicyConfiguration::updateOrCreate([...], [...]);

        // Invalidate
        $this->clearCache($entity, $type);
    }
}
```

---

## Security Architecture

### Multi-Tenant Isolation

**Hierarchy:**

```
Company (tenant boundary)
  └─► Branch (sub-tenant)
      └─► Service, Staff (resources)
```

**Enforcement Points:**

1. **Call Context Resolution:**

```php
// Extract tenant from Retell call
$context = $this->getCallContext($callId);
// $context = ['company_id' => X, 'branch_id' => Y]
```

2. **Query Scoping:**

```php
// All queries must include tenant filters
Service::where('company_id', $context['company_id'])
    ->where('branch_id', $context['branch_id'])
    ->get();
```

3. **Cache Isolation:**

```php
// Include tenant in cache keys
$cacheKey = "availability:{$companyId}:{$branchId}:service_{$serviceId}";
```

### Authentication

**Retell Webhooks:**
- Custom signature validation (see API_REFERENCE.md)
- Payload verification
- Replay attack prevention

**Internal API:**
- Laravel Sanctum tokens
- Role-based access control (Admin, Staff, Customer)

### Data Protection

**PII Handling:**

```php
// LogSanitizer for logging
Log::info('Callback created', LogSanitizer::sanitize([
    'phone' => '+49123456789',  // Masked in logs
    'customer_name' => 'Max M.', // Truncated
]));
```

**Database Encryption:**
- Sensitive fields encrypted at rest
- Phone numbers stored in E.164 format

**Soft Deletes:**
- All models use `SoftDeletes` trait
- Data retained for audit/GDPR compliance

---

## Performance Considerations

### Database Optimization

**Indexes:**

```sql
-- Multi-column indexes for common queries
INDEX idx_customer_status (customer_id, status)
INDEX idx_branch_date (branch_id, starts_at)
INDEX idx_status_priority_expires (status, priority, expires_at)
```

**Query Optimization:**

```php
// Eager loading to prevent N+1
$callbacks = CallbackRequest::with([
    'customer',
    'branch',
    'service',
    'assignedTo',
    'escalations'
])->get();

// Query optimization in auto-assignment
Staff::where('branch_id', $branchId)
    ->where('is_active', true)
    ->withCount(['callbackRequests' => fn($q) => $q->whereIn('status', [...])])
    ->orderBy('callback_requests_count', 'asc')
    ->first();
```

### Caching Benefits

**Measured Impact:**

```
Availability Query (uncached): 800-1200ms
Availability Query (cached):    2-5ms
Improvement:                    99.5% faster

Policy Resolution (uncached):   50-150ms
Policy Resolution (cached):     1-2ms
Improvement:                    98% faster
```

### Rate Limiting

**Cal.com API:**
- Adaptive exponential backoff
- Request queue with throttling
- Circuit breaker pattern (future)

**Retell Webhooks:**
- No rate limiting (trusted source)
- Async processing for heavy operations

---

## Gotchas and Best Practices

### Common Pitfalls

1. **Policy Hierarchy Confusion:**
   - Remember: Staff > Service > Branch > Company
   - Always test policy resolution with diverse entity structures

2. **Cache Invalidation:**
   - Clear availability cache after EVERY booking/cancellation
   - Policy cache must be cleared on update (automatic in PolicyConfigurationService)

3. **Time Zone Handling:**
   - All timestamps stored in UTC
   - Convert to local time only for display
   - Cal.com returns ISO 8601 with timezone info

4. **Callback Auto-Assignment:**
   - Only triggers for `high` and `urgent` priorities by default
   - Check config: `config('callbacks.auto_assign', true)`

### Best Practices

1. **Service Layer:**
   - Keep controllers thin (HTTP concerns only)
   - Business logic belongs in services
   - Use dependency injection

2. **Testing:**
   - Always use database transactions in tests
   - Mock external APIs (Cal.com, Retell)
   - Test policy hierarchy thoroughly

3. **Error Handling:**
   - Log exceptions with context
   - Return user-friendly error messages
   - Never expose internal errors to Retell AI

4. **Performance:**
   - Use eager loading for relationships
   - Cache aggressively with short TTLs
   - Monitor slow query log

---

## Future Architecture Considerations

### Scalability

**Horizontal Scaling:**
- Stateless service layer (ready for multiple instances)
- Redis cache shared across instances
- Database read replicas for heavy reporting

**Queue Workers:**
- Move callback escalation to background jobs
- Async notification delivery
- Batch policy cache warming

### Monitoring

**Metrics to Track:**
- Cal.com API response times
- Cache hit rates
- Callback escalation rates
- Policy violation patterns

**Logging:**
- Structured JSON logs
- ELK stack integration
- Real-time alerting for errors

### Extensions

**Potential Features:**
- SMS notifications for callbacks
- Calendar sync (Google/Outlook)
- Advanced analytics dashboard
- Multi-language support (English, French)

---

## References

- **Laravel Documentation:** https://laravel.com/docs/11.x
- **Cal.com API Docs:** https://cal.com/docs/api-reference
- **Retell AI Integration:** Internal docs
- **E2E Test Suite:** `/var/www/api-gateway/tests/Feature/EndToEndFlowTest.php`
- **Database Migrations:** `/var/www/api-gateway/database/migrations/`
