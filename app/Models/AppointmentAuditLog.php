<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Appointment Audit Log Model
 *
 * PURPOSE: Immutable audit trail for compliance and debugging
 *
 * TRACKED ACTIONS:
 * - created: Initial appointment creation
 * - rescheduled: Time/service change
 * - cancelled: Appointment cancellation
 * - restored: Cancelled appointment reactivation
 * - status_changed: Status transitions
 * - staff_changed: Staff reassignment
 * - calcom_sync_failed: Cal.com sync failures
 *
 * COMPLIANCE:
 * - GDPR: Tracks data modifications with user attribution
 * - SOC2: Audit trail for access control
 * - ISO 27001: Information security audit requirements
 */
class AppointmentAuditLog extends Model
{
    const UPDATED_AT = null; // Immutable records

    protected $fillable = [
        'appointment_id',
        'user_id',
        'action',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'reason',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    // ==========================================
    // ACTION CONSTANTS
    // ==========================================

    const ACTION_CREATED = 'created';
    const ACTION_RESCHEDULED = 'rescheduled';
    const ACTION_CANCELLED = 'cancelled';
    const ACTION_RESTORED = 'restored';
    const ACTION_STATUS_CHANGED = 'status_changed';
    const ACTION_STAFF_CHANGED = 'staff_changed';
    const ACTION_CALCOM_SYNC_FAILED = 'calcom_sync_failed';
    const ACTION_CALCOM_SYNCED = 'calcom_synced';

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeForAppointment($query, int $appointmentId)
    {
        return $query->where('appointment_id', $appointmentId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ==========================================
    // BUSINESS LOGIC
    // ==========================================

    /**
     * Create audit log entry
     */
    public static function logAction(
        Appointment $appointment,
        string $action,
        ?User $user = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null
    ): self {
        return self::create([
            'appointment_id' => $appointment->id,
            'user_id' => $user?->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'reason' => $reason,
        ]);
    }

    /**
     * Get human-readable action description
     */
    public function getActionDescriptionAttribute(): string
    {
        $actor = $this->user ? $this->user->name : 'System';

        return match ($this->action) {
            self::ACTION_CREATED => "{$actor} created the appointment",
            self::ACTION_RESCHEDULED => "{$actor} rescheduled the appointment",
            self::ACTION_CANCELLED => "{$actor} cancelled the appointment",
            self::ACTION_RESTORED => "{$actor} restored the appointment",
            self::ACTION_STATUS_CHANGED => "{$actor} changed the appointment status",
            self::ACTION_STAFF_CHANGED => "{$actor} reassigned the appointment",
            self::ACTION_CALCOM_SYNC_FAILED => "Cal.com synchronization failed",
            self::ACTION_CALCOM_SYNCED => "Successfully synchronized with Cal.com",
            default => "{$actor} performed {$this->action}",
        };
    }

    /**
     * Get changes summary
     */
    public function getChangesSummaryAttribute(): array
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changes = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'from' => $oldValue,
                    'to' => $newValue,
                ];
            }
        }

        return $changes;
    }

    /**
     * Get formatted time change (for reschedule actions)
     */
    public function getTimeChangeAttribute(): ?array
    {
        if ($this->action !== self::ACTION_RESCHEDULED) {
            return null;
        }

        $changes = $this->changes_summary;
        if (isset($changes['start_time'])) {
            return [
                'old' => \Carbon\Carbon::parse($changes['start_time']['from'])->format('Y-m-d H:i'),
                'new' => \Carbon\Carbon::parse($changes['start_time']['to'])->format('Y-m-d H:i'),
            ];
        }

        return null;
    }

    // ==========================================
    // REPORTING HELPERS
    // ==========================================

    /**
     * Get reschedule count for appointment
     */
    public static function getRescheduleCount(int $appointmentId): int
    {
        return self::forAppointment($appointmentId)
            ->action(self::ACTION_RESCHEDULED)
            ->count();
    }

    /**
     * Get cancellation history for appointment
     */
    public static function getCancellationHistory(int $appointmentId): \Illuminate\Support\Collection
    {
        return self::forAppointment($appointmentId)
            ->whereIn('action', [self::ACTION_CANCELLED, self::ACTION_RESTORED])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get user's recent activity
     */
    public static function getUserActivity(int $userId, int $days = 30): \Illuminate\Support\Collection
    {
        return self::byUser($userId)
            ->recent($days)
            ->with('appointment')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
