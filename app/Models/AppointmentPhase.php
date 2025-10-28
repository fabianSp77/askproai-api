<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * AppointmentPhase Model
 *
 * Represents individual phases of a multi-phase appointment (Processing Time feature).
 * Each phase has a type (initial, processing, final) and a staff_required flag.
 *
 * @property int $id
 * @property int $appointment_id
 * @property string $phase_type ('initial', 'processing', 'final')
 * @property int $start_offset_minutes
 * @property int $duration_minutes
 * @property bool $staff_required
 * @property Carbon $start_time
 * @property Carbon $end_time
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AppointmentPhase extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'appointment_phases';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'appointment_id',
        'phase_type',
        'start_offset_minutes',
        'duration_minutes',
        'staff_required',
        'start_time',
        'end_time',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'staff_required' => 'boolean',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'start_offset_minutes' => 'integer',
        'duration_minutes' => 'integer',
    ];

    /**
     * Get the appointment that owns the phase.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Scope: Only phases where staff is required (busy phases).
     */
    public function scopeStaffRequired($query)
    {
        return $query->where('staff_required', true);
    }

    /**
     * Scope: Only phases where staff is available (processing/gap phases).
     */
    public function scopeStaffAvailable($query)
    {
        return $query->where('staff_required', false);
    }

    /**
     * Scope: Phases within a specific time range.
     */
    public function scopeInTimeRange($query, Carbon $start, Carbon $end)
    {
        return $query->where(function ($q) use ($start, $end) {
            $q->whereBetween('start_time', [$start, $end])
              ->orWhereBetween('end_time', [$start, $end])
              ->orWhere(function ($q2) use ($start, $end) {
                  // Phase spans the entire range
                  $q2->where('start_time', '<=', $start)
                     ->where('end_time', '>=', $end);
              });
        });
    }

    /**
     * Scope: Phases by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('phase_type', $type);
    }

    /**
     * Check if this is an initial phase.
     */
    public function isInitial(): bool
    {
        return $this->phase_type === 'initial';
    }

    /**
     * Check if this is a processing/gap phase.
     */
    public function isProcessing(): bool
    {
        return $this->phase_type === 'processing';
    }

    /**
     * Check if this is a final phase.
     */
    public function isFinal(): bool
    {
        return $this->phase_type === 'final';
    }

    /**
     * Check if staff is busy during this phase.
     */
    public function isStaffBusy(): bool
    {
        return $this->staff_required;
    }

    /**
     * Check if staff is available during this phase.
     */
    public function isStaffAvailable(): bool
    {
        return !$this->staff_required;
    }

    /**
     * Get the duration in minutes.
     */
    public function getDuration(): int
    {
        return $this->duration_minutes;
    }

    /**
     * Check if this phase overlaps with a given time range.
     */
    public function overlaps(Carbon $start, Carbon $end): bool
    {
        return $this->start_time < $end && $this->end_time > $start;
    }

    /**
     * Get human-readable phase type.
     */
    public function getPhaseTypeLabel(): string
    {
        return match($this->phase_type) {
            'initial' => 'Initial Phase (Staff Busy)',
            'processing' => 'Processing Phase (Staff Available)',
            'final' => 'Final Phase (Staff Busy)',
            default => $this->phase_type,
        };
    }
}
