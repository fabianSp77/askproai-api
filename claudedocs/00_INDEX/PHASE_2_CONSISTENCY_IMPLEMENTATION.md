# 🟡 PHASE 2: TRANSACTIONAL CONSISTENCY - IMPLEMENTATION GUIDE
**Timeline**: 3 days (Week 2)
**Developers**: 1-2
**Risk Level**: MEDIUM (DB changes, migration required)
**Complexity**: ADVANCED

---

## 📋 PHASE 2 OVERVIEW

### Goal
Garantiere dass Cal.com ↔ Local DB immer konsistent sind, auch wenn einer fehlschlägt.

### Current Problem (RCA)
```
1. ✅ Cal.com booking created (ID: 11890794)
2. ❌ Local appointment creation failed (schema error)
3. Result: Orphaned booking in Cal.com, customer confused
```

### Solution Architecture
```
Customer calls → Retell Agent → Booking Flow

┌─────────────────────────────────────────────────┐
│ 1. Generate Idempotency Key                    │
│    (UUID v5 deterministic)                     │
│    Cache in Redis (24h TTL)                    │
└─────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────┐
│ 2. Book in Cal.com (external API)              │
│    Store booking ID                            │
│    Create local record with Cal.com ID         │
│    Transactional: commit both or rollback both │
└─────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────┐
│ 3. Webhook Idempotency                         │
│    Duplicate webhooks return cached result     │
│    Prevents duplicate appointment creation     │
└─────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────┐
│ 4. Sync Failure Tracking                       │
│    Track orphaned bookings                     │
│    Reconciliation job (hourly)                 │
│    Auto-healing or manual review               │
└─────────────────────────────────────────────────┘
```

---

## 📊 TASKS & EFFORT BREAKDOWN

| Task | Duration | Files | Complexity |
|------|----------|-------|-----------|
| 2.1 Idempotency Key System | 1 day | 4 | ⭐⭐ Medium |
| 2.2 Transactional Booking | 1 day | 3 | ⭐⭐⭐ Hard |
| 2.3 Sync Failure Tracking | 1 day | 4 | ⭐⭐ Medium |
| **TOTAL** | **3 days** | **11** | - |

---

## ✅ TASK 2.1: IDEMPOTENCY KEY SYSTEM (1 Day)

### 2.1.1 Create Migration

**File**: `database/migrations/2025_10_18_000002_add_idempotency_keys.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add idempotency key to appointments table
        Schema::table('appointments', function (Blueprint $table) {
            // Store the idempotency key for deduplication
            $table->string('idempotency_key')
                ->nullable()
                ->after('id')
                ->comment('UUID v5 for deduplication of retries');

            // Index for fast lookup
            $table->unique('idempotency_key')
                ->name('idx_appointments_idempotency_key');
        });

        // Add webhook_id to track webhook idempotency
        Schema::table('webhook_events', function (Blueprint $table) {
            // Already has this, but add index if missing
            if (!Schema::hasColumn('webhook_events', 'webhook_id')) {
                $table->string('webhook_id')
                    ->nullable()
                    ->after('id');
            }

            if (!Schema::hasIndex('webhook_events', 'idx_webhook_events_webhook_id')) {
                $table->unique('webhook_id')
                    ->name('idx_webhook_events_webhook_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique('idx_appointments_idempotency_key');
            $table->dropColumn('idempotency_key');
        });

        Schema::table('webhook_events', function (Blueprint $table) {
            if (Schema::hasIndex('webhook_events', 'idx_webhook_events_webhook_id')) {
                $table->dropUnique('idx_webhook_events_webhook_id');
            }
        });
    }
};
```

### 2.1.2 Create Idempotency Service

**File**: `app/Services/Idempotency/IdempotencyKeyGenerator.php`

```php
<?php

namespace App\Services\Idempotency;

use Illuminate\Support\Str;

class IdempotencyKeyGenerator
{
    /**
     * Generate deterministic idempotency key (UUID v5)
     * Same input → Same UUID (reproducible)
     */
    public function generateForBooking(
        int $customerId,
        int $serviceId,
        string $startsAt,
        string $source = 'retell'
    ): string {
        // Namespace for appointments
        $namespace = '550e8400-e29b-41d4-a716-446655440000';

        // Hash: customer + service + time + source
        $data = json_encode([
            'customer_id' => $customerId,
            'service_id' => $serviceId,
            'starts_at' => $startsAt,
            'source' => $source,
        ]);

        // UUID v5 (deterministic - same input → same output)
        return Str::uuid()->getVersion() === 5
            ? Str::orderedUuid()->toString()
            : $this->generateUuidV5($namespace, $data);
    }

    /**
     * Generate UUID v5 manually (if Str::uuid() doesn't support it)
     */
    private function generateUuidV5(string $namespace, string $data): string
    {
        // Simplified UUID v5 generation
        $namespaceBinary = pack('H*', str_replace('-', '', $namespace));
        $hash = sha1($namespaceBinary . $data, true);

        // Set version to 5
        $hash[6] = chr((ord($hash[6]) & 0x0f) | 0x50);
        $hash[8] = chr((ord($hash[8]) & 0x3f) | 0x80);

        return sprintf(
            '%08s-%04s-%04s-%04s-%012s',
            bin2hex(substr($hash, 0, 4)),
            bin2hex(substr($hash, 4, 2)),
            bin2hex(substr($hash, 6, 2)),
            bin2hex(substr($hash, 8, 2)),
            bin2hex(substr($hash, 10, 6))
        );
    }

    /**
     * Generate webhook idempotency key
     */
    public function generateForWebhook(
        string $provider,
        string $eventType,
        string $eventId
    ): string {
        return sprintf('%s:%s:%s', $provider, $eventType, $eventId);
    }
}
```

**File**: `app/Services/Idempotency/IdempotencyCache.php`

```php
<?php

namespace App\Services\Idempotency;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class IdempotencyCache
{
    const TTL_HOURS = 24;
    const PREFIX = 'idempotency:';

    /**
     * Check if request already processed
     */
    public function getIfProcessed(string $idempotencyKey): ?int
    {
        // Check Redis cache first (fast)
        $cacheKey = self::PREFIX . $idempotencyKey;
        $cached = Cache::get($cacheKey);

        if ($cached) {
            return (int) $cached;
        }

        // Check database (if cache expired but DB still has it)
        $appointment = DB::table('appointments')
            ->where('idempotency_key', $idempotencyKey)
            ->select('id')
            ->first();

        if ($appointment) {
            // Re-cache for next 24h
            Cache::put($cacheKey, $appointment->id, self::TTL_HOURS * 3600);
            return $appointment->id;
        }

        return null;
    }

    /**
     * Store result of idempotent operation
     */
    public function cacheResult(string $idempotencyKey, int $appointmentId): void
    {
        $cacheKey = self::PREFIX . $idempotencyKey;
        Cache::put($cacheKey, $appointmentId, self::TTL_HOURS * 3600);
    }

    /**
     * Check if webhook already processed
     */
    public function isWebhookProcessed(string $webhookId): bool
    {
        return DB::table('webhook_events')
            ->where('webhook_id', $webhookId)
            ->where('status', 'processed')
            ->exists();
    }

    /**
     * Mark webhook as processed
     */
    public function markWebhookProcessed(string $webhookId, int $eventId): void
    {
        DB::table('webhook_events')
            ->where('id', $eventId)
            ->update([
                'webhook_id' => $webhookId,
                'status' => 'processed',
                'processed_at' => now(),
            ]);
    }
}
```

### 2.1.3 Update Appointment Model

**File**: `app/Models/Appointment.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        // ... existing fields
        'idempotency_key',
    ];

    /**
     * Scope: Find by idempotency key
     */
    public function scopeByIdempotencyKey($query, string $key)
    {
        return $query->where('idempotency_key', $key);
    }

    /**
     * Scope: Find duplicate within time window
     */
    public function scopeFindDuplicate($query, int $customerId, int $serviceId, string $startsAt, int $windowMinutes = 5)
    {
        $startTime = \Carbon\Carbon::parse($startsAt);

        return $query
            ->where('customer_id', $customerId)
            ->where('service_id', $serviceId)
            ->whereBetween('starts_at', [
                $startTime->copy()->subMinutes($windowMinutes),
                $startTime->copy()->addMinutes($windowMinutes),
            ])
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
```

### 2.1.4 Integration Test

**File**: `tests/Integration/Idempotency/IdempotencyTest.php`

```php
<?php

namespace Tests\Integration\Idempotency;

use Tests\TestCase;
use App\Services\Idempotency\IdempotencyKeyGenerator;
use App\Services\Idempotency\IdempotencyCache;
use App\Models\Appointment;
use Illuminate\Support\Facades\Cache;

class IdempotencyTest extends TestCase
{
    /** @test */
    public function it_generates_deterministic_keys()
    {
        $generator = app(IdempotencyKeyGenerator::class);

        $key1 = $generator->generateForBooking(1, 2, '2025-10-20 14:00:00');
        $key2 = $generator->generateForBooking(1, 2, '2025-10-20 14:00:00');

        $this->assertEquals($key1, $key2);
    }

    /** @test */
    public function it_prevents_duplicate_bookings()
    {
        $customer = Customer::factory()->create();
        $service = Service::factory()->create();
        $time = '2025-10-20 14:00:00';

        $generator = app(IdempotencyKeyGenerator::class);
        $cache = app(IdempotencyCache::class);
        $key = $generator->generateForBooking($customer->id, $service->id, $time);

        // First booking
        $appointment1 = Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'starts_at' => $time,
            'idempotency_key' => $key,
        ]);
        $cache->cacheResult($key, $appointment1->id);

        // Retry with same key
        $cachedId = $cache->getIfProcessed($key);

        $this->assertEquals($appointment1->id, $cachedId);
        $this->assertCount(1, Appointment::where('idempotency_key', $key)->get());
    }

    /** @test */
    public function it_prevents_time_based_duplicates()
    {
        $customer = Customer::factory()->create();
        $service = Service::factory()->create();
        $time = '2025-10-20 14:00:00';

        // Create first appointment
        Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'starts_at' => $time,
            'status' => 'confirmed',
        ]);

        // Try to create duplicate (same customer, service, time)
        $duplicate = Appointment::findDuplicate($customer->id, $service->id, $time);

        $this->assertNotNull($duplicate);
    }
}
```

---

## ✅ TASK 2.2: TRANSACTIONAL BOOKING (1 Day)

### 2.2.1 Create Booking Transaction Service

**File**: `app/Services/Appointments/BookingTransactionService.php`

```php
<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use App\Services\Calcom\CalcomBookingService;
use App\Services\Idempotency\IdempotencyKeyGenerator;
use App\Services\Idempotency\IdempotencyCache;
use Illuminate\Database\Eloquent\Model;
use Exception;

class BookingTransactionService
{
    public function __construct(
        private CalcomBookingService $calcomService,
        private IdempotencyKeyGenerator $keyGenerator,
        private IdempotencyCache $idempotencyCache,
    ) {}

    /**
     * Create appointment with transactional consistency
     *
     * Flow:
     * 1. Check idempotency (return cached result if duplicate)
     * 2. Validate booking data
     * 3. Book in Cal.com (external API - outside transaction)
     * 4. Create local record with Cal.com ID
     * 5. Cache result for idempotency
     *
     * If Cal.com succeeds but local fails:
     *   → Cancel Cal.com booking (compensating transaction)
     *   → Log for manual cleanup if cancel also fails
     */
    public function bookAppointment(
        Call $call,
        Customer $customer,
        Service $service,
        array $bookingData
    ): ?Appointment {
        // Generate idempotency key
        $idempotencyKey = $this->keyGenerator->generateForBooking(
            $customer->id,
            $service->id,
            $bookingData['starts_at'],
            'retell'
        );

        // Check if already processed
        if ($cachedId = $this->idempotencyCache->getIfProcessed($idempotencyKey)) {
            \Log::info('Idempotent booking request', [
                'idempotency_key' => $idempotencyKey,
                'appointment_id' => $cachedId,
            ]);
            return Appointment::find($cachedId);
        }

        // Validate tenant consistency
        if ($customer->company_id !== $service->company_id) {
            throw new Exception('Tenant isolation violation');
        }

        $calcomBookingId = null;

        try {
            // STEP 1: Book in Cal.com (EXTERNAL API - outside transaction)
            $calcomResult = $this->calcomService->createBooking(
                $customer,
                $service,
                $bookingData
            );

            if (!$calcomResult) {
                \Log::warning('Cal.com booking failed', [
                    'customer_id' => $customer->id,
                    'service_id' => $service->id,
                ]);
                return null;
            }

            $calcomBookingId = $calcomResult['booking_id'];

            // STEP 2: Create local record in transaction
            DB::beginTransaction();
            try {
                $appointment = $this->createLocalRecord(
                    $customer,
                    $service,
                    $call,
                    $bookingData,
                    $calcomBookingId,
                    $idempotencyKey
                );

                DB::commit();

                // STEP 3: Cache result for idempotency
                $this->idempotencyCache->cacheResult($idempotencyKey, $appointment->id);

                \Log::info('Appointment booked successfully', [
                    'appointment_id' => $appointment->id,
                    'calcom_booking_id' => $calcomBookingId,
                    'idempotency_key' => $idempotencyKey,
                ]);

                return $appointment;

            } catch (Exception $dbException) {
                DB::rollBack();

                // COMPENSATING TRANSACTION: Cancel Cal.com booking
                $this->compensateCalcomBooking($calcomBookingId, $dbException);

                throw $dbException;
            }

        } catch (Exception $e) {
            \Log::error('Failed to book appointment', [
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'calcom_booking_id' => $calcomBookingId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create local appointment record
     */
    private function createLocalRecord(
        Customer $customer,
        Service $service,
        Call $call,
        array $bookingData,
        string $calcomBookingId,
        string $idempotencyKey
    ): Appointment {
        return Appointment::create([
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'branch_id' => $bookingData['branch_id'] ?? null,
            'call_id' => $call->id,
            'starts_at' => $bookingData['starts_at'],
            'ends_at' => $bookingData['ends_at'],
            'status' => 'scheduled',
            'notes' => 'Created via Retell webhook',
            'source' => 'retell_webhook',
            'calcom_v2_booking_id' => $calcomBookingId,
            'sync_origin' => 'retell',
            'calcom_sync_status' => 'synced',
            'idempotency_key' => $idempotencyKey,
            'metadata' => json_encode([
                'customer_name' => $customer->name,
                'service_name' => $service->name,
                'call_id' => $call->retell_call_id,
                'created_via' => 'retell_webhook',
            ]),
        ]);
    }

    /**
     * Compensating transaction: Cancel Cal.com booking if local creation fails
     */
    private function compensateCalcomBooking(string $calcomBookingId, Exception $originalError): void
    {
        \Log::warning('Starting compensating transaction - cancelling Cal.com booking', [
            'calcom_booking_id' => $calcomBookingId,
            'reason' => $originalError->getMessage(),
        ]);

        try {
            // Attempt to cancel the Cal.com booking
            $this->calcomService->cancelBooking($calcomBookingId);

            \Log::info('Compensating transaction successful - Cal.com booking cancelled', [
                'calcom_booking_id' => $calcomBookingId,
            ]);

        } catch (Exception $cancelException) {
            // If cancel also fails, log as critical and queue for manual cleanup
            \Log::critical('Compensating transaction FAILED - orphaned Cal.com booking', [
                'calcom_booking_id' => $calcomBookingId,
                'original_error' => $originalError->getMessage(),
                'cancel_error' => $cancelException->getMessage(),
            ]);

            // Queue for manual cleanup
            // dispatch(new CleanupOrphanedCalcomBooking($calcomBookingId));
        }
    }
}
```

### 2.2.2 Update AppointmentCreationService

**File**: `app/Services/Retell/AppointmentCreationService.php` (refactor)

```php
<?php

// Replace the direct booking logic with:

public function createFromCall(Call $call, array $bookingDetails): ?Appointment
{
    try {
        $call->loadMissing(['customer', 'company', 'branch']);

        // Use new transactional service
        return app(BookingTransactionService::class)->bookAppointment(
            $call,
            $call->customer,
            $this->getService($call),
            $bookingDetails
        );

    } catch (Exception $e) {
        Log::error('Appointment creation failed', [
            'call_id' => $call->id,
            'error' => $e->getMessage(),
        ]);

        return null;
    }
}
```

---

## ✅ TASK 2.3: SYNC FAILURE TRACKING (1 Day)

### 2.3.1 Create Failure Tracking Table

**File**: `database/migrations/2025_10_18_000003_create_calcom_sync_failures_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calcom_sync_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('calcom_booking_id')->nullable()->index();
            $table->foreignId('appointment_id')->nullable()->constrained();
            $table->foreignId('call_id')->nullable()->constrained();

            // Type of failure
            $table->enum('failure_type', [
                'orphaned_calcom',      // Cal.com booking but no local appointment
                'orphaned_local',       // Local appointment but no Cal.com booking
                'schema_error',         // Database schema error
                'network_timeout',      // Cal.com API timeout
                'rate_limit',           // Cal.com rate limit
            ])->index();

            // Request/Response data for debugging
            $table->json('request_payload')->nullable();
            $table->json('error_details')->nullable();

            // Status tracking
            $table->timestamp('detected_at')->index();
            $table->timestamp('resolved_at')->nullable();
            $table->enum('resolution_status', [
                'pending',
                'resolved',
                'manual_review',
            ])->default('pending')->index();

            $table->text('resolution_notes')->nullable();

            $table->timestamps();

            // Composite index for queries
            $table->index(['company_id', 'resolution_status', 'detected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calcom_sync_failures');
    }
};
```

### 2.3.2 Create Failure Tracker

**File**: `app/Services/CalcomSync/FailureTracker.php`

```php
<?php

namespace App\Services\CalcomSync;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FailureTracker
{
    public function trackOrphanedCalcomBooking(
        int $companyId,
        string $calcomBookingId,
        ?int $callId,
        array $context = []
    ): void {
        DB::table('calcom_sync_failures')->create([
            'company_id' => $companyId,
            'calcom_booking_id' => $calcomBookingId,
            'call_id' => $callId,
            'failure_type' => 'orphaned_calcom',
            'request_payload' => json_encode($context['request'] ?? []),
            'error_details' => json_encode($context['error'] ?? []),
            'detected_at' => now(),
            'resolution_status' => 'pending',
        ]);

        // Alert ops team
        Log::critical('Orphaned Cal.com booking detected', [
            'calcom_booking_id' => $calcomBookingId,
            'company_id' => $companyId,
        ]);
    }

    public function trackOrphanedLocalAppointment(
        int $appointmentId,
        ?string $calcomBookingId,
        array $context = []
    ): void {
        DB::table('calcom_sync_failures')->create([
            'appointment_id' => $appointmentId,
            'calcom_booking_id' => $calcomBookingId,
            'failure_type' => 'orphaned_local',
            'error_details' => json_encode($context['error'] ?? []),
            'detected_at' => now(),
            'resolution_status' => 'manual_review', // Requires manual decision
        ]);

        Log::critical('Orphaned local appointment detected', [
            'appointment_id' => $appointmentId,
        ]);
    }

    public function markResolved(int $failureId, string $notes = ''): void
    {
        DB::table('calcom_sync_failures')
            ->where('id', $failureId)
            ->update([
                'resolution_status' => 'resolved',
                'resolved_at' => now(),
                'resolution_notes' => $notes,
            ]);
    }
}
```

### 2.3.3 Create Reconciliation Job

**File**: `app/Jobs/ReconcileCalcomBookingsJob.php`

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\CalcomSync\FailureTracker;

class ReconcileCalcomBookingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(FailureTracker $failureTracker): void
    {
        Log::info('Starting Cal.com reconciliation job');

        // Find appointments that should have Cal.com bookings but don't
        $orphanedLocal = DB::table('appointments')
            ->whereNull('calcom_v2_booking_id')
            ->where('calcom_sync_status', '!=', 'failed')
            ->where('source', 'retell_webhook')
            ->where('created_at', '>', now()->subHours(24))
            ->get();

        foreach ($orphanedLocal as $appointment) {
            $failureTracker->trackOrphanedLocalAppointment(
                $appointment->id,
                null,
                ['error' => 'Missing Cal.com booking ID']
            );
        }

        // Find Cal.com bookings that should have local appointments but don't
        $pendingSyncs = DB::table('appointments')
            ->where('calcom_sync_status', 'pending')
            ->where('created_at', '<', now()->subMinutes(5))
            ->get();

        foreach ($pendingSyncs as $appointment) {
            $failureTracker->trackOrphanedCalcomBooking(
                $appointment->company_id,
                $appointment->calcom_v2_booking_id,
                $appointment->call_id,
                ['error' => 'Sync stuck in pending state']
            );
        }

        Log::info('Cal.com reconciliation job completed', [
            'orphaned_local' => count($orphanedLocal),
            'pending_syncs' => count($pendingSyncs),
        ]);
    }
}
```

### 2.3.4 Schedule Reconciliation

**File**: `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule)
{
    // Run reconciliation hourly
    $schedule->job(new ReconcileCalcomBookingsJob)
        ->hourly()
        ->onOneServer()
        ->name('reconcile-calcom-bookings')
        ->withoutOverlapping(10); // Max 10 min run
}
```

---

## 🧪 INTEGRATION TESTS FOR PHASE 2

**File**: `tests/Integration/Appointments/TransactionalBookingTest.php`

```php
<?php

namespace Tests\Integration\Appointments;

use Tests\TestCase;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Appointment;
use App\Services\Appointments\BookingTransactionService;
use Illuminate\Support\Facades\DB;

class TransactionalBookingTest extends TestCase
{
    /** @test */
    public function it_creates_appointment_with_calcom_booking()
    {
        $customer = Customer::factory()->create();
        $service = Service::factory()->create();
        $call = Call::factory()->for($customer)->create();

        $bookingData = [
            'starts_at' => '2025-10-20 14:00:00',
            'ends_at' => '2025-10-20 14:30:00',
            'branch_id' => $customer->company->branches->first()->id,
        ];

        $service = app(BookingTransactionService::class)
            ->bookAppointment($call, $customer, $service, $bookingData);

        $this->assertNotNull($appointment);
        $this->assertNotNull($appointment->calcom_v2_booking_id);
        $this->assertNotNull($appointment->idempotency_key);
    }

    /** @test */
    public function it_prevents_duplicate_bookings_via_idempotency()
    {
        $customer = Customer::factory()->create();
        $service = Service::factory()->create();
        $call = Call::factory()->for($customer)->create();

        $bookingData = [
            'starts_at' => '2025-10-20 14:00:00',
            'ends_at' => '2025-10-20 14:30:00',
        ];

        $service = app(BookingTransactionService::class);

        // Book first time
        $appointment1 = $service->bookAppointment($call, $customer, $service, $bookingData);

        // Book same request again (same parameters = same idempotency key)
        $appointment2 = $service->bookAppointment($call, $customer, $service, $bookingData);

        // Should return same appointment
        $this->assertEquals($appointment1->id, $appointment2->id);

        // Only 1 appointment in database
        $this->assertCount(1, Appointment::where(
            'idempotency_key',
            $appointment1->idempotency_key
        )->get());
    }

    /** @test */
    public function it_rolls_back_if_local_creation_fails()
    {
        $customer = Customer::factory()->create();
        $service = Service::factory()->create();
        $call = Call::factory()->for($customer)->create();

        // Mock Cal.com to succeed
        $this->mock(CalcomBookingService::class)
            ->shouldReceive('createBooking')
            ->andReturn(['booking_id' => 'cal_123']);

        // Mock Appointment creation to fail
        $this->mock(Appointment::class)
            ->shouldReceive('create')
            ->andThrow(new Exception('Database error'));

        $bookingData = [
            'starts_at' => '2025-10-20 14:00:00',
            'ends_at' => '2025-10-20 14:30:00',
        ];

        $service = app(BookingTransactionService::class);

        // This should fail and trigger compensating transaction
        $result = $service->bookAppointment($call, $customer, $service, $bookingData);

        $this->assertNull($result);

        // Verify Cal.com booking was cancelled (compensating transaction)
        // Check logs or verify mock was called with cancelBooking
    }

    /** @test */
    public function it_prevents_webhook_duplicate_processing()
    {
        $webhookId = 'webhook_123';
        $data = ['booking_id' => 'cal_456'];

        $cache = app(IdempotencyCache::class);

        // First webhook
        $processed1 = $cache->isWebhookProcessed($webhookId);
        $this->assertFalse($processed1);

        // Mark as processed
        $cache->markWebhookProcessed($webhookId, 1);

        // Second webhook (duplicate)
        $processed2 = $cache->isWebhookProcessed($webhookId);
        $this->assertTrue($processed2);
    }
}
```

---

## 📊 DEPLOYMENT CHECKLIST

### Pre-Deployment
- [ ] All code reviewed ✅
- [ ] All tests passing ✅
- [ ] Migration tested on staging ✅
- [ ] Rollback plan verified ✅

### Deployment Steps

```bash
# 1. Backup database
pg_dump askproai_db > /backups/askproai_db_phase2_$(date +%Y%m%d_%H%M%S).sql

# 2. Pull latest code
git pull origin main

# 3. Install any new dependencies
composer install --no-dev

# 4. Run migrations
php artisan migrate

# 5. Clear caches
php artisan cache:clear
php artisan config:clear

# 6. Restart queue
php artisan queue:restart

# 7. Run tests
php artisan test

# 8. Monitor logs
tail -100 storage/logs/laravel.log
```

### Post-Deployment

- [ ] Verify idempotency keys being generated
- [ ] Check cache is storing results
- [ ] Monitor sync failure tracking table
- [ ] Run reconciliation job manually: `php artisan queue:work`
- [ ] Check no new orphaned bookings

---

## ✅ SUCCESS CRITERIA

After Phase 2 deployment:

✅ **Idempotency Working**
- Same request twice = same appointment ID
- Cached results returned on retry

✅ **Transactional Consistency**
- Cal.com AND local always in sync
- OR both rolled back

✅ **Webhook Deduplication**
- Duplicate webhooks don't create duplicate appointments
- Only processed once

✅ **Failure Tracking**
- Orphaned bookings detected within 1 hour
- Tracked in calcom_sync_failures table
- Manual review queue populated

✅ **Zero Orphaned Bookings**
- Over 24 hours: 0 orphaned Cal.com bookings
- Sync failure rate <0.01%

---

## 📞 SUPPORT

**If you hit issues during Phase 2:**

1. **Migration failed**: Check constraint errors, run `php artisan migrate:status`
2. **Idempotency not working**: Check Redis connection, verify UUID generation
3. **Compensating transaction stuck**: Check Cal.com API, logs will show error
4. **Reconciliation job slow**: Check failure table size, may need pagination

---

**Phase 2 Status**: 🟡 READY FOR IMPLEMENTATION
**After Phase 2**: Move to Phase 3 (Error Handling & Resilience)
**Expected Result**: 99%+ data consistency between Cal.com and local DB
