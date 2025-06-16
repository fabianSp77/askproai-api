<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\SmartLoader;
use App\Models\Traits\HasLoadingProfiles;
use App\Traits\HasEncryptedAttributes;

class Company extends Model
{
    use HasFactory, SoftDeletes, SmartLoader, HasLoadingProfiles, HasEncryptedAttributes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'companies';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'city',
        'postal_code',
        'country',
        'tax_number',
        'company_type',
        'subscription_plan',
        'subscription_status',
        'trial_ends_at',
        'active',
        'is_active',
        'typ',
        'settings',
        'calcom_api_key',
        'calcom_team_slug',
        'calcom_event_type_id',
        'calcom_user_id',
        'retell_api_key',
        'retell_webhook_url',
        'retell_agent_id',
        'calcom_calendar_mode',
        'billing_status',
        'stripe_customer_id',
        'stripe_subscription_id',
        'webhook_url',
        'opening_hours',
        'notification_email',
        'notification_emails',
        'notify_on_booking',
        'send_booking_confirmations',
        'calendar_mode',
        'calendar_mapping',
        'api_test_errors',
    ];

    /**
     * The attributes that should be encrypted.
     *
     * @var array<int, string>
     */
    protected $encrypted = [
        'calcom_api_key',
        'retell_api_key',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
        'is_active' => 'boolean',
        'notify_on_booking' => 'boolean',
        'send_booking_confirmations' => 'boolean',
        'settings' => 'array',
        'opening_hours' => 'array',
        'notification_emails' => 'array',
        'calendar_mapping' => 'array',
        'api_test_errors' => 'array',
        'trial_ends_at' => 'datetime',
    ];

    /**
     * Get the branches for the company.
     */
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Get the customers for the company.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get the staff members for the company.
     */
    public function staff()
    {
        return $this->hasMany(Staff::class);
    }

    /**
     * Get the services for the company.
     */
    public function services()
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get the appointments for the company.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the calls for the company.
     */
    public function calls()
    {
        return $this->hasMany(Call::class);
    }

    /**
     * Get the integrations for the company.
     */
    public function integrations()
    {
        return $this->hasMany(Integration::class);
    }

    /**
     * Get the users for the company.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the retell agents for the company.
     */
    public function retellAgents()
    {
        return $this->hasMany(RetellAgent::class);
    }

    /**
     * Get the event types for the company.
     */
    public function eventTypes()
    {
        return $this->hasMany(CalcomEventType::class);
    }

    /**
     * Get the calcom bookings for the company.
     */
    public function calcomBookings()
    {
        return $this->hasMany(CalcomBooking::class);
    }

    /**
     * Scope active companies.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

public function tenants()
{
    return $this->hasMany(Tenant::class);
}


    /**
     * Check if company is on trial.
     *
     * @return bool
     */
    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial' &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    /**
     * Check if company has valid subscription.
     *
     * @return bool
     */
    public function hasValidSubscription(): bool
    {
        return in_array($this->subscription_status, ['active', 'trial']);
    }

    /**
     * Get settings value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set settings value.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
        $this->save();
    }

    /**
     * Get the display name with contact person.
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->contact_person) {
            return $this->name . ' (' . $this->contact_person . ')';
        }
        return $this->name;
    }

    /**
     * Get Cal.com configuration for the company
     *
     * @return array|null
     */
    public function getCalcomConfig()
    {
        if ($this->calcom_api_key) {
            return [
                'api_key' => $this->calcom_api_key,
                'event_type_id' => $this->calcom_event_type_id,
                'team_slug' => $this->calcom_team_slug,
                'user_id' => $this->calcom_user_id,
            ];
        }

        return null;
    }

    /**
     * Get Retell.ai configuration for the company
     *
     * @return array|null
     */
    public function getRetellConfig()
    {
        if ($this->retell_api_key) {
            return [
                'api_key' => $this->retell_api_key,
                'webhook_url' => $this->webhook_url ?? 'https://api.askproai.de/api/retell/webhook',
            ];
        }

        return null;
    }
    
    /**
     * Define the loading profiles for this model
     */
    protected static function defineLoadingProfiles(): void
    {
        // Minimal profile - no relationships
        static::defineLoadingProfile('minimal', []);
        
        // Standard profile - essential relationships
        static::defineLoadingProfile('standard', [
            'branches:id,company_id,name,address,city',
        ]);
        
        // Full profile - all relationships (be careful with this!)
        static::defineLoadingProfile('full', [
            'branches',
            'staff',
            'services',
            'users',
            'integrations',
        ]);
        
        // Counts profile - for dashboard and listings
        static::defineLoadingProfile('counts', [
            'branches',
            'staff',
            'customers',
            'appointments',
            'services',
        ]);
    }
    
    /**
     * Get allowed includes for API
     */
    protected function getAllowedIncludes(): array
    {
        return [
            'branches',
            'staff',
            'services',
            'customers',
            'users',
        ];
    }
    
    /**
     * Get countable relations
     */
    protected function getCountableRelations(): array
    {
        return [
            'branches',
            'staff',
            'customers',
            'appointments',
            'services',
            'calls',
        ];
    }
}
