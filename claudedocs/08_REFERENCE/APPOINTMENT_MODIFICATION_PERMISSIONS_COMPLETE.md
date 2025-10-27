# Appointment Modification Rules & Permissions - Complete Reference

**Document Version**: 1.0
**Date**: 2025-10-25
**System**: AskPro AI Gateway
**Scope**: Cancellation & Reschedule Rules, Permissions, and Enforcement

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [WHO Can Do WHAT - Permission Matrix](#who-can-do-what---permission-matrix)
3. [WHERE Rules Are Defined](#where-rules-are-defined)
4. [HOW Rules Are Enforced](#how-rules-are-enforced)
5. [Configuration Options](#configuration-options)
6. [Business Rules & Restrictions](#business-rules--restrictions)
7. [Actor-Based Modification Flows](#actor-based-modification-flows)
8. [Code Reference Index](#code-reference-index)

---

## Executive Summary

The appointment modification system implements a **multi-layered permission and policy enforcement architecture** that distinguishes between:

1. **Authorization** (WHO can perform actions) - Role-based via Laravel Policies
2. **Business Rules** (WHEN/HOW actions are allowed) - Time-based, quota-based via Policy Engine
3. **Actor Context** (Customer vs Staff vs AI) - Different validation flows

**Key Design Principles**:
- **Hierarchical Policy Resolution**: Staff → Service → Branch → Company
- **Multi-tenant Isolation**: All operations scoped to `company_id`
- **Audit Trail**: Every modification tracked in `appointment_modifications` table
- **Fee Calculation**: Tiered based on notice period (48h, 24h, <24h)
- **Quota Enforcement**: Monthly limits per customer (cancellations/reschedules)

---

## WHO Can Do WHAT - Permission Matrix

### 1. Authorization by Role

| Role | Cancel Appointment | Reschedule Appointment | View Modifications | Override Policy |
|------|-------------------|------------------------|-------------------|----------------|
| **super_admin** | ✅ All (bypass all checks) | ✅ All (bypass all checks) | ✅ All companies | ✅ Yes |
| **admin** | ✅ All in company | ✅ All in company | ✅ Company-wide | ❌ No |
| **manager** | ✅ Company appointments (future only) | ✅ Company appointments | ✅ Company-wide | ❌ No |
| **receptionist** | ✅ Company appointments | ✅ Company appointments | ❌ No | ❌ No |
| **staff** | ✅ Own appointments only | ✅ Own appointments only | ❌ No | ❌ No |
| **customer** (via portal) | ✅ Own appointments (with policy check) | ✅ Own appointments (with policy check) | ❌ No | ❌ No |
| **anonymous caller** | ❌ Callback request only | ❌ Callback request only | ❌ No | ❌ No |
| **AI Agent (Retell)** | ✅ Via function call (with policy check) | ✅ Via function call (with policy check) | ❌ No | ❌ No |

### 2. Special Restrictions

#### Past Appointments
- **Cancel**: Only `admin` and `super_admin` can cancel past appointments
- **Reschedule**: Cannot reschedule past appointments (enforced at policy level)

#### Completed Appointments
- **Cancel**: Cannot cancel appointments with `status = 'completed'` (all roles)
- **Reschedule**: Not applicable

#### Anonymous Callers
- **Security Requirement**: Anonymous/hidden numbers → `CallbackRequest` for manual verification
- **Enforcement**: `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php:3313`

---

## WHERE Rules Are Defined

### 1. Authorization Policies (Laravel Policies)

#### AppointmentPolicy.php
**Location**: `/var/www/api-gateway/app/Policies/AppointmentPolicy.php`

| Method | Purpose | Lines |
|--------|---------|-------|
| `before()` | Super admin bypass | 16-23 |
| `view()` | Who can view appointments | 36-54 |
| `update()` | Who can modify appointments | 67-86 |
| `delete()` | Who can delete appointments | 91-106 |
| `cancel()` | Who can cancel (role check only) | 157-179 |
| `reschedule()` | Who can reschedule (role check + time) | 184-192 |

**Key Authorization Rules**:
```php
// Line 157-179: Cancel authorization
public function cancel(User $user, Appointment $appointment): bool
{
    // Can't cancel completed appointments
    if ($appointment->status === 'completed') {
        return false;
    }

    if ($user->hasAnyRole(['admin', 'manager'])) {
        return true;
    }

    // Staff can cancel their own appointments
    if ($user->id === $appointment->staff_id) {
        return true;
    }

    // Receptionists can cancel appointments in their company
    if ($user->hasRole('receptionist') && $user->company_id === $appointment->company_id) {
        return true;
    }

    return false;
}

// Line 184-192: Reschedule authorization
public function reschedule(User $user, Appointment $appointment): bool
{
    // Can't reschedule past appointments
    if ($appointment->starts_at < now()) {
        return false;
    }

    return $this->update($user, $appointment);
}
```

#### AppointmentModificationPolicy.php
**Location**: `/var/www/api-gateway/app/Policies/AppointmentModificationPolicy.php`

| Method | Purpose | Lines |
|--------|---------|-------|
| `viewAny()` | Who can see modification history | 28-31 |
| `view()` | Who can see specific modification | 36-44 |
| `create()` | Who can manually create records | 49-54 |
| `update()` | Modifications are immutable | 59-64 |

**Key Rule**: Modification records are **immutable audit logs** - only `super_admin` can update.

### 2. Business Logic Policies (Policy Engine)

#### PolicyConfiguration Model
**Location**: `/var/www/api-gateway/app/Models/PolicyConfiguration.php`

**Polymorphic Configuration** (lines 22-47):
- `configurable_type`: Company | Branch | Service | Staff
- `policy_type`: cancellation | reschedule | recurring
- `config`: JSON with flexible rules

**Hierarchy Resolution** (lines 115-131):
```php
public function getEffectiveConfig(): array
{
    if (!$this->is_override || !$this->overrides_id) {
        return $this->config ?? [];
    }

    $parentPolicy = $this->overrides;
    if (!$parentPolicy) {
        return $this->config ?? [];
    }

    // Recursively get parent's effective config
    $parentConfig = $parentPolicy->getEffectiveConfig();

    // Merge with current config, current takes precedence
    return array_merge($parentConfig, $this->config ?? []);
}
```

#### AppointmentPolicyEngine
**Location**: `/var/www/api-gateway/app/Services/Policies/AppointmentPolicyEngine.php`

| Method | Purpose | Lines |
|--------|---------|-------|
| `canCancel()` | Check cancellation eligibility | 29-88 |
| `canReschedule()` | Check reschedule eligibility | 98-155 |
| `calculateFee()` | Calculate modification fee | 165-202 |
| `getRemainingModifications()` | Get customer quota | 211-233 |
| `resolvePolicy()` | Traverse policy hierarchy | 244-279 |

**Business Rule Checks** (lines 29-88):
```php
public function canCancel(Appointment $appointment, ?Carbon $now = null): PolicyResult
{
    $now = $now ?? Carbon::now();

    // Get applicable policy
    $policy = $this->resolvePolicy($appointment, 'cancellation');

    if (!$policy) {
        return PolicyResult::allow(fee: 0.0, details: ['policy' => 'default']);
    }

    $hoursNotice = $now->diffInHours($appointment->starts_at, false);

    // CHECK 1: Deadline
    $requiredHours = $policy['hours_before'] ?? 0;
    if ($hoursNotice < $requiredHours) {
        $fee = $this->calculateFee($appointment, 'cancellation', $hoursNotice);
        return PolicyResult::deny(
            reason: "Cancellation requires {$requiredHours} hours notice. Only {$hoursNotice} hours remain.",
            details: ['hours_notice' => $hoursNotice, 'required_hours' => $requiredHours, 'fee_if_forced' => $fee]
        );
    }

    // CHECK 2: Monthly quota
    $maxPerMonth = $policy['max_cancellations_per_month'] ?? null;
    if ($maxPerMonth !== null) {
        $recentCount = $this->getModificationCount($appointment->customer_id, 'cancel', 30);

        if ($recentCount >= $maxPerMonth) {
            return PolicyResult::deny(
                reason: "Monthly cancellation quota exceeded ({$recentCount}/{$maxPerMonth})",
                details: ['quota_used' => $recentCount, 'quota_max' => $maxPerMonth]
            );
        }
    }

    // Calculate fee if applicable
    $fee = $this->calculateFee($appointment, 'cancellation', $hoursNotice);

    return PolicyResult::allow(fee: $fee, details: ['hours_notice' => $hoursNotice, 'required_hours' => $requiredHours, 'policy' => $policy]);
}
```

### 3. Database Schema

#### policy_configurations Table
**Migration**: `/var/www/api-gateway/database/migrations/2025_10_01_060201_create_policy_configurations_table.php`

**Schema** (lines 22-67):
```sql
CREATE TABLE policy_configurations (
    id BIGINT PRIMARY KEY,
    company_id BIGINT NOT NULL,
    configurable_type VARCHAR(255),  -- Company|Branch|Service|Staff
    configurable_id VARCHAR(255),
    policy_type ENUM('cancellation', 'reschedule', 'recurring'),
    config JSON,  -- Flexible policy rules
    is_override BOOLEAN DEFAULT false,
    overrides_id BIGINT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Indexes
    INDEX idx_company (company_id),
    INDEX idx_polymorphic_config (company_id, configurable_type, configurable_id),
    INDEX idx_policy_type (company_id, policy_type),
    INDEX idx_override_chain (is_override, overrides_id),

    -- Constraint: One policy type per company-entity combination
    UNIQUE (company_id, configurable_type, configurable_id, policy_type, deleted_at)
);
```

#### appointment_modifications Table
**Migration**: `/var/www/api-gateway/database/migrations/2025_10_01_060304_create_appointment_modifications_table.php`

**Schema** (lines 22-88):
```sql
CREATE TABLE appointment_modifications (
    id BIGINT PRIMARY KEY,
    company_id BIGINT NOT NULL,
    appointment_id BIGINT NOT NULL,
    customer_id BIGINT NOT NULL,
    modification_type ENUM('cancel', 'reschedule'),
    within_policy BOOLEAN DEFAULT true,
    fee_charged DECIMAL(10,2) DEFAULT 0,
    reason TEXT NULL,
    modified_by_type VARCHAR(255) NULL,  -- User|System|Customer|Staff
    modified_by_id BIGINT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Critical index for 30-day rolling window queries
    INDEX idx_customer_mods_rolling (company_id, customer_id, modification_type, created_at),
    INDEX idx_appointment_history (company_id, appointment_id, created_at),
    INDEX idx_policy_compliance (company_id, within_policy, modification_type),
    INDEX idx_fee_analysis (company_id, fee_charged, created_at),
    INDEX idx_modified_by (modified_by_type, modified_by_id)
);
```

---

## HOW Rules Are Enforced

### 1. Multi-Layer Enforcement Architecture

```
┌─────────────────────────────────────────────────────────┐
│ REQUEST (Customer/Staff/AI/API)                         │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ LAYER 1: Authentication & Tenant Isolation              │
│ - TenantMiddleware (company_id scoping)                 │
│ - X-Company-ID header validation (super_admin only)     │
│ Location: /app/Http/Middleware/TenantMiddleware.php     │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ LAYER 2: Authorization (Laravel Policies)               │
│ - AppointmentPolicy::cancel() / reschedule()            │
│ - Role-based: admin, manager, staff, receptionist       │
│ - Time-based: Past appointments check                   │
│ Location: /app/Policies/AppointmentPolicy.php           │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ LAYER 3: Business Rules (Policy Engine)                 │
│ - AppointmentPolicyEngine::canCancel() / canReschedule()│
│ - Hours notice deadline check                           │
│ - Monthly quota enforcement                             │
│ - Per-appointment reschedule limit                      │
│ Location: /app/Services/Policies/AppointmentPolicyEngine│
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ LAYER 4: Audit Trail & Event System                     │
│ - AppointmentModification record created                │
│ - AppointmentCancellationRequested event fired          │
│ - AppointmentRescheduled event fired                    │
│ - Stats updated (appointment_modification_stats)        │
└─────────────────────────────────────────────────────────┘
```

### 2. Enforcement Points by Channel

#### A. Filament Admin Interface
**Location**: `/var/www/api-gateway/app/Filament/Resources/AppointmentResource.php`

**Enforcement**:
- **Authorization**: Automatic via Filament's `authorize()` method calling `AppointmentPolicy`
- **Business Rules**: Not automatically enforced - admins can bypass
- **UI Restriction**: Actions conditionally shown based on `can('cancel', $record)`

**Code Reference** (lines not shown in excerpt, but pattern):
```php
Tables\Actions\Action::make('cancel')
    ->visible(fn ($record) => auth()->user()->can('cancel', $record))
    ->action(function ($record) {
        // Direct cancellation without policy engine check
        // Admins bypass business rules
    })
```

#### B. Retell AI Voice Agent
**Location**: `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`

**Cancel Flow** (lines 3299-3387):
```php
private function handleCancellationAttempt(array $params, ?string $callId)
{
    // 1. Get call context
    $callContext = $this->getCallContext($callId);
    $call = Call::find($callContext['call_id']);

    // 2. SECURITY: Anonymous callers → Callback request
    if ($call && ($call->from_number === 'anonymous' || ...)) {
        return $this->createAnonymousCallbackRequest($call, $params, 'cancellation');
    }

    // 3. Find appointment (customer phone number match)
    $appointment = $this->findAppointmentFromCall($call, $params);

    if (!$appointment) {
        return response()->json([
            'success' => false,
            'status' => 'not_found',
            'message' => "Ich konnte keinen Termin am {$dateStr} finden..."
        ], 200);
    }

    // 4. CHECK POLICY ENGINE (business rules)
    $policyEngine = app(\App\Services\Policies\AppointmentPolicyEngine::class);
    $policyResult = $policyEngine->canCancel($appointment);

    // 5a. If allowed: Cancel appointment
    if ($policyResult->allowed) {
        $appointment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $params['reason'] ?? 'Via Telefonassistent storniert'
        ]);

        // Track modification
        \App\Models\AppointmentModification::create([
            'appointment_id' => $appointment->id,
            'customer_id' => $appointment->customer_id,
            'company_id' => $appointment->company_id,
            'modification_type' => 'cancel',
            'within_policy' => true,
            'fee_charged' => $policyResult->fee,
            'reason' => $params['reason'] ?? null,
            'modified_by_type' => 'System',
            'metadata' => [
                'call_id' => $callId,
                'hours_notice' => $policyResult->details['hours_notice'] ?? null,
                'policy_required' => $policyResult->details['required_hours'] ?? null,
                'cancelled_via' => 'retell_ai'
            ]
        ]);

        // Fire event
        event(new \App\Events\Appointments\AppointmentCancellationRequested(...));

        $feeMessage = $policyResult->fee > 0
            ? " Es fällt eine Stornogebühr von {$policyResult->fee}€ an."
            : "";

        return response()->json([
            'success' => true,
            'status' => 'cancelled',
            'message' => "Ihr Termin wurde erfolgreich storniert.{$feeMessage}",
            'fee' => $policyResult->fee
        ], 200);
    }

    // 5b. If denied: Explain reason
    return response()->json([
        'success' => false,
        'status' => 'denied',
        'message' => $policyResult->reason,
        'details' => $policyResult->details
    ], 200);
}
```

**Reschedule Flow** (lines 3625-3724):
- **2-Step Confirmation**: First check availability, then wait for "Ja" confirmation
- **Policy Check**: Only after availability confirmed (line 3664)
- **Same Tracking**: Creates `AppointmentModification` record with metadata

#### C. API Endpoints (Customer Portal / Third-Party)
**Location**: `/var/www/api-gateway/app/Http/Controllers/Api/V2/BookingController.php` (not shown in excerpts)

**Expected Enforcement Pattern**:
```php
public function cancelAppointment(Request $request, $appointmentId)
{
    // 1. Find appointment
    $appointment = Appointment::findOrFail($appointmentId);

    // 2. AUTHORIZATION CHECK (via middleware or explicit)
    $this->authorize('cancel', $appointment);

    // 3. BUSINESS RULES CHECK
    $policyEngine = app(\App\Services\Policies\AppointmentPolicyEngine::class);
    $policyResult = $policyEngine->canCancel($appointment);

    if (!$policyResult->allowed) {
        return response()->json([
            'error' => $policyResult->reason,
            'details' => $policyResult->details
        ], 422);
    }

    // 4. Execute cancellation + audit trail
    // ... (same as Retell flow)
}
```

### 3. Policy Resolution Hierarchy

**Order of Precedence** (most specific wins):

```
Staff-specific Policy (highest priority)
    ↓ (if not found)
Service-specific Policy
    ↓ (if not found)
Branch-specific Policy
    ↓ (if not found)
Company-wide Policy
    ↓ (if not found)
System Default (allow with no fee)
```

**Implementation** (lines 244-279):
```php
private function resolvePolicy(Appointment $appointment, string $policyType): ?array
{
    // 1. Try staff first
    if ($appointment->staff) {
        $policy = $this->policyService->resolvePolicy($appointment->staff, $policyType);
        if ($policy) return $policy;
    }

    // 2. Try service
    if ($appointment->service ?? null) {
        $policy = $this->policyService->resolvePolicy($appointment->service, $policyType);
        if ($policy) return $policy;
    }

    // 3. Try branch
    if ($appointment->branch) {
        $policy = $this->policyService->resolvePolicy($appointment->branch, $policyType);
        if ($policy) return $policy;
    }

    // 4. Try company
    if ($appointment->company) {
        $policy = $this->policyService->resolvePolicy($appointment->company, $policyType);
        if ($policy) return $policy;
    }

    return null; // No policy = allow with no fee
}
```

### 4. Cache Strategy

**PolicyConfigurationService** caches resolved policies for 5 minutes:

**Location**: `/var/www/api-gateway/app/Services/Policies/PolicyConfigurationService.php`

```php
private const CACHE_TTL = 300; // 5 minutes
private const CACHE_PREFIX = 'policy_config';

public function resolvePolicy(Model $entity, string $policyType): ?array
{
    $cacheKey = $this->getCacheKey($entity, $policyType);

    return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($entity, $policyType) {
        return $this->resolvePolicyFromDatabase($entity, $policyType);
    });
}

// Cache key format: policy_config_Staff_123_cancellation
private function getCacheKey(Model $entity, string $policyType): string
{
    return sprintf(
        '%s_%s_%d_%s',
        self::CACHE_PREFIX,
        class_basename($entity),
        $entity->id,
        $policyType
    );
}
```

**Cache Invalidation**: Automatic via `PolicyConfigurationObserver` when policies updated.

---

## Configuration Options

### 1. Cancellation Policy Configuration

**JSON Schema** (stored in `policy_configurations.config`):

```json
{
  "hours_before": 24,
  "max_cancellations_per_month": 3,
  "fee": null,
  "fee_percentage": 50,
  "fee_tiers": [
    {"min_hours": 48, "fee": 0.0},
    {"min_hours": 24, "fee": 10.0},
    {"min_hours": 0, "fee": 15.0}
  ],
  "allow_same_day_cancel": false,
  "send_confirmation_email": true
}
```

**Field Descriptions**:
- `hours_before` (int): Minimum hours notice required (e.g., 24 = must cancel 24h before)
- `max_cancellations_per_month` (int|null): Monthly quota per customer (null = unlimited)
- `fee` (float|null): Fixed cancellation fee in EUR (overrides percentage and tiers)
- `fee_percentage` (float|null): Percentage of appointment price (e.g., 50 = 50% of price)
- `fee_tiers` (array): Time-based fee structure (most flexible)
- `allow_same_day_cancel` (bool): Whether same-day cancellation allowed (with fee)
- `send_confirmation_email` (bool): Whether to email customer after cancellation

### 2. Reschedule Policy Configuration

**JSON Schema**:

```json
{
  "hours_before": 12,
  "max_reschedules_per_appointment": 2,
  "fee": null,
  "fee_percentage": 25,
  "fee_tiers": [
    {"min_hours": 48, "fee": 0.0},
    {"min_hours": 24, "fee": 5.0},
    {"min_hours": 0, "fee": 10.0}
  ],
  "require_availability_check": true,
  "allow_cross_staff_reschedule": false
}
```

**Field Descriptions**:
- `hours_before` (int): Minimum hours notice required
- `max_reschedules_per_appointment` (int|null): Per-appointment limit (null = unlimited)
- `fee` (float|null): Fixed reschedule fee
- `fee_percentage` (float|null): Percentage of appointment price
- `fee_tiers` (array): Time-based fee structure
- `require_availability_check` (bool): Whether to check Cal.com availability first
- `allow_cross_staff_reschedule` (bool): Whether customer can change staff when rescheduling

### 3. Default System Behavior (No Policy Configured)

**Fallback Rules** (lines 194-201):

```php
// Default tiered structure (applies even without policy)
$defaultTiers = [
    ['min_hours' => 48, 'fee' => 0.0],   // >48h: Free
    ['min_hours' => 24, 'fee' => 10.0],  // 24-48h: 10€
    ['min_hours' => 0, 'fee' => 15.0],   // <24h: 15€
];
```

**Authorization Defaults**:
- No policy configured = Allow modification (with default fee tiers)
- No authorization = Deny (Laravel Policy default)

### 4. Environment Variables

**No environment variables** - All configuration stored in database for multi-tenant flexibility.

**Tenant Isolation Config**: `/var/www/api-gateway/config/companyscope.php` (not shown but referenced in middleware)

---

## Business Rules & Restrictions

### 1. Cancellation Rules

#### Time-Based Restrictions

| Hours Before Appointment | Default Fee | Can Cancel? | Notes |
|-------------------------|-------------|-------------|-------|
| > 48 hours | 0€ | ✅ Yes | Free cancellation |
| 24-48 hours | 10€ | ✅ Yes | Standard fee |
| < 24 hours | 15€ | ⚠️ Policy-dependent | High fee or denied |
| Past appointment | N/A | ❌ No (admin only) | Audit trail only |
| Status = completed | N/A | ❌ No | Cannot cancel completed |

**Override Conditions**:
- Admins can cancel anytime without policy checks
- Super admins can cancel past appointments
- Policy can customize hours/fees per entity

#### Quota-Based Restrictions

**Monthly Cancellation Quota** (lines 58-74):
```php
$maxPerMonth = $policy['max_cancellations_per_month'] ?? null;
if ($maxPerMonth !== null) {
    $recentCount = $this->getModificationCount(
        $appointment->customer_id,
        'cancel',
        30
    );

    if ($recentCount >= $maxPerMonth) {
        return PolicyResult::deny(
            reason: "Monthly cancellation quota exceeded ({$recentCount}/{$maxPerMonth})",
            details: [
                'quota_used' => $recentCount,
                'quota_max' => $maxPerMonth,
            ]
        );
    }
}
```

**Rolling Window**: 30 days from current date (not calendar month)

**Performance Optimization**: Uses materialized `appointment_modification_stats` table for O(1) lookups (lines 316-324).

### 2. Reschedule Rules

#### Time-Based Restrictions

| Hours Before Appointment | Default Fee | Can Reschedule? | Notes |
|-------------------------|-------------|-----------------|-------|
| > 48 hours | 0€ | ✅ Yes | Free reschedule |
| 24-48 hours | 5€ | ✅ Yes | Lower fee than cancel |
| < 24 hours | 10€ | ⚠️ Policy-dependent | Higher fee |
| Past appointment | N/A | ❌ No | Cannot reschedule past |

#### Per-Appointment Limits

**Max Reschedules** (lines 127-142):
```php
$maxPerAppointment = $policy['max_reschedules_per_appointment'] ?? null;
if ($maxPerAppointment !== null) {
    $rescheduleCount = AppointmentModification::where('appointment_id', $appointment->id)
        ->where('modification_type', 'reschedule')
        ->count();

    if ($rescheduleCount >= $maxPerAppointment) {
        return PolicyResult::deny(
            reason: "This appointment has been rescheduled {$rescheduleCount} times (max: {$maxPerAppointment})",
            details: [
                'reschedule_count' => $rescheduleCount,
                'max_allowed' => $maxPerAppointment,
            ]
        );
    }
}
```

**Typical Limit**: 2 reschedules per appointment

#### Availability Check Requirement

**2-Step Confirmation Flow** (Retell AI, lines 3630-3652):
1. **Step 1**: Check availability at new time → Ask "Ist das in Ordnung?"
2. **Step 2**: Wait for customer "Ja" → Execute reschedule with policy check

**Prevents**: Double-booking, staff unavailability, customer disappointment

### 3. Fee Calculation Logic

**Priority Order** (lines 176-192):

1. **Fixed Fee** (highest priority):
   ```json
   {"fee": 20.0}
   ```
   Result: Always 20€ regardless of timing

2. **Tiered Fees** (next priority):
   ```json
   {
     "fee_tiers": [
       {"min_hours": 48, "fee": 0.0},
       {"min_hours": 24, "fee": 10.0},
       {"min_hours": 12, "fee": 15.0},
       {"min_hours": 0, "fee": 25.0}
     ]
   }
   ```
   Result: Fee based on hours notice (e.g., 20h notice = 15€)

3. **Percentage-Based** (next priority):
   ```json
   {"fee_percentage": 50}
   ```
   Result: 50% of `appointment.price` (e.g., 50€ service = 25€ fee)

4. **System Default** (fallback):
   ```php
   ['min_hours' => 48, 'fee' => 0.0],
   ['min_hours' => 24, 'fee' => 10.0],
   ['min_hours' => 0, 'fee' => 15.0]
   ```

**Fee Display**:
- Retell AI: Announces fee in German ("Es fällt eine Stornogebühr von 15€ an")
- Admin UI: Shows in modification history
- Customer Portal: TBD (not implemented in excerpts)

### 4. Anonymous Caller Security Rules

**Security Requirement** (lines 3312-3315):
```php
// 🔒 SECURITY: Anonymous callers → CallbackRequest for verification
if ($call && ($call->from_number === 'anonymous' ||
    in_array(strtolower($call->from_number ?? ''),
    ['anonymous', 'unknown', 'withheld', 'restricted', '']))) {
    return $this->createAnonymousCallbackRequest($call, $params, 'cancellation');
}
```

**Workflow**:
1. Anonymous caller requests cancellation/reschedule
2. System creates `CallbackRequest` with customer details
3. Staff calls customer back on known number
4. Manual verification before proceeding

**Rationale**: Prevents unauthorized appointment modifications via caller ID spoofing.

---

## Actor-Based Modification Flows

### 1. Customer-Initiated (via Retell AI Voice Agent)

```
┌──────────────────────────────────────────────────────────────┐
│ 1. Customer calls company phone number                       │
│    Phone: 0176... (identified) or "anonymous"                │
└────────────────┬─────────────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────────────┐
│ 2. Retell AI answers, identifies customer by phone number    │
│    - Customer exists: Load appointment history               │
│    - Anonymous: Flag for manual callback                     │
└────────────────┬─────────────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────────────┐
│ 3. Customer intent: "Ich möchte stornieren"                  │
│    Retell calls: cancel_appointment function                 │
└────────────────┬─────────────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────────────┐
│ 4. RetellFunctionCallHandler::handleCancellationAttempt()    │
│    ├─ Find appointment by phone + date                       │
│    ├─ Policy check: hours_before, quota                      │
│    └─ Execute or deny with reason                            │
└────────────────┬─────────────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────────────┐
│ 5. Result communicated to customer                           │
│    ✅ Success: "Termin storniert. Gebühr: 10€"               │
│    ❌ Denied: "24 Stunden Vorlauf erforderlich"              │
└──────────────────────────────────────────────────────────────┘
```

**Key Characteristics**:
- **No authentication required** (phone number = identity)
- **Policy enforcement**: Full (cannot bypass)
- **Fee charged**: Yes, according to policy
- **Audit trail**: `modified_by_type = 'System'`, metadata includes `call_id`

### 2. Staff-Initiated (via Filament Admin Panel)

```
┌──────────────────────────────────────────────────────────────┐
│ 1. Staff logs into Filament admin                            │
│    Role: staff, receptionist, manager, or admin              │
└────────────────┬─────────────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────────────┐
│ 2. Navigate to Appointments → View specific appointment      │
│    URL: /admin/appointments/{id}                             │
└────────────────┬─────────────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────────────┐
│ 3. Click "Cancel" or "Reschedule" action                     │
│    Visibility: Conditional on AppointmentPolicy::can()       │
└────────────────┬─────────────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────────────┐
│ 4. Authorization Check (automatic via Filament)              │
│    AppointmentPolicy::cancel() or ::reschedule()             │
│    - Staff: Only own appointments                            │
│    - Receptionist: Company appointments                      │
│    - Manager/Admin: All company appointments                 │
└────────────────┬─────────────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────────────┐
│ 5. Execute modification (MAY bypass policy engine)           │
│    - Admins: Direct update, no policy check                  │
│    - Staff/Receptionist: Should check policy (TBD)           │
└────────────────┬─────────────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────────────┐
│ 6. Audit trail created                                       │
│    modified_by_type = 'User', modified_by_id = {user_id}     │
└──────────────────────────────────────────────────────────────┘
```

**Key Characteristics**:
- **Authentication required**: Laravel session-based
- **Policy enforcement**: Partial (admins can bypass)
- **Fee charged**: Admin discretion (can override)
- **Audit trail**: `modified_by_type = 'User'`, stores user ID

**⚠️ Gap**: Admin panel actions may not enforce policy engine checks consistently.

### 3. AI-Initiated (Retell Agent Automated)

**Same as Customer-Initiated** - AI acts on behalf of customer via phone call.

**Special Considerations**:
- Function calls versioned: `cancel_appointment`, `reschedule_appointment`
- V4 wrappers: `/api/retell/cancel-appointment-v4`, `/api/retell/reschedule-appointment-v4`
- Call context tracked: `retell_call_sessions` table

### 4. System-Initiated (Automated Policies)

**Not Implemented** - Future use cases:
- Auto-cancel if customer doesn't confirm 24h before
- Auto-reschedule if staff unavailable
- Auto-notify if policy quota reached

**Audit Trail Pattern**:
```php
'modified_by_type' => 'System',
'modified_by_id' => null,
'metadata' => ['automated_reason' => 'no_show_auto_cancel']
```

---

## Code Reference Index

### Core Files

| File Path | Purpose | Key Lines |
|-----------|---------|-----------|
| `/app/Policies/AppointmentPolicy.php` | Authorization rules | 16-192 |
| `/app/Policies/AppointmentModificationPolicy.php` | Audit record access control | 16-90 |
| `/app/Models/PolicyConfiguration.php` | Policy storage model | 32-174 |
| `/app/Models/AppointmentModification.php` | Audit trail model | 33-159 |
| `/app/Services/Policies/AppointmentPolicyEngine.php` | Business rule enforcement | 14-332 |
| `/app/Services/Policies/PolicyConfigurationService.php` | Policy resolution service | 15-242 |
| `/app/ValueObjects/PolicyResult.php` | Policy result value object | 5-64 |
| `/app/Http/Controllers/RetellFunctionCallHandler.php` | AI cancellation/reschedule | 3293-3724 |
| `/app/Http/Middleware/TenantMiddleware.php` | Multi-tenant isolation | 9-62 |

### Database Migrations

| File Path | Purpose | Key Schema |
|-----------|---------|------------|
| `/database/migrations/2025_10_01_060201_create_policy_configurations_table.php` | Policy storage | Polymorphic + hierarchy |
| `/database/migrations/2025_10_01_060304_create_appointment_modifications_table.php` | Audit trail | Actor tracking + metadata |

### Configuration Files

| File Path | Purpose | Notes |
|-----------|---------|-------|
| `/config/companyscope.php` | Multi-tenant config | Tenant isolation rules |
| `/config/permission.php` | Role/permission config | Spatie permissions |

### Tests

| File Path | Purpose | Coverage |
|-----------|---------|----------|
| `/tests/Unit/AppointmentPolicyEngineTest.php` | Policy engine unit tests | Business rules |
| `/tests/Feature/RetellIntegration/PolicyCancellationTest.php` | AI cancellation E2E | Voice agent flow |
| `/tests/Feature/RetellIntegration/PolicyRescheduleTest.php` | AI reschedule E2E | Voice agent flow |

### Event Handlers

| File Path | Purpose | Trigger |
|-----------|---------|---------|
| `/app/Events/Appointments/AppointmentCancellationRequested.php` | Cancellation event | After successful cancel |
| `/app/Events/Appointments/AppointmentRescheduled.php` | Reschedule event | After successful reschedule |
| `/app/Events/Appointments/AppointmentPolicyViolation.php` | Policy violation event | When denied by policy |
| `/app/Listeners/Appointments/UpdateModificationStats.php` | Stats aggregation | On modification created |

---

## Implementation Notes

### Strengths

1. **Separation of Concerns**: Authorization (who) vs Business Rules (when/how)
2. **Hierarchical Flexibility**: Company → Branch → Service → Staff overrides
3. **Audit Trail**: Complete modification history with actor tracking
4. **Multi-Channel Support**: Filament, Retell AI, API all use same engine
5. **Performance**: Cached policy resolution (5min TTL) + materialized stats

### Gaps & Improvement Areas

1. **Admin Bypass Inconsistency**:
   - Filament admin actions may not enforce policy engine consistently
   - **Recommendation**: Add policy checks to admin actions with override flag

2. **Customer Portal Not Implemented**:
   - No self-service cancellation/reschedule UI for customers
   - **Recommendation**: Build customer portal with policy engine integration

3. **Fee Payment Not Automated**:
   - Fees calculated but not charged via payment gateway
   - **Recommendation**: Integrate with Stripe/payment system

4. **Policy Violation Notifications**:
   - Events fired but listeners not fully implemented
   - **Recommendation**: Send email/SMS when quota exhausted or denied

5. **Testing Coverage**:
   - Unit tests exist for engine, but E2E tests incomplete
   - **Recommendation**: Add puppeteer tests for admin panel flows

### Future Enhancements

1. **Dynamic Quotas**: Per-customer overrides (VIP customers get unlimited)
2. **Grace Period**: 5-minute "undo" window after cancellation
3. **Refund Automation**: Auto-refund if cancelled >48h before
4. **Batch Operations**: Cancel multiple appointments at once (series)
5. **Waitlist Integration**: Auto-fill cancelled slots from waitlist

---

## Quick Reference Commands

### Check Policy for Appointment
```php
use App\Services\Policies\AppointmentPolicyEngine;

$policyEngine = app(AppointmentPolicyEngine::class);
$result = $policyEngine->canCancel($appointment);

if ($result->allowed) {
    echo "Fee: €{$result->fee}\n";
    echo "Hours notice: {$result->details['hours_notice']}\n";
} else {
    echo "Denied: {$result->reason}\n";
}
```

### Get Customer Quota Status
```php
$remaining = $policyEngine->getRemainingModifications($customer, 'cancel');
echo "Remaining cancellations this month: $remaining\n";
```

### Configure Company Policy
```php
use App\Models\Company;
use App\Services\Policies\PolicyConfigurationService;

$policyService = app(PolicyConfigurationService::class);
$company = Company::find(1);

$policyService->setPolicy($company, 'cancellation', [
    'hours_before' => 24,
    'max_cancellations_per_month' => 3,
    'fee_tiers' => [
        ['min_hours' => 48, 'fee' => 0.0],
        ['min_hours' => 24, 'fee' => 10.0],
        ['min_hours' => 0, 'fee' => 15.0]
    ]
]);
```

### Clear Policy Cache
```php
$policyService->clearCache($staff, 'cancellation');
```

---

## Change Log

| Date | Version | Changes |
|------|---------|---------|
| 2025-10-25 | 1.0 | Initial comprehensive documentation |

---

**Document Maintainer**: Development Team
**Last Review**: 2025-10-25
**Next Review**: 2025-11-25
