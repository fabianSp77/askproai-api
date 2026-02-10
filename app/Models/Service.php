<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Traits\HasConfigurationInheritance;

class Service extends Model
{
    use HasFactory, SoftDeletes, HasConfigurationInheritance, BelongsToCompany;

    /**
     * Mass Assignment Protection - WHITELIST APPROACH
     *
     * SECURITY: Protects against VULN-009 - Mass Assignment vulnerability
     * Using $fillable (whitelist) instead of $guarded (blacklist) for clarity
     *
     * NOTE 2025-10-14: Switched from $guarded to $fillable
     * - Explicit whitelist is safer and clearer
     * - Settings Dashboard can edit all business fields
     * - Authorization: Already protected by SettingsDashboard::canAccess()
     * - System fields (sync, assignment) are NOT in this list
     * - Tenant isolation fields (company_id, branch_id) are NOT in this list
     */
    protected $fillable = [
        // Basic Info
        'name',
        'display_name',
        'calcom_name',
        'slug',
        'description',
        'category',

        // Settings
        'is_active',
        'is_default',
        'is_online',
        'priority',

        // Timing
        'duration_minutes',
        'buffer_time_minutes',
        'minimum_booking_notice',
        'before_event_buffer',

        // Processing Time (Split Appointments)
        'has_processing_time',
        'initial_duration',
        'processing_duration',
        'final_duration',

        // Pricing
        'price',

        // Composite Services
        'composite',
        'segments',
        'min_staff_required',

        // Policies
        'pause_bookable_policy',
        'reminder_policy',
        'reschedule_policy',
        'requires_confirmation',
        'disable_guests',

        // Integration
        'calcom_event_type_id',
        'schedule_id',
        'booking_link',

        // Metadata
        'locations_json',
        'metadata_json',
        'booking_fields_json',
        'assignment_notes',
        'assignment_method',
        'assignment_confidence',
    ];

    /**
     * PROTECTED FIELDS (NOT in $fillable):
     * - id                    (Primary key - never mass-assign)
     * - company_id            (Multi-tenant isolation - CRITICAL)
     * - branch_id             (Multi-tenant isolation - CRITICAL)
     * - last_calcom_sync      (System field - set by sync)
     * - sync_status           (System field - set by sync)
     * - sync_error            (System field - set by sync)
     * - assignment_date       (System field - set by assignment)
     * - assigned_by           (System field - set by assignment)
     * - created_at            (Timestamp - automatic)
     * - updated_at            (Timestamp - automatic)
     * - deleted_at            (Timestamp - automatic)
     */

    protected $casts = [
        'metadata' => 'array',          // CANONICAL: Used in code (NotificationService)
        'locations_json' => 'array',
        'metadata_json' => 'array',     // LEGACY: Kept for backwards compatibility, not actively used
        'booking_fields_json' => 'array',
        'is_active' => 'boolean',
        'is_online' => 'boolean',
        'deposit_required' => 'boolean',
        'requires_confirmation' => 'boolean',
        'disable_guests' => 'boolean',
        'assignment_confidence' => 'float',
        'assignment_date' => 'datetime',
        'allow_cancellation' => 'boolean',
        'price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'last_calcom_sync' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // Composite casts
        'composite' => 'boolean',
        'segments' => 'array',
        'reschedule_policy' => 'array',
        // Processing Time casts
        'has_processing_time' => 'boolean',
        'initial_duration' => 'integer',
        'processing_duration' => 'integer',
        'final_duration' => 'integer',
    ];

    /**
     * Boot the model - Add Cal.com event type ownership validation
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($service) {
            // Skip validation if calcom_event_mappings table doesn't exist or is not used
            // (happens in test environment where factories don't create event mappings)
            if (!\Illuminate\Support\Facades\Schema::hasTable('calcom_event_mappings')) {
                return;
            }

            // Skip validation if no mappings exist (test environment or fresh install)
            if (\Illuminate\Support\Facades\DB::table('calcom_event_mappings')->count() === 0) {
                return;
            }

            // Validate Cal.com event type ownership (Multi-Tenant Security)
            // ONLY check if the calcom_event_type_id is being CHANGED (not on every update)
            if ($service->isDirty('calcom_event_type_id') && $service->calcom_event_type_id && $service->company_id) {
                $isValid = \Illuminate\Support\Facades\DB::table('calcom_event_mappings')
                    ->where('calcom_event_type_id', (string)$service->calcom_event_type_id)
                    ->where('company_id', $service->company_id)
                    ->exists();

                if (!$isValid) {
                    throw new \Exception(
                        "Security violation: Event Type {$service->calcom_event_type_id} does not " .
                        "belong to company {$service->company_id}'s Cal.com team. " .
                        "Only event types from your Cal.com team are allowed."
                    );
                }
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'service_staff')
            ->withPivot([
                'is_primary',
                'can_book',
                'custom_price',
                'custom_duration_minutes',
                'commission_rate',
                'specialization_notes',
                'allowed_segments',
                'skill_level',
                'weight',
                'is_active',
                'assigned_at'
            ])
            ->withTimestamps()
            ->wherePivot('is_active', true)
            ->orderByPivot('is_primary', 'desc')
            ->orderBy('name');
    }

    public function primaryStaff(): BelongsToMany
    {
        return $this->staff()->wherePivot('is_primary', true);
    }

    public function availableStaff(): BelongsToMany
    {
        return $this->staff()->wherePivot('can_book', true);
    }

    /**
     * Get allowed staff for this service
     * Used by admin interface to display staff assignments
     */
    public function allowedStaff(): BelongsToMany
    {
        return $this->staff();
    }

    /**
     * Get display name (custom or cal.com name)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->attributes['display_name'] ?? $this->name;
    }

    /**
     * Get formatted sync status with time information
     */
    public function getFormattedSyncStatusAttribute(): string
    {
        if (!$this->last_calcom_sync) {
            return '⚠️ Never synced';
        }

        $diff = now()->diffInMinutes($this->last_calcom_sync);

        if ($this->sync_status === 'error') {
            return '❌ Sync error';
        }

        if ($this->sync_status === 'pending') {
            return '⏳ Sync pending';
        }

        if ($diff < 5) return '✅ Just synced';
        if ($diff < 60) return "✅ Synced {$diff}m ago";
        if ($diff < 1440) return "⚠️ Synced " . round($diff/60) . "h ago";
        return "⚠️ Synced " . round($diff/1440) . "d ago";
    }

    /**
     * Check if service needs Cal.com sync
     */
    public function needsCalcomSync(): bool
    {
        if (!$this->calcom_event_type_id) {
            return false; // Can't sync without Cal.com ID
        }

        if ($this->sync_status === 'error' || $this->sync_status === 'never') {
            return true;
        }

        if (!$this->last_calcom_sync) {
            return true;
        }

        // Sync if older than 24 hours
        return $this->last_calcom_sync->diffInHours(now()) > 24;
    }

    /**
     * User who assigned this service to a company
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Check if service has been assigned to a company
     */
    public function isAssigned(): bool
    {
        return $this->company_id !== null && $this->assignment_method !== null;
    }

    /**
     * Get assignment confidence level as text
     */
    public function getAssignmentConfidenceLevelAttribute(): string
    {
        if (!$this->assignment_confidence) {
            return 'unknown';
        }

        if ($this->assignment_confidence >= 80) return 'high';
        if ($this->assignment_confidence >= 60) return 'good';
        if ($this->assignment_confidence >= 40) return 'medium';
        return 'low';
    }

    /**
     * Get formatted assignment status
     */
    public function getFormattedAssignmentStatusAttribute(): string
    {
        if (!$this->company_id) {
            return '❌ Not assigned';
        }

        $emoji = match($this->assignment_method) {
            'manual' => '👤',
            'auto' => '🤖',
            'import' => '📥',
            'suggested' => '💡',
            default => '❓'
        };

        $confidence = $this->assignment_confidence
            ? " ({$this->assignment_confidence}%)"
            : '';

        return "{$emoji} {$this->assignment_method}{$confidence}";
    }

    /**
     * Check if service is composite
     */
    public function isComposite(): bool
    {
        return $this->composite === true;
    }

    /**
     * Get segments for composite service
     */
    public function getSegments(): array
    {
        return $this->segments ?? [];
    }

    /**
     * Relationship to branch-specific overrides
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_service')
            ->withPivot([
                'duration_override_minutes',
                'gap_after_override_minutes',
                'price_override',
                'custom_segments',
                'branch_policies',
                'is_active'
            ])
            ->withTimestamps();
    }

    /**
     * Get Cal.com event mappings
     */
    public function calcomMappings(): HasMany
    {
        return $this->hasMany(CalcomEventMap::class);
    }

    // =========================================================================
    // PROCESSING TIME (SPLIT APPOINTMENTS) METHODS
    // =========================================================================

    /**
     * Check if service uses processing time (split appointments)
     *
     * Checks both service configuration AND feature flags for controlled rollout.
     *
     * Feature Flag Logic:
     * 1. Master toggle must be enabled (processing_time_enabled)
     * 2. Service must be configured (has_processing_time = true)
     * 3. Check whitelist rules (service OR company whitelist)
     * 4. Fallback: If enabled globally and not restricted, allow all
     *
     * Rollout Strategy:
     * - Phase 1: Service whitelist only (testing)
     * - Phase 2: Company whitelist (pilot)
     * - Phase 3: Global enabled (full rollout)
     *
     * @return bool True if Processing Time is enabled for this service
     */
    public function hasProcessingTime(): bool
    {
        // Service must have processing time configured
        if (!$this->has_processing_time) {
            return false;
        }

        // Check feature flag: Master toggle
        if (!config('features.processing_time_enabled', false)) {
            // Master toggle OFF - check service whitelist (for testing)
            $serviceWhitelist = config('features.processing_time_service_whitelist', []);
            return in_array($this->id, $serviceWhitelist, true);
        }

        // Master toggle ON - check company whitelist
        $companyWhitelist = config('features.processing_time_company_whitelist', []);

        // If company whitelist is empty, feature is available to all companies
        if (empty($companyWhitelist)) {
            return true;
        }

        // Check if this service's company is in the whitelist
        return in_array($this->company_id, $companyWhitelist, true);
    }

    /**
     * Get total duration including all phases
     */
    public function getTotalDuration(): int
    {
        if ($this->hasProcessingTime()) {
            return ($this->initial_duration ?? 0) +
                   ($this->processing_duration ?? 0) +
                   ($this->final_duration ?? 0);
        }

        return $this->duration_minutes;
    }

    /**
     * Get phase durations breakdown
     *
     * @return array{initial: int, processing: int, final: int, total: int}|null
     */
    public function getPhasesDuration(): ?array
    {
        if (!$this->hasProcessingTime()) {
            return null;
        }

        return [
            'initial' => $this->initial_duration ?? 0,
            'processing' => $this->processing_duration ?? 0,
            'final' => $this->final_duration ?? 0,
            'total' => $this->getTotalDuration(),
        ];
    }

    /**
     * Generate AppointmentPhase records for a given appointment start time
     *
     * @param \Carbon\Carbon $startTime
     * @return array Array of phase data ready for insertion
     */
    public function generatePhases(\Carbon\Carbon $startTime): array
    {
        if (!$this->hasProcessingTime()) {
            return [];
        }

        $phases = [];
        $offset = 0;

        // Initial Phase (Staff BUSY)
        if ($this->initial_duration > 0) {
            $phases[] = [
                'phase_type' => 'initial',
                'start_offset_minutes' => $offset,
                'duration_minutes' => $this->initial_duration,
                'staff_required' => true,
                'start_time' => $startTime->copy()->addMinutes($offset),
                'end_time' => $startTime->copy()->addMinutes($offset + $this->initial_duration),
            ];
            $offset += $this->initial_duration;
        }

        // Processing Phase (Staff AVAILABLE)
        if ($this->processing_duration > 0) {
            $phases[] = [
                'phase_type' => 'processing',
                'start_offset_minutes' => $offset,
                'duration_minutes' => $this->processing_duration,
                'staff_required' => false,
                'start_time' => $startTime->copy()->addMinutes($offset),
                'end_time' => $startTime->copy()->addMinutes($offset + $this->processing_duration),
            ];
            $offset += $this->processing_duration;
        }

        // Final Phase (Staff BUSY)
        if ($this->final_duration > 0) {
            $phases[] = [
                'phase_type' => 'final',
                'start_offset_minutes' => $offset,
                'duration_minutes' => $this->final_duration,
                'staff_required' => true,
                'start_time' => $startTime->copy()->addMinutes($offset),
                'end_time' => $startTime->copy()->addMinutes($offset + $this->final_duration),
            ];
        }

        return $phases;
    }

    /**
     * Validate processing time configuration
     *
     * @return array{valid: bool, errors: array<string>}
     */
    public function validateProcessingTime(): array
    {
        $errors = [];

        if (!$this->hasProcessingTime()) {
            return ['valid' => true, 'errors' => []];
        }

        // Check that at least initial or final duration is set
        if (($this->initial_duration ?? 0) === 0 && ($this->final_duration ?? 0) === 0) {
            $errors[] = 'Processing time service must have at least an initial or final phase.';
        }

        // Check that processing duration is set
        if (($this->processing_duration ?? 0) === 0) {
            $errors[] = 'Processing time service must have a processing duration (gap time).';
        }

        // Check that total duration matches
        $calculatedTotal = $this->getTotalDuration();
        if ($calculatedTotal !== $this->duration_minutes) {
            $errors[] = "Total phase duration ({$calculatedTotal} min) must match service duration ({$this->duration_minutes} min).";
        }

        // Check for negative values
        if (($this->initial_duration ?? 0) < 0 || ($this->processing_duration ?? 0) < 0 || ($this->final_duration ?? 0) < 0) {
            $errors[] = 'Phase durations cannot be negative.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get human-readable processing time description
     */
    public function getProcessingTimeDescription(): ?string
    {
        if (!$this->hasProcessingTime()) {
            return null;
        }

        $phases = $this->getPhasesDuration();
        $parts = [];

        if ($phases['initial'] > 0) {
            $parts[] = "Initial phase: {$phases['initial']} min (staff busy)";
        }

        if ($phases['processing'] > 0) {
            $parts[] = "Processing: {$phases['processing']} min (staff available)";
        }

        if ($phases['final'] > 0) {
            $parts[] = "Final phase: {$phases['final']} min (staff busy)";
        }

        return implode(' → ', $parts) . " | Total: {$phases['total']} min";
    }
}