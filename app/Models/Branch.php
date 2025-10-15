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
        // NOTE: calcom_event_type_id removed - branches link to services (which have event_type_ids)
        'calcom_api_key', 'retell_agent_id', 'integration_status', 'calendar_mode',
        'integrations_tested_at', 'calcom_user_id', 'retell_agent_cache', 'retell_last_sync',
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
        'call_notification_overrides' => 'json',
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
}