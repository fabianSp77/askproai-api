<?php

namespace App\Models;

use App\Traits\BelongsToCompany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Services\IntegrationTestService;
use App\Models\Staff;
use App\Models\WorkingHour;
use App\Scopes\TenantScope;
use App\Services\Validation\PhoneNumberValidator;
use App\Services\Validation\InvalidPhoneNumberException;

class Branch extends Model
{
    use BelongsToCompany;

    use HasFactory, SoftDeletes, HasUuids;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'branches';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
        protected $fillable = [
        'company_id',
        'name',
        'slug',
        'phone_number',
        'notification_email',
        'address',
        'city',
        'postal_code',
        'country',
        'website',
        'business_hours',
        'retell_agent_id',
        'retell_agent_data',
        'retell_synced_at',
        'retell_agent_status',
        'retell_agent_created_at',
        'settings',
        'calcom_api_key',
        'calcom_event_type_id',
        'calcom_team_slug',
        'calendar_mode',
        'active',
        'notify_on_booking',
        'deleted_at',
        'features',
        'call_notification_overrides',
        // Call notification preferences
        'send_call_summaries',
        'call_summary_recipients',
        'include_transcript_in_summary',
        'include_csv_export',
        'summary_email_frequency'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_main' => 'boolean',
        'active' => 'boolean',
        'invoice_recipient' => 'boolean',
        'notify_on_booking' => 'boolean',
        'calendar_mapping' => 'array',
        'integration_status' => 'array',
        'opening_hours' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'integrations_tested_at' => 'datetime',
        // Neue Casts
        'retell_agent_cache' => 'array',
        'retell_last_sync' => 'datetime',
        'configuration_status' => 'array',
        'parent_settings' => 'array',
        'call_notification_overrides' => 'array',
        'business_hours' => 'array',
        'services_override' => 'array',
        'settings' => 'array',
        'features' => 'array',
        'retell_agent_data' => 'array',
        'retell_agent_created_at' => 'datetime',
        'retell_synced_at' => 'datetime',
        'coordinates' => 'array',
        'features' => 'array',
        'transport_info' => 'array',
        // Call notification preferences
        'send_call_summaries' => 'boolean',
        'call_summary_recipients' => 'array',
        'include_transcript_in_summary' => 'boolean',
        'include_csv_export' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
        
        // Validate and normalize phone number before saving
        static::saving(function ($branch) {
            if (!empty($branch->phone_number)) {
                $validator = app(PhoneNumberValidator::class);
                
                try {
                    $branch->phone_number = $validator->validateForStorage($branch->phone_number);
                } catch (InvalidPhoneNumberException $e) {
                    // For branches, phone number is critical - always throw
                    throw new \InvalidArgumentException(
                        "Invalid phone number for branch '{$branch->name}': " . $e->getMessage()
                    );
                }
            }
        });
    }
    
    /**
     * Set the phone number attribute with validation
     */
    public function setPhoneNumberAttribute($value)
    {
        if (!empty($value)) {
            $validator = app(PhoneNumberValidator::class);
            $this->attributes['phone_number'] = $validator->validateForStorage($value);
        } else {
            $this->attributes['phone_number'] = $value;
        }
    }

    /**
     * Get the company that owns the branch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    // REMOVED: customer() relationship - branches belong to companies, not customers

    /**
     * Get the staff members for the branch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function staff()
    {
        return $this->hasMany(Staff::class, 'home_branch_id');
    }

    /**
     * Get all staff members that can work in this branch
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function availableStaff()
    {
        return $this->belongsToMany(Staff::class, 'staff_branches')
            ->withTimestamps();
    }

    /**
     * Get the appointments for the branch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the phone numbers for the branch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function phoneNumbers()
    {
        return $this->hasMany(PhoneNumber::class);
    }

    /**
     * The services that belong to the branch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function services()
    {
        return $this->hasMany(Service::class, 'branch_id')
            ->orderBy('name');
    }

    /**
     * Master Services relationship for the branch
     * Diese Services werden vom Unternehmen geerbt und können pro Filiale angepasst werden
     * 
     * NOTE: Temporarily disabled - the branch_service_overrides table does not exist
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    // public function masterServices()
    // {
    //     return $this->belongsToMany(MasterService::class, 'branch_service_overrides')
    //                 ->withPivot(['custom_duration', 'custom_price', 'custom_calcom_event_type_id', 'active'])
    //                 ->withTimestamps();
    // }

    /**
     * Get only active master services for this branch
     * 
     * NOTE: Temporarily disabled - depends on masterServices() relationship
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    // public function activeServices()
    // {
    //     return $this->masterServices()->wherePivot('active', true);
    // }

    /**
     * Get calendar mappings for the branch
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function calendarMappings()
    {
        return $this->hasMany(CalendarMapping::class);
    }

    /**
     * Get the event types for the branch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function eventTypes()
    {
        return $this->belongsToMany(CalcomEventType::class, 'branch_event_types', 'branch_id', 'event_type_id')
            ->using(BranchEventType::class)
            ->withPivot(['is_primary'])
            ->withTimestamps()
            ->orderByPivot('is_primary', 'desc');
    }
    
    /**
     * Get the primary event type for the branch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function primaryEventType()
    {
        return $this->eventTypes()->wherePivot('is_primary', true);
    }

    /**
     * Scope a query to only include active branches.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include main branches.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMain($query)
    {
        return $query->where('is_main', true);
    }

    /**
     * Check if this is the main branch.
     *
     * @return bool
     */
    public function isMain(): bool
    {
        return $this->is_main === true;
    }

    /**
     * Check if the branch is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * Get the full address of the branch.
     *
     * @return string
     */
    public function getFullAddressAttribute(): string
    {
        return implode(', ', array_filter([
            $this->address,
            $this->postal_code,
            $this->city,
            $this->country
        ]));
    }

    /**
     * Get the effective Cal.com configuration
     * Returns branch config if in override mode, otherwise company config
     *
     * @return array|null
     */
    public function getEffectiveCalcomConfig()
    {
        if ($this->calendar_mode === 'override' && $this->calcom_api_key) {
            return [
                'api_key' => $this->calcom_api_key,
                'event_type_id' => $this->calcom_event_type_id,
                'team_slug' => $this->calcom_team_slug,
            ];
        }

        // Fallback to company configuration
        // Use relationLoaded to check if company is already loaded to avoid N+1
        if ($this->relationLoaded('company') && $this->company) {
            return [
                'api_key' => $this->company->calcom_api_key,
                'event_type_id' => $this->company->calcom_event_type_id,
                'team_slug' => $this->company->calcom_team_slug,
            ];
        } elseif ($this->company_id) {
            // If company is not loaded but we have company_id, load it
            $company = $this->company()->first();
            if ($company) {
                return [
                    'api_key' => $company->calcom_api_key,
                    'event_type_id' => $company->calcom_event_type_id,
                    'team_slug' => $company->calcom_team_slug,
                ];
            }
        }

        return null;
    }

    /**
     * Get the effective Retell configuration
     *
     * @return array|null
     */
    public function getEffectiveRetellConfig()
    {
        return [
            'agent_id' => $this->retell_agent_id ?: $this->company->retell_agent_id,
            'api_key' => $this->company->retell_api_key ?? config('services.retell.api_key'),
        ];
    }

    /**
     * Test all integrations for this branch
     *
     * @return array
     */
    public function testIntegrations()
    {
        $testService = new IntegrationTestService();

        $results = [];

        // Test Cal.com
        if ($config = $this->getEffectiveCalcomConfig()) {
            $results['calcom'] = $testService->testCalcomConnection($config['api_key']);
        }

        // Test Retell.ai
        if ($retellConfig = $this->getEffectiveRetellConfig()) {
            $results['retell'] = $testService->testRetellConnection(
                $retellConfig['api_key'],
                $retellConfig['agent_id']
            );
        }

        // Update integration status
        $this->update([
            'integration_status' => [
                'calcom' => $results['calcom']['success'] ?? false,
                'retell' => $results['retell']['success'] ?? false,
                'last_tested' => now()->toIso8601String()
            ]
        ]);

        return $results;
    }
    
    /**
     * Get setting value with fallback
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }
    
    /**
     * Check if branch has an active Retell agent
     */
    public function hasRetellAgent(): bool
    {
        return !empty($this->retell_agent_id) && $this->retell_agent_status === 'active';
    }
    
    /**
     * Check if agent needs provisioning
     */
    public function needsAgentProvisioning(): bool
    {
        return empty($this->retell_agent_id) || $this->retell_agent_status !== 'active';
    }

    /**
     * Get the effective Cal.com Event Type ID (own or inherited from company)
     * Now uses the new relationship table
     */
    public function getEffectiveCalcomEventTypeId()
    {
        // First check if we have a primary event type in the new structure
        $primaryEventType = $this->primaryEventType()->first();
        if ($primaryEventType) {
            return $primaryEventType->calcom_numeric_event_type_id;
        }
        
        // Fallback to old field for backward compatibility (will be removed later)
        if ($this->calendar_mode === 'override' && $this->calcom_event_type_id) {
            return $this->calcom_event_type_id;
        }

        // Ansonsten vom Unternehmen erben
        return $this->company ? $this->company->calcom_event_type_id : null;
    }
    
    /**
     * Get the primary Cal.com Event Type ID
     */
    public function getPrimaryCalcomEventTypeId()
    {
        $primaryEventType = $this->primaryEventType()->first();
        return $primaryEventType ? $primaryEventType->calcom_numeric_event_type_id : null;
    }

    /**
     * Get the effective Cal.com User ID (own or inherited from company)
     */
    public function getEffectiveCalcomUserId()
    {
        // Wenn eigener Wert gesetzt ist und calendar_mode auf 'override' steht
        if ($this->calendar_mode === 'override' && $this->calcom_user_id) {
            return $this->calcom_user_id;
        }

        // Ansonsten vom Unternehmen erben
        return $this->company ? $this->company->calcom_user_id : null;
    }

    /**
     * Get configuration progress
     */
    public function getConfigurationProgressAttribute(): array
    {
        $progress = [];
        $score = 0;
        $maxScore = 0;
        
        // Basic information (30%)
        $progress['basic_info'] = [
            'label' => 'Grunddaten',
            'items' => []
        ];
        
        if (!empty($this->name)) {
            $progress['basic_info']['items'][] = ['label' => 'Name', 'completed' => true];
            $score += 5;
        } else {
            $progress['basic_info']['items'][] = ['label' => 'Name', 'completed' => false];
        }
        $maxScore += 5;
        
        if (!empty($this->address) && !empty($this->city) && !empty($this->postal_code)) {
            $progress['basic_info']['items'][] = ['label' => 'Adresse', 'completed' => true];
            $score += 10;
        } else {
            $progress['basic_info']['items'][] = ['label' => 'Adresse', 'completed' => false];
        }
        $maxScore += 10;
        
        if (!empty($this->phone_number)) {
            $progress['basic_info']['items'][] = ['label' => 'Telefonnummer', 'completed' => true];
            $score += 10;
        } else {
            $progress['basic_info']['items'][] = ['label' => 'Telefonnummer', 'completed' => false];
        }
        $maxScore += 10;
        
        if (!empty($this->notification_email)) {
            $progress['basic_info']['items'][] = ['label' => 'E-Mail', 'completed' => true];
            $score += 5;
        } else {
            $progress['basic_info']['items'][] = ['label' => 'E-Mail', 'completed' => false];
        }
        $maxScore += 5;
        
        // Business hours (20%)
        if (!empty($this->business_hours)) {
            $progress['business_hours'] = [
                'label' => 'Öffnungszeiten',
                'completed' => true
            ];
            $score += 20;
        } else {
            $progress['business_hours'] = [
                'label' => 'Öffnungszeiten',
                'completed' => false
            ];
        }
        $maxScore += 20;
        
        // KI-Agent (30%)
        if (!empty($this->retell_agent_id)) {
            $progress['ai_agent'] = [
                'label' => 'KI-Agent konfiguriert',
                'completed' => true
            ];
            $score += 30;
        } else {
            $progress['ai_agent'] = [
                'label' => 'KI-Agent konfiguriert',
                'completed' => false
            ];
        }
        $maxScore += 30;
        
        // Services & Staff (20%)
        try {
            $hasServices = $this->services()->withoutGlobalScope(\App\Scopes\TenantScope::class)->exists();
        } catch (\Exception $e) {
            // If we can't check services due to scope issues, assume false
            $hasServices = false;
        }
        
        try {
            $hasStaff = $this->staff()->withoutGlobalScope(\App\Scopes\TenantScope::class)->exists() || 
                        $this->availableStaff()->withoutGlobalScope(\App\Scopes\TenantScope::class)->exists();
        } catch (\Exception $e) {
            // If we can't check staff due to scope issues, assume false
            $hasStaff = false;
        }
        
        if ($hasServices && $hasStaff) {
            $progress['services_staff'] = [
                'label' => 'Services & Mitarbeiter',
                'completed' => true
            ];
            $score += 20;
        } else {
            $progress['services_staff'] = [
                'label' => 'Services & Mitarbeiter',
                'completed' => false
            ];
        }
        $maxScore += 20;
        
        // Calculate percentage
        $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100) : 0;
        
        return [
            'percentage' => $percentage,
            'score' => $score,
            'maxScore' => $maxScore,
            'details' => $progress
        ];
    }
    
    /**
     * Check if branch can be activated
     */
    public function canBeActivated(): bool
    {
        return !empty($this->name) &&
               !empty($this->phone_number) &&
               !empty($this->notification_email) &&
               !empty($this->address) &&
               !empty($this->city) &&
               !empty($this->postal_code);
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'id';
    }

    /**
     * Get the Retell agent for the branch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function retellAgent()
    {
        return $this->belongsTo(RetellAgent::class, 'retell_agent_id');
    }


    /**
     * Check if branch is fully configured
     *
     * @return bool
     */
    public function isFullyConfigured()
    {
        return $this->configuration_progress['percentage'] === 100;
    }

    /**
     * Sync Retell agent data from API
     *
     * @return void
     */
    public function syncRetellAgent()
    {
        if (!$this->retell_agent_id) return;
        
        $service = app(\App\Services\RetellAgentService::class);
        $agentData = $service->getAgentDetails($this->retell_agent_id);
        
        if ($agentData) {
            $this->update([
                'retell_agent_cache' => $agentData,
                'retell_last_sync' => now()
            ]);
        }
    }

    /**
     * Get cached Retell agent data or sync if needed
     *
     * @return array|null
     */
    public function getRetellAgentDataAttribute()
    {
        // Wenn Cache älter als 1 Stunde ist, neu synchronisieren
        if (!$this->retell_agent_cache || 
            !$this->retell_last_sync || 
            $this->retell_last_sync->diffInHours(now()) > 1) {
            $this->syncRetellAgent();
        }
        
        return $this->retell_agent_cache;
    }

    /**
     * Check if Retell agent needs sync
     *
     * @return bool
     */
    public function needsRetellSync()
    {
        return !$this->retell_last_sync || 
               $this->retell_last_sync->diffInHours(now()) > 1;
    }
    
    /**
     * Check if branch needs appointment booking
     * 
     * @return bool
     */
    public function needsAppointmentBooking(): bool
    {
        // Check if branch has override setting
        if (isset($this->settings['appointment_booking_mode']) && 
            $this->settings['appointment_booking_mode'] === 'override') {
            return $this->settings['needs_appointment_booking'] ?? true;
        }
        
        // Otherwise inherit from company
        if ($this->company) {
            return $this->company->needsAppointmentBooking();
        }
        
        // Default to true
        return true;
    }


}
