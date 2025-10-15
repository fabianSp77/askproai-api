# Cal.com Sync Verification Architecture
**System Architect Design Document**
**Created**: 2025-10-11
**Status**: Design Complete - Ready for Implementation

## Executive Summary

Complete architecture for detecting, tracking, and resolving Cal.com synchronization failures. When appointments exist in database but not in Cal.com (or vice versa), the system flags them for manual review and provides comprehensive retry mechanisms.

## Problem Statement

**User Requirement (German)**:
> "Wenn Termin in DB aber nicht in Cal.com → markieren und für manuelle Überprüfung flaggen. Termine die nicht vollständig synchronisiert sind sollen sichtbar sein für Firma."

**Translation**:
When appointment exists in DB but not in Cal.com → mark and flag for manual verification. Appointments that are not fully synchronized should be visible to the company.

### Current Sync Mechanism Analysis

**Booking Flow** (analyzed from CalcomService.php & AppointmentCreationService.php):

```
1. Customer books via Retell/Phone → AppointmentCreationService
2. Service calls CalcomService::createBooking()
3. Cal.com responds with booking_id
4. Appointment saved with calcom_v2_booking_id
```

**Identified Failure Points**:

| Failure Point | Current Behavior | Impact |
|--------------|------------------|--------|
| Cal.com API timeout | CircuitBreaker opens, no DB record created | Lost booking |
| DB save fails after Cal.com success | Orphaned Cal.com booking | Ghost booking |
| Cal.com accepts but silently fails | Returns 200 but booking not created | Silent failure |
| Network interruption mid-request | Uncertain state | Data inconsistency |
| Webhook delivery failure | No DB update for Cal.com-created booking | Missing appointment |

**Current Error Handling**:
- CircuitBreaker pattern (5 failures → 60s timeout)
- CalcomApiException for API errors
- Duplicate prevention via calcom_v2_booking_id check
- NO verification mechanism post-creation
- NO recovery mechanism for partial failures

## Architecture Design

### 1. Database Schema Changes

**Migration**: `2025_10_11_add_calcom_sync_tracking_to_appointments.php`

```php
Schema::table('appointments', function (Blueprint $table) {
    // Sync status tracking
    $table->enum('calcom_sync_status', [
        'synced',           // Verified present in both systems
        'pending',          // Created locally, awaiting Cal.com confirmation
        'failed',           // Sync attempt failed
        'orphaned_local',   // Exists in DB but not in Cal.com
        'orphaned_calcom',  // Exists in Cal.com but not in DB
        'verification_pending' // Scheduled for verification
    ])->default('pending')->after('calcom_v2_booking_id');

    // Sync attempt tracking
    $table->timestamp('last_sync_attempt_at')->nullable()
        ->after('calcom_sync_status')
        ->comment('Last verification attempt timestamp');

    $table->unsignedTinyInteger('sync_attempt_count')->default(0)
        ->after('last_sync_attempt_at')
        ->comment('Number of sync verification attempts');

    // Error details
    $table->text('sync_error_message')->nullable()
        ->after('sync_attempt_count')
        ->comment('Last sync error details');

    $table->string('sync_error_code', 50)->nullable()
        ->after('sync_error_message')
        ->comment('Error classification code');

    // Recovery tracking
    $table->timestamp('sync_verified_at')->nullable()
        ->after('sync_error_code')
        ->comment('Last successful verification timestamp');

    $table->boolean('requires_manual_review')->default(false)
        ->after('sync_verified_at')
        ->comment('Flagged for admin attention');

    $table->timestamp('manual_review_flagged_at')->nullable()
        ->after('requires_manual_review')
        ->comment('When flagged for manual review');

    // Indexes for performance
    $table->index(['calcom_sync_status', 'company_id'], 'idx_appointments_sync_status');
    $table->index(['requires_manual_review', 'company_id'], 'idx_appointments_manual_review');
    $table->index(['last_sync_attempt_at'], 'idx_appointments_last_sync');
});
```

**Sync Status State Machine**:

```
pending → [verification] → synced (success)
                        ↓
                      failed → [retry] → synced
                        ↓
                   orphaned_local → [manual_review]
                        ↓
                   orphaned_calcom → [manual_review]
```

### 2. Sync Verification Job

**File**: `/var/www/api-gateway/app/Jobs/VerifyCalcomSyncJob.php`

```php
<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\CalcomService;
use App\Services\CalcomSyncVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Verify Cal.com Sync Status Job
 *
 * Checks if appointments exist in both DB and Cal.com
 * Flags discrepancies for manual review
 */
class VerifyCalcomSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutes
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    protected ?int $appointmentId;
    protected ?int $companyId;
    protected bool $verifyAll;

    /**
     * @param int|null $appointmentId Specific appointment to verify
     * @param int|null $companyId Verify all appointments for company
     * @param bool $verifyAll Verify all pending appointments
     */
    public function __construct(
        ?int $appointmentId = null,
        ?int $companyId = null,
        bool $verifyAll = false
    ) {
        $this->appointmentId = $appointmentId;
        $this->companyId = $companyId;
        $this->verifyAll = $verifyAll;
    }

    public function handle(CalcomSyncVerificationService $verificationService): void
    {
        Log::info('VerifyCalcomSyncJob started', [
            'appointment_id' => $this->appointmentId,
            'company_id' => $this->companyId,
            'verify_all' => $this->verifyAll
        ]);

        // Specific appointment verification
        if ($this->appointmentId) {
            $appointment = Appointment::find($this->appointmentId);
            if ($appointment) {
                $verificationService->verifyAppointment($appointment);
            }
            return;
        }

        // Company-wide verification
        if ($this->companyId) {
            $appointments = Appointment::where('company_id', $this->companyId)
                ->whereIn('calcom_sync_status', ['pending', 'failed', 'verification_pending'])
                ->where('starts_at', '>', now()->subDays(90)) // Last 90 days
                ->get();

            foreach ($appointments as $appointment) {
                $verificationService->verifyAppointment($appointment);
            }
            return;
        }

        // System-wide verification (scheduled job)
        if ($this->verifyAll) {
            $appointments = Appointment::whereIn('calcom_sync_status', ['pending', 'failed'])
                ->where('starts_at', '>', now()->subDays(30)) // Last 30 days
                ->where(function ($query) {
                    $query->whereNull('last_sync_attempt_at')
                        ->orWhere('last_sync_attempt_at', '<', now()->subHours(6));
                })
                ->limit(100) // Process in batches
                ->get();

            Log::info('System-wide verification', [
                'appointments_to_verify' => $appointments->count()
            ]);

            foreach ($appointments as $appointment) {
                $verificationService->verifyAppointment($appointment);
            }
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('VerifyCalcomSyncJob failed', [
            'appointment_id' => $this->appointmentId,
            'company_id' => $this->companyId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
```

### 3. Sync Verification Service

**File**: `/var/www/api-gateway/app/Services/CalcomSyncVerificationService.php`

```php
<?php

namespace App\Services;

use App\Models\Appointment;
use App\Services\CalcomService;
use App\Notifications\CalcomSyncFailureNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

/**
 * Cal.com Sync Verification Service
 *
 * Verifies appointment sync status between local DB and Cal.com
 * Flags discrepancies and triggers notifications
 */
class CalcomSyncVerificationService
{
    protected CalcomService $calcomService;
    protected int $maxRetries = 3;
    protected int $verificationWindow = 90; // days

    public function __construct(CalcomService $calcomService)
    {
        $this->calcomService = $calcomService;
    }

    /**
     * Verify single appointment sync status
     */
    public function verifyAppointment(Appointment $appointment): array
    {
        // Update attempt tracking
        $appointment->increment('sync_attempt_count');
        $appointment->update(['last_sync_attempt_at' => now()]);

        Log::info('Verifying appointment sync', [
            'appointment_id' => $appointment->id,
            'calcom_v2_booking_id' => $appointment->calcom_v2_booking_id,
            'current_status' => $appointment->calcom_sync_status
        ]);

        // Case 1: No Cal.com booking ID - orphaned local appointment
        if (!$appointment->calcom_v2_booking_id) {
            return $this->handleOrphanedLocal($appointment);
        }

        // Case 2: Verify Cal.com booking exists
        try {
            $calcomBooking = $this->fetchCalcomBooking($appointment->calcom_v2_booking_id);

            if ($calcomBooking) {
                // Booking exists - verify data consistency
                return $this->verifyDataConsistency($appointment, $calcomBooking);
            } else {
                // Booking doesn't exist in Cal.com
                return $this->handleOrphanedLocal($appointment);
            }
        } catch (\Exception $e) {
            return $this->handleVerificationError($appointment, $e);
        }
    }

    /**
     * Fetch booking from Cal.com API
     */
    protected function fetchCalcomBooking(string $bookingId): ?array
    {
        try {
            // Cal.com V2 API: GET /bookings/{id}
            $response = $this->calcomService->getBooking($bookingId);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? $data;
            }

            if ($response->status() === 404) {
                return null; // Booking not found
            }

            throw new \Exception("Cal.com API error: " . $response->status());
        } catch (\Exception $e) {
            Log::error('Failed to fetch Cal.com booking', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Verify data consistency between local and Cal.com
     */
    protected function verifyDataConsistency(Appointment $appointment, array $calcomData): array
    {
        $inconsistencies = [];

        // Check start time consistency (allow 1 minute tolerance)
        $localStart = Carbon::parse($appointment->starts_at);
        $calcomStart = Carbon::parse($calcomData['startTime'] ?? $calcomData['start']);

        if ($localStart->diffInMinutes($calcomStart) > 1) {
            $inconsistencies[] = [
                'field' => 'start_time',
                'local' => $localStart->toIso8601String(),
                'calcom' => $calcomStart->toIso8601String()
            ];
        }

        // Check status consistency
        $calcomStatus = $this->mapCalcomStatus($calcomData['status'] ?? 'ACCEPTED');
        if ($appointment->status !== $calcomStatus) {
            $inconsistencies[] = [
                'field' => 'status',
                'local' => $appointment->status,
                'calcom' => $calcomStatus
            ];
        }

        if (empty($inconsistencies)) {
            // Perfect sync - mark as verified
            $appointment->update([
                'calcom_sync_status' => 'synced',
                'sync_verified_at' => now(),
                'sync_error_message' => null,
                'sync_error_code' => null
            ]);

            Log::info('Appointment sync verified successfully', [
                'appointment_id' => $appointment->id
            ]);

            return [
                'status' => 'synced',
                'verified_at' => now(),
                'inconsistencies' => []
            ];
        } else {
            // Data inconsistencies found
            return $this->handleDataInconsistency($appointment, $inconsistencies);
        }
    }

    /**
     * Handle appointment with no Cal.com booking
     */
    protected function handleOrphanedLocal(Appointment $appointment): array
    {
        $appointment->update([
            'calcom_sync_status' => 'orphaned_local',
            'sync_error_message' => 'Appointment exists in DB but not in Cal.com',
            'sync_error_code' => 'ORPHANED_LOCAL',
            'requires_manual_review' => true,
            'manual_review_flagged_at' => now()
        ]);

        // Notify admins
        $this->notifyAdmins($appointment, 'orphaned_local');

        Log::warning('Orphaned local appointment detected', [
            'appointment_id' => $appointment->id,
            'customer' => $appointment->customer->name ?? 'Unknown'
        ]);

        return [
            'status' => 'orphaned_local',
            'requires_review' => true,
            'error' => 'Appointment not found in Cal.com'
        ];
    }

    /**
     * Handle data inconsistencies
     */
    protected function handleDataInconsistency(Appointment $appointment, array $inconsistencies): array
    {
        $appointment->update([
            'calcom_sync_status' => 'failed',
            'sync_error_message' => json_encode([
                'type' => 'data_inconsistency',
                'inconsistencies' => $inconsistencies
            ]),
            'sync_error_code' => 'DATA_MISMATCH',
            'requires_manual_review' => true,
            'manual_review_flagged_at' => now()
        ]);

        // Notify admins
        $this->notifyAdmins($appointment, 'data_mismatch', $inconsistencies);

        Log::warning('Data inconsistency detected', [
            'appointment_id' => $appointment->id,
            'inconsistencies' => $inconsistencies
        ]);

        return [
            'status' => 'failed',
            'requires_review' => true,
            'inconsistencies' => $inconsistencies
        ];
    }

    /**
     * Handle verification errors
     */
    protected function handleVerificationError(Appointment $appointment, \Exception $e): array
    {
        $appointment->update([
            'calcom_sync_status' => 'failed',
            'sync_error_message' => $e->getMessage(),
            'sync_error_code' => 'VERIFICATION_ERROR',
            'requires_manual_review' => $appointment->sync_attempt_count >= $this->maxRetries
        ]);

        if ($appointment->sync_attempt_count >= $this->maxRetries) {
            $this->notifyAdmins($appointment, 'verification_error');
        }

        Log::error('Verification error', [
            'appointment_id' => $appointment->id,
            'error' => $e->getMessage(),
            'attempts' => $appointment->sync_attempt_count
        ]);

        return [
            'status' => 'failed',
            'error' => $e->getMessage(),
            'attempts' => $appointment->sync_attempt_count
        ];
    }

    /**
     * Map Cal.com status to local status
     */
    protected function mapCalcomStatus(string $calcomStatus): string
    {
        return match($calcomStatus) {
            'ACCEPTED' => 'confirmed',
            'PENDING' => 'pending',
            'CANCELLED' => 'cancelled',
            'REJECTED' => 'cancelled',
            default => 'scheduled'
        };
    }

    /**
     * Notify admins about sync issues
     */
    protected function notifyAdmins(Appointment $appointment, string $issueType, array $details = []): void
    {
        // Get company admins
        $admins = $appointment->company->users()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['admin', 'manager']);
            })
            ->get();

        if ($admins->isEmpty()) {
            Log::warning('No admins to notify for sync issue', [
                'company_id' => $appointment->company_id,
                'appointment_id' => $appointment->id
            ]);
            return;
        }

        Notification::send(
            $admins,
            new CalcomSyncFailureNotification($appointment, $issueType, $details)
        );
    }

    /**
     * Manually retry sync for appointment
     */
    public function retrySync(Appointment $appointment): array
    {
        Log::info('Manual sync retry initiated', [
            'appointment_id' => $appointment->id,
            'previous_status' => $appointment->calcom_sync_status
        ]);

        // Reset retry count for manual retry
        $appointment->update([
            'sync_attempt_count' => 0,
            'calcom_sync_status' => 'verification_pending'
        ]);

        return $this->verifyAppointment($appointment);
    }

    /**
     * Get sync statistics for company
     */
    public function getSyncStats(int $companyId): array
    {
        $stats = Appointment::where('company_id', $companyId)
            ->selectRaw('
                calcom_sync_status,
                COUNT(*) as count,
                MAX(last_sync_attempt_at) as last_attempt
            ')
            ->groupBy('calcom_sync_status')
            ->get()
            ->keyBy('calcom_sync_status')
            ->toArray();

        return [
            'synced' => $stats['synced']['count'] ?? 0,
            'pending' => $stats['pending']['count'] ?? 0,
            'failed' => $stats['failed']['count'] ?? 0,
            'orphaned_local' => $stats['orphaned_local']['count'] ?? 0,
            'orphaned_calcom' => $stats['orphaned_calcom']['count'] ?? 0,
            'requires_review' => Appointment::where('company_id', $companyId)
                ->where('requires_manual_review', true)
                ->count(),
            'last_verification' => $stats['synced']['last_attempt'] ?? null
        ];
    }
}
```

### 4. Cal.com Service Extension

**Add to**: `/var/www/api-gateway/app/Services/CalcomService.php`

```php
/**
 * Get booking details from Cal.com
 *
 * @param string $bookingId Cal.com booking ID
 * @return Response
 */
public function getBooking(string $bookingId): Response
{
    try {
        return $this->circuitBreaker->call(function() use ($bookingId) {
            $fullUrl = $this->baseUrl . '/bookings/' . $bookingId;
            $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => config('services.calcom.api_version', '2024-08-13'),
            ])->acceptJson()->timeout(10)->get($fullUrl);

            Log::channel('calcom')->debug('[Cal.com V2] Get Booking Response:', [
                'booking_id' => $bookingId,
                'status' => $resp->status(),
                'exists' => $resp->successful()
            ]);

            return $resp;
        });
    } catch (CircuitBreakerOpenException $e) {
        Log::error('Circuit breaker OPEN for getBooking', [
            'booking_id' => $bookingId
        ]);

        throw new CalcomApiException(
            'Cal.com API circuit breaker is open',
            null,
            "/bookings/{$bookingId}",
            [],
            503
        );
    }
}
```

### 5. Admin Notification

**File**: `/var/www/api-gateway/app/Notifications/CalcomSyncFailureNotification.php`

```php
<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class CalcomSyncFailureNotification extends Notification
{
    use Queueable;

    protected Appointment $appointment;
    protected string $issueType;
    protected array $details;

    public function __construct(Appointment $appointment, string $issueType, array $details = [])
    {
        $this->appointment = $appointment;
        $this->issueType = $issueType;
        $this->details = $details;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = url('/admin/appointments/' . $this->appointment->id . '/sync-review');

        return (new MailMessage)
            ->subject('Cal.com Sync Issue: Appointment #' . $this->appointment->id)
            ->warning()
            ->line('An appointment synchronization issue has been detected.')
            ->line('**Issue Type**: ' . $this->getIssueDescription())
            ->line('**Appointment**: #' . $this->appointment->id)
            ->line('**Customer**: ' . $this->appointment->customer->name ?? 'Unknown')
            ->line('**Time**: ' . $this->appointment->starts_at->format('d.m.Y H:i'))
            ->action('Review Appointment', $url)
            ->line('Please review and resolve this sync issue.');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'calcom_sync_failure',
            'issue_type' => $this->issueType,
            'appointment_id' => $this->appointment->id,
            'customer_name' => $this->appointment->customer->name ?? 'Unknown',
            'appointment_time' => $this->appointment->starts_at,
            'details' => $this->details,
            'requires_action' => true,
            'url' => '/admin/appointments/' . $this->appointment->id . '/sync-review'
        ];
    }

    protected function getIssueDescription(): string
    {
        return match($this->issueType) {
            'orphaned_local' => 'Appointment exists in database but not in Cal.com',
            'orphaned_calcom' => 'Appointment exists in Cal.com but not in database',
            'data_mismatch' => 'Data inconsistency between database and Cal.com',
            'verification_error' => 'Unable to verify sync status after multiple attempts',
            default => 'Unknown sync issue'
        };
    }
}
```

### 6. Scheduled Job Configuration

**Add to**: `/var/www/api-gateway/app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule): void
{
    // Verify Cal.com sync status every 6 hours
    $schedule->job(new VerifyCalcomSyncJob(verifyAll: true))
        ->everySixHours()
        ->name('calcom-sync-verification')
        ->withoutOverlapping()
        ->onOneServer();

    // Daily comprehensive verification for flagged appointments
    $schedule->job(new VerifyCalcomSyncJob(verifyAll: true))
        ->daily()
        ->at('02:00')
        ->name('calcom-sync-daily-check')
        ->withoutOverlapping()
        ->onOneServer();
}
```

### 7. Dashboard Widget (Filament)

**File**: `/var/www/api-gateway/app/Filament/Widgets/CalcomSyncStatusWidget.php`

```php
<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Services\CalcomSyncVerificationService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CalcomSyncStatusWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $companyId = Auth::user()->company_id;
        $verificationService = app(CalcomSyncVerificationService::class);
        $stats = $verificationService->getSyncStats($companyId);

        return [
            Stat::make('Synced Appointments', $stats['synced'])
                ->description('Successfully synchronized')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Pending Verification', $stats['pending'])
                ->description('Awaiting sync verification')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Requires Review', $stats['requires_review'])
                ->description('Manual review needed')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->url(route('filament.admin.resources.appointments.index', [
                    'tableFilters[requires_manual_review][value]' => true
                ])),

            Stat::make('Sync Failures', $stats['failed'] + $stats['orphaned_local'])
                ->description('Failed or orphaned')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}
```

### 8. Manual Retry Action (Filament)

**Add to**: Appointment Resource Actions

```php
use App\Services\CalcomSyncVerificationService;

Tables\Actions\Action::make('retry_sync')
    ->label('Retry Sync')
    ->icon('heroicon-o-arrow-path')
    ->color('warning')
    ->visible(fn (Appointment $record) =>
        in_array($record->calcom_sync_status, ['failed', 'orphaned_local', 'verification_pending'])
    )
    ->requiresConfirmation()
    ->action(function (Appointment $record) {
        $service = app(CalcomSyncVerificationService::class);
        $result = $service->retrySync($record);

        if ($result['status'] === 'synced') {
            Notification::make()
                ->success()
                ->title('Sync successful')
                ->send();
        } else {
            Notification::make()
                ->warning()
                ->title('Sync verification completed')
                ->body($result['error'] ?? 'Still requires review')
                ->send();
        }
    }),
```

## Implementation Plan

### Phase 1: Database & Core Service (Week 1)

1. **Migration** (Day 1)
   - Create migration for sync tracking fields
   - Test migration on staging
   - Deploy to production

2. **CalcomService Extension** (Day 1-2)
   - Add `getBooking()` method
   - Test API endpoint
   - Add error handling

3. **CalcomSyncVerificationService** (Day 2-3)
   - Implement verification logic
   - Add data consistency checks
   - Test with real appointments

### Phase 2: Jobs & Notifications (Week 1)

4. **VerifyCalcomSyncJob** (Day 3-4)
   - Implement job logic
   - Add queue configuration
   - Test batch processing

5. **CalcomSyncFailureNotification** (Day 4)
   - Email notification template
   - Database notification
   - Test notification delivery

### Phase 3: UI & Automation (Week 2)

6. **Dashboard Widget** (Day 5-6)
   - Implement Filament widget
   - Add real-time stats
   - Test in admin panel

7. **Manual Retry Actions** (Day 6-7)
   - Add Filament actions
   - Implement retry logic
   - Test manual intervention

8. **Scheduled Jobs** (Day 7)
   - Configure Laravel scheduler
   - Test automated verification
   - Monitor job execution

### Phase 4: Testing & Deployment (Week 2)

9. **Integration Testing** (Day 8-9)
   - Test failure scenarios
   - Verify notification delivery
   - Test retry mechanisms

10. **Documentation & Training** (Day 9-10)
    - Admin user guide
    - Troubleshooting procedures
    - Deploy to production

## Testing Strategy

### Unit Tests

```php
// tests/Unit/Services/CalcomSyncVerificationServiceTest.php

test('detects orphaned local appointments', function () {
    $appointment = Appointment::factory()->create([
        'calcom_v2_booking_id' => 'non-existent-id',
        'calcom_sync_status' => 'pending'
    ]);

    $service = app(CalcomSyncVerificationService::class);
    $result = $service->verifyAppointment($appointment);

    expect($result['status'])->toBe('orphaned_local')
        ->and($appointment->fresh()->requires_manual_review)->toBeTrue();
});

test('verifies data consistency', function () {
    // Mock Cal.com response
    Http::fake([
        '*/bookings/*' => Http::response([
            'data' => [
                'id' => 'test-booking-id',
                'startTime' => now()->toIso8601String(),
                'status' => 'ACCEPTED'
            ]
        ])
    ]);

    $appointment = Appointment::factory()->create([
        'calcom_v2_booking_id' => 'test-booking-id',
        'starts_at' => now(),
        'status' => 'confirmed'
    ]);

    $service = app(CalcomSyncVerificationService::class);
    $result = $service->verifyAppointment($appointment);

    expect($result['status'])->toBe('synced');
});
```

### Integration Tests

```php
// tests/Feature/CalcomSyncVerificationTest.php

test('scheduled job verifies pending appointments', function () {
    // Create test appointments
    Appointment::factory()->count(5)->create([
        'calcom_sync_status' => 'pending'
    ]);

    // Run job
    VerifyCalcomSyncJob::dispatch(verifyAll: true);

    // Verify job processed appointments
    expect(Appointment::where('calcom_sync_status', 'pending')->count())
        ->toBeLessThan(5);
});
```

## Monitoring & Alerts

### Metrics to Track

| Metric | Description | Alert Threshold |
|--------|-------------|-----------------|
| Orphaned appointments | Appointments without Cal.com booking | > 5 per day |
| Verification failures | Failed verification attempts | > 10 per hour |
| Manual review queue | Appointments requiring review | > 20 total |
| Sync success rate | Percentage of successful syncs | < 95% |

### Logging

```php
// All sync operations logged to 'calcom' channel
Log::channel('calcom')->info('Sync verification', [
    'appointment_id' => $id,
    'status' => $status,
    'attempt' => $attemptCount
]);
```

## Security Considerations

1. **API Rate Limiting**: Circuit breaker prevents API abuse
2. **Tenant Isolation**: All queries filtered by company_id
3. **Authentication**: Only admins can retry sync
4. **Data Validation**: All Cal.com responses validated before processing
5. **Error Sanitization**: Sensitive data removed from error messages

## Performance Optimization

1. **Batch Processing**: Verify max 100 appointments per job run
2. **Queue Priority**: High priority for manual retries
3. **Caching**: Cache verification results for 5 minutes
4. **Indexes**: Optimized indexes for sync status queries
5. **Lazy Loading**: Prevent N+1 queries with eager loading

## Rollback Plan

If issues occur:

1. **Migration Rollback**: `php artisan migrate:rollback`
2. **Disable Scheduled Jobs**: Comment out in Kernel.php
3. **Clear Queue**: `php artisan queue:clear`
4. **Revert Code**: Git revert to previous commit

## Success Criteria

- 95%+ sync verification rate
- < 1% orphaned appointments
- Manual review queue < 20 appointments
- Notifications delivered within 5 minutes
- Zero data loss during verification

## Files Created/Modified Summary

### New Files
- `/var/www/api-gateway/database/migrations/2025_10_11_add_calcom_sync_tracking_to_appointments.php`
- `/var/www/api-gateway/app/Jobs/VerifyCalcomSyncJob.php`
- `/var/www/api-gateway/app/Services/CalcomSyncVerificationService.php`
- `/var/www/api-gateway/app/Notifications/CalcomSyncFailureNotification.php`
- `/var/www/api-gateway/app/Filament/Widgets/CalcomSyncStatusWidget.php`
- `/var/www/api-gateway/tests/Unit/Services/CalcomSyncVerificationServiceTest.php`
- `/var/www/api-gateway/tests/Feature/CalcomSyncVerificationTest.php`

### Modified Files
- `/var/www/api-gateway/app/Services/CalcomService.php` (add getBooking method)
- `/var/www/api-gateway/app/Console/Kernel.php` (add scheduled jobs)
- `/var/www/api-gateway/app/Filament/Resources/AppointmentResource.php` (add retry action)

## Next Steps

1. Review architecture with team
2. Approve implementation plan
3. Begin Phase 1 development
4. Schedule staging deployment
5. Plan production rollout

---

**Architecture Status**: ✅ Complete
**Ready for Implementation**: Yes
**Estimated Development Time**: 2 weeks
**Risk Level**: Low (non-breaking changes)
