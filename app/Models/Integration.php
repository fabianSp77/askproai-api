<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class Integration extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'integrable_type',
        'integrable_id',
        'name',
        'type',
        'provider',
        'provider_version',
        'description',
        'api_key',
        'api_secret',
        'access_token',
        'refresh_token',
        'webhook_url',
        'webhook_secret',
        'credentials',
        'config',
        'field_mappings',
        'sync_settings',
        'environment',
        'status',
        'health_status',
        'health_score',
        'last_error',
        'error_count',
        'success_count',
        'last_sync_at',
        'last_success_at',
        'last_error_at',
        'next_sync_at',
        'sync_interval_minutes',
        'auto_sync',
        'api_calls_count',
        'api_calls_limit',
        'records_synced',
        'usage_stats',
        'is_active',
        'is_visible',
        'requires_auth',
        'permissions',
        'external_id',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'credentials' => 'array',
        'config' => 'array',
        'field_mappings' => 'array',
        'sync_settings' => 'array',
        'usage_stats' => 'array',
        'permissions' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
        'requires_auth' => 'boolean',
        'auto_sync' => 'boolean',
        'last_sync_at' => 'datetime',
        'last_success_at' => 'datetime',
        'last_error_at' => 'datetime',
        'next_sync_at' => 'datetime',
        'health_score' => 'integer',
        'error_count' => 'integer',
        'success_count' => 'integer',
        'api_calls_count' => 'integer',
        'api_calls_limit' => 'integer',
        'records_synced' => 'integer',
        'sync_interval_minutes' => 'integer',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
        'access_token',
        'refresh_token',
        'webhook_secret',
        'credentials',
    ];

    /**
     * Available integration providers
     */
    const PROVIDERS = [
        'calcom' => [
            'name' => 'Cal.com',
            'icon' => 'heroicon-o-calendar',
            'color' => 'primary',
            'description' => 'Calendar and appointment scheduling',
        ],
        'retell' => [
            'name' => 'Retell AI',
            'icon' => 'heroicon-o-phone',
            'color' => 'success',
            'description' => 'AI-powered phone agents',
        ],
        'webhook' => [
            'name' => 'Webhook',
            'icon' => 'heroicon-o-globe-alt',
            'color' => 'warning',
            'description' => 'Custom webhook integration',
        ],
        'api' => [
            'name' => 'Custom API',
            'icon' => 'heroicon-o-code-bracket',
            'color' => 'info',
            'description' => 'Generic API integration',
        ],
        'oauth2' => [
            'name' => 'OAuth 2.0',
            'icon' => 'heroicon-o-lock-closed',
            'color' => 'danger',
            'description' => 'OAuth 2.0 authentication',
        ],
    ];

    /**
     * Status options
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_ERROR = 'error';
    const STATUS_SYNCING = 'syncing';
    const STATUS_PENDING = 'pending';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * Health status options
     */
    const HEALTH_HEALTHY = 'healthy';
    const HEALTH_DEGRADED = 'degraded';
    const HEALTH_UNHEALTHY = 'unhealthy';
    const HEALTH_UNKNOWN = 'unknown';

    /**
     * Boot method to handle model events
     */
    protected static function booted()
    {
        static::creating(function ($integration) {
            if (auth()->check()) {
                $integration->created_by = auth()->id();
            }
        });

        static::updating(function ($integration) {
            if (auth()->check()) {
                $integration->updated_by = auth()->id();
            }
        });
    }

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function integrable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHealthy($query)
    {
        return $query->where('health_status', self::HEALTH_HEALTHY);
    }

    public function scopeProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeNeedSync($query)
    {
        return $query->where('auto_sync', true)
            ->where(function ($q) {
                $q->whereNull('next_sync_at')
                    ->orWhere('next_sync_at', '<=', now());
            });
    }

    /**
     * Accessors & Mutators
     */
    public function getProviderNameAttribute(): string
    {
        return self::PROVIDERS[$this->provider]['name'] ?? ucfirst($this->provider);
    }

    public function getProviderIconAttribute(): string
    {
        return self::PROVIDERS[$this->provider]['icon'] ?? 'heroicon-o-puzzle-piece';
    }

    public function getProviderColorAttribute(): string
    {
        return self::PROVIDERS[$this->provider]['color'] ?? 'gray';
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_INACTIVE => 'gray',
            self::STATUS_ERROR => 'danger',
            self::STATUS_SYNCING => 'info',
            self::STATUS_PENDING => 'warning',
            self::STATUS_SUSPENDED => 'danger',
            default => 'gray',
        };
    }

    public function getHealthColorAttribute(): string
    {
        return match($this->health_status) {
            self::HEALTH_HEALTHY => 'success',
            self::HEALTH_DEGRADED => 'warning',
            self::HEALTH_UNHEALTHY => 'danger',
            self::HEALTH_UNKNOWN => 'gray',
            default => 'gray',
        };
    }

    public function getHealthIconAttribute(): string
    {
        return match($this->health_status) {
            self::HEALTH_HEALTHY => '✅',
            self::HEALTH_DEGRADED => '⚠️',
            self::HEALTH_UNHEALTHY => '❌',
            self::HEALTH_UNKNOWN => '❓',
            default => '❓',
        };
    }

    public function getFormattedLastSyncAttribute(): string
    {
        if (!$this->last_sync_at) {
            return 'Never';
        }
        return $this->last_sync_at->diffForHumans();
    }

    public function getFormattedStatusAttribute(): string
    {
        $icon = match($this->status) {
            self::STATUS_ACTIVE => '🟢',
            self::STATUS_INACTIVE => '⚫',
            self::STATUS_ERROR => '🔴',
            self::STATUS_SYNCING => '🔄',
            self::STATUS_PENDING => '🟡',
            self::STATUS_SUSPENDED => '⛔',
            default => '⚫',
        };

        return "{$icon} " . ucfirst($this->status);
    }

    public function getApiUsagePercentageAttribute(): ?float
    {
        if (!$this->api_calls_limit) {
            return null;
        }
        return round(($this->api_calls_count / $this->api_calls_limit) * 100, 2);
    }

    public function getSyncIntervalHoursAttribute(): float
    {
        return round($this->sync_interval_minutes / 60, 1);
    }

    /**
     * Helper Methods
     */
    public function calculateHealthScore(): int
    {
        $score = 100;

        // Deduct for errors
        if ($this->error_count > 0) {
            $score -= min(30, $this->error_count * 5);
        }

        // Deduct for recent errors
        if ($this->last_error_at && $this->last_error_at->isAfter(now()->subHours(1))) {
            $score -= 20;
        }

        // Deduct for no recent success
        if (!$this->last_success_at || $this->last_success_at->isBefore(now()->subHours(24))) {
            $score -= 20;
        }

        // Bonus for recent success
        if ($this->last_success_at && $this->last_success_at->isAfter(now()->subHours(1))) {
            $score = min(100, $score + 10);
        }

        return max(0, $score);
    }

    public function updateHealthStatus(): void
    {
        $score = $this->calculateHealthScore();

        $this->health_score = $score;
        $this->health_status = match(true) {
            $score >= 80 => self::HEALTH_HEALTHY,
            $score >= 50 => self::HEALTH_DEGRADED,
            $score > 0 => self::HEALTH_UNHEALTHY,
            default => self::HEALTH_UNKNOWN,
        };

        $this->save();
    }

    public function markSyncSuccess(): void
    {
        $this->update([
            'last_sync_at' => now(),
            'last_success_at' => now(),
            'success_count' => $this->success_count + 1,
            'error_count' => 0, // Reset error count on success
            'last_error' => null,
            'status' => self::STATUS_ACTIVE,
            'next_sync_at' => now()->addMinutes($this->sync_interval_minutes),
        ]);

        $this->updateHealthStatus();
    }

    public function markSyncError(string $error): void
    {
        $this->update([
            'last_sync_at' => now(),
            'last_error_at' => now(),
            'error_count' => $this->error_count + 1,
            'last_error' => $error,
            'status' => $this->error_count >= 5 ? self::STATUS_ERROR : $this->status,
            'next_sync_at' => now()->addMinutes($this->sync_interval_minutes * 2), // Double interval on error
        ]);

        $this->updateHealthStatus();
    }

    public function incrementApiCalls(int $count = 1): void
    {
        $this->increment('api_calls_count', $count);

        // Check if limit exceeded
        if ($this->api_calls_limit && $this->api_calls_count >= $this->api_calls_limit) {
            $this->update(['status' => self::STATUS_SUSPENDED]);
        }
    }

    public function resetApiCalls(): void
    {
        $this->update([
            'api_calls_count' => 0,
            'status' => $this->status === self::STATUS_SUSPENDED ? self::STATUS_ACTIVE : $this->status,
        ]);
    }

    /**
     * Encryption helpers
     */
    public function setApiKeyAttribute($value)
    {
        $this->attributes['api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getApiKeyAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setApiSecretAttribute($value)
    {
        $this->attributes['api_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getApiSecretAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setAccessTokenAttribute($value)
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getAccessTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setRefreshTokenAttribute($value)
    {
        $this->attributes['refresh_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getRefreshTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setWebhookSecretAttribute($value)
    {
        $this->attributes['webhook_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getWebhookSecretAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Check if integration can sync
     */
    public function canSync(): bool
    {
        return $this->is_active
            && $this->status !== self::STATUS_SUSPENDED
            && $this->status !== self::STATUS_ERROR
            && $this->status !== self::STATUS_SYNCING;
    }

    /**
     * Get display label for integration
     */
    public function getDisplayLabelAttribute(): string
    {
        $entity = '';
        if ($this->integrable) {
            $entity = ' (' . class_basename($this->integrable) . ': ' . $this->integrable->name . ')';
        }
        return $this->name . $entity;
    }
}