<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Integration extends Model
{
    protected $fillable = [
        'company_id',
        'customer_id',
        'kunde_id',
        'system',
        'service',
        'type',
        'api_key',
        'settings',
        'webhook_url',
        'health_status',
        'last_sync',
        'usage_count',
        'metadata',
        'credentials',
        'zugangsdaten',
        'active',
    ];

    protected $casts = [
        'credentials' => 'array',
        'zugangsdaten' => 'array',
        'settings' => 'array',
        'metadata' => 'array',
        'active' => 'boolean',
        'last_sync' => 'datetime',
    ];
    
    // Map new field names to existing database columns
    public function getServiceAttribute()
    {
        return $this->system;
    }
    
    public function setServiceAttribute($value)
    {
        $this->system = $value;
    }
    
    public function getSettingsAttribute()
    {
        return $this->zugangsdaten;
    }
    
    public function setSettingsAttribute($value)
    {
        $this->zugangsdaten = $value;
    }
    
    // Provide default values for missing columns
    public function getTypeAttribute()
    {
        return match($this->system) {
            'calcom', 'google_calendar', 'outlook' => 'calendar',
            'retell' => 'phone_ai',
            'zoom' => 'video',
            'stripe' => 'payment',
            'twilio' => 'sms',
            'sendgrid' => 'email',
            default => 'other'
        };
    }
    
    public function getApiKeyAttribute()
    {
        $settings = $this->zugangsdaten ?? [];
        return $settings['api_key'] ?? null;
    }
    
    public function setApiKeyAttribute($value)
    {
        $settings = $this->zugangsdaten ?? [];
        $settings['api_key'] = $value;
        $this->zugangsdaten = $settings;
    }
    
    public function getWebhookUrlAttribute()
    {
        $settings = $this->zugangsdaten ?? [];
        return $settings['webhook_url'] ?? null;
    }
    
    public function setWebhookUrlAttribute($value)
    {
        $settings = $this->zugangsdaten ?? [];
        $settings['webhook_url'] = $value;
        $this->zugangsdaten = $settings;
    }
    
    public function getHealthStatusAttribute()
    {
        return $this->attributes['health_status'] ?? 'unknown';
    }
    
    public function getLastSyncAttribute()
    {
        return $this->attributes['last_sync'] ?? null;
    }
    
    public function getUsageCountAttribute()
    {
        return $this->attributes['usage_count'] ?? 0;
    }
    
    public function getMetadataAttribute()
    {
        return $this->attributes['metadata'] ?? [];
    }
    
    public function getActiveAttribute()
    {
        return $this->attributes['active'] ?? true;
    }
    
    public function getCustomerIdAttribute()
    {
        return $this->kunde_id;
    }
    
    public function setCustomerIdAttribute($value)
    {
        $this->kunde_id = $value;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'kunde_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
