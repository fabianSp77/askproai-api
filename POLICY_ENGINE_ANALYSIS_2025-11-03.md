# Policy Engine & Business Rules Analysis Report

**Date:** 2025-11-03  
**Thoroughness Level:** Very Thorough  
**Analysis Scope:** Complete policy system architecture and enforcement

---

## Executive Summary

The system has a **well-structured but partially implemented** policy engine:

- ✅ **Core infrastructure exists:** PolicyConfiguration model, AppointmentPolicyEngine, hierarchical resolution
- ✅ **Policy enforcement implemented:** Cancellation & reschedule checks with fees
- ✅ **Anonymous caller handling:** Comprehensive CLIR detection
- ⚠️ **No-show detection:** Tracked but not policy-enforced
- ❌ **Recurring appointment policies:** Framework only, no enforcement
- ⚠️ **Policy violations:** Logged but violation history not persisted

---

## 1. PolicyConfiguration Model & System

**File:** `/var/www/api-gateway/app/Models/PolicyConfiguration.php`

### Architecture

```php
PolicyConfiguration
├── Polymorphic Relationship (configurable_type/configurable_id)
│   ├── Company
│   ├── Branch
│   ├── Service
│   └── Staff
├── Policy Types (3)
│   ├── cancellation
│   ├── reschedule
│   └── recurring
└── Hierarchy
    ├── is_override (bool)
    └── overrides_id (self-reference)
```

### Database Structure

**Migration:** `2025_10_01_060201_create_policy_configurations_table.php`

```sql
CREATE TABLE policy_configurations (
  id BIGINT PRIMARY KEY,
  company_id BIGINT (FK -> companies),
  configurable_type VARCHAR (morph: Company|Branch|Service|Staff),
  configurable_id VARCHAR (UUID or BIGINT),
  policy_type ENUM (cancellation|reschedule|recurring),
  config JSON (flexible policy data),
  is_override BOOLEAN,
  overrides_id BIGINT (FK -> self, nullable),
  timestamps
)

INDEXES:
  - idx_company
  - idx_polymorphic_config (company_id, configurable_type, configurable_id)
  - idx_policy_type
  - idx_override_chain
  - UNIQUE (company_id, configurable_type, configurable_id, policy_type)
```

### Hierarchy Resolution

**Service:** `PolicyConfigurationService`

**Resolution Order (most-specific wins):**
1. Staff → 2. Service → 3. Branch → 4. Company

**Optimization:**
- Cache-aware: 5-minute TTL
- Batch resolution for performance
- Cache warming capability
- Handles recursive parent traversal

---

## 2. IMPLEMENTED Policies

### A. CANCELLATION Policy

**Status:** ✅ FULLY IMPLEMENTED

#### Configuration Fields
```json
{
  "hours_before": 24,                    // Default deadline
  "max_cancellations_per_month": 5,      // Quota
  "fee_percentage": 50,                  // Percent of appointment price
  "fee": 15.0,                           // Fixed fee (alternative to %)
  "fee_tiers": [                         // Tiered fees based on notice
    {"min_hours": 48, "fee": 0.0},
    {"min_hours": 24, "fee": 10.0},
    {"min_hours": 0, "fee": 15.0}
  ]
}
```

#### Business Rules Enforced

1. **Deadline Check** (hours_before)
   - Requires N hours notice before appointment
   - Default: 24 hours
   - Deny if insufficient notice

2. **Monthly Quota** (max_cancellations_per_month)
   - Max cancellations per customer per month
   - Default: 5 cancellations
   - Tracked via AppointmentModification & AppointmentModificationStat

3. **Fee Calculation**
   - Fixed fee, percentage, or tiered structure
   - Default tiers: 48h→0€, 24h→10€, <24h→15€
   - Calculated based on actual hours notice

#### Enforcement Points

**File:** `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php` (line 3330-3430)

```php
// Retell AI cancellation flow
$policyEngine->canCancel($appointment)
  → PolicyResult {
      allowed: bool,
      fee: float,
      reason: string,
      details: array
    }

// If denied: Fire AppointmentPolicyViolation event
// If allowed: 
//   - Update appointment status
//   - Create AppointmentModification record
//   - Fire AppointmentCancellationRequested event
//   - Apply fee
```

**Alternative:** `/var/www/api-gateway/app/Http/Controllers/Api/RetellApiController.php` (line 1057)

---

### B. RESCHEDULE (Umbuchung) Policy

**Status:** ✅ FULLY IMPLEMENTED

#### Configuration Fields
```json
{
  "hours_before": 12,                     // Default deadline
  "max_reschedules_per_appointment": 3,   // Limit per single appointment
  "max_reschedules_per_month": null,      // (optional, not in current impl)
  "fee_percentage": 10,                   // Percent of appointment price
  "fee": 10.0,                            // Fixed fee (alternative)
  "fee_tiers": [...]                      // Tiered fees
}
```

#### Business Rules Enforced

1. **Deadline Check** (hours_before)
   - Requires N hours notice before appointment
   - Default: 12 hours (stricter than cancellation)
   - Deny if insufficient notice

2. **Per-Appointment Reschedule Limit** (max_reschedules_per_appointment)
   - Max rescheduling for single appointment
   - Default: 3 times
   - Prevents unlimited rebooking

3. **Fee Calculation** (same as cancellation)

#### Enforcement Points

**File:** `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php` (line 3663-3750)

```php
// 2-step Retell AI reschedule flow:
// STEP 1: Find new slot (check_availability)
// STEP 2: Confirm & check policy
$policyEngine->canReschedule($appointment)
  → PolicyResult {...}

// If denied: Fire AppointmentPolicyViolation event
// If allowed:
//   - Update appointment starts_at/ends_at
//   - Create AppointmentModification record
//   - Fire AppointmentRescheduled event
//   - Apply fee
```

---

### C. RECURRING Policy

**Status:** ⚠️ FRAMEWORK ONLY (No Enforcement)

#### Configuration Fields
```json
{
  "recurrence_frequency": "weekly",    // daily|weekly|biweekly|monthly
  "max_occurrences": 10,               // Max recurring instances
  "allow_partial_cancel": false,       // (not implemented)
  "require_full_series_notice": false  // (not implemented)
}
```

#### Status
- **UI:** Fully designed in PolicyConfigurationResource (line 245-276)
- **Storage:** Can persist in policy_configurations table
- **Enforcement:** ❌ NOT IMPLEMENTED
- **Impact:** Recurring appointments stored in Appointment.recurring_pattern (JSON), but policies NOT checked

---

## 3. MISSING/INCOMPLETE Policies

### A. No-Show Detection & Handling

**Status:** ❌ NOT POLICY-ENFORCED (only tracked)

#### What Exists
- `Customer.no_show_count` (calculated field)
- `Customer.no_show_appointments` (calculated field)
- `Call.no_show_count` (field for call tracking)
- `Customer.getReliabilityScore()` includes penalty: `-no_show_count * 5`

#### What's Missing
- **No-show policy type** (would be 4th policy type)
- **No-show detection mechanism:**
  - When is an appointment marked as no-show?
  - After what time (30 min? 1 hour after start)?
- **No-show consequences:**
  - Should subsequent bookings be denied?
  - Should no-show customers need pre-payment?
  - Should automatic rescheduling be prevented?
- **No-show escalation:** No workflow for handling repeat no-shows

#### Code Location
```php
// Tracked but not enforced
App\Models\Customer::no_show_count          // Attribute
App\Models\Customer::getReliabilityScore()  // Used in scoring

// Would need:
AppointmentPolicyEngine::checkNoShow()      // Not implemented
```

---

### B. Anonymous Caller (CLIR) Handling

**Status:** ✅ DETECTION ONLY (No Policy Enforcement)

#### Anonymous Call Detection

**File:** `/var/www/api-gateway/app/ValueObjects/AnonymousCallDetector.php`

```php
ANONYMOUS_INDICATORS = [
  'anonymous', 'unknown', 'blocked', 'private', 'withheld', 'unavailable', null, ''
]

AnonymousCallDetector::isAnonymous($call)     // Check if CLIR
AnonymousCallDetector::getLinkabilityScore()  // 0-100% link probability
AnonymousCallDetector::shouldAttemptLinking() // Smart customer matching
```

#### Linkability Scoring
- **100%:** Valid phone number
- **70%:** Name but no phone
- **40%:** Transcript available
- **0%:** Completely anonymous

#### Current Behavior
- ✅ Detects anonymous callers
- ✅ Attempts fuzzy name matching
- ⚠️ **Missing:** No policy to enforce on anonymous callers
  - Can anonymous customers book?
  - Require deposit/pre-payment for anonymous?
  - Require callback verification?
  - Block rescheduling for anonymous?

---

## 4. Policy Enforcement Integration

### Event-Based Enforcement

**File:** `/var/www/api-gateway/app/Listeners/Appointments/TriggerPolicyEnforcement.php`

**Event:** `AppointmentPolicyViolation` (dispatched in RetellFunctionCallHandler)

```
AppointmentPolicyViolation Event
├── appointment
├── policyResult (denied reason)
├── attemptedAction (cancel|reschedule)
├── source (retell_ai)
└── getSeverity() → high|medium|low

TriggerPolicyEnforcement Listener (Queued)
├── Log violation (warning/notice/info)
├── Record in database (policy_violations table)
├── Alert managers (high severity only)
└── Track violation pattern (cache)
```

### Violation Recording

**File:** `/var/www/api-gateway/app/Listeners/Appointments/TriggerPolicyEnforcement.php` (line 67-88)

```php
// Attempted to persist to policy_violations table
// BUT: Table doesn't exist in current migrations!

DB::table('policy_violations')->insert([
  'appointment_id',
  'customer_id',
  'company_id',
  'violation_type',      // cancel|reschedule
  'violation_reason',
  'severity',            // high|medium|low
  'policy_details',      // JSON
  'source',              // retell_ai
  'created_at',
  'updated_at'
])

// Handled gracefully:
// catch(\Exception) → just log, don't fail
```

**Critical Issue:** The listener tries to insert into `policy_violations` table but **migration doesn't exist**. Falls back silently with Debug log.

---

## 5. Composition & Modification Tracking

### AppointmentModification Model

**Purpose:** Track all appointment changes with policy context

```php
AppointmentModification {
  appointment_id,
  customer_id,
  company_id,
  modification_type: 'cancel'|'reschedule',
  within_policy: bool,         // Was modification allowed by policy?
  fee_charged: float,
  reason: string,
  modified_by_type: string,    // System|User|Admin|retell_ai
  metadata: {
    call_id,
    hours_notice,
    policy_required,
    cancelled_via: 'retell_ai',
    original_time,
    new_time,
    rescheduled_via
  }
}
```

### Materialized Stats

**Model:** `AppointmentModificationStat`

**Purpose:** Performance optimization for quota checking

```php
AppointmentModificationStat {
  customer_id,
  stat_type:        // cancel_30d|cancel_90d|reschedule_30d|reschedule_90d
  count: int,
  period_end: date,
  updated_at
}
```

**Used in:** AppointmentPolicyEngine::getModificationCount() (line 307-331)

**Fallback:** Real-time count if materialized stat stale

---

## 6. Policy Configuration UI

**File:** `/var/www/api-gateway/app/Filament/Resources/PolicyConfigurationResource.php`

### Admin Interface
- ✅ Create/Edit policies per Company|Branch|Service|Staff
- ✅ Configure all fields (hours_before, max_*, fees)
- ✅ Visual hierarchy explanation
- ✅ German language labels & help text
- ✅ Recommended defaults shown

### Permissions
- **SecurityFix (SEC-002):** Explicit company_id filtering to prevent IDOR

---

## 7. Fee Calculation System

### Fee Types Supported

1. **Fixed Fee**
   ```json
   {"fee": 15.0}  // €15 flat
   ```

2. **Percentage-Based**
   ```json
   {"fee_percentage": 50}  // 50% of appointment.price
   ```

3. **Tiered by Notice**
   ```json
   {
     "fee_tiers": [
       {"min_hours": 48, "fee": 0.0},
       {"min_hours": 24, "fee": 10.0},
       {"min_hours": 0, "fee": 15.0}
     ]
   }
   ```

### Default Tier Structure
```
if hours_notice >= 48: €0
if hours_notice >= 24: €10
if hours_notice > 0:   €15
```

### Implementation
**File:** `AppointmentPolicyEngine::calculateTieredFee()` (line 284-297)

---

## SUMMARY TABLE

| Policy | Status | Enforcement | Database | Notes |
|--------|--------|-------------|----------|-------|
| **Cancellation** | ✅ Complete | Yes | ✅ AppointmentModification | 24h default, 5/month quota |
| **Reschedule** | ✅ Complete | Yes | ✅ AppointmentModification | 12h default, 3x per appointment |
| **Recurring** | ⚠️ Partial | ❌ No | ✅ JSON in policy_config | Framework only, UI only |
| **No-Show** | ❌ Missing | ❌ No | ⚠️ Counted, not tracked | Needs implementation |
| **Anonymous (CLIR)** | ⚠️ Detection | ❌ Policy | ✅ Call.from_number | Linkability scoring exists |
| **Violations** | ⚠️ Partial | Events | ⚠️ Table missing | Logged to cache, not DB |

---

## CRITICAL GAPS & RECOMMENDATIONS

### 1. Create policy_violations Table (P1)
```php
// Missing migration needed:
Schema::create('policy_violations', function(Blueprint $table) {
  $table->id();
  $table->foreignId('appointment_id');
  $table->foreignId('customer_id');
  $table->foreignId('company_id');
  $table->enum('violation_type', ['cancel', 'reschedule']);
  $table->string('violation_reason');
  $table->enum('severity', ['high', 'medium', 'low']);
  $table->json('policy_details');
  $table->string('source')->default('retell_ai');
  $table->timestamps();
  
  $table->index(['company_id', 'created_at']);
  $table->index(['customer_id', 'created_at']);
});
```

### 2. Implement No-Show Policy (P1)
- Add 4th policy type: `POLICY_TYPE_NOSHOW`
- Define: Timeout to mark as no-show (30min after start)
- Implement: `AppointmentPolicyEngine::handleNoShow()`
- Enforce: Block rebooking, require pre-payment, etc.
- Mark: Create observer on Appointment to auto-mark no-shows

### 3. Implement Recurring Policy Enforcement (P2)
- Add recurring policy check in appointment creation
- Validate: max_occurrences, recurrence_frequency
- Job: Auto-create recurring appointments with policy validation

### 4. Implement Anonymous Caller Policy (P2)
- Add policy type or flags: `allow_anonymous_booking`, `require_verification`
- Enforcement: Check linkability score before confirmation
- Escalation: Require phone verification for score < 70%

### 5. Policy Violation Reporting (P2)
- Dashboard: Show violations by customer/type/company
- Analytics: Violation trends & patterns
- Compliance: Export audit trail for regulatory

---

## File Locations Summary

| Component | File |
|-----------|------|
| Model | `app/Models/PolicyConfiguration.php` |
| Service | `app/Services/Policies/PolicyConfigurationService.php` |
| Engine | `app/Services/Policies/AppointmentPolicyEngine.php` |
| Event | `app/Events/Appointments/AppointmentPolicyViolation.php` |
| Listener | `app/Listeners/Appointments/TriggerPolicyEnforcement.php` |
| Detector | `app/ValueObjects/AnonymousCallDetector.php` |
| Migration | `database/migrations/2025_10_01_060201_create_policy_configurations_table.php` |
| UI | `app/Filament/Resources/PolicyConfigurationResource.php` |
| Enforcement (Cancel) | `app/Http/Controllers/RetellFunctionCallHandler.php:3330-3430` |
| Enforcement (Reschedule) | `app/Http/Controllers/RetellFunctionCallHandler.php:3663-3750` |

