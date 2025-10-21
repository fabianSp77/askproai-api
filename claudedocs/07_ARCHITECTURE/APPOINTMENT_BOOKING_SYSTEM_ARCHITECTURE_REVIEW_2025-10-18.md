# Appointment Booking System - Comprehensive Architecture Review

**Date:** 2025-10-18
**Reviewer:** Master Software Architect (Claude)
**Severity:** CRITICAL
**Status:** REQUIRES IMMEDIATE ARCHITECTURAL REFACTORING

---

## EXECUTIVE SUMMARY

**Current State:** The appointment booking system operates as a **tightly-coupled, dual-write distributed system** without proper consistency guarantees, event-driven coordination, or resilience patterns.

**Critical Findings:**
- **7 identified entry points** for appointment creation/modification with **NO centralized coordination**
- **Dual-write problem** between local DB and Cal.com with **NO transaction boundaries**
- **5 out of 7 entry points** lack proper cache invalidation (CRITICAL BUG)
- **Missing sync_origin validation** in webhook handlers allows infinite loops
- **No reconciliation service** for orphaned bookings or sync failures
- **No circuit breaker** for Cal.com API despite documented failures
- **No idempotency guarantees** for webhook processing
- **No saga pattern** for multi-step distributed transactions

**Architectural Impact:** HIGH - System cannot scale beyond current load, prone to data inconsistencies, lacks fault tolerance

**Business Impact:** CRITICAL - Double bookings, customer dissatisfaction, manual cleanup overhead, data integrity violations

---

## 1. CURRENT SYSTEM ARCHITECTURE

### 1.1 System Diagram - AS-IS

```
┌─────────────────────────────────────────────────────────────────────┐
│                         APPOINTMENT BOOKING SYSTEM                   │
└─────────────────────────────────────────────────────────────────────┘

                    ┌──────────────────────────┐
                    │   External Systems       │
                    │                          │
                    │  ┌─────────────────────┐ │
                    │  │  Retell.ai Voice AI │ │
                    │  │  (Voice Bookings)   │ │
                    │  └──────────┬──────────┘ │
                    │             │            │
                    │  ┌──────────▼──────────┐ │
                    │  │  Cal.com Platform   │ │
                    │  │  (Calendar System)  │ │
                    │  └──────────┬──────────┘ │
                    └─────────────┼────────────┘
                                  │
                    ┌─────────────▼──────────────┐
                    │   Laravel Application      │
                    │   (API Gateway)            │
                    └────────────────────────────┘
                                  │
        ┌─────────────────────────┼─────────────────────────┐
        │                         │                         │
        │                         │                         │
┌───────▼────────┐   ┌───────────▼──────────┐   ┌─────────▼─────────┐
│ RetellAPI      │   │ CalcomWebhook        │   │ Filament Admin    │
│ Controller     │   │ Controller           │   │ (Manual Booking)  │
│                │   │                      │   │                   │
│ ✅ Phone call  │   │ ❌ No cache clear    │   │ ✅ Via Service    │
│    bookings    │   │ ❌ No idempotency    │   │                   │
└───────┬────────┘   └───────────┬──────────┘   └─────────┬─────────┘
        │                        │                         │
        └────────────────────────┼─────────────────────────┘
                                 │
                    ┌────────────▼─────────────┐
                    │ AppointmentCreation      │
                    │ Service                  │
                    │                          │
                    │ ⚠️ Creates in both:      │
                    │    1. Local DB           │
                    │    2. Cal.com (async)    │
                    │                          │
                    │ ❌ No transaction        │
                    │ ❌ No rollback           │
                    │ ❌ No consistency check  │
                    └────────────┬─────────────┘
                                 │
                    ┌────────────▼─────────────┐
                    │  CalcomService           │
                    │                          │
                    │  createBooking()         │
                    │  ✅ Cache clear          │
                    │                          │
                    │  rescheduleBooking()     │
                    │  ❌ No cache clear       │
                    │                          │
                    │  cancelBooking()         │
                    │  ❌ No cache clear       │
                    └────────────┬─────────────┘
                                 │
                    ┌────────────▼─────────────┐
                    │  SyncToCalcomJob         │
                    │  (Queue Worker)          │
                    │                          │
                    │  ✅ Loop prevention      │
                    │  ✅ Retry logic (3x)     │
                    │  ❌ No saga pattern      │
                    │  ❌ No compensation      │
                    └────────────┬─────────────┘
                                 │
                    ┌────────────▼─────────────┐
                    │  Data Layer              │
                    │                          │
                    │  PostgreSQL              │
                    │  ├─ appointments         │
                    │  ├─ customers            │
                    │  ├─ services             │
                    │  └─ calls                │
                    │                          │
                    │  Redis Cache             │
                    │  ├─ calcom:slots:*       │
                    │  └─ cal_slots_*          │
                    └──────────────────────────┘

IDENTIFIED ISSUES:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
❌ NO centralized appointment coordinator
❌ Dual-write to DB + Cal.com without distributed transaction
❌ NO reconciliation service for failed syncs
❌ Cache invalidation missing in 5/7 entry points
❌ NO idempotency keys for webhook processing
❌ NO circuit breaker for Cal.com API
❌ NO saga pattern for multi-step operations
```

### 1.2 Service Boundaries Analysis

**FINDING:** Service boundaries are **poorly defined** with overlapping responsibilities:

| Service | Responsibilities | Issues |
|---------|-----------------|--------|
| `AppointmentCreationService` | Customer creation, Cal.com booking, local DB persistence, alternative finding | ❌ Too many responsibilities (SRP violation) |
| `CalcomService` | Cal.com API wrapper, caching, cache invalidation | ⚠️ Cache invalidation is incomplete |
| `CalcomWebhookController` | Webhook ingestion, appointment creation, staff assignment | ❌ No cache invalidation, no idempotency |
| `SyncToCalcomJob` | Async sync to Cal.com | ✅ Good separation but lacks saga |
| `AppointmentAlternativeFinder` | Find alternative slots | ⚠️ Duplicate caching layer |

**VIOLATION:** Single Responsibility Principle - `AppointmentCreationService` has 6+ responsibilities

---

## 2. ARCHITECTURAL PROBLEMS IDENTIFIED

### 2.1 CRITICAL: Dual-Write Problem

**Problem:** System writes to TWO systems (PostgreSQL + Cal.com) without distributed transaction support.

**Evidence:**
```php
// AppointmentCreationService.php - Lines 322-479
public function createLocalRecord(...) {
    // Write 1: Local PostgreSQL
    $appointment->save();  // ← Can succeed

    // Write 2: Cal.com API (async via job)
    // NO guarantee this succeeds!
    // NO rollback if Cal.com fails!

    // Phase 2: Staff Assignment
    $this->assignStaffFromCalcomHost(...);  // ← Third write!
}
```

**Consequences:**
- Appointment exists in DB but NOT in Cal.com → Customer thinks they're booked but staff never sees it
- Cal.com booking exists but DB record fails → Staff sees booking but CRM has no record
- No way to detect inconsistencies automatically
- Manual reconciliation required (expensive, error-prone)

**Pattern Violation:** CAP Theorem - System chooses Availability over Consistency without eventual consistency guarantees

### 2.2 CRITICAL: Missing Idempotency in Webhook Processing

**Problem:** Webhooks can be delivered multiple times by Cal.com, but handler has NO idempotency protection.

**Evidence:**
```php
// CalcomWebhookController.php - Line 281
$appointment = Appointment::updateOrCreate(
    ['calcom_v2_booking_id' => $calcomId],  // ← RACE CONDITION!
    [/* data */]
);
```

**Race Condition Window:**
```
T+0ms:  Webhook 1 arrives → Checks for existing (NOT FOUND)
T+5ms:  Webhook 2 arrives → Checks for existing (NOT FOUND)
T+10ms: Webhook 1 creates appointment #123
T+15ms: Webhook 2 updates appointment #123 (OVERWRITES!)
```

**Solution Required:** Idempotency keys + database unique constraints + pessimistic locking

### 2.3 CRITICAL: Cache Invalidation Gaps

**Problem:** 5 out of 7 booking entry points do NOT invalidate cache.

**Evidence from RCA:**
```
Entry Point                           | Invalidates Cache | Gap Severity
──────────────────────────────────────┼──────────────────┼─────────────
CalcomService::createBooking()        | ✅ Yes           | 🟢 OK
CalcomWebhookController::created()    | ❌ No            | 🔴 CRITICAL
CalcomWebhookController::updated()    | ❌ No            | 🔴 CRITICAL
CalcomWebhookController::cancelled()  | ❌ No            | 🔴 CRITICAL
CalcomService::rescheduleBooking()    | ❌ No            | 🔴 CRITICAL
CalcomService::cancelBooking()        | ❌ No            | 🔴 CRITICAL
```

**Impact:** Stale availability data shown to customers for up to 5 minutes → Double booking attempts

**Root Cause:** Cache invalidation implemented as **private method** inside one service, not accessible to other entry points

### 2.4 HIGH: No Saga Pattern for Multi-Step Transactions

**Problem:** Appointment booking is a multi-step distributed transaction with NO compensation logic.

**Current Flow (NO SAGA):**
```
Step 1: Create customer (DB)           → Success
Step 2: Book in Cal.com (API)          → Success
Step 3: Create local appointment (DB)  → Success
Step 4: Assign staff (DB)              → FAILS!
Step 5: Sync to Cal.com (Job)          → FAILS!

Result: Inconsistent state, NO automatic rollback
```

**Required Saga Pattern:**
```
Step 1: Create customer              → Compensate: Delete customer
Step 2: Book in Cal.com              → Compensate: Cancel Cal.com booking
Step 3: Create local appointment     → Compensate: Delete appointment
Step 4: Assign staff                 → Compensate: Unassign staff
Step 5: Sync verification            → Compensate: Mark for manual review
```

### 2.5 HIGH: No Circuit Breaker for Cal.com API

**Problem:** System makes blocking calls to Cal.com API with NO circuit breaker.

**Evidence:**
```php
// CalcomService.php - Line 687
$response = $this->calcomService->createBooking($bookingData);

// If Cal.com is down → Laravel app blocks
// If Cal.com is slow → Timeouts cascade
// No fallback strategy
```

**Current State:**
- SyncToCalcomJob has retry logic (3 attempts)
- NO circuit breaker to prevent cascade failures
- NO fallback mode (e.g., queue for later)

**Required Pattern:** Circuit Breaker with states (CLOSED → OPEN → HALF_OPEN)

### 2.6 MEDIUM: No Reconciliation Service

**Problem:** Failed syncs are flagged for manual review but NO automated reconciliation.

**Evidence:**
```php
// SyncAppointmentToCalcomJob.php - Line 314
$this->appointment->update([
    'requires_manual_review' => true,
    'manual_review_flagged_at' => now(),
]);

// TODO: Send alert to monitoring system (Slack/PagerDuty)
// TODO: Integrate with Sentry for error tracking
```

**Missing Components:**
- Automated reconciliation job (daily)
- Orphaned booking detector (Cal.com exists, DB doesn't)
- Drift detector (DB != Cal.com state)
- Healing service (auto-retry failed syncs)

### 2.7 MEDIUM: Event-Driven Architecture Incomplete

**Problem:** Events exist but are NOT used for cross-service coordination.

**Evidence:**
```php
// Events found:
app/Events/Appointments/AppointmentBooked.php
app/Events/Appointments/AppointmentCancelled.php
app/Events/Appointments/AppointmentRescheduled.php

// Listeners found:
app/Listeners/Appointments/SyncToCalcomOnBooked.php
app/Listeners/Appointments/SyncToCalcomOnCancelled.php
app/Listeners/Appointments/SyncToCalcomOnRescheduled.php
app/Listeners/Appointments/InvalidateWeekCacheListener.php
app/Listeners/Appointments/InvalidateSlotsCache.php
```

**BUT:** Not all entry points dispatch events!
- `CalcomWebhookController` → Does NOT dispatch events
- `AppointmentCreationService` → Does NOT dispatch events
- Only Filament admin triggers events

**Result:** Inconsistent event propagation, listeners don't fire reliably

### 2.8 LOW: Missing Database Schema Column

**Problem:** Code references `created_by` column that doesn't exist in schema.

**Evidence:**
```php
// AppointmentCreationService.php - Line 440
'created_by' => 'customer',

// Database error: Column 'created_by' not found
```

**Related RCA Findings:**
- Schema-Fehler: created_by Column nicht vorhanden
- Fehlende Sync-Pattern: Cal.com ≠ Local DB
- Mehrfache booking_details Overwrites

---

## 3. ARCHITECTURAL PATTERNS EVALUATION

### 3.1 Should Use Event-Driven Architecture?

**RECOMMENDATION: YES - CRITICAL**

**Reasons:**
1. Multiple entry points need to coordinate (webhooks, API, admin)
2. Cross-cutting concerns (cache invalidation, notifications, analytics)
3. Eventual consistency model fits business domain
4. Enables async processing without tight coupling

**Implementation:**
```php
// Central Event Bus
Event::dispatch(new AppointmentBooked($appointment, $context));

// All entry points dispatch the SAME events
// Listeners handle side effects:
- InvalidateCalcomCacheListener
- SyncToCalcomListener (only if origin != calcom)
- SendNotificationListener
- UpdateAnalyticsListener
- RecordAuditLogListener
```

**Benefits:**
- ✅ Decouples services
- ✅ Centralized side-effect handling
- ✅ Easy to add new listeners (extensibility)
- ✅ Testable in isolation

### 3.2 Should Use CQRS?

**RECOMMENDATION: YES - MEDIUM PRIORITY**

**Reasons:**
1. Read model (availability queries) != Write model (bookings)
2. Cal.com is source of truth for availability
3. Local DB is source of truth for business data
4. Different scaling characteristics (reads >> writes)

**Implementation:**
```
WRITE SIDE (Commands):
├─ CreateAppointmentCommand
├─ RescheduleAppointmentCommand
├─ CancelAppointmentCommand
└─ Handlers enforce business rules, write to DB, dispatch events

READ SIDE (Queries):
├─ GetAvailabilityQuery → Cal.com API (cached)
├─ GetAppointmentQuery → Local DB (optimized indexes)
├─ GetCustomerHistoryQuery → Read replica
└─ Materialized views for reporting
```

**Benefits:**
- ✅ Optimized read paths (no join hell)
- ✅ Scales reads independently
- ✅ Clear separation of concerns
- ✅ Easier to cache read models

### 3.3 Should Use Saga Pattern?

**RECOMMENDATION: YES - CRITICAL**

**Reasons:**
1. Booking spans multiple systems (DB, Cal.com, Email, Analytics)
2. Each step can fail independently
3. Need compensation logic for partial failures
4. NO distributed transaction support (PostgreSQL + Cal.com)

**Implementation: Orchestration Saga**
```php
class BookingOrchestrationSaga
{
    public function execute(BookingRequest $request): BookingResult
    {
        $sagaLog = SagaLog::create(['type' => 'booking']);

        try {
            // Step 1: Reserve slot in Cal.com (with timeout)
            $calcomBooking = $this->reserveCalcomSlot($request);
            $sagaLog->recordStep('calcom_reserved', $calcomBooking);

            // Step 2: Create local appointment (transactional)
            $appointment = DB::transaction(fn() =>
                $this->createAppointment($request, $calcomBooking)
            );
            $sagaLog->recordStep('appointment_created', $appointment);

            // Step 3: Confirm Cal.com booking (idempotent)
            $confirmed = $this->confirmCalcomBooking($calcomBooking);
            $sagaLog->recordStep('calcom_confirmed', $confirmed);

            // Step 4: Send notifications (async, can retry)
            dispatch(new SendBookingNotification($appointment));
            $sagaLog->recordStep('notification_queued');

            $sagaLog->markComplete();
            return BookingResult::success($appointment);

        } catch (\Exception $e) {
            $this->compensate($sagaLog, $e);
            return BookingResult::failure($e);
        }
    }

    private function compensate(SagaLog $sagaLog, \Exception $error): void
    {
        foreach ($sagaLog->steps()->reverse() as $step) {
            match($step->name) {
                'calcom_reserved' => $this->cancelCalcomBooking($step->data),
                'appointment_created' => $this->deleteAppointment($step->data),
                'calcom_confirmed' => null, // Already rolled back
                default => null
            };
        }

        $sagaLog->markFailed($error);
    }
}
```

**Benefits:**
- ✅ Automatic compensation on failure
- ✅ Audit trail of all steps
- ✅ Can retry individual steps
- ✅ Handles partial failures gracefully

### 3.4 Should Use Domain-Driven Design?

**RECOMMENDATION: YES - LONG TERM**

**Bounded Contexts Identified:**

```
1. BOOKING CONTEXT
   ├─ Aggregates: Appointment, TimeSlot
   ├─ Entities: Customer, Service, Staff
   ├─ Value Objects: TimeRange, BookingStatus
   └─ Domain Events: AppointmentBooked, SlotReserved

2. SCHEDULING CONTEXT (Cal.com)
   ├─ Aggregates: Calendar, Availability
   ├─ Entities: EventType, TeamMember
   └─ Anti-Corruption Layer: CalcomAdapter

3. COMMUNICATION CONTEXT (Retell.ai)
   ├─ Aggregates: Call, Conversation
   ├─ Entities: VoiceAgent, Transcript
   └─ Anti-Corruption Layer: RetellAdapter

4. NOTIFICATION CONTEXT
   ├─ Aggregates: Notification, Campaign
   ├─ Value Objects: EmailTemplate, SMSMessage
   └─ Domain Events: NotificationSent, ReminderScheduled
```

**Ubiquitous Language:**
- "Booking" (not "Appointment Creation")
- "Slot" (not "Time Window")
- "Reservation" (Cal.com temporary hold)
- "Confirmation" (finalized booking)

---

## 4. SYSTEM-WIDE IMPROVEMENTS

### 4.1 Proposed Architecture - TO-BE

```
┌──────────────────────────────────────────────────────────────────────┐
│                  IMPROVED APPOINTMENT BOOKING SYSTEM                  │
│                  (Event-Driven + Saga + Circuit Breaker)             │
└──────────────────────────────────────────────────────────────────────┘

EXTERNAL SYSTEMS                    API GATEWAY (Laravel)
┌─────────────────┐                ┌──────────────────────┐
│ Retell.ai       │───webhook───→  │ RetellWebhook        │
│ Cal.com         │───webhook───→  │ CalcomWebhook        │
│ Filament Admin  │───HTTP────────→│ AppointmentAPI       │
└─────────────────┘                └──────────┬───────────┘
                                              │
                            ┌─────────────────▼───────────────┐
                            │  BookingOrchestrationSaga       │
                            │  (Centralized Coordinator)      │
                            │                                 │
                            │  ✅ Idempotency keys            │
                            │  ✅ Distributed transaction     │
                            │  ✅ Compensation logic          │
                            │  ✅ Event dispatch              │
                            └─────────────┬───────────────────┘
                                          │
                    ┌─────────────────────┼─────────────────────┐
                    │                     │                     │
         ┌──────────▼────────┐  ┌────────▼────────┐  ┌────────▼────────┐
         │ BookingRepository │  │ CalcomAdapter   │  │ EventBus        │
         │ (Write Side)      │  │ (Anti-Corrupt)  │  │ (Pub/Sub)       │
         │                   │  │                 │  │                 │
         │ ✅ Business rules │  │ ✅ Circuit      │  │ AppointmentBooked│
         │ ✅ Validation     │  │    Breaker      │  │ CacheInvalidate  │
         │ ✅ Persistence    │  │ ✅ Retry policy │  │ NotificationSent │
         └──────────┬────────┘  │ ✅ Fallback     │  │ SyncRequested    │
                    │           └────────┬────────┘  └────────┬────────┘
                    │                    │                     │
         ┌──────────▼────────────────────▼─────────────────────▼────────┐
         │                      EVENT LISTENERS                          │
         ├───────────────────────────────────────────────────────────────┤
         │  InvalidateCalcomCacheListener    ✅ Centralized invalidation │
         │  SyncToCalcomListener             ✅ Loop prevention         │
         │  SendNotificationListener         ✅ Async processing        │
         │  UpdateAnalyticsListener          ✅ Decoupled metrics       │
         │  RecordAuditLogListener           ✅ Compliance tracking     │
         │  ReconciliationListener           ✅ Eventual consistency    │
         └───────────────────────────────────────────────────────────────┘
                                          │
                    ┌─────────────────────┼─────────────────────┐
                    │                     │                     │
         ┌──────────▼────────┐  ┌────────▼────────┐  ┌────────▼────────┐
         │ PostgreSQL        │  │ Redis Cache     │  │ Queue (Jobs)    │
         │ (Source of Truth) │  │ (Tagged Cache)  │  │                 │
         │                   │  │                 │  │ ✅ Retry logic  │
         │ ✅ ACID           │  │ ✅ Tag-based    │  │ ✅ Dead letter  │
         │ ✅ Constraints    │  │    invalidation │  │ ✅ Monitoring   │
         │ ✅ Indexes        │  │ ✅ Multi-layer  │  │                 │
         └───────────────────┘  └─────────────────┘  └─────────────────┘

RECONCILIATION SERVICE (Background Job - Daily)
┌──────────────────────────────────────────────────────────────────────┐
│  1. Detect orphaned bookings (Cal.com exists, DB doesn't)            │
│  2. Detect drift (DB state != Cal.com state)                         │
│  3. Heal inconsistencies (auto-retry failed syncs)                   │
│  4. Alert on unrecoverable conflicts                                 │
└──────────────────────────────────────────────────────────────────────┘
```

### 4.2 Service Boundaries - Improved

```
┌─────────────────────────────────────────────────────────────────────┐
│ BOUNDED CONTEXT: BOOKING                                            │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  BookingOrchestrationSaga        ← Entry point for ALL bookings    │
│  ├─ CreateBookingCommand                                           │
│  ├─ RescheduleBookingCommand                                       │
│  └─ CancelBookingCommand                                           │
│                                                                     │
│  BookingRepository               ← Single write interface          │
│  ├─ save(Appointment)                                              │
│  ├─ findByCalcomId(string)                                         │
│  └─ findConflicting(TimeRange)                                     │
│                                                                     │
│  AvailabilityQueryService        ← Read-only interface             │
│  ├─ getSlots(date, eventTypeId)                                    │
│  └─ checkConflict(TimeRange)                                       │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│ BOUNDED CONTEXT: INTEGRATION (Cal.com)                             │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  CalcomAdapter                   ← Anti-corruption layer           │
│  ├─ reserveSlot()                ← Domain operation                │
│  ├─ confirmBooking()                                               │
│  ├─ cancelBooking()                                                │
│  └─ getAvailability()                                              │
│                                                                     │
│  CalcomCircuitBreaker            ← Resilience pattern              │
│  ├─ execute(callable)                                              │
│  ├─ fallback(callable)                                             │
│  └─ getState(): CLOSED|OPEN|HALF_OPEN                             │
│                                                                     │
│  CalcomWebhookHandler            ← Webhook ingestion               │
│  ├─ validateSignature()                                            │
│  ├─ deduplicateByIdempotencyKey()                                  │
│  └─ transformToCommand()         ← Convert to domain command       │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│ BOUNDED CONTEXT: CONSISTENCY                                        │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ReconciliationService           ← Eventual consistency            │
│  ├─ detectOrphans()                                                │
│  ├─ detectDrift()                                                  │
│  ├─ healInconsistencies()                                          │
│  └─ reportConflicts()                                              │
│                                                                     │
│  SagaCoordinator                 ← Transaction orchestration       │
│  ├─ startSaga(SagaDefinition)                                      │
│  ├─ recordStep(StepResult)                                         │
│  ├─ compensate(SagaLog)                                            │
│  └─ markComplete()                                                 │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│ BOUNDED CONTEXT: CACHING                                            │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  CacheManager                    ← Centralized cache control       │
│  ├─ get(key, callable)                                             │
│  ├─ invalidate(tags[])           ← Tag-based invalidation         │
│  ├─ invalidatePattern(pattern)                                     │
│  └─ clear()                                                        │
│                                                                     │
│  CacheInvalidationListener       ← Event-driven invalidation      │
│  ├─ onBookingCreated(event)      ← Invalidate availability cache  │
│  ├─ onBookingRescheduled(event)                                    │
│  └─ onBookingCancelled(event)                                      │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 5. CONSISTENCY STRATEGY

### 5.1 Consistency Model: Eventual Consistency

**Rationale:**
- Cal.com is external system (no distributed transaction support)
- Business domain tolerates temporary inconsistency (seconds, not minutes)
- Strong consistency would require 2PC (too slow, not supported)

**Implementation:**

```php
/**
 * Eventual Consistency Guarantee
 *
 * 1. Local DB is ALWAYS written first (source of truth for booking intent)
 * 2. Cal.com sync is ASYNC via queue (eventual consistency)
 * 3. Reconciliation service detects drift (daily + on-demand)
 * 4. Manual review queue for unrecoverable conflicts
 */

// WRITE PATH (Optimistic)
DB::transaction(function() {
    $appointment = Appointment::create([...]);

    // Dispatch event immediately (within transaction)
    event(new AppointmentBooked($appointment));

    return $appointment;
});

// Event listener queues sync job
class SyncToCalcomListener
{
    public function handle(AppointmentBooked $event): void
    {
        // Skip if origin is calcom (prevent loop)
        if ($event->appointment->sync_origin === 'calcom') {
            return;
        }

        // Queue job with exponential backoff
        dispatch(new SyncToCalcomJob($event->appointment, 'create'))
            ->onQueue('calcom-sync')
            ->backoff([1, 5, 30, 120, 600]); // Up to 10min retry
    }
}

// RECONCILIATION (Pessimistic)
class ReconciliationService
{
    public function reconcile(): ReconciliationReport
    {
        $report = new ReconciliationReport();

        // Find appointments in DB but not in Cal.com
        $orphans = Appointment::where('calcom_sync_status', 'failed')
            ->where('created_at', '>', now()->subDays(7))
            ->get();

        foreach ($orphans as $appointment) {
            $calcomBooking = $this->calcomService->findBooking(
                $appointment->calcom_v2_booking_id
            );

            if (!$calcomBooking) {
                // Attempt to create in Cal.com
                $result = $this->healOrphan($appointment);
                $report->recordHealing($appointment, $result);
            } else {
                // Update local sync status
                $appointment->update(['calcom_sync_status' => 'synced']);
                $report->recordFixed($appointment);
            }
        }

        return $report;
    }
}
```

### 5.2 Idempotency Strategy

**Problem:** Webhooks can be delivered multiple times.

**Solution:** Idempotency keys + database unique constraints.

```php
// Migration
Schema::table('appointments', function (Blueprint $table) {
    $table->string('idempotency_key', 64)->unique()->nullable();
    $table->index(['calcom_v2_booking_id', 'idempotency_key']);
});

// CalcomWebhookController
public function handle(CalcomWebhookRequest $request): JsonResponse
{
    $idempotencyKey = $request->header('X-Cal-Idempotency-Key')
                   ?? $this->generateIdempotencyKey($request);

    // Check if already processed
    $existing = Appointment::where('idempotency_key', $idempotencyKey)->first();
    if ($existing) {
        Log::info('Webhook already processed (idempotent)', [
            'idempotency_key' => $idempotencyKey,
            'appointment_id' => $existing->id
        ]);

        return response()->json([
            'received' => true,
            'status' => 'already_processed',
            'appointment_id' => $existing->id
        ]);
    }

    // Process webhook (within DB transaction for atomicity)
    $appointment = DB::transaction(function() use ($request, $idempotencyKey) {
        $appointment = $this->handleBookingCreated($request->sanitized());
        $appointment->update(['idempotency_key' => $idempotencyKey]);
        return $appointment;
    });

    return response()->json(['received' => true, 'status' => 'processed']);
}

private function generateIdempotencyKey(Request $request): string
{
    // Hash of webhook payload to detect duplicates
    return hash('sha256', json_encode([
        'event' => $request->input('triggerEvent'),
        'booking_id' => $request->input('payload.id'),
        'timestamp' => $request->input('payload.createdAt'),
    ]));
}
```

### 5.3 Conflict Resolution Strategy

**Scenarios:**

| Conflict | Detection | Resolution |
|----------|-----------|------------|
| DB has appointment, Cal.com doesn't | Reconciliation job | Create in Cal.com (heal) |
| Cal.com has booking, DB doesn't | Webhook creates it | Normal flow |
| Both exist but different times | Drift detector | Manual review (alert admin) |
| Both cancelled in different systems | Reconciliation | Mark both as cancelled |
| Booking in progress during reschedule | Pessimistic locking | Queue reschedule, retry |

---

## 6. SCALABILITY PLAN

### 6.1 Current Bottlenecks

1. **Synchronous Cal.com API calls** - Blocks request thread
2. **No connection pooling** - HTTP client creates new connection per request
3. **Cache misses on high load** - 5-minute TTL too long
4. **N+1 queries** - Appointment loads customer, service, staff separately
5. **No read replicas** - All queries hit master DB

### 6.2 Scalability Improvements

```
┌─────────────────────────────────────────────────────────────────────┐
│ HORIZONTAL SCALING (1000 concurrent bookings)                       │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  Load Balancer (NGINX)                                             │
│  ├─ Health checks every 5s                                         │
│  ├─ Sticky sessions (if needed)                                    │
│  └─ Rate limiting (100 req/sec per IP)                             │
│                                                                     │
│  Application Servers (Auto-scaling 2-10 instances)                 │
│  ├─ Laravel Octane (FrankenPHP/Swoole)                            │
│  ├─ Connection pooling (10 DB connections per server)             │
│  ├─ Async queue workers (separate from web)                       │
│  └─ Circuit breaker per instance                                  │
│                                                                     │
│  Database Layer                                                    │
│  ├─ PostgreSQL Master (writes only)                               │
│  ├─ Read Replicas (2-3 instances)                                 │
│  │  └─ Availability queries use replicas                          │
│  ├─ Connection pooling (PgBouncer)                                │
│  └─ Partitioning (by date for appointments)                       │
│                                                                     │
│  Cache Layer (Redis Cluster)                                      │
│  ├─ 3-node cluster (HA)                                           │
│  ├─ Tag-based invalidation                                        │
│  ├─ Reduced TTL (60s instead of 300s)                             │
│  └─ Cache warming on deploy                                       │
│                                                                     │
│  Queue Workers (Separate containers)                               │
│  ├─ Horizon (monitoring)                                           │
│  ├─ 5 workers for calcom-sync queue                               │
│  ├─ 3 workers for notifications queue                             │
│  └─ Circuit breaker per worker                                    │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### 6.3 Database Optimization

```sql
-- Add covering indexes for hot queries
CREATE INDEX idx_appointments_calcom_lookup
ON appointments(calcom_v2_booking_id, company_id, sync_origin)
INCLUDE (id, starts_at, status);

-- Add composite index for availability checks
CREATE INDEX idx_appointments_availability
ON appointments(service_id, starts_at, ends_at, status)
WHERE status IN ('scheduled', 'confirmed');

-- Partition appointments table by month
CREATE TABLE appointments_2025_10 PARTITION OF appointments
FOR VALUES FROM ('2025-10-01') TO ('2025-11-01');

-- Add GIN index for JSONB metadata search
CREATE INDEX idx_appointments_metadata_gin
ON appointments USING GIN(metadata);
```

### 6.4 Caching Strategy

```php
/**
 * Multi-Layer Caching with Tagging
 */

// Layer 1: Application-level cache (Redis)
Cache::tags(['calcom', 'availability', "event:{$eventTypeId}"])
    ->remember("slots:{$eventTypeId}:{$date}", 60, function() {
        // Layer 2: HTTP cache (Cal.com CDN)
        return $this->calcomClient->getSlots($eventTypeId, $date);
    });

// Invalidation via tags
Cache::tags(['calcom', "event:{$eventTypeId}"])->flush();

// Cache warming on deploy
Artisan::command('cache:warm', function() {
    $eventTypes = Service::pluck('calcom_event_type_id');
    $dates = CarbonPeriod::create(today(), '+30 days');

    foreach ($eventTypes as $eventTypeId) {
        foreach ($dates as $date) {
            Cache::tags(['calcom', "event:{$eventTypeId}"])
                ->put("slots:{$eventTypeId}:{$date->format('Y-m-d')}",
                      $this->fetchSlots($eventTypeId, $date),
                      60
                );
        }
    }
});
```

---

## 7. RESILIENCE PATTERNS

### 7.1 Circuit Breaker Implementation

```php
/**
 * Circuit Breaker for Cal.com API
 *
 * States:
 * - CLOSED: Normal operation
 * - OPEN: Failing, reject requests immediately
 * - HALF_OPEN: Testing if service recovered
 */

use Illuminate\Support\Facades\Cache;

class CalcomCircuitBreaker
{
    private const FAILURE_THRESHOLD = 5;      // Open after 5 failures
    private const TIMEOUT_SECONDS = 60;       // Stay open for 60s
    private const SUCCESS_THRESHOLD = 2;      // Close after 2 successes

    private string $serviceName;

    public function __construct(string $serviceName = 'calcom')
    {
        $this->serviceName = $serviceName;
    }

    public function execute(callable $operation, ?callable $fallback = null): mixed
    {
        $state = $this->getState();

        if ($state === 'OPEN') {
            Log::warning('Circuit breaker OPEN, rejecting request', [
                'service' => $this->serviceName
            ]);

            if ($fallback) {
                return $fallback();
            }

            throw new CircuitBreakerOpenException("Service {$this->serviceName} is unavailable");
        }

        try {
            $result = $operation();
            $this->recordSuccess();
            return $result;

        } catch (\Exception $e) {
            $this->recordFailure();

            if ($fallback && $this->getState() === 'OPEN') {
                return $fallback();
            }

            throw $e;
        }
    }

    private function getState(): string
    {
        $failures = Cache::get("circuit:{$this->serviceName}:failures", 0);
        $openedAt = Cache::get("circuit:{$this->serviceName}:opened_at");

        // Check if circuit should transition from OPEN to HALF_OPEN
        if ($openedAt && now()->diffInSeconds($openedAt) > self::TIMEOUT_SECONDS) {
            Cache::put("circuit:{$this->serviceName}:state", 'HALF_OPEN', 300);
            return 'HALF_OPEN';
        }

        // Check if circuit should open
        if ($failures >= self::FAILURE_THRESHOLD) {
            Cache::put("circuit:{$this->serviceName}:state", 'OPEN', 300);
            Cache::put("circuit:{$this->serviceName}:opened_at", now(), 300);
            return 'OPEN';
        }

        return Cache::get("circuit:{$this->serviceName}:state", 'CLOSED');
    }

    private function recordSuccess(): void
    {
        $state = $this->getState();

        if ($state === 'HALF_OPEN') {
            $successes = Cache::increment("circuit:{$this->serviceName}:successes");

            if ($successes >= self::SUCCESS_THRESHOLD) {
                // Transition to CLOSED
                Cache::put("circuit:{$this->serviceName}:state", 'CLOSED', 300);
                Cache::forget("circuit:{$this->serviceName}:failures");
                Cache::forget("circuit:{$this->serviceName}:successes");
                Cache::forget("circuit:{$this->serviceName}:opened_at");

                Log::info('Circuit breaker CLOSED (recovered)', [
                    'service' => $this->serviceName
                ]);
            }
        } else {
            // Reset failure count on success
            Cache::forget("circuit:{$this->serviceName}:failures");
        }
    }

    private function recordFailure(): void
    {
        Cache::increment("circuit:{$this->serviceName}:failures");

        $failures = Cache::get("circuit:{$this->serviceName}:failures", 0);

        if ($failures >= self::FAILURE_THRESHOLD) {
            Log::error('Circuit breaker OPENED (failure threshold reached)', [
                'service' => $this->serviceName,
                'failures' => $failures
            ]);
        }
    }
}

// Usage in CalcomService
class CalcomService
{
    private CalcomCircuitBreaker $circuitBreaker;

    public function __construct()
    {
        $this->circuitBreaker = new CalcomCircuitBreaker('calcom');
    }

    public function createBooking(array $data): Response
    {
        return $this->circuitBreaker->execute(
            operation: fn() => $this->client->post('/bookings', $data),
            fallback: fn() => $this->queueForLater($data)
        );
    }

    private function queueForLater(array $data): Response
    {
        dispatch(new SyncToCalcomJob($data, 'create'))
            ->delay(now()->addMinutes(5));

        return new Response(['status' => 'queued', 'message' => 'Cal.com unavailable, queued for retry']);
    }
}
```

### 7.2 Bulkhead Pattern

```php
/**
 * Isolate failures - Cal.com issues don't block other operations
 */

// Separate queue for Cal.com operations
config/queue.php:
'connections' => [
    'calcom-sync' => [
        'driver' => 'redis',
        'connection' => 'calcom-queue',
        'queue' => 'calcom-sync',
        'retry_after' => 90,
        'block_for' => null,
    ],
    'notifications' => [
        'driver' => 'redis',
        'connection' => 'notifications-queue',
        'queue' => 'notifications',
        'retry_after' => 60,
    ],
];

// Separate worker processes
supervisor:
[program:calcom-worker]
command=php artisan queue:work calcom-sync --tries=3 --backoff=1,5,30
numprocs=3

[program:notification-worker]
command=php artisan queue:work notifications --tries=5
numprocs=5
```

### 7.3 Retry Policy with Exponential Backoff

```php
/**
 * Intelligent retry strategy
 */

class SyncToCalcomJob implements ShouldQueue
{
    public int $tries = 5;
    public array $backoff = [1, 5, 30, 120, 600]; // 1s, 5s, 30s, 2min, 10min
    public int $maxExceptions = 3;

    public function retryUntil(): DateTime
    {
        // Give up after 24 hours
        return now()->addDay();
    }

    public function backoff(): array
    {
        return $this->backoff;
    }

    public function failed(\Throwable $exception): void
    {
        // All retries exhausted - escalate
        Log::critical('Sync permanently failed', [
            'appointment_id' => $this->appointment->id,
            'error' => $exception->getMessage()
        ]);

        // Mark for manual intervention
        $this->appointment->update([
            'requires_manual_review' => true,
            'sync_error' => $exception->getMessage()
        ]);

        // Alert team
        app(AlertService::class)->sendCritical(
            'Cal.com sync failed after all retries',
            ['appointment' => $this->appointment]
        );
    }
}
```

---

## 8. MIGRATION PLAN

### Phase 1: Critical Fixes (Week 1) - NO BREAKING CHANGES

**Priority: CRITICAL**

```
✅ Fix 1: Cache Invalidation (IMMEDIATE)
─────────────────────────────────────────────────────────────────
1. Extract clearAvailabilityCacheForEventType() to public method
2. Add cache invalidation to ALL webhook handlers
3. Add cache invalidation to reschedule/cancel methods
4. Deploy as hotfix

Effort: 2 hours dev + 1 hour testing
Risk: LOW (only adds cache invalidation)
```

```
✅ Fix 2: Idempotency Keys (DAY 2-3)
─────────────────────────────────────────────────────────────────
1. Add idempotency_key column to appointments table
2. Update CalcomWebhookController to check/set idempotency
3. Add unique constraint on (calcom_v2_booking_id, idempotency_key)
4. Deploy with monitoring

Effort: 4 hours dev + 2 hours testing
Risk: LOW (backward compatible, nullable column)
```

```
✅ Fix 3: Missing Schema Columns (DAY 4)
─────────────────────────────────────────────────────────────────
1. Add created_by, booking_source columns to appointments
2. Add rescheduled_by, cancelled_by columns
3. Update AppointmentCreationService to populate
4. Run migration

Effort: 2 hours dev + 1 hour testing
Risk: LOW (adds columns, doesn't modify existing)
```

### Phase 2: Event-Driven Architecture (Week 2-3) - REFACTORING

**Priority: HIGH**

```
✅ Task 1: Centralize Event Dispatch
─────────────────────────────────────────────────────────────────
1. Create BookingOrchestrator service
2. Make it single entry point for ALL bookings
3. Dispatch events from orchestrator only
4. Migrate CalcomWebhookController to use orchestrator
5. Migrate AppointmentCreationService to use orchestrator

Effort: 3 days dev + 2 days testing
Risk: MEDIUM (requires refactoring, extensive testing)
```

```
✅ Task 2: Implement Cache Tagging
─────────────────────────────────────────────────────────────────
1. Enable Redis tagging in config
2. Tag all cache entries with [calcom, event:{id}]
3. Update CacheInvalidationListener to use tags
4. Remove loop-based invalidation

Effort: 1 day dev + 1 day testing
Risk: LOW (doesn't change cache behavior)
```

### Phase 3: Saga Pattern (Week 4-5) - MAJOR REFACTOR

**Priority: HIGH**

```
✅ Task 1: Saga Infrastructure
─────────────────────────────────────────────────────────────────
1. Create SagaLog model + migration
2. Create SagaCoordinator service
3. Define BookingSaga steps + compensations
4. Implement step recording + rollback

Effort: 5 days dev + 3 days testing
Risk: HIGH (complex, requires careful design)
```

```
✅ Task 2: Migrate Booking Flow to Saga
─────────────────────────────────────────────────────────────────
1. Wrap existing flow in saga steps
2. Add compensation logic for each step
3. Test partial failure scenarios
4. Gradual rollout (10% → 50% → 100%)

Effort: 5 days dev + 5 days testing
Risk: HIGH (mission critical flow)
```

### Phase 4: Circuit Breaker (Week 6)

**Priority: MEDIUM**

```
✅ Task 1: Circuit Breaker Implementation
─────────────────────────────────────────────────────────────────
1. Implement CalcomCircuitBreaker class
2. Wrap all Cal.com API calls
3. Add fallback handlers (queue for later)
4. Monitor state transitions

Effort: 3 days dev + 2 days testing
Risk: MEDIUM (changes error handling)
```

### Phase 5: Reconciliation Service (Week 7-8)

**Priority: MEDIUM**

```
✅ Task 1: Drift Detection
─────────────────────────────────────────────────────────────────
1. Create ReconciliationService
2. Implement orphan detection (DB but not Cal.com)
3. Implement drift detection (different states)
4. Schedule daily job

Effort: 4 days dev + 2 days testing
Risk: LOW (read-only analysis)
```

```
✅ Task 2: Auto-Healing
─────────────────────────────────────────────────────────────────
1. Implement healing logic for orphans
2. Retry failed syncs automatically
3. Alert on unrecoverable conflicts
4. Manual review dashboard

Effort: 5 days dev + 3 days testing
Risk: MEDIUM (writes to both systems)
```

### Phase 6: CQRS (Week 9-12) - LONG TERM

**Priority: LOW (Optional, for scale)**

```
✅ Task 1: Separate Read/Write Models
─────────────────────────────────────────────────────────────────
1. Create AvailabilityQueryService (read-only)
2. Create BookingCommandService (write-only)
3. Materialized views for reporting
4. Read replicas for queries

Effort: 10 days dev + 5 days testing
Risk: MEDIUM (architectural change)
```

---

## 9. MONITORING & OBSERVABILITY

### 9.1 Key Metrics to Track

```php
// APM Metrics (New Relic / Datadog)

1. Booking Success Rate
   - Total bookings attempted
   - Successful bookings (DB + Cal.com synced)
   - Failed bookings (DB only or neither)
   - Target: >99% success rate

2. Sync Latency
   - Time from DB write to Cal.com confirmation
   - P50, P95, P99 latencies
   - Target: P95 < 5 seconds

3. Cache Hit Ratio
   - Cache hits / total requests
   - Per layer (CalcomService vs AlternativeFinder)
   - Target: >80% hit rate

4. Circuit Breaker State
   - Time in OPEN state
   - Transitions per hour
   - Failure rate before opening
   - Target: CLOSED >99.5% of time

5. Webhook Processing Time
   - Latency from webhook receipt to processing complete
   - Idempotency collision rate
   - Target: <500ms processing time

6. Reconciliation Metrics
   - Orphaned bookings detected
   - Healed automatically
   - Requiring manual review
   - Target: <1% orphan rate

7. Queue Metrics
   - Job wait time (time in queue)
   - Job processing time
   - Failed jobs
   - Retry rate
   - Target: Job wait <10s, retry <5%
```

### 9.2 Alerts

```yaml
# Example alert configuration

- name: high_booking_failure_rate
  condition: booking_failure_rate > 5%
  window: 5 minutes
  severity: critical
  notify: [slack, pagerduty]

- name: circuit_breaker_open
  condition: circuit_breaker_state == OPEN
  window: 1 minute
  severity: critical
  notify: [slack, pagerduty]

- name: high_sync_latency
  condition: sync_latency_p95 > 10 seconds
  window: 10 minutes
  severity: warning
  notify: [slack]

- name: orphaned_bookings_detected
  condition: orphaned_bookings_count > 10
  window: 1 hour
  severity: warning
  notify: [slack]

- name: cache_hit_ratio_low
  condition: cache_hit_ratio < 60%
  window: 15 minutes
  severity: warning
  notify: [slack]
```

---

## 10. CONCLUSION & RECOMMENDATIONS

### 10.1 Critical Path Forward

**IMMEDIATE (Deploy by EOD Today):**
1. ✅ Fix cache invalidation in webhook handlers (2 hours)
2. ✅ Add missing schema columns (created_by, etc.) (2 hours)
3. ✅ Deploy hotfix to production

**THIS WEEK:**
1. ✅ Implement idempotency keys for webhooks (1 day)
2. ✅ Add circuit breaker to Cal.com calls (2 days)
3. ✅ Implement cache tagging (1 day)

**NEXT 2 WEEKS:**
1. ✅ Refactor to event-driven architecture (1 week)
2. ✅ Implement saga pattern for bookings (1 week)

**NEXT MONTH:**
1. ✅ Build reconciliation service (1 week)
2. ✅ Implement auto-healing (1 week)
3. ✅ Add comprehensive monitoring (1 week)

### 10.2 Architecture Decision Records (ADRs)

**ADR-001: Use Eventual Consistency over Strong Consistency**
- Context: Distributed system with external Cal.com dependency
- Decision: Accept eventual consistency with reconciliation
- Rationale: Cal.com doesn't support 2PC, business tolerates seconds of delay
- Consequences: Must implement reconciliation service

**ADR-002: Use Saga Pattern over Distributed Transactions**
- Context: Multi-step booking process (DB → Cal.com → Notifications)
- Decision: Implement orchestration saga with compensation
- Rationale: No distributed transaction support, need rollback capability
- Consequences: More complex code, better error handling

**ADR-003: Use Event-Driven Architecture for Cross-Cutting Concerns**
- Context: Cache invalidation, notifications, analytics needed across entry points
- Decision: Dispatch domain events, listeners handle side effects
- Rationale: Decouples services, easier to extend
- Consequences: Eventual consistency, harder to debug

**ADR-004: Use Circuit Breaker for Cal.com API**
- Context: Cal.com API has documented failures, can cascade
- Decision: Wrap all Cal.com calls in circuit breaker
- Rationale: Prevent cascade failures, provide fallback
- Consequences: Must implement fallback strategies

### 10.3 Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Double bookings during migration | MEDIUM | CRITICAL | Gradual rollout, extensive testing, rollback plan |
| Circuit breaker false positives | LOW | MEDIUM | Tune thresholds, monitor state transitions |
| Saga compensation failures | LOW | HIGH | Manual review queue, alerts, retry logic |
| Cache invalidation over-aggressive | LOW | LOW | Monitor cache hit ratio, tune TTL |
| Reconciliation creates duplicates | LOW | HIGH | Idempotency keys, dry-run mode first |

### 10.4 Success Criteria

**Phase 1 Success:**
- ✅ ZERO double bookings in 1 week
- ✅ Cache invalidation works in ALL entry points
- ✅ Webhook idempotency prevents duplicates

**Phase 2 Success:**
- ✅ 99%+ booking success rate
- ✅ <5s P95 sync latency
- ✅ Events dispatched consistently

**Phase 3 Success:**
- ✅ Saga handles partial failures gracefully
- ✅ Automatic compensation works
- ✅ Manual review queue < 1% of bookings

**Long-Term Success:**
- ✅ System handles 1000 concurrent bookings
- ✅ <0.1% orphaned bookings
- ✅ Auto-healing resolves >95% of inconsistencies
- ✅ Circuit breaker prevents cascade failures

---

## 11. APPENDICES

### Appendix A: Current vs Proposed Comparison

| Aspect | Current State | Proposed State |
|--------|---------------|----------------|
| **Entry Points** | 7 uncoordinated entry points | 1 orchestrator (BookingSaga) |
| **Consistency** | None (dual-write, no checks) | Eventual (with reconciliation) |
| **Cache Invalidation** | 2/7 entry points only | 100% coverage via events |
| **Idempotency** | None | Webhook idempotency keys |
| **Transaction Handling** | No distributed transactions | Saga pattern with compensation |
| **Error Handling** | Retry only (SyncJob) | Circuit breaker + saga + retry |
| **Monitoring** | Basic logs | APM metrics + alerts + dashboards |
| **Scalability** | <100 concurrent bookings | 1000+ concurrent bookings |
| **Data Consistency** | Manual reconciliation | Auto-healing + drift detection |

### Appendix B: Code Examples

**See inline code examples throughout sections 4-7**

### Appendix C: Testing Strategy

```php
/**
 * Critical test scenarios
 */

1. Test_Webhook_Idempotency:
   - Send same webhook twice
   - Assert only one appointment created
   - Assert second returns "already_processed"

2. Test_Saga_Compensation:
   - Trigger booking saga
   - Fail at step 3 (staff assignment)
   - Assert steps 1-2 compensated (Cal.com booking cancelled)

3. Test_Circuit_Breaker:
   - Simulate Cal.com downtime (5 failures)
   - Assert circuit opens
   - Assert requests rejected immediately
   - Simulate recovery (2 successes in HALF_OPEN)
   - Assert circuit closes

4. Test_Cache_Invalidation_All_Paths:
   - Test createBooking() invalidates cache
   - Test webhook invalidates cache
   - Test reschedule invalidates cache
   - Test cancel invalidates cache

5. Test_Concurrent_Bookings:
   - Simulate 100 concurrent booking requests
   - Assert no race conditions
   - Assert no duplicate Cal.com bookings
   - Assert all DB writes succeed

6. Test_Reconciliation_Orphan_Detection:
   - Create appointment in DB only (simulate failed sync)
   - Run reconciliation service
   - Assert orphan detected
   - Assert auto-heal creates Cal.com booking

7. Test_Reconciliation_Drift_Detection:
   - Create appointment in both systems with different times
   - Run drift detector
   - Assert conflict detected
   - Assert flagged for manual review
```

---

**END OF ARCHITECTURE REVIEW**

**Next Steps:**
1. Review this document with team
2. Prioritize fixes based on business impact
3. Begin Phase 1 implementation immediately
4. Schedule weekly architecture review meetings
5. Update ADRs as decisions are made

**Questions/Feedback:** Please add comments or schedule architecture review session.
