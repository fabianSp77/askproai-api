<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * CompanyGatewayConfiguration Model
 *
 * Per-company gateway settings for multi-tenant configuration.
 * Provides company-specific overrides for global gateway.php settings.
 *
 * Configuration Hierarchy:
 * 1. This model (highest priority)
 * 2. PolicyConfiguration (legacy, backward compatible)
 * 3. config/gateway.php (global defaults)
 *
 * @property int $id
 * @property int $company_id
 * @property bool $gateway_enabled
 * @property string $gateway_mode
 * @property string $hybrid_fallback_mode
 * @property bool $enrichment_enabled
 * @property bool $audio_in_webhook
 * @property int $delivery_initial_delay_seconds
 * @property int $enrichment_timeout_seconds
 * @property int $audio_url_ttl_minutes
 * @property string|null $admin_email
 * @property bool $alerts_enabled
 * @property string|null $slack_webhook
 * @property float $intent_confidence_threshold
 * @property int|null $default_priority
 * @property string $default_case_type
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @property-read Company $company
 */
class CompanyGatewayConfiguration extends Model
{
    use HasFactory;

    /**
     * Cache TTL in seconds (5 minutes)
     */
    public const CACHE_TTL = 300;

    /**
     * Valid gateway modes
     */
    public const MODES = ['appointment', 'service_desk', 'hybrid'];

    /**
     * Valid case types - must match ServiceCase::CASE_TYPES
     */
    public const CASE_TYPES = ['incident', 'request', 'inquiry'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'gateway_enabled',
        'gateway_mode',
        'hybrid_fallback_mode',
        'enrichment_enabled',
        'audio_in_webhook',
        'delivery_initial_delay_seconds',
        'enrichment_timeout_seconds',
        'audio_url_ttl_minutes',
        'admin_email',
        'alerts_enabled',
        'slack_webhook',
        'intent_confidence_threshold',
        'default_priority',
        'default_case_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'gateway_enabled' => 'boolean',
        'enrichment_enabled' => 'boolean',
        'audio_in_webhook' => 'boolean',
        'alerts_enabled' => 'boolean',
        'delivery_initial_delay_seconds' => 'integer',
        'enrichment_timeout_seconds' => 'integer',
        'audio_url_ttl_minutes' => 'integer',
        'intent_confidence_threshold' => 'float',
        'default_priority' => 'integer',
    ];

    /**
     * Get the company that owns this configuration.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get cached configuration for a company.
     *
     * Returns null if no configuration exists for the company.
     * Use GatewayConfigService for full hierarchy resolution.
     *
     * @param int $companyId
     * @return static|null
     */
    public static function getCached(int $companyId): ?static
    {
        $cacheKey = "company_gateway_config:{$companyId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId) {
            return static::where('company_id', $companyId)->first();
        });
    }

    /**
     * Clear cached configuration for a company.
     *
     * @param int $companyId
     * @return void
     */
    public static function clearCache(int $companyId): void
    {
        Cache::forget("company_gateway_config:{$companyId}");
    }

    /**
     * Get admin email addresses as array.
     *
     * @return array
     */
    public function getAdminEmailsAttribute(): array
    {
        if (empty($this->admin_email)) {
            return [];
        }

        return array_filter(
            array_map('trim', explode(',', $this->admin_email))
        );
    }

    /**
     * Check if this company uses hybrid mode.
     *
     * @return bool
     */
    public function isHybridMode(): bool
    {
        return $this->gateway_mode === 'hybrid';
    }

    /**
     * Check if this company uses service desk mode.
     *
     * @return bool
     */
    public function isServiceDeskMode(): bool
    {
        return $this->gateway_mode === 'service_desk';
    }

    /**
     * Check if this company uses appointment mode.
     *
     * @return bool
     */
    public function isAppointmentMode(): bool
    {
        return $this->gateway_mode === 'appointment';
    }

    /**
     * Get hybrid mode configuration array.
     *
     * @return array
     */
    public function getHybridConfigArray(): array
    {
        return [
            'fallback_mode' => $this->hybrid_fallback_mode,
            'intent_confidence_threshold' => $this->intent_confidence_threshold,
        ];
    }

    /**
     * Get delivery configuration array.
     *
     * @return array
     */
    public function getDeliveryConfigArray(): array
    {
        return [
            'initial_delay_seconds' => $this->delivery_initial_delay_seconds,
            'enrichment_timeout_seconds' => $this->enrichment_timeout_seconds,
            'audio_url_ttl_minutes' => $this->audio_url_ttl_minutes,
        ];
    }

    /**
     * Get alerts configuration array.
     *
     * @return array
     */
    public function getAlertsConfigArray(): array
    {
        return [
            'admin_email' => $this->admin_email,
            'enabled' => $this->alerts_enabled,
            'slack_webhook' => $this->slack_webhook,
        ];
    }

    /**
     * Boot method for model events.
     */
    protected static function booted(): void
    {
        // Clear cache when config is saved or deleted
        static::saved(function (self $config) {
            self::clearCache($config->company_id);
        });

        static::deleted(function (self $config) {
            self::clearCache($config->company_id);
        });
    }
}
