<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'industry',
        'website',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'timezone',
        'currency',
        'logo',
        'settings',
        'metadata',
        'is_active',
        'trial_ends_at',
        'subscription_status',
        'subscription_plan',
        // API Keys
        'retell_api_key',
        'retell_agent_id',
        'calcom_api_key',
        'calcom_team_slug',
        'calcom_user_id',
        'google_calendar_credentials',
        'stripe_customer_id',
        'stripe_subscription_id',
    ];

    protected $casts = [
        'settings' => 'array',
        'metadata' => 'array',
        'google_calendar_credentials' => 'encrypted:array',
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    protected $hidden = [
        'retell_api_key',
        'calcom_api_key',
        'google_calendar_credentials',
        'stripe_customer_id',
    ];

    protected $attributes = [
        'is_active' => true,
        'currency' => 'EUR',
        'timezone' => 'Europe/Berlin',
        'country' => 'DE',
    ];

    /**
     * Get the branches for the company.
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Get the staff members for the company.
     */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    /**
     * Get the customers for the company.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get the appointments for the company.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the calls for the company.
     */
    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    /**
     * Get the services for the company.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get the users associated with the company.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the invoices for the company.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the pricing for the company.
     */
    public function pricing()
    {
        return $this->hasOne(CompanyPricing::class);
    }

    /**
     * Get the event types for the company.
     */
    public function eventTypes(): HasMany
    {
        return $this->hasMany(CalcomEventType::class);
    }

    /**
     * Check if company is in trial period
     */
    public function isInTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if company has active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_status === 'active';
    }

    /**
     * Get setting value
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set setting value
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
        $this->save();
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = \Str::slug($company->name);
            }
            
            // Set trial period for new companies (14 days)
            if (empty($company->trial_ends_at)) {
                $company->trial_ends_at = now()->addDays(14);
            }
        });
    }
}