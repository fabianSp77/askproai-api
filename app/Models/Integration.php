<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToCompany;

class Integration extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'status',
        'config',
        'credentials',
        'is_active',
        'last_sync_at',
    ];

    protected $casts = [
        'config' => 'array',
        'credentials' => 'encrypted:array',
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Integration types
     */
    const TYPE_RETELL = 'retell';
    const TYPE_CALCOM = 'calcom';
    const TYPE_STRIPE = 'stripe';
    const TYPE_TWILIO = 'twilio';
    const TYPE_WHATSAPP = 'whatsapp';
    const TYPE_GOOGLE_CALENDAR = 'google_calendar';
    const TYPE_WEBHOOK = 'webhook';
    const TYPE_API = 'api';

    /**
     * Integration statuses
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_ERROR = 'error';
    const STATUS_PENDING = 'pending';

    /**
     * Get available integration types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_RETELL => 'Retell.ai',
            self::TYPE_CALCOM => 'Cal.com',
            self::TYPE_STRIPE => 'Stripe',
            self::TYPE_TWILIO => 'Twilio',
            self::TYPE_WHATSAPP => 'WhatsApp',
            self::TYPE_GOOGLE_CALENDAR => 'Google Calendar',
            self::TYPE_WEBHOOK => 'Webhook',
            self::TYPE_API => 'API',
        ];
    }

    /**
     * Get available statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Aktiv',
            self::STATUS_INACTIVE => 'Inaktiv',
            self::STATUS_ERROR => 'Fehler',
            self::STATUS_PENDING => 'Ausstehend',
        ];
    }

    /**
     * Get the company that owns the integration
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope a query to only include active integrations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope a query to only include integrations of a given type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if the integration is active
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Get decrypted credentials
     */
    public function getCredentials(): array
    {
        return $this->credentials ?? [];
    }

    /**
     * Get configuration value
     */
    public function getConfig(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->config ?? [];
        }

        return data_get($this->config, $key, $default);
    }

    /**
     * Set configuration value
     */
    public function setConfig(string $key, $value): self
    {
        $config = $this->config ?? [];
        data_set($config, $key, $value);
        $this->config = $config;
        
        return $this;
    }
}