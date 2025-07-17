<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Security\ApiKeyService;

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
        // Language settings
        'default_language',
        'supported_languages',
        'auto_translate',
        'translation_provider',
        // API Keys
        'retell_api_key',
        'retell_agent_id',
        'retell_default_settings',
        'calcom_api_key',
        'calcom_team_slug',
        'calcom_user_id',
        'google_calendar_credentials',
        'stripe_customer_id',
        // White-label fields
        'parent_company_id',
        'company_type',
        'is_white_label',
        'white_label_settings',
        'commission_rate',
        'stripe_subscription_id',
        // Tax fields
        'tax_number',
        'vat_id',
        'is_small_business',
        'small_business_threshold_date',
        // Billing fields
        'alert_preferences',
        'billing_contact_email',
        'billing_contact_phone',
        'usage_budget',
        'alerts_enabled',
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
        // Call notification preferences
        'send_call_summaries',
        'call_summary_recipients',
        'include_transcript_in_summary',
        'include_csv_export',
        'summary_email_frequency',
        'call_notification_settings',
        'email_notifications_enabled',
    ];

    protected $casts = [
        'settings' => 'array',
        'metadata' => 'array',
        'tax_configuration' => 'array',
        'retell_default_settings' => 'array',
        'google_calendar_credentials' => 'encrypted:array',
        'supported_languages' => 'array',
        'is_active' => 'boolean',
        'is_small_business' => 'boolean',
        'auto_invoice' => 'boolean',
        'auto_translate' => 'boolean',
        'trial_ends_at' => 'datetime',
        'small_business_threshold_date' => 'date',
        'next_invoice_number' => 'integer',
        'invoice_day_of_month' => 'integer',
        'credit_limit' => 'decimal:2',
        'revenue_ytd' => 'decimal:2',
        'revenue_previous_year' => 'decimal:2',
        'subscription_started_at' => 'datetime',
        'subscription_current_period_end' => 'datetime',
        // Call notification preferences
        'send_call_summaries' => 'boolean',
        'call_summary_recipients' => 'array',
        'include_transcript_in_summary' => 'boolean',
        'include_csv_export' => 'boolean',
        'call_notification_settings' => 'array',
        'email_notifications_enabled' => 'boolean',
        // White-label fields
        'is_white_label' => 'boolean',
        'white_label_settings' => 'array',
        'commission_rate' => 'decimal:2',
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
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        // Set defaults on creation
        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = \Str::slug($company->name);
            }
            
            // Set trial period for new companies (14 days)
            if (empty($company->trial_ends_at)) {
                $company->trial_ends_at = now()->addDays(14);
            }
        });
        
        // Encrypt API keys on save
        static::saving(function ($company) {
            $apiKeyService = app(ApiKeyService::class);
            
            // Encrypt Retell API key if changed
            if ($company->isDirty('retell_api_key') && !empty($company->retell_api_key)) {
                $company->retell_api_key = $apiKeyService->encrypt($company->retell_api_key);
            }
            
            // Encrypt Cal.com API key if changed
            if ($company->isDirty('calcom_api_key') && !empty($company->calcom_api_key)) {
                $company->calcom_api_key = $apiKeyService->encrypt($company->calcom_api_key);
            }
        });
    }
    
    /**
     * Check if company needs appointment booking
     */
    public function needsAppointmentBooking(): bool
    {
        return $this->settings['needs_appointment_booking'] ?? true;
    }
    
    /**
     * Get decrypted Retell API key
     */
    public function getRetellApiKeyAttribute($value)
    {
        if (empty($value)) {
            return null;
        }
        
        try {
            $apiKeyService = app(ApiKeyService::class);
            
            // Check if it looks like an encrypted value
            if ($apiKeyService->isEncrypted($value)) {
                $decrypted = $apiKeyService->decrypt($value);
                if ($decrypted) {
                    \Log::debug('Retell API key decrypted successfully', [
                        'company_id' => $this->id,
                        'key_preview' => substr($decrypted, 0, 8) . '...'
                    ]);
                    return $decrypted;
                }
            }
            
            // If it looks like a plain API key, use it directly
            if (str_starts_with($value, 'key_')) {
                \Log::debug('Using plain Retell API key', [
                    'company_id' => $this->id,
                    'key_preview' => substr($value, 0, 8) . '...'
                ]);
                return $value;
            }
            
            // Otherwise try to decrypt anyway
            return $apiKeyService->decrypt($value) ?: $value;
        } catch (\Exception $e) {
            \Log::warning('Failed to decrypt Retell API key, using as-is', [
                'company_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            // If decryption fails, return the value as-is
            return $value;
        }
    }
    
    /**
     * Get decrypted Cal.com API key
     */
    public function getCalcomApiKeyAttribute($value)
    {
        if (empty($value)) {
            return null;
        }
        
        try {
            $apiKeyService = app(ApiKeyService::class);
            
            // Check if it looks like an encrypted value
            if ($apiKeyService->isEncrypted($value)) {
                $decrypted = $apiKeyService->decrypt($value);
                if ($decrypted) {
                    \Log::debug('Cal.com API key decrypted successfully', [
                        'company_id' => $this->id,
                        'key_preview' => substr($decrypted, 0, 8) . '...'
                    ]);
                    return $decrypted;
                }
            }
            
            // If it looks like a plain UUID (Cal.com keys), use it directly
            if (preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $value)) {
                \Log::debug('Using plain Cal.com API key', [
                    'company_id' => $this->id,
                    'key_preview' => substr($value, 0, 8) . '...'
                ]);
                return $value;
            }
            
            // Otherwise try to decrypt anyway
            return $apiKeyService->decrypt($value) ?: $value;
        } catch (\Exception $e) {
            \Log::warning('Failed to decrypt Cal.com API key, using as-is', [
                'company_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            // If decryption fails, return the value as-is
            return $value;
        }
    }
    
    /**
     * Get masked API key for display
     */
    public function getMaskedRetellApiKey(): string
    {
        $apiKeyService = app(ApiKeyService::class);
        return $apiKeyService->mask($this->retell_api_key);
    }
    
    /**
     * Get masked Cal.com API key for display
     */
    public function getMaskedCalcomApiKey(): string
    {
        $apiKeyService = app(ApiKeyService::class);
        return $apiKeyService->mask($this->calcom_api_key);
    }

    /**
     * Get the branches for the company.
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
    
    /**
     * Get the parent company (for white-label clients).
     */
    public function parentCompany()
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }
    
    /**
     * Get the child companies (for resellers).
     */
    public function childCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_company_id');
    }
    
    /**
     * Check if this company is a reseller.
     */
    public function isReseller(): bool
    {
        return $this->company_type === 'reseller';
    }
    
    /**
     * Check if this company is a client of a reseller.
     */
    public function isClient(): bool
    {
        return $this->company_type === 'client';
    }
    
    /**
     * Get all companies this company can access (self + children if reseller).
     */
    public function getAccessibleCompanies()
    {
        if ($this->isReseller()) {
            return Company::where('id', $this->id)
                ->orWhere('parent_company_id', $this->id)
                ->get();
        }
        
        return collect([$this]);
    }

    public function onboardingState(): HasOne
    {
        return $this->hasOne(OnboardingState::class);
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
     * Get the prepaid balance for the company.
     */
    public function prepaidBalance(): HasOne
    {
        return $this->hasOne(PrepaidBalance::class);
    }

    /**
     * Get the balance transactions for the company.
     */
    public function balanceTransactions(): HasMany
    {
        return $this->hasMany(BalanceTransaction::class);
    }

    /**
     * Get the balance topups for the company.
     */
    public function balanceTopups(): HasMany
    {
        return $this->hasMany(BalanceTopup::class);
    }

    /**
     * Get the billing rate for the company.
     */
    public function billingRate(): HasOne
    {
        return $this->hasOne(BillingRate::class);
    }

    /**
     * Get the call charges for the company.
     */
    public function callCharges(): HasMany
    {
        return $this->hasMany(CallCharge::class);
    }

    /**
     * Get the portal users for the company.
     */
    public function portalUsers(): HasMany
    {
        return $this->hasMany(PortalUser::class);
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
     * Get the goals for the company.
     */
    public function goals(): HasMany
    {
        return $this->hasMany(CompanyGoal::class);
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
     * Get the subscriptions for the company.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the active subscription for the company.
     */
    public function activeSubscription()
    {
        return $this->subscriptions()->active()->latest()->first();
    }

    /**
     * Check if company is in trial period
     */
    public function isInTrial(): bool
    {
        $activeSubscription = $this->activeSubscription();
        if ($activeSubscription) {
            return $activeSubscription->onTrial();
        }
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if company has active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
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
     * Check if company has a specific module enabled
     */
    public function hasModule(string $module): bool
    {
        // By default, all companies have calls module
        if ($module === 'calls') {
            return true;
        }
        
        // Check settings for other modules
        $enabledModules = $this->getSetting('enabled_modules', [
            'calls' => true,
            'appointments' => true,
            'customers' => true,
            'billing' => true,
            'analytics' => true,
        ]);
        
        return $enabledModules[$module] ?? false;
    }
    

}