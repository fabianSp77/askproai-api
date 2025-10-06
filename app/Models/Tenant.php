<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Tenant extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'type',
        'logo_url',
        'domain',
        'subdomain',
        'email',
        'slug',
        'api_key',
        'api_key_hash',
        'api_key_prefix',
        'api_secret',
        'allowed_ips',
        'webhook_url',
        'webhook_events',
        'webhook_secret',
        'pricing_plan',
        'monthly_fee',
        'per_minute_rate',
        'discount_percentage',
        'billing_info',
        'payment_method',
        'billing_cycle',
        'next_billing_date',
        'last_payment_date',
        'balance_cents',
        'calcom_team_slug',
        'calcom_enabled',
        'calcom_api_key',
        'calcom_event_types',
        'retell_enabled',
        'retell_api_key',
        'integrations',
        'limits',
        'max_users',
        'max_companies',
        'max_branches',
        'max_agents',
        'max_phone_numbers',
        'max_monthly_calls',
        'max_storage_gb',
        'settings',
        'features',
        'feature_flags',
        'gdpr_settings',
        'timezone',
        'language',
        'currency',
        'date_format',
        'time_format',
        'total_calls',
        'total_minutes',
        'monthly_calls',
        'monthly_minutes',
        'storage_used_mb',
        'api_calls_today',
        'api_calls_month',
        'last_api_call_at',
        'last_login_at',
        'status',
        'is_active',
        'is_verified',
        'trial_ends_at',
        'subscription_ends_at',
        'suspended_at',
        'suspended_reason',
        'onboarding_completed',
        'onboarding_step',
        'company_name',
        'tax_id',
        'billing_address',
        'contact_email',
        'contact_phone',
        'notes',
        'tags',
        'custom_fields',
        'metadata',
    ];

    protected $casts = [
        'allowed_ips' => 'array',
        'webhook_events' => 'array',
        'billing_info' => 'array',
        'calcom_event_types' => 'array',
        'integrations' => 'array',
        'limits' => 'array',
        'settings' => 'array',
        'features' => 'array',
        'feature_flags' => 'array',
        'gdpr_settings' => 'array',
        'billing_address' => 'array',
        'tags' => 'array',
        'custom_fields' => 'array',
        'metadata' => 'array',
        'monthly_fee' => 'decimal:2',
        'per_minute_rate' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'balance_cents' => 'integer',
        'total_calls' => 'integer',
        'total_minutes' => 'integer',
        'monthly_calls' => 'integer',
        'monthly_minutes' => 'integer',
        'storage_used_mb' => 'integer',
        'api_calls_today' => 'integer',
        'api_calls_month' => 'integer',
        'max_users' => 'integer',
        'max_companies' => 'integer',
        'max_branches' => 'integer',
        'max_agents' => 'integer',
        'max_phone_numbers' => 'integer',
        'max_monthly_calls' => 'integer',
        'max_storage_gb' => 'integer',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'calcom_enabled' => 'boolean',
        'retell_enabled' => 'boolean',
        'onboarding_completed' => 'boolean',
        'next_billing_date' => 'date',
        'last_payment_date' => 'date',
        'last_api_call_at' => 'datetime',
        'last_login_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'suspended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
        'webhook_secret',
        'calcom_api_key',
        'retell_api_key',
    ];

    protected $attributes = [
        'pricing_plan' => 'starter',
        'billing_cycle' => 'monthly',
        'status' => 'active',
        'is_active' => true,
        'timezone' => 'Europe/Berlin',
        'language' => 'de',
        'currency' => 'EUR',
        'date_format' => 'd.m.Y',
        'time_format' => 'H:i',
        'onboarding_completed' => false,
    ];

    /**
     * Encrypt sensitive fields
     */
    public function setApiKeyAttribute($value)
    {
        if ($value) {
            $this->attributes['api_key'] = encrypt($value);
            $this->attributes['api_key_prefix'] = substr($value, 0, 8) . '...';
            $this->attributes['api_key_hash'] = hash('sha256', $value);
        }
    }

    public function getApiKeyAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    public function setApiSecretAttribute($value)
    {
        $this->attributes['api_secret'] = $value ? encrypt($value) : null;
    }

    public function getApiSecretAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    public function setWebhookSecretAttribute($value)
    {
        $this->attributes['webhook_secret'] = $value ? encrypt($value) : null;
    }

    public function getWebhookSecretAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    public function setCalcomApiKeyAttribute($value)
    {
        $this->attributes['calcom_api_key'] = $value ? encrypt($value) : null;
    }

    public function getCalcomApiKeyAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    public function setRetellApiKeyAttribute($value)
    {
        $this->attributes['retell_api_key'] = $value ? encrypt($value) : null;
    }

    public function getRetellApiKeyAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    /**
     * Relationships
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Get related data through company relationship
    public function phoneNumbers()
    {
        if ($this->company_id) {
            return PhoneNumber::where('company_id', $this->company_id);
        }
        return PhoneNumber::where('id', null); // Empty query
    }

    public function retellAgents()
    {
        if ($this->company_id) {
            return RetellAgent::where('company_id', $this->company_id);
        }
        return RetellAgent::where('id', null); // Empty query
    }

    public function integrations()
    {
        if ($this->company_id) {
            return Integration::where('company_id', $this->company_id);
        }
        return Integration::where('id', null); // Empty query
    }

    /**
     * Helper Methods
     */
    public function getBalanceInEuros()
    {
        return $this->balance_cents / 100;
    }

    public function setBalanceInEuros($euros)
    {
        $this->balance_cents = $euros * 100;
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isSubscribed(): bool
    {
        return $this->subscription_ends_at && $this->subscription_ends_at->isFuture();
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []) ||
               in_array($feature, $this->feature_flags ?? []);
    }

    public function isWithinLimit(string $resource, int $count = 1): bool
    {
        $limitKey = 'max_' . $resource;
        $limit = $this->$limitKey ?? $this->limits[$resource] ?? null;

        if ($limit === null) {
            return true;
        }

        $currentCount = match($resource) {
            'users' => $this->users()->count(),
            'companies' => $this->company ? 1 : 0,
            'agents' => $this->retellAgents()->count(),
            'phone_numbers' => $this->phoneNumbers()->count(),
            default => 0,
        };

        return ($currentCount + $count) <= $limit;
    }

    public function getStorageUsedGB(): float
    {
        return round($this->storage_used_mb / 1024, 2);
    }

    public function getStoragePercentage(): float
    {
        if ($this->max_storage_gb == 0) {
            return 0;
        }
        return round(($this->getStorageUsedGB() / $this->max_storage_gb) * 100, 1);
    }

    public function getCallsPercentage(): float
    {
        if ($this->max_monthly_calls == 0) {
            return 0;
        }
        return round(($this->monthly_calls / $this->max_monthly_calls) * 100, 1);
    }
}