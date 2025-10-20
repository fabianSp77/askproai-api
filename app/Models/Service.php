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
        'metadata' => 'array',
        'locations_json' => 'array',
        'metadata_json' => 'array',
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
    ];

    /**
     * Boot the model - Add Cal.com event type ownership validation
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($service) {
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
}