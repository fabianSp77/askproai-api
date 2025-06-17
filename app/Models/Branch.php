<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Services\IntegrationTestService;
use App\Models\Staff;
use App\Models\WorkingHour;

class Branch extends Model
{
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
        'customer_id',
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
        'deleted_at'
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
        'business_hours' => 'array',
        'services_override' => 'array',
        'settings' => 'array',
        'retell_agent_data' => 'array',
        'retell_agent_created_at' => 'datetime',
        'retell_synced_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Get the company that owns the branch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    /**
     * Get the customer that owns the branch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

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
     * The services that belong to the branch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'branch_service')
            ->withTimestamps()
            ->withPivot(['price', 'duration', 'active'])
            ->orderBy('name');
    }

    /**
     * Master Services relationship for the branch
     * Diese Services werden vom Unternehmen geerbt und können pro Filiale angepasst werden
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function masterServices()
    {
        return $this->belongsToMany(MasterService::class, 'branch_service_overrides')
                    ->withPivot(['custom_duration', 'custom_price', 'custom_calcom_event_type_id', 'active'])
                    ->withTimestamps();
    }

    /**
     * Get only active master services for this branch
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function activeServices()
    {
        return $this->masterServices()->wherePivot('active', true);
    }

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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function eventTypes()
    {
        return $this->hasMany(CalcomEventType::class);
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
        if ($this->company) {
            return [
                'api_key' => $this->company->calcom_api_key,
                'event_type_id' => $this->company->calcom_event_type_id,
                'team_slug' => $this->company->calcom_team_slug,
            ];
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
     */
    public function getEffectiveCalcomEventTypeId()
    {
        // Wenn eigener Wert gesetzt ist und calendar_mode auf 'override' steht
        if ($this->calendar_mode === 'override' && $this->calcom_event_type_id) {
            return $this->calcom_event_type_id;
        }

        // Ansonsten vom Unternehmen erben
        return $this->company ? $this->company->calcom_event_type_id : null;
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
     * Get configuration progress for this branch
     *
     * @return array
     */
    public function getConfigurationProgressAttribute()
    {
        $status = $this->configuration_status ?? [];
        
        $steps = [
            'basic_info' => !empty($this->name) && !empty($this->city),
            'contact' => !empty($this->phone_number) && !empty($this->notification_email),
            'hours' => !empty($this->business_hours),
            'retell' => !empty($this->retell_agent_id),
            'calendar' => !empty($this->calcom_api_key) && !empty($this->calcom_event_type_id),
        ];
        
        $completed = count(array_filter($steps));
        $total = count($steps);
        
        return [
            'percentage' => round(($completed / $total) * 100),
            'completed' => $completed,
            'total' => $total,
            'steps' => $steps
        ];
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
     * Prüft ob die Filiale aktiviert werden kann
     * 
     * @return bool
     */
    public function canBeActivated(): bool
    {
        // Pflichtfelder für Aktivierung
        $requiredFields = [
            'company_id',
            'name',
            'address',
            'city',
            'postal_code',
            'country',
            'phone_number',
            'notification_email'
        ];
        
        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }
        
        return true;
    }

}
