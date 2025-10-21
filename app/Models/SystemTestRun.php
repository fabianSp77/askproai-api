<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * System Test Run
 *
 * Records of Cal.com Integration tests executed in admin panel
 * Only accessible to admin@askproai.de
 */
class SystemTestRun extends Model
{
    use HasFactory;

    protected $table = 'system_test_runs';

    protected $fillable = [
        'user_id',
        'test_type',
        'status',
        'output',
        'error_message',
        'started_at',
        'completed_at',
        'duration_ms'
    ];

    protected $casts = [
        'output' => 'json',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Status: pending, running, completed, failed
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Test Types (9 Cal.com Integration Tests)
     */
    public const TEST_EVENT_ID_VERIFICATION = 'event_id_verification';
    public const TEST_AVAILABILITY_CHECK = 'availability_check';
    public const TEST_APPOINTMENT_BOOKING = 'appointment_booking';
    public const TEST_APPOINTMENT_RESCHEDULE = 'appointment_reschedule';
    public const TEST_APPOINTMENT_CANCELLATION = 'appointment_cancellation';
    public const TEST_APPOINTMENT_QUERY = 'appointment_query';
    public const TEST_BIDIRECTIONAL_SYNC = 'bidirectional_sync';
    public const TEST_V2_API_COMPATIBILITY = 'v2_api_compatibility';
    public const TEST_MULTI_TENANT_ISOLATION = 'multi_tenant_isolation';

    public static function testTypes(): array
    {
        return [
            self::TEST_EVENT_ID_VERIFICATION => '1. Event-ID Verification',
            self::TEST_AVAILABILITY_CHECK => '2. Availability Check',
            self::TEST_APPOINTMENT_BOOKING => '3. Appointment Booking',
            self::TEST_APPOINTMENT_RESCHEDULE => '4. Appointment Reschedule',
            self::TEST_APPOINTMENT_CANCELLATION => '5. Appointment Cancellation',
            self::TEST_APPOINTMENT_QUERY => '6. Appointment Query',
            self::TEST_BIDIRECTIONAL_SYNC => '7. Bidirectional Sync',
            self::TEST_V2_API_COMPATIBILITY => '8. V2 API Compatibility',
            self::TEST_MULTI_TENANT_ISOLATION => '9. Multi-Tenant Isolation',
        ];
    }

    /**
     * User who ran the test
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if test is still running
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Check if test succeeded
     */
    public function succeeded(): bool
    {
        return $this->status === self::STATUS_COMPLETED && !$this->error_message;
    }

    /**
     * Get formatted test type label
     */
    public function getTestTypeLabel(): string
    {
        return self::testTypes()[$this->test_type] ?? $this->test_type;
    }

    /**
     * Get duration in seconds (readable)
     */
    public function getDurationSeconds(): float
    {
        return $this->duration_ms ? $this->duration_ms / 1000 : 0;
    }

    /**
     * Mark test as completed
     */
    public function markCompleted(array $output = [], ?string $error = null): void
    {
        $this->update([
            'status' => $error ? self::STATUS_FAILED : self::STATUS_COMPLETED,
            'output' => $output,
            'error_message' => $error,
            'completed_at' => now(),
            'duration_ms' => now()->diffInMilliseconds($this->started_at)
        ]);
    }
}
