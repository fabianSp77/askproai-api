<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Security\ApiKeyEncryptionService;

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
        'retell_default_settings',
        'calcom_api_key',
        'calcom_team_slug',
        'calcom_user_id',
        'google_calendar_credentials',
        'stripe_customer_id',
        'stripe_subscription_id',
        // Tax fields
        'tax_number',
        'vat_id',
        'is_small_business',
        'small_business_threshold_date',
        'tax_configuration',
        'invoice_prefix',
        'next_invoice_number',
        'payment_terms',
        'auto_invoice',
        'invoice_day_of_month',
        'credit_limit',
        // Revenue tracking
        'revenue_ytd',
        'revenue_previous_year',
        // Subscription dates
        'subscription_started_at',
        'subscription_current_period_end',
    ];

    protected $casts = [
        'settings' => 'array',
        'metadata' => 'array',
        'tax_configuration' => 'array',
        'retell_default_settings' => 'array',
        'google_calendar_credentials' => 'encrypted:array',
        'is_active' => 'boolean',
        'is_small_business' => 'boolean',
        'auto_invoice' => 'boolean',
        'trial_ends_at' => 'datetime',
        'small_business_threshold_date' => 'date',
        'next_invoice_number' => 'integer',
        'invoice_day_of_month' => 'integer',
        'credit_limit' => 'decimal:2',
        'revenue_ytd' => 'decimal:2',
        'revenue_previous_year' => 'decimal:2',
        'subscription_started_at' => 'datetime',
        'subscription_current_period_end' => 'datetime',
    ];

    protected $hidden = [
        'retell_api_key',
        'calcom_api_key',
        'google_calendar_credentials',
        'stripe_customer_id',
    ];

    protected $attributes = [
        // Removed default values to prevent test issues with SQLite
        // These will be set by factory or creation logic
    ];

    /**
     * Get the branches for the company.
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
    
    /**
     * Get decrypted Retell API key
     */
    public function getRetellApiKeyAttribute($value)
    {
        if (empty($value)) {
            return null;
        }
        
        $encryptionService = app(ApiKeyEncryptionService::class);
        return $encryptionService->decrypt($value);
    }
    
    /**
     * Set encrypted Retell API key
     */
    public function setRetellApiKeyAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['retell_api_key'] = null;
            return;
        }
        
        $encryptionService = app(ApiKeyEncryptionService::class);
        $this->attributes['retell_api_key'] = $encryptionService->encrypt($value);
    }
    
    /**
     * Get decrypted Cal.com API key
     */
    public function getCalcomApiKeyAttribute($value)
    {
        if (empty($value)) {
            return null;
        }
        
        $encryptionService = app(ApiKeyEncryptionService::class);
        return $encryptionService->decrypt($value);
    }
    
    /**
     * Set encrypted Cal.com API key
     */
    public function setCalcomApiKeyAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['calcom_api_key'] = null;
            return;
        }
        
        $encryptionService = app(ApiKeyEncryptionService::class);
        $this->attributes['calcom_api_key'] = $encryptionService->encrypt($value);
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
     * Get the tax rates for the company.
     */
    public function taxRates(): HasMany
    {
        return $this->hasMany(TaxRate::class);
    }

    /**
     * Get the payments for the company.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the event types for the company.
     */
    public function eventTypes(): HasMany
    {
        return $this->hasMany(CalcomEventType::class);
    }

    /**
     * Get the phone numbers for the company.
     */
    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(PhoneNumber::class);
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