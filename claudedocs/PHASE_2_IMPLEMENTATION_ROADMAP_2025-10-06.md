# 🎯 Phase 2 Implementation Roadmap: Staff Assignment System

**Date**: 2025-10-06
**Status**: READY FOR IMPLEMENTATION
**Decision**: ✅ GO - Phase 1 PoC validated Cal.com hosts array availability

---

## 📊 Executive Summary

### Phase 1 PoC Results
**VALIDATION**: Call 687 confirmed Cal.com API v2 **reliably returns hosts array** with staff data:

```json
{
  "hosts": [
    {
      "id": 1420209,
      "name": "Fabian Spitzer",
      "email": "fabian@askproai.de",
      "username": "fabianaskproai",
      "timeZone": "Europe/Berlin"
    }
  ]
}
```

**Confidence Level**: 90% - Production data proves Cal.com integration is viable
**Go/No-Go Decision**: **GO FOR PHASE 2**

---

## 🏗️ Architecture Overview

### System Components

```
┌─────────────────────────────────────────────────────┐
│           Retell Webhook (Entry Point)              │
│  POST /api/retell/webhook → Handle booking event    │
└────────────────┬────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────┐
│      AppointmentCreationService (Existing)          │
│  • Create appointment record                        │
│  • Store Cal.com booking data                       │
└────────────────┬────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────┐
│  CalcomHostMappingService (NEW - Phase 2)           │
│  • Extract hosts array from Cal.com response        │
│  • Resolve staff_id via matching strategies         │
│  • Update appointment.staff_id                      │
└────────────────┬────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────┐
│         HostMatchingStrategy (NEW)                  │
│  • EmailMatchingStrategy (priority 100)             │
│  • NameMatchingStrategy (priority 50)               │
│  • ManualMappingStrategy (priority 200)             │
└─────────────────────────────────────────────────────┘
```

---

## 📋 Implementation Phases

### Phase 2.1: Database Schema (Day 1-2)
**Estimated Time**: 4 hours
**Risk Level**: LOW

#### Migration 1: calcom_host_mappings
```sql
CREATE TABLE calcom_host_mappings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id BIGINT UNSIGNED NOT NULL,
    calcom_host_id INTEGER NOT NULL,
    calcom_name VARCHAR(255) NOT NULL,
    calcom_email VARCHAR(255) NOT NULL,
    calcom_username VARCHAR(255) NULL,
    calcom_timezone VARCHAR(50) NULL,
    mapping_source ENUM('auto_email', 'auto_name', 'manual', 'admin') NOT NULL,
    confidence_score TINYINT UNSIGNED NOT NULL DEFAULT 100,
    last_synced_at TIMESTAMP NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    metadata JSON NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,

    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    UNIQUE KEY unique_host_staff (calcom_host_id, staff_id),
    INDEX idx_staff (staff_id),
    INDEX idx_email (calcom_email),
    INDEX idx_active (is_active, calcom_host_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Migration 2: calcom_host_mapping_audits
```sql
CREATE TABLE calcom_host_mapping_audits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mapping_id BIGINT UNSIGNED NOT NULL,
    action ENUM('created', 'updated', 'deleted', 'auto_matched', 'manual_override') NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    changed_by BIGINT UNSIGNED NULL,
    changed_at TIMESTAMP NOT NULL,
    reason TEXT NULL,

    FOREIGN KEY (mapping_id) REFERENCES calcom_host_mappings(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_mapping (mapping_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Migration 3: Add calcom_host_id to appointments
```sql
ALTER TABLE appointments
ADD COLUMN calcom_host_id INTEGER NULL AFTER calcom_v2_booking_id,
ADD INDEX idx_calcom_host (calcom_host_id);
```

**Rollback SQL**:
```sql
DROP TABLE IF EXISTS calcom_host_mapping_audits;
DROP TABLE IF EXISTS calcom_host_mappings;
ALTER TABLE appointments DROP COLUMN calcom_host_id;
```

---

### Phase 2.2: Core Service Implementation (Day 3-5)
**Estimated Time**: 12 hours
**Risk Level**: MEDIUM

#### File 1: `app/Services/CalcomHostMappingService.php`
```php
<?php

namespace App\Services;

use App\Models\CalcomHostMapping;
use App\Models\Staff;
use App\Models\Appointment;
use App\Services\Strategies\HostMatchingStrategy;
use App\Services\Strategies\EmailMatchingStrategy;
use App\Services\Strategies\NameMatchingStrategy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CalcomHostMappingService
{
    protected array $strategies;

    public function __construct(
        protected EmailMatchingStrategy $emailStrategy,
        protected NameMatchingStrategy $nameStrategy
    ) {
        $this->strategies = collect([
            $this->emailStrategy,
            $this->nameStrategy,
        ])->sortByDesc(fn($s) => $s->getPriority())->all();
    }

    /**
     * Resolve staff_id from Cal.com host data
     *
     * @param array $hostData Cal.com host object from API response
     * @param HostMatchContext $context Tenant and booking context
     * @return int|null staff_id or null if no match
     */
    public function resolveStaffForHost(array $hostData, HostMatchContext $context): ?int
    {
        $hostId = $hostData['id'] ?? null;

        if (!$hostId) {
            Log::warning('CalcomHostMappingService: Missing host ID in Cal.com response', [
                'host_data' => $hostData
            ]);
            return null;
        }

        // 1. Check existing active mapping
        $mapping = CalcomHostMapping::where('calcom_host_id', $hostId)
            ->where('is_active', true)
            ->with('staff')
            ->first();

        if ($mapping && $this->validateMapping($mapping, $context)) {
            Log::info('CalcomHostMappingService: Using existing mapping', [
                'host_id' => $hostId,
                'staff_id' => $mapping->staff_id,
                'source' => $mapping->mapping_source
            ]);
            return $mapping->staff_id;
        }

        // 2. Attempt auto-discovery via matching strategies
        foreach ($this->strategies as $strategy) {
            $matchResult = $strategy->match($hostData, $context);

            if ($matchResult && $matchResult->confidence >= 80) {
                Log::info('CalcomHostMappingService: Auto-matched via strategy', [
                    'host_id' => $hostId,
                    'strategy' => get_class($strategy),
                    'staff_id' => $matchResult->staff->id,
                    'confidence' => $matchResult->confidence
                ]);

                $mapping = $this->createMapping(
                    $matchResult->staff,
                    $hostData,
                    $strategy->getSource(),
                    $matchResult->confidence
                );

                return $mapping->staff_id;
            }
        }

        Log::warning('CalcomHostMappingService: No staff match found', [
            'host_id' => $hostId,
            'host_email' => $hostData['email'] ?? null,
            'context' => $context
        ]);

        return null;
    }

    /**
     * Create new host-to-staff mapping
     */
    protected function createMapping(
        Staff $staff,
        array $hostData,
        string $source,
        int $confidence
    ): CalcomHostMapping {
        $mapping = CalcomHostMapping::create([
            'staff_id' => $staff->id,
            'calcom_host_id' => $hostData['id'],
            'calcom_name' => $hostData['name'] ?? '',
            'calcom_email' => $hostData['email'] ?? '',
            'calcom_username' => $hostData['username'] ?? null,
            'calcom_timezone' => $hostData['timeZone'] ?? null,
            'mapping_source' => $source,
            'confidence_score' => $confidence,
            'last_synced_at' => now(),
            'is_active' => true,
            'metadata' => [
                'auto_created_at' => now()->toISOString(),
                'original_host_data' => $hostData
            ]
        ]);

        // Audit trail
        $mapping->audits()->create([
            'action' => 'auto_matched',
            'new_values' => $mapping->toArray(),
            'changed_at' => now(),
            'reason' => "Auto-matched via {$source} with {$confidence}% confidence"
        ]);

        return $mapping;
    }

    /**
     * Validate existing mapping is still valid
     */
    protected function validateMapping(CalcomHostMapping $mapping, HostMatchContext $context): bool
    {
        // Check staff is still active
        if (!$mapping->staff->is_active) {
            Log::warning('CalcomHostMappingService: Mapping points to inactive staff', [
                'mapping_id' => $mapping->id,
                'staff_id' => $mapping->staff_id
            ]);
            return false;
        }

        // Check staff belongs to correct company/branch
        if ($mapping->staff->company_id !== $context->companyId) {
            Log::warning('CalcomHostMappingService: Mapping company mismatch', [
                'mapping_id' => $mapping->id,
                'expected_company' => $context->companyId,
                'actual_company' => $mapping->staff->company_id
            ]);
            return false;
        }

        return true;
    }

    /**
     * Extract host data from Cal.com booking response
     */
    public function extractHostFromBooking(array $calcomResponse): ?array
    {
        // Round-Robin & Collective events: hosts[0] is primary
        $hosts = $calcomResponse['hosts'] ?? [];

        if (empty($hosts)) {
            Log::warning('CalcomHostMappingService: No hosts in Cal.com response', [
                'booking_id' => $calcomResponse['id'] ?? null
            ]);
            return null;
        }

        return $hosts[0]; // Primary host
    }
}
```

#### File 2: `app/Services/Strategies/HostMatchingStrategy.php` (Interface)
```php
<?php

namespace App\Services\Strategies;

interface HostMatchingStrategy
{
    /**
     * Attempt to match Cal.com host to internal staff
     *
     * @param array $hostData Cal.com host object
     * @param HostMatchContext $context Tenant context
     * @return MatchResult|null Match result or null if no match
     */
    public function match(array $hostData, HostMatchContext $context): ?MatchResult;

    /**
     * Get mapping source identifier
     */
    public function getSource(): string;

    /**
     * Get strategy priority (higher = run first)
     */
    public function getPriority(): int;
}
```

#### File 3: `app/Services/Strategies/EmailMatchingStrategy.php`
```php
<?php

namespace App\Services\Strategies;

use App\Models\Staff;

class EmailMatchingStrategy implements HostMatchingStrategy
{
    public function match(array $hostData, HostMatchContext $context): ?MatchResult
    {
        $email = $hostData['email'] ?? null;

        if (!$email) {
            return null;
        }

        $staff = Staff::query()
            ->where('company_id', $context->companyId)
            ->where('email', $email)
            ->where('is_active', true)
            ->first();

        if (!$staff) {
            return null;
        }

        return new MatchResult(
            staff: $staff,
            confidence: 95,
            reason: "Exact email match: {$email}",
            metadata: ['match_field' => 'email', 'match_value' => $email]
        );
    }

    public function getSource(): string
    {
        return 'auto_email';
    }

    public function getPriority(): int
    {
        return 100; // Highest priority - email is most reliable
    }
}
```

#### File 4: `app/Services/Strategies/NameMatchingStrategy.php`
```php
<?php

namespace App\Services\Strategies;

use App\Models\Staff;
use Illuminate\Support\Str;

class NameMatchingStrategy implements HostMatchingStrategy
{
    public function match(array $hostData, HostMatchContext $context): ?MatchResult
    {
        $calcomName = $hostData['name'] ?? null;

        if (!$calcomName) {
            return null;
        }

        // Normalize: "Fabian Spitzer" -> "fabian spitzer"
        $normalizedCalcomName = Str::lower(trim($calcomName));

        $staff = Staff::query()
            ->where('company_id', $context->companyId)
            ->where('is_active', true)
            ->get()
            ->first(function ($staff) use ($normalizedCalcomName) {
                $staffFullName = Str::lower(trim("{$staff->first_name} {$staff->last_name}"));
                return $staffFullName === $normalizedCalcomName;
            });

        if (!$staff) {
            return null;
        }

        return new MatchResult(
            staff: $staff,
            confidence: 75, // Lower confidence than email
            reason: "Name match: {$calcomName}",
            metadata: ['match_field' => 'name', 'match_value' => $calcomName]
        );
    }

    public function getSource(): string
    {
        return 'auto_name';
    }

    public function getPriority(): int
    {
        return 50; // Lower priority than email
    }
}
```

#### File 5: `app/Services/Strategies/MatchResult.php` (DTO)
```php
<?php

namespace App\Services\Strategies;

use App\Models\Staff;

class MatchResult
{
    public function __construct(
        public Staff $staff,
        public int $confidence, // 0-100
        public string $reason,
        public array $metadata = []
    ) {}
}
```

#### File 6: `app/Services/Strategies/HostMatchContext.php` (DTO)
```php
<?php

namespace App\Services\Strategies;

class HostMatchContext
{
    public function __construct(
        public int $companyId,
        public ?string $branchId = null,
        public ?int $serviceId = null,
        public ?array $calcomBooking = null
    ) {}
}
```

---

### Phase 2.3: Integration with Booking Flow (Day 6-8)
**Estimated Time**: 8 hours
**Risk Level**: MEDIUM

#### Modify: `app/Services/Retell/AppointmentCreationService.php`

**Location**: Around line 100-150 (after appointment creation, before return)

```php
use App\Services\CalcomHostMappingService;
use App\Services\Strategies\HostMatchContext;

// ... existing appointment creation code ...

// PHASE 2: Staff Assignment (NEW)
if (!empty($calcomBooking)) {
    $hostMappingService = app(CalcomHostMappingService::class);

    // Extract host from Cal.com response
    $hostData = $hostMappingService->extractHostFromBooking($calcomBooking);

    if ($hostData) {
        $context = new HostMatchContext(
            companyId: $call->company_id,
            branchId: $call->branch_id,
            serviceId: $appointment->service_id,
            calcomBooking: $calcomBooking
        );

        $staffId = $hostMappingService->resolveStaffForHost($hostData, $context);

        if ($staffId) {
            $appointment->update([
                'staff_id' => $staffId,
                'calcom_host_id' => $hostData['id']
            ]);

            Log::info('AppointmentCreationService: Staff assigned', [
                'appointment_id' => $appointment->id,
                'staff_id' => $staffId,
                'calcom_host_id' => $hostData['id']
            ]);
        } else {
            Log::warning('AppointmentCreationService: Could not resolve staff', [
                'appointment_id' => $appointment->id,
                'calcom_host_id' => $hostData['id'] ?? null,
                'host_email' => $hostData['email'] ?? null
            ]);
        }
    }
}
```

---

### Phase 2.4: Models & Relationships (Day 9)
**Estimated Time**: 4 hours
**Risk Level**: LOW

#### Model 1: `app/Models/CalcomHostMapping.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalcomHostMapping extends Model
{
    protected $fillable = [
        'staff_id',
        'calcom_host_id',
        'calcom_name',
        'calcom_email',
        'calcom_username',
        'calcom_timezone',
        'mapping_source',
        'confidence_score',
        'last_synced_at',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'confidence_score' => 'integer'
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(CalcomHostMappingAudit::class, 'mapping_id');
    }
}
```

#### Model 2: `app/Models/CalcomHostMappingAudit.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalcomHostMappingAudit extends Model
{
    const UPDATED_AT = null; // No updated_at column
    const CREATED_AT = 'changed_at';

    protected $fillable = [
        'mapping_id',
        'action',
        'old_values',
        'new_values',
        'changed_by',
        'changed_at',
        'reason'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_at' => 'datetime'
    ];

    public function mapping(): BelongsTo
    {
        return $this->belongsTo(CalcomHostMapping::class, 'mapping_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
```

#### Update: `app/Models/Appointment.php`
```php
// Add to existing model
use Illuminate\Database\Eloquent\Relations\BelongsTo;

protected $fillable = [
    // ... existing fields ...
    'staff_id',
    'calcom_host_id'
];

public function staff(): BelongsTo
{
    return $this->belongsTo(Staff::class);
}

public function calcomHostMapping(): BelongsTo
{
    return $this->belongsTo(CalcomHostMapping::class, 'calcom_host_id', 'calcom_host_id');
}
```

#### Update: `app/Models/Staff.php`
```php
// Add to existing model
use Illuminate\Database\Eloquent\Relations\HasMany;

public function appointments(): HasMany
{
    return $this->hasMany(Appointment::class);
}

public function calcomHostMappings(): HasMany
{
    return $this->hasMany(CalcomHostMapping::class);
}
```

---

### Phase 2.5: Testing Strategy (Day 10-12)
**Estimated Time**: 12 hours
**Risk Level**: LOW

#### Unit Tests
```php
// tests/Unit/CalcomHostMappingServiceTest.php

public function test_resolves_staff_via_email_match()
{
    $staff = Staff::factory()->create(['email' => 'fabian@askproai.de']);

    $hostData = [
        'id' => 1420209,
        'name' => 'Fabian Spitzer',
        'email' => 'fabian@askproai.de'
    ];

    $context = new HostMatchContext(companyId: $staff->company_id);

    $service = app(CalcomHostMappingService::class);
    $staffId = $service->resolveStaffForHost($hostData, $context);

    $this->assertEquals($staff->id, $staffId);
}

public function test_creates_mapping_on_first_match()
{
    $staff = Staff::factory()->create(['email' => 'test@example.com']);

    $hostData = [
        'id' => 999,
        'email' => 'test@example.com',
        'name' => 'Test User'
    ];

    $context = new HostMatchContext(companyId: $staff->company_id);

    $service = app(CalcomHostMappingService::class);
    $service->resolveStaffForHost($hostData, $context);

    $this->assertDatabaseHas('calcom_host_mappings', [
        'staff_id' => $staff->id,
        'calcom_host_id' => 999,
        'mapping_source' => 'auto_email'
    ]);
}
```

#### Integration Tests
```php
// tests/Feature/AppointmentStaffAssignmentTest.php

public function test_appointment_creation_assigns_staff_from_calcom_host()
{
    $staff = Staff::factory()->create(['email' => 'fabian@askproai.de']);

    $webhookPayload = [
        'event' => 'appointment.created',
        'call_id' => 'test_call_123',
        'calcom_booking' => [
            'id' => 'ABC123',
            'hosts' => [
                [
                    'id' => 1420209,
                    'email' => 'fabian@askproai.de',
                    'name' => 'Fabian Spitzer'
                ]
            ]
        ]
    ];

    $response = $this->postJson('/api/retell/webhook', $webhookPayload);

    $response->assertOk();

    $appointment = Appointment::where('calcom_v2_booking_id', 'ABC123')->first();

    $this->assertEquals($staff->id, $appointment->staff_id);
    $this->assertEquals(1420209, $appointment->calcom_host_id);
}
```

---

## 🚀 Deployment Checklist

### Pre-Deployment (Day 1)
- [ ] Review all migration files
- [ ] Backup production database
- [ ] Run migrations in staging environment
- [ ] Verify foreign keys and indexes created
- [ ] Test rollback migrations

### Deployment (Day 2-12)
- [ ] Deploy migrations to production
- [ ] Deploy service classes (CalcomHostMappingService)
- [ ] Deploy strategy classes (Email, Name)
- [ ] Deploy model updates (Appointment, Staff)
- [ ] Update AppointmentCreationService integration
- [ ] Run unit tests (100% pass required)
- [ ] Run integration tests (100% pass required)

### Post-Deployment Validation (Day 13-14)
- [ ] Monitor first 10 real bookings for staff assignment
- [ ] Verify calcom_host_mappings table population
- [ ] Check logs for any matching failures
- [ ] Validate audit trail creation
- [ ] Compare staff assignment accuracy vs manual review

### Monitoring Metrics
- **Staff Assignment Rate**: Target 90%+ bookings with staff_id
- **Auto-Match Confidence**: Target 95%+ via email, 75%+ via name
- **Mapping Creation Rate**: Track new mappings per day
- **Failed Matches**: Log and investigate <10% failure rate

---

## ⚠️ Risk Mitigation

### Risk 1: Cal.com API Changes
**Probability**: LOW
**Impact**: HIGH
**Mitigation**:
- Log complete Cal.com response for every booking
- Alert on missing hosts array
- Graceful degradation (appointment created without staff_id)

### Risk 2: Email/Name Matching Failures
**Probability**: MEDIUM
**Impact**: MEDIUM
**Mitigation**:
- Manual mapping UI (Phase 3)
- Alert on >20% unmapped appointments
- Admin override capability

### Risk 3: Performance Impact
**Probability**: LOW
**Impact**: LOW
**Mitigation**:
- Cache existing mappings (resolved via active=true check)
- Database indexes on email, calcom_host_id
- Async processing option for high volume

### Risk 4: Data Inconsistency
**Probability**: LOW
**Impact**: MEDIUM
**Mitigation**:
- Complete audit trail for all changes
- Soft delete for mappings (is_active flag)
- Rollback migrations tested

---

## 📈 Success Criteria

### Phase 2 Complete When:
1. ✅ All migrations deployed without errors
2. ✅ 90%+ of bookings auto-assign staff_id
3. ✅ Email matching strategy achieves 95%+ confidence
4. ✅ Audit trail captures all mapping operations
5. ✅ Zero breaking changes to existing booking flow
6. ✅ All unit/integration tests passing
7. ✅ Production monitoring shows <5% error rate

---

## 🔄 Next Phases (Future)

### Phase 3: Admin UI (Week 3-4)
- Filament resource for CalcomHostMapping management
- Manual mapping creation/override
- Audit log viewer
- Bulk import from Cal.com

### Phase 4: Advanced Matching (Week 5)
- Fuzzy name matching (Levenshtein distance)
- Historical booking pattern analysis
- ML-based confidence scoring

### Phase 5: Cal.com Sync Service (Week 6)
- Periodic sync of Cal.com hosts
- Detect host changes (email updates, new hosts)
- Automatic mapping refresh

---

## 📝 Implementation Commands

### Create Migrations
```bash
php artisan make:migration create_calcom_host_mappings_table
php artisan make:migration create_calcom_host_mapping_audits_table
php artisan make:migration add_calcom_host_id_to_appointments_table
```

### Create Service Classes
```bash
php artisan make:class Services/CalcomHostMappingService
php artisan make:class Services/Strategies/HostMatchingStrategy
php artisan make:class Services/Strategies/EmailMatchingStrategy
php artisan make:class Services/Strategies/NameMatchingStrategy
php artisan make:class Services/Strategies/MatchResult
php artisan make:class Services/Strategies/HostMatchContext
```

### Create Models
```bash
php artisan make:model CalcomHostMapping
php artisan make:model CalcomHostMappingAudit
```

### Run Tests
```bash
php artisan test --filter CalcomHostMappingServiceTest
php artisan test --filter AppointmentStaffAssignmentTest
php artisan test --testsuite Feature
```

### Deploy to Production
```bash
git checkout -b feature/phase-2-staff-assignment
git add .
git commit -m "feat: Phase 2 - Staff assignment from Cal.com hosts array"
git push origin feature/phase-2-staff-assignment
# Create PR → Review → Merge → Deploy
php artisan migrate --force
php artisan cache:clear
php artisan config:clear
```

---

## 📚 Reference Documents

- [Call 687 Complete Analysis](/var/www/api-gateway/claudedocs/CALL_687_COMPLETE_ANALYSIS_2025-10-06.md)
- [Cal.com API Hosts Research](/var/www/api-gateway/claudedocs/calcom_api_hosts_research.md)
- [Booking Error Analysis](/var/www/api-gateway/claudedocs/BOOKING_ERROR_ANALYSIS_2025-10-06.md)
- [Sentiment Fixes](/var/www/api-gateway/claudedocs/SENTIMENT_FIXES_2025-10-06.md)

---

**Status**: ✅ ROADMAP COMPLETE - READY FOR PHASE 2.1 IMPLEMENTATION
**Next Action**: Create database migrations (Phase 2.1)
**Estimated Total Time**: 40 hours (2 weeks with testing)
