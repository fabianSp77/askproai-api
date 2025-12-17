<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Models\Traits\HasConfigurationInheritance;

class Company extends Model
{
    use HasFactory, SoftDeletes, HasConfigurationInheritance;

    /**
     * Mass Assignment Protection
     *
     * SECURITY: Protects against VULN-009 - Mass Assignment vulnerability
     * Critical financial and authentication fields must never be mass-assigned
     */
    protected $guarded = [
        'id',                          // Primary key

        // Financial data (CRITICAL)
        'credit_balance',              // Must be modified only through billing system
        'commission_rate',             // Must be set by admin only
        'low_credit_threshold',        // Must be set by admin only
        'usage_budget',                // Must be set by admin only
        'outbound_calls_used',         // Calculated field

        // Payment integration (CRITICAL)
        'stripe_customer_id',          // Set only by payment system
        'stripe_subscription_id',      // Set only by payment system
        'billing_status',              // Controlled by billing system
        'billing_type',                // Controlled by billing system
        'payment_terms',               // Set by admin only

        // Authentication & Security (CRITICAL)
        'calcom_api_key',              // Sensitive credentials
        'retell_api_key',              // Sensitive credentials
        'webhook_signing_secret',      // Sensitive credentials
        'google_calendar_credentials', // Sensitive credentials
        'security_settings',           // Security config
        'allowed_ip_addresses',        // Security config

        // System timestamps
        'created_at',
        'updated_at',
        'deleted_at',
        'archived_at',
        'balance_warning_sent_at',
        'last_team_sync',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'archived_at' => 'datetime',
        'balance_warning_sent_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'small_business_threshold_date' => 'datetime',
        'last_team_sync' => 'datetime',
        'is_white_label' => 'boolean',
        'can_make_outbound_calls' => 'boolean',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'active' => 'boolean',
        'alerts_enabled' => 'boolean',
        'send_call_summaries' => 'boolean',
        'include_transcript_in_summary' => 'boolean',
        'include_csv_export' => 'boolean',
        'calcom_handles_notifications' => 'boolean',
        'email_notifications_enabled' => 'boolean',
        'send_booking_confirmations' => 'boolean',
        'notify_on_unfulfilled_wishes' => 'boolean',  // 💾 NEW: Notify on appointment wish failures
        'wish_notification_emails' => 'array',          // 💾 NEW: Email recipients for wishes
        'wish_notification_delay_minutes' => 'integer', // 💾 NEW: Notification delay
        'retell_enabled' => 'boolean',
        'auto_translate' => 'boolean',
        'prepaid_billing_enabled' => 'boolean',
        'outbound_settings' => 'json',
        'white_label_settings' => 'json',
        'call_notification_settings' => 'json',
        'retell_default_settings' => 'json',
        'settings' => 'json',
        'v128_config' => 'json',  // V128 Retell AI conversation flow settings
        'metadata' => 'json',
        'alert_preferences' => 'json',
        'supported_languages' => 'json',
        'google_calendar_credentials' => 'json',
        'security_settings' => 'json',
        'allowed_ip_addresses' => 'json',
        'api_test_errors' => 'json',
        'credit_balance' => 'decimal:2',
        'low_credit_threshold' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        // Customer Portal: Pilot program mechanism
        'is_pilot' => 'boolean',
        'pilot_enabled_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    /**
     * Get all appointments for this company (across all branches)
     * Used for company-level analytics, revenue reporting, and scheduling overview
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get upcoming scheduled/confirmed appointments across all branches
     * Used for company-level scheduling dashboard and resource planning
     * Performance: More efficient than filtering appointments() every time
     */
    public function upcomingAppointments(): HasMany
    {
        return $this->appointments()
            ->where('starts_at', '>=', now())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->orderBy('starts_at', 'asc');
    }

    /**
     * Get completed appointments across all branches (historical record)
     * Used for company-level performance metrics and revenue analytics
     * Performance: Specialized query for completed appointments only
     */
    public function completedAppointments(): HasMany
    {
        return $this->appointments()
            ->where('status', 'completed')
            ->orderBy('starts_at', 'desc');
    }

    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(PhoneNumber::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function workingHours(): HasManyThrough
    {
        return $this->hasManyThrough(
            WorkingHour::class,  // Target model (working hours)
            Staff::class,        // Intermediate model (staff)
            'company_id',        // Foreign key on staff table pointing to company
            'staff_id',          // Foreign key on working_hours table pointing to staff
            'id',                // Local key on companies table
            'id'                 // Local key on staff table
        );
    }

    /**
     * Team-related relationships and methods
     */

    /**
     * Get team event type mappings
     */
    public function teamEventTypeMappings()
    {
        return $this->hasMany(\App\Models\TeamEventTypeMapping::class);
    }

    /**
     * Get team members
     */
    public function teamMembers()
    {
        return $this->hasMany(\App\Models\CalcomTeamMember::class);
    }

    /**
     * Check if company has a Cal.com team assigned
     */
    public function hasTeam(): bool
    {
        return !empty($this->calcom_team_id);
    }

    /**
     * Check if team sync is due (older than 24 hours)
     */
    public function teamSyncIsDue(): bool
    {
        if (!$this->last_team_sync) {
            return true;
        }

        return $this->last_team_sync->lt(now()->subDay());
    }

    /**
     * Sync team event types
     */
    public function syncTeamEventTypes(): void
    {
        if (!$this->hasTeam()) {
            throw new \Exception('Company has no Cal.com team assigned');
        }

        dispatch(new \App\Jobs\ImportTeamEventTypesJob($this));
    }

    /**
     * Get services that belong to this company's team
     */
    public function teamServices()
    {
        return $this->services()->whereNotNull('calcom_event_type_id');
    }

    /**
     * Validate that a service belongs to this company's team
     */
    public function ownsService(int $calcomEventTypeId): bool
    {
        if (!$this->hasTeam()) {
            return false;
        }

        $calcomService = new \App\Services\CalcomV2Service($this);
        return $calcomService->validateTeamAccess($this->calcom_team_id, $calcomEventTypeId);
    }

    /**
     * Customer Portal: Pilot Program
     */

    /**
     * User who enabled pilot program for this company
     */
    public function pilotEnabledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pilot_enabled_by');
    }

    /**
     * Query scope to filter only pilot companies
     */
    public function scopePilot($query)
    {
        return $query->where('is_pilot', true);
    }

    /**
     * Check if company is in pilot program
     */
    public function isPilotCompany(): bool
    {
        return $this->is_pilot === true;
    }

    /**
     * Enable pilot program for this company
     */
    public function enablePilot(User $user, ?string $notes = null): void
    {
        $this->update([
            'is_pilot' => true,
            'pilot_enabled_at' => now(),
            'pilot_enabled_by' => $user->id,
            'pilot_notes' => $notes,
        ]);

        activity()
            ->performedOn($this)
            ->causedBy($user)
            ->withProperties(['notes' => $notes])
            ->log('pilot_enabled');
    }

    /**
     * Disable pilot program for this company
     */
    public function disablePilot(User $user, ?string $reason = null): void
    {
        $this->update([
            'is_pilot' => false,
            'pilot_notes' => $reason ? "Disabled: {$reason}" : $this->pilot_notes,
        ]);

        activity()
            ->performedOn($this)
            ->causedBy($user)
            ->withProperties(['reason' => $reason])
            ->log('pilot_disabled');
    }

    /**
     * Encrypt/Decrypt API Keys
     */
    public function setCalcomApiKeyAttribute($value)
    {
        $this->attributes['calcom_api_key'] = $value ? encrypt($value) : null;
    }

    public function getCalcomApiKeyAttribute($value)
    {
        if (!$value) return null;

        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // Log the issue for monitoring
            \Log::warning("Failed to decrypt calcom_api_key for company {$this->id}: {$e->getMessage()}");

            // Return null to prevent 500 errors
            return null;
        }
    }

    public function setRetellApiKeyAttribute($value)
    {
        $this->attributes['retell_api_key'] = $value ? encrypt($value) : null;
    }

    public function getRetellApiKeyAttribute($value)
    {
        if (!$value) return null;

        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // Log the issue for monitoring
            \Log::warning("Failed to decrypt retell_api_key for company {$this->id}: {$e->getMessage()}");

            // Return null to prevent 500 errors
            return null;
        }
    }

    /**
     * Get the team sync status badge
     */
    public function getTeamSyncStatusBadgeAttribute(): string
    {
        return match($this->team_sync_status) {
            'synced' => 'success',
            'syncing' => 'warning',
            'error' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Get the team sync status label
     */
    public function getTeamSyncStatusLabelAttribute(): string
    {
        return match($this->team_sync_status) {
            'synced' => 'Synchronized',
            'syncing' => 'Syncing...',
            'error' => 'Error',
            'pending' => 'Pending',
            default => 'Unknown'
        };
    }

    /**
     * Get V128 config with defaults
     *
     * Returns merged config with sensible defaults for Retell AI conversation flow.
     */
    public function getV128ConfigWithDefaults(): array
    {
        $defaults = [
            'time_shift_enabled' => true,
            'time_shift_message' => '{label} ist leider schon ausgebucht. Soll ich am nächsten Tag {label} schauen, oder würde heute Abend auch passen? Heute hätte ich noch {alternatives} frei.',
            'name_skip_enabled' => true,
            'full_confirmation_enabled' => true,
            'silence_handling_enabled' => true,
            'silence_timeout_seconds' => 20,
            'max_silence_repeats' => 2,
        ];

        return array_merge($defaults, $this->v128_config ?? []);
    }

    /**
     * Get a specific V128 config value with default fallback
     */
    public function getV128Config(string $key, mixed $default = null): mixed
    {
        $config = $this->getV128ConfigWithDefaults();
        return $config[$key] ?? $default;
    }
}