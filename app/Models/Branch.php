<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\HasConfigurationInheritance;

class Branch extends Model
{
    use HasFactory, SoftDeletes, HasConfigurationInheritance, BelongsToCompany;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'company_id', 'customer_id', 'name', 'slug', 'city', 'phone_number',
        'notification_email', 'send_call_summaries', 'call_summary_recipients',
        'include_transcript_in_summary', 'include_csv_export', 'summary_email_frequency',
        'call_notification_overrides', 'active', 'invoice_recipient', 'invoice_name',
        'invoice_email', 'invoice_address', 'invoice_phone',
        // Cal.com integration
        'calcom_api_key', 'calcom_team_id', 'calcom_user_id',
        // Retell AI integration
        'retell_agent_id', 'retell_conversation_flow_id', 'retell_agent_cache', 'retell_last_sync',
        // Other settings
        'integration_status', 'calendar_mode', 'integrations_tested_at',
        'configuration_status', 'parent_settings', 'address', 'postal_code', 'website',
        'business_hours', 'services_override', 'country', 'uuid', 'settings', 'coordinates',
        'features', 'transport_info', 'service_radius_km', 'accepts_walkins',
        'parking_available', 'public_transport_access', 'is_active',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'integrations_tested_at' => 'datetime',
        'retell_last_sync' => 'datetime',
        'is_active' => 'boolean',
        'active' => 'boolean',
        'send_call_summaries' => 'boolean',
        'include_transcript_in_summary' => 'boolean',
        'include_csv_export' => 'boolean',
        'accepts_walkins' => 'boolean',
        'parking_available' => 'boolean',
        'public_transport_access' => 'boolean',
        'call_notification_overrides' => 'array',
        'retell_agent_cache' => 'json',
        'parent_settings' => 'json',
        'business_hours' => 'json',
        'services_override' => 'json',
        'settings' => 'json',
        'coordinates' => 'json',
        'features' => 'json',
        'transport_info' => 'json',
        'service_radius_km' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get all calls processed for this branch
     * Used for branch-level analytics and performance reporting
     */
    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    /**
     * Get upcoming scheduled/confirmed appointments for this branch
     * Used for branch scheduling, resource planning, and booking management
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
     * Get completed appointments for this branch (historical record)
     * Used for branch metrics, performance tracking, and revenue analysis
     * Performance: Specialized query for completed appointments only
     */
    public function completedAppointments(): HasMany
    {
        return $this->appointments()
            ->where('status', 'completed')
            ->orderBy('starts_at', 'desc');
    }

    public function services(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'branch_service')
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

    public function activeServices(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->services()->wherePivot('is_active', true);
    }

    /**
     * Get working hours through staff members.
     *
     * NOTE: working_hours table does not exist yet in database.
     * FIX: Corrected invalid ->through() syntax to hasManyThrough().
     * Previous: hasMany(WorkingHour::class)->through('staff') - INVALID SYNTAX
     * Current: Commented out until table is created
     */
    // public function workingHours(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    // {
    //     return $this->hasManyThrough(
    //         WorkingHour::class,
    //         Staff::class,
    //         'branch_id',      // Foreign key on staff table
    //         'staff_id',       // Foreign key on working_hours table
    //         'id',             // Local key on branches table
    //         'id'              // Local key on staff table
    //     );
    // }

    /**
     * Get all Retell agent prompts for this branch
     */
    public function retellAgentPrompts(): HasMany
    {
        return $this->hasMany(RetellAgentPrompt::class);
    }

    /**
     * ✅ Phase 2: Get policy configurations for this branch
     *
     * Branch can have operational policies (booking, inquiry, etc.)
     * Polymorphic relationship via configurable_type/configurable_id
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function policyConfigurations(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(PolicyConfiguration::class, 'configurable');
    }

    /**
     * ✅ Phase 2: Get call forwarding configuration for this branch
     *
     * Each branch can have one active forwarding configuration
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function callForwardingConfiguration(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CallForwardingConfiguration::class);
    }

    /**
     * ✅ Phase 2: Get callback requests for this branch
     *
     * Callback requests when immediate booking not possible
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function callbackRequests(): HasMany
    {
        return $this->hasMany(CallbackRequest::class);
    }
}