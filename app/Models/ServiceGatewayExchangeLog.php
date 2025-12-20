<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * ServiceGatewayExchangeLog
 *
 * Audit trail for all external communications made by the Service Gateway.
 * Implements No-Leak Guarantee by storing only redacted payloads.
 *
 * Key features:
 * - Automatic UUID generation for event_id
 * - JSON casting for payload fields
 * - Company scoping for multi-tenant queries
 * - Retry chain tracking via parent_event_id
 *
 * @property int $id
 * @property string $event_id UUID for external correlation
 * @property string $direction 'outbound' or 'inbound'
 * @property int|null $call_id
 * @property int|null $service_case_id
 * @property int|null $company_id
 * @property string $endpoint
 * @property string $http_method
 * @property array|null $request_body_redacted
 * @property array|null $response_body_redacted
 * @property array|null $headers_redacted
 * @property int|null $status_code
 * @property int|null $duration_ms
 * @property int $attempt_no
 * @property int $max_attempts
 * @property string|null $error_class
 * @property string|null $error_message
 * @property string|null $correlation_id
 * @property string|null $parent_event_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $completed_at
 */
class ServiceGatewayExchangeLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_gateway_exchange_logs';

    /**
     * Indicates if the model should be timestamped.
     * We only use created_at (auto) and completed_at (manual).
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'event_id',
        'direction',
        'call_id',
        'service_case_id',
        'company_id',
        'endpoint',
        'http_method',
        'request_body_redacted',
        'response_body_redacted',
        'headers_redacted',
        'status_code',
        'duration_ms',
        'attempt_no',
        'max_attempts',
        'error_class',
        'error_message',
        'correlation_id',
        'parent_event_id',
        'created_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_body_redacted' => 'array',
        'response_body_redacted' => 'array',
        'headers_redacted' => 'array',
        'status_code' => 'integer',
        'duration_ms' => 'integer',
        'attempt_no' => 'integer',
        'max_attempts' => 'integer',
        'created_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Bootstrap the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-generate UUID for event_id
        static::creating(function (self $log) {
            if (empty($log->event_id)) {
                $log->event_id = (string) Str::uuid();
            }
            if (empty($log->created_at)) {
                $log->created_at = now();
            }
        });
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the call this exchange belongs to.
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Get the service case this exchange belongs to.
     */
    public function serviceCase(): BelongsTo
    {
        return $this->belongsTo(ServiceCase::class);
    }

    /**
     * Get the company this exchange belongs to.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // =========================================================================
    // Query Scopes
    // =========================================================================

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to filter by service case.
     */
    public function scopeForCase($query, int $caseId)
    {
        return $query->where('service_case_id', $caseId);
    }

    /**
     * Scope to filter by call.
     */
    public function scopeForCall($query, int $callId)
    {
        return $query->where('call_id', $callId);
    }

    /**
     * Scope to filter by direction.
     */
    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    /**
     * Scope to filter by direction.
     */
    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    /**
     * Scope to filter failed exchanges.
     */
    public function scopeFailed($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('error_class')
              ->orWhere('status_code', '>=', 400);
        });
    }

    /**
     * Scope to filter successful exchanges.
     */
    public function scopeSuccessful($query)
    {
        return $query->whereNull('error_class')
            ->where(function ($q) {
                $q->whereNull('status_code')
                  ->orWhere('status_code', '<', 400);
            });
    }

    /**
     * Scope to get retry chain (all attempts for same parent).
     */
    public function scopeRetryChain($query, string $parentEventId)
    {
        return $query->where(function ($q) use ($parentEventId) {
            $q->where('event_id', $parentEventId)
              ->orWhere('parent_event_id', $parentEventId);
        })->orderBy('attempt_no');
    }

    // =========================================================================
    // Accessors & Helpers
    // =========================================================================

    /**
     * Check if exchange was successful.
     */
    public function isSuccessful(): bool
    {
        return is_null($this->error_class)
            && (is_null($this->status_code) || $this->status_code < 400);
    }

    /**
     * Check if exchange failed.
     */
    public function isFailed(): bool
    {
        return !$this->isSuccessful();
    }

    /**
     * Check if this is a retry attempt.
     */
    public function isRetry(): bool
    {
        return $this->attempt_no > 1;
    }

    /**
     * Check if more retries are allowed.
     */
    public function canRetry(): bool
    {
        return $this->attempt_no < $this->max_attempts;
    }

    /**
     * Get formatted duration string.
     */
    public function getFormattedDurationAttribute(): string
    {
        if (is_null($this->duration_ms)) {
            return '-';
        }

        if ($this->duration_ms < 1000) {
            return $this->duration_ms . 'ms';
        }

        return number_format($this->duration_ms / 1000, 2) . 's';
    }

    /**
     * Get status badge color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->isFailed()) {
            return 'danger';
        }

        if ($this->status_code >= 300) {
            return 'warning';
        }

        return 'success';
    }
}
