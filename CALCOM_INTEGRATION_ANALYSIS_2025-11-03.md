# Cal.com Integration Analysis - AskPro AI Gateway

## Executive Summary

**Analysis Date:** 2025-11-03  
**Scope:** Comprehensive Cal.com integration audit (v2 API, webhooks, bidirectional sync)  
**Overall Status:** ✅ **SUBSTANTIALLY IMPLEMENTED** with strategic gaps

### Key Findings

| Component | Status | Completeness | Notes |
|-----------|--------|--------------|-------|
| Webhook Handling | ✅ Complete | 100% | BOOKING_CREATED, RESCHEDULED, CANCELLED all implemented |
| Availability Checking | ✅ Complete | 95% | Cached implementation, multi-tier support |
| Booking Creation | ✅ Complete | 100% | V2 API with retry logic and error handling |
| Rescheduling | ⚠️ Partial | 70% | Listener disabled pending migrations |
| Cancellation | ✅ Complete | 100% | Full implementation with compensation |
| Team/Event Type Mappings | ✅ Complete | 90% | Framework complete, test data incomplete |
| Idempotency Handling | ✅ Complete | 100% | 2-tier Redis+DB cache with 24h TTL |
| Loop Prevention | ✅ Complete | 100% | sync_origin tracking prevents infinite loops |
| Configuration | ⚠️ Partial | 50% | Infrastructure ready, Friseur 1 data missing |

**CRITICAL GAPS:**
- E2E test data incomplete (missing Cal.com Event Type IDs)
- Cal.com reschedule listener temporarily disabled
- Configuration mismatch for Friseur 1 company
- Missing 13/16 Friseur services + their Event ID mappings

---

## 1. WHAT IS IMPLEMENTED

### 1.1 Webhook Handling (CalcomWebhookController)

**File:** `/app/Http/Controllers/CalcomWebhookController.php`

#### Supported Events
✅ **BOOKING.CREATED** - Full implementation
- Extracts booking metadata (attendee, times, phone number)
- Creates/Updates local appointment with security verification
- Assigns staff via multi-model assignment system
- Supports multi-branch via service or staff home branch
- Cache invalidation after creation

✅ **BOOKING.RESCHEDULED / BOOKING.UPDATED** - Full implementation
- Finds existing appointment by calcom_v2_booking_id
- Updates start/end times with audit trail
- Tracks rescheduling metadata (rescheduled_at, rescheduled_by, previous_starts_at)
- Cache invalidation after reschedule

✅ **BOOKING.CANCELLED** - Full implementation
- Soft deletes appointments (status = 'cancelled')
- Captures cancellation reason
- Populates cancelled_at, cancelled_by, cancellation_source fields
- Cache invalidation to restore availability

✅ **EVENT_TYPE.CREATED/UPDATED/DELETED** - Async job dispatch
- Creates ImportEventTypeJob for async processing
- Handles service creation/update from Cal.com
- Gracefully handles missing services (creates new ones)

✅ **PING** - Health check support
- Simple 200 OK response for Cal.com connectivity verification

#### Security Features

🛡️ **Webhook Ownership Verification (VULN-001 FIX)**
```php
verifyWebhookOwnership($payload) // Checks Service.calcom_event_type_id
→ Prevents cross-tenant attacks
→ Enforces company_id isolation
```

🛡️ **Signature Verification**
- Middleware: `VerifyCalcomSignature`
- Validates HMAC-SHA256 signatures
- Supports multiple header variants (X-Cal-Signature-256, Cal-Signature-256, etc.)

🛡️ **Tenant Isolation**
- All webhook-created appointments scoped to verified company_id
- `where('company_id', $expectedCompanyId)` enforces isolation

#### Routes

```php
Route::post('/api/calcom/webhook', [CalcomWebhookController::class, 'handle'])
  →middleware(['calcom.signature', 'throttle:60,1'])
Route::post('/api/webhooks/calcom', [CalcomWebhookController::class, 'handle'])
  →middleware(['calcom.signature', 'throttle:60,1'])
```

**Rate Limiting:** 60 requests/minute (Cal.com sends ~1-2 per booking lifecycle)

---

### 1.2 Bidirectional Sync System

#### Sync Origin Tracking (Loop Prevention)

✅ **Implementation:** `sync_origin` field on appointments table
- `'calcom'` = Booking came from Cal.com webhook (don't sync back)
- `'system'` = Booking created locally (sync to Cal.com)
- `null` = Legacy/unknown origin

**Applied in:**
- CalcomWebhookController: Sets `sync_origin = 'calcom'` on webhook bookings
- SyncToCalcomOnBooked listener: Checks `sync_origin === 'calcom'` → skips sync
- SyncAppointmentToCalcomJob: Double-checks in `shouldSkipSync()`

#### Listener Pattern

**File:** `/app/Listeners/Appointments/`

✅ **SyncToCalcomOnBooked.php** - FULLY IMPLEMENTED
```php
Trigger: AppointmentBooked event
Logic:
  1. Check sync_origin (skip if 'calcom')
  2. Check service.calcom_event_type_id (skip if missing)
  3. Check calcom_sync_status (skip if 'synced' or 'pending')
  4. Dispatch SyncAppointmentToCalcomJob('create')
```

⚠️ **SyncToCalcomOnRescheduled.php** - TEMPORARILY DISABLED
```php
Status: Disabled due to missing database columns
Missing Columns:
  - sync_job_id
  - calcom_sync_status
  - sync_verified_at
  - previous_starts_at
  - rescheduled_at
  - rescheduled_by
  - reschedule_source
Logic in code but commented out with early return
```

✅ **SyncToCalcomOnCancelled.php** - FULLY IMPLEMENTED
```php
Trigger: AppointmentCancelled event
Logic:
  1. Check sync_origin (skip if 'calcom')
  2. Check calcom_v2_booking_id (skip if missing)
  3. Check service.calcom_event_type_id (skip if missing)
  4. Dispatch SyncAppointmentToCalcomJob('cancel')
```

#### Job Implementation

**File:** `/app/Jobs/SyncAppointmentToCalcomJob.php`

✅ **Features:**
- **Retry Logic:** 3 attempts with exponential backoff [1s, 5s, 30s]
- **Pessimistic Locking:** `lockForUpdate()` to prevent race conditions
- **Action Types:** 'create', 'cancel', 'reschedule'
- **State Tracking:** sync_job_id, calcom_sync_status, sync_verified_at, sync_error_*
- **Manual Review Flagging:** Sets requires_manual_review after 3 failed retries
- **Race Condition Fix (RC3):** Locks appointment row during sync

**Status Updates:**
| Status | When | Next Status |
|--------|------|------------|
| `pending` | Job dispatched | `synced` or `failed` |
| `synced` | API call successful | (no change for 30s) |
| `failed` | All retries exhausted | Manual review required |

---

### 1.3 Availability Checking

**File:** `/app/Services/Appointments/CalcomAvailabilityService.php`

✅ **Full Implementation:**
```php
getAvailabilityForWeek(
  serviceId: string,
  weekStart: Carbon,
  durationMinutes: int = 45,
  staffId?: string
): array
```

**Features:**
- Validates Monday start date, normalizes to week boundaries
- Multi-tier caching (5-min Redis TTL)
- Handles service→Cal.com event type lookup
- Transforms Cal.com time slots to weekly calendar grid
- Duration-aware: Filters slots that can't fit requested duration
- Timezone handling: Europe/Berlin with fallback

**Response Format:**
```php
[
  'monday' => [
    [
      'time' => '09:00',
      'full_datetime' => '2025-10-20T09:00:00+02:00',
      'availability' => 'available',
      'duration_ok' => true
    ],
    ...
  ],
  'tuesday' => [...],
  ...
]
```

---

### 1.4 Team & Event Type Mappings

#### CalcomHostMapping Model
**File:** `/app/Models/CalcomHostMapping.php`

✅ Maps Cal.com user IDs → internal staff records
- Fields: calcom_host_id, staff_id, calcom_name, calcom_email, etc.
- Multi-tenant isolation via BelongsToCompany trait
- Confidence scoring for fuzzy matches
- Audit trail via CalcomHostMappingAudit

#### CalcomEventMap Model
**File:** `/app/Models/CalcomEventMap.php`

✅ Maps internal services → Cal.com event types
- Fields: event_type_id, event_type_slug, service_id, staff_id, segment_key
- Drift detection (external_changes, drift_data, drift_detected_at)
- Sync status tracking (pending/synced/error)
- Auto-generates event names: `COMPANY-BRANCH-SERVICE-SEGMENT-STAFF`

#### Host Mapping Service
**File:** `/app/Services/CalcomHostMappingService.php`

✅ Resolves Cal.com hosts to staff
- Strategy pattern: EmailMatchingStrategy, NameMatchingStrategy
- Confidence-based auto-matching (75% threshold)
- Fallback to manual mapping if low confidence
- Persistent storage with audit trail

---

### 1.5 Idempotency & Deduplication

**File:** `/app/Services/Idempotency/IdempotencyCache.php`

✅ **2-Tier Caching System:**

```
Request comes in
  ↓
Check Redis Cache (fast, <1ms) 
  ↓ (cache hit) → Return cached appointment ID
  ↓ (cache miss)
Check Database (slow, 5-50ms)
  ↓ (DB hit) → Re-populate Redis, return ID
  ↓ (DB miss) → New request, process booking
  ↓
Cache result in Redis + DB (via idempotency_key field)
```

✅ **API:**
- `getIfProcessed(idempotencyKey)`: Returns appointment ID if duplicate
- `cacheResult(idempotencyKey, appointmentId)`: Stores result
- `isWebhookProcessed(webhookId)`: Checks webhook_events table
- `markWebhookProcessed(webhookId, eventId)`: Records in webhook_events

✅ **TTL:** 24 hours (industry standard for idempotency window)

---

### 1.6 Availability Caching & Cache Invalidation

✅ **Cache Strategy:**

**In CalcomWebhookController:**
```php
// After BOOKING.CREATED webhook
app(CalcomService::class)->clearAvailabilityCacheForEventType(
  $service->calcom_event_type_id
);

// After BOOKING.RESCHEDULED webhook
// After BOOKING.CANCELLED webhook
```

**Cache Keys:** `company:{id}:{resource}:{identifier}`
**TTL:** 5 minutes (availability), 1 hour (config)

✅ **Non-blocking Invalidation:**
- Wrapped in try-catch
- Logs warnings if cache invalidation fails
- Doesn't fail webhook processing

---

### 1.7 Error Handling & Resilience

✅ **Circuit Breaker Pattern**
```php
// In CalcomService.__construct()
$this->circuitBreaker = new CircuitBreaker(
  serviceName: 'calcom_api',
  failureThreshold: 5,
  recoveryTimeout: 60,
  successThreshold: 2
);
```
- Opens after 5 failures, recovers after 60s of 2 successes

✅ **Retry Logic**
- Job: 3 attempts with exponential backoff
- HTTP Client: Automatic retry on 409 Conflict, 429 Rate Limit

✅ **Graceful Degradation**
- Missing Cal.com event type → Skips sync, logs warning
- Timeout → Retries up to 3x, then flags for manual review
- Partial failure → Job marked failed, appointment flagged

---

### 1.8 V2 API Implementation

**File:** `/app/Services/CalcomV2Client.php`

✅ **Endpoints Implemented:**

| Endpoint | Method | Purpose | Status |
|----------|--------|---------|--------|
| `/v2/slots/available` | GET | Availability query | ✅ |
| `/v2/bookings` | POST | Create booking | ✅ |
| `/v2/bookings/{id}` | PATCH | Update booking | ✅ |
| `/v2/bookings/{id}/cancel` | DELETE | Cancel booking | ✅ |
| `/v2/teams` | GET | List teams | ✅ |
| `/v2/teams/{id}` | GET | Get team details | ✅ |
| `/v2/teams/{id}/event-types` | GET | List team event types | ✅ |

✅ **Key Features:**
- Team-scoped URLs: `/v2/teams/{teamId}/event-types`
- Proper headers: `Authorization: Bearer {key}`, `cal-api-version: 2024-08-13`
- Company-level API keys with fallback to system key
- Retry on 409 Conflict and 429 Rate Limit

---

## 2. WHAT IS MISSING OR INCOMPLETE

### 2.1 Reschedule Listener Disabled ⚠️

**File:** `/app/Listeners/Appointments/SyncToCalcomOnRescheduled.php`

**Status:** ⚠️ Commented out with early return
```php
public function handle(AppointmentRescheduled $event): void
{
    // ⚠️ TEMPORARILY DISABLED - Migration pending
    Log::channel('calcom')->info('⏭️ Cal.com reschedule sync DISABLED (migration pending)');
    return;
    // ... rest of implementation never executes
}
```

**Root Cause:** Missing database columns on appointments table:
- `sync_job_id`
- `calcom_sync_status`
- `sync_verified_at`
- `previous_starts_at`
- `rescheduled_at`
- `rescheduled_by`
- `reschedule_source`

**Impact:** 
- ⚠️ Rescheduling via Retell/Admin doesn't sync back to Cal.com
- ⚠️ Potential double-booking if customer also reschedules in Cal.com
- ✅ Cal.com→System reschedules work (via webhook)

**Fix Required:** Apply migration or seed these columns (should already exist in newer migrations)

---

### 2.2 Test Data for Friseur 1 ❌

**Per:** `/docs/e2e/audit/gaps.yaml` (GAP-003, GAP-004, GAP-009)

| Gap | Issue | Status | Impact |
|-----|-------|--------|--------|
| GAP-003 | No Cal.com Event Type IDs for services | ❌ BLOCKER | Can't create bookings |
| GAP-004 | Only 3 services instead of 16 | ❌ BLOCKER | Missing full service catalog |
| GAP-009 | Branch missing calcom_team_id | ⚠️ MAJOR | Team mapping unclear |

**Specific Issues:**

❌ **Services have NULL calcom_event_type_id:**
```sql
SELECT id, name, settings->>'calcom_event_type_id' FROM services 
WHERE company_id = 1;
-- Returns: NULL, NULL, NULL
```

❌ **Service names don't match Friseur:**
- "Premium Hair Treatment"
- "Comprehensive Therapy Session"
- "Medical Examination Series"

**Expected (from config.sample.yaml):**
- Kinderhaarschnitt (€20.50)
- Trockenschnitt (€25.00)
- Waschen & Styling (€40.00)
- ... 13 more services

---

### 2.3 Partial Feature Implementations

#### Saga Pattern (Compensation) - 70% Complete

**File:** `/app/Services/Saga/CalcomCompensationService.php`

✅ What works:
- Cancels single booking if local creation fails
- Cancels composite bookings if saga fails
- Rollback of local changes when Cal.com sync fails

⚠️ What's incomplete:
- No dead-letter queue for failed compensations
- No retry logic for compensation failures
- Manual cleanup required if compensation fails
- TODO comment: "Send alert to monitoring system"

#### CalcomV2Service (Older Implementation) - Deprecated

**File:** `/app/Services/CalcomV2Service.php` (100+ lines)

⚠️ **Status:** Partially overlaps with CalcomV2Client
- `fetchTeams()`, `fetchTeamEventTypes()` use V2
- Still has V1 fallback logic
- Creates some code duplication

**Recommendation:** Consolidate into single client if not in use

---

### 2.4 Monitoring & Observability Gaps

#### Missing Endpoints

❌ **Drift Detection API:**
- `/v2/calcom/detect-drift`
- `/v2/calcom/resolve-drift`
- `/v2/calcom/auto-resolve`

**Implementation Status:** Routes defined but no controller action

**Impact:** Can't detect conflicts between Cal.com and local DB

❌ **Sync Status Endpoints:**
- No `/v2/calcom/sync-status` endpoint
- No way to query appointment sync state from API

#### Incomplete Monitoring

✅ CalcomHealthCheck exists but:
- No real-time sync status dashboard
- No webhook latency metrics
- No double-booking detection alerts

---

### 2.5 E2E Documentation Gaps vs Implementation

**Per:** `/docs/e2e/e2e.md` Specification

| Requirement | Implemented | Notes |
|-------------|-------------|-------|
| FR-1: Book Appointment | ✅ 95% | Works except E2E data incomplete |
| FR-2: Reschedule | ⚠️ 70% | Listener disabled, webhook works |
| FR-3: Cancel Appointment | ✅ 100% | Full implementation |
| NFR-1: Latency SLO | ✅ 90% | Circuit breaker ready, monitoring partial |
| NFR-2: Reliability (99.5% uptime) | ✅ 80% | Retry logic ready, observability incomplete |

---

### 2.6 Configuration Issues

⚠️ **Friseur 1 Company Settings Mismatched**

Current:
```json
{
  "needs_appointment_booking": false,
  "service_type": "call_center",
  "business_type": "telefonie_service"
}
```

Expected (from E2E):
```json
{
  "needs_appointment_booking": true,
  "service_type": "appointment_booking",
  "business_type": "hair_salon",
  "calcom_team_id": 34209,
  "retell_agent_id": "agent_b36ecd3927a81834b6d56ab07b"
}
```

---

## 3. DISCREPANCIES WITH E2E DOCUMENTATION

### 3.1 Data Model Alignment

| E2E Expectation | Actual Implementation | Gap |
|-----------------|----------------------|-----|
| 2 Branches | 1 Branch exists | ⚠️ Major (but framework supports N branches) |
| 16 Services | 3 Services exist | ❌ Blocker |
| Cal.com Event Type IDs | All NULL | ❌ Blocker |
| Staff Cal.com User IDs | 1001-1005 (test IDs?) | ⚠️ Verify against Cal.com API |

### 3.2 Functional Flow Alignment

**Happy Path (FR-1: New Booking):** 
✅ Documented, mostly implemented
- Missing: E2E test data (Event IDs)

**Reschedule (FR-2):**
⚠️ Documented in spec but Retell→Cal.com reschedule sync disabled
- Cal.com→Retell reschedule works via webhook ✅

**Cancel (FR-3):**
✅ Fully documented and implemented

### 3.3 Architecture Alignment

**Sync Origin Strategy (Loop Prevention):**
✅ Matches spec exactly
- sync_origin field prevents infinite loops
- Webhook sets 'calcom', listeners check for it

**Policy Engine:**
❌ E2E spec defines policies (24h cancellation window)
- Policy table exists but empty for Friseur 1
- Logic implemented in PolicyEngine but not wired to Cal.com

---

## 4. ARCHITECTURE REVIEW

### 4.1 Request Flow Diagram

```
Cal.com Booking Created
  ↓
webhook.php: POST /api/calcom/webhook
  ↓
Middleware: VerifyCalcomSignature (HMAC-SHA256)
  ↓
CalcomWebhookController::handle()
  ├─ Verify ownership (security)
  ├─ Create/Update Appointment
  │  ├─ Find or create Customer
  │  ├─ Assign Staff (multi-model strategy)
  │  ├─ Set sync_origin = 'calcom' (loop prevention)
  │  └─ Set calcom_sync_status = 'synced'
  ├─ Invalidate availability cache
  └─ Return 200 OK
```

### 4.2 Outbound Sync (Local → Cal.com)

```
Appointment booked locally via Retell
  ↓
AppointmentBooked event dispatched
  ↓
SyncToCalcomOnBooked listener
  ├─ Check sync_origin (skip if 'calcom')
  ├─ Check service.calcom_event_type_id (skip if missing)
  ├─ Dispatch SyncAppointmentToCalcomJob('create')
  └─ Queue job
  
  ↓
SyncAppointmentToCalcomJob processes
  ├─ Pessimistic lock on appointment
  ├─ shouldSkipSync() check (loop prevention)
  ├─ CalcomV2Client::createBooking()
  └─ Update calcom_sync_status = 'synced' or 'failed'
```

### 4.3 Security Architecture

✅ **Webhook Signature Verification**
- HMAC-SHA256 signature validation
- Multiple header formats supported
- Signature failure → 401 Unauthorized

✅ **Tenant Isolation**
- Service lookup by calcom_event_type_id
- Enforces company_id ownership
- Cross-tenant attacks blocked

✅ **Loop Prevention**
- sync_origin tracking
- shouldSkipSync() in listener and job
- No infinite webhook loops possible

⚠️ **Remaining Gaps**
- No rate limiting on sync jobs (could spam Cal.com)
- No auth on some admin endpoints

---

## 5. CODE QUALITY ASSESSMENT

### 5.1 What's Well Implemented

✅ **Listener Pattern**
- Clean event-driven architecture
- ShouldQueue for async processing
- Failed() callback for error handling

✅ **Error Handling**
- Try-catch in all critical paths
- Comprehensive logging to calcom channel
- Graceful degradation on failures

✅ **Testing Infrastructure**
- CalcomV2ClientTest, CalcomV2IntegrationTest, CalcomV2ErrorHandlingTest
- Test mocks available (CalcomV2MockServer.php)
- Performance tests included

✅ **Configuration**
- Multi-tier: Company-level, System-level, ENV fallback
- Flexible API version support
- Rate limiting configured

### 5.2 What Needs Improvement

⚠️ **Code Organization**
- CalcomV2Service and CalcomV2Client overlap
- Some logic in controllers (CalcomWebhookController is 642 lines)
- Suggest: Extract booking logic to service classes

⚠️ **Documentation**
- Listener code has good comments but scattered
- No architecture diagram in codebase
- E2E gaps.yaml is outside source tree

⚠️ **Testing**
- Integration tests exist but may be outdated
- No E2E tests in automation (scripts exist but manual)
- Mock data incomplete for Friseur 1

⚠️ **Instrumentation**
- Basic logging present but sparse
- No distributed tracing (call_id propagation)
- No APM metrics (latency, error rates)

---

## 6. IMPLEMENTATION CHECKLIST

### Must-Have (Blockers)
- [ ] Add Cal.com Event Type IDs for all 3 services
- [ ] Create 13 missing Friseur services with Event IDs
- [ ] Update Company settings: `needs_appointment_booking: true`
- [ ] Verify Staff Cal.com User IDs against Cal.com API

### Should-Have (Critical Path)
- [ ] Re-enable SyncToCalcomOnRescheduled listener
- [ ] Fix migrations for missing columns
- [ ] Implement Policy Engine for cancellation rules
- [ ] Set up complete E2E test data (Friseur 1)
- [ ] Add Branch 2 (Zweigstelle) with separate Cal.com Team

### Nice-to-Have (Enhancement)
- [ ] Implement drift detection API endpoints
- [ ] Add sync status dashboard
- [ ] Consolidate CalcomV2Service and CalcomV2Client
- [ ] Extract booking logic from webhook controller
- [ ] Add distributed tracing/APM metrics
- [ ] Implement component services (composite appointments)
- [ ] Implement billing integration

---

## 7. DEPLOYMENT READINESS

**Overall:** ⚠️ **Not Production Ready** (data missing, listener disabled)

### Go/No-Go Checklist

| Item | Status | Blocker |
|------|--------|---------|
| Webhook handling works | ✅ | No |
| Basic booking flow tested | ⚠️ Partial | Yes |
| Reschedule listener enabled | ❌ | Yes |
| Cal.com Event IDs configured | ❌ | **YES** |
| Test data complete | ❌ | **YES** |
| Error handling robust | ✅ | No |
| Security verified | ✅ | No |
| Monitoring configured | ⚠️ Partial | No |
| Idempotency working | ✅ | No |

---

## 8. RECOMMENDATIONS

### Immediate Actions (This Sprint)
1. **Add missing Cal.com Event Type IDs** (8 hours) - CRITICAL
2. **Re-enable reschedule listener** (2 hours) - Required for full sync
3. **Fix Company settings** (1 hour) - Unblocks testing
4. **Verify Staff Cal.com IDs** (1 hour) - Validation

### Short-term (Next Sprint)
1. **Create 13 missing Friseur services** (4 hours)
2. **Implement policy engine** (2 hours)
3. **Add second Branch** (2 hours) - For E2E alignment
4. **Complete E2E test suite** (4 hours)

### Medium-term (Q4)
1. **Implement drift detection** (6 hours)
2. **Add sync status monitoring** (4 hours)
3. **Implement component services** (16 hours) - Larger feature
4. **Extract service layer from controller** (4 hours) - Refactor

### Long-term (Q1 2026)
1. **Full billing integration** (12 hours)
2. **Advanced analytics dashboard** (8 hours)
3. **Distributed tracing/observability** (8 hours)

---

## 9. FILES SUMMARY

### Core Integration (✅ Complete)
- `/app/Http/Controllers/CalcomWebhookController.php` - Webhook handler
- `/app/Jobs/SyncAppointmentToCalcomJob.php` - Async sync job
- `/app/Services/CalcomV2Client.php` - API client
- `/app/Services/Idempotency/IdempotencyCache.php` - Deduplication
- `/app/Http/Middleware/VerifyCalcomSignature.php` - Signature verification

### Listeners (⚠️ Partial)
- `/app/Listeners/Appointments/SyncToCalcomOnBooked.php` - ✅ Working
- `/app/Listeners/Appointments/SyncToCalcomOnRescheduled.php` - ⚠️ Disabled
- `/app/Listeners/Appointments/SyncToCalcomOnCancelled.php` - ✅ Working

### Models (✅ Complete)
- `/app/Models/CalcomHostMapping.php` - Staff mapping
- `/app/Models/CalcomEventMap.php` - Service→Event Type
- `/app/Models/CalcomTeamMember.php` - Team member tracking

### Services (⚠️ Partial)
- `/app/Services/CalcomService.php` - Older V2 service (deprecated?)
- `/app/Services/CalcomV2Service.php` - Team/Event Type management
- `/app/Services/Appointments/CalcomAvailabilityService.php` - Availability
- `/app/Services/CalcomHostMappingService.php` - Host matching
- `/app/Services/Saga/CalcomCompensationService.php` - Rollback logic

### Routes
- `/api/calcom/webhook` - Primary webhook endpoint
- `/api/webhooks/calcom` - Alternative endpoint
- `/api/health/calcom/*` - Health checks

### Configuration
- `config/services.php` - API keys, base URL
- `routes/api.php` - Route definitions

---

## Conclusion

The Cal.com integration is **substantially feature-complete** with proper webhook handling, bidirectional sync, idempotency, and security measures. However, it's **not production-ready** for Friseur 1 due to missing test data (Event Type IDs, services) and temporarily disabled reschedule listener. 

Focus on the immediate actions to unblock E2E testing, then proceed with enhancements.

