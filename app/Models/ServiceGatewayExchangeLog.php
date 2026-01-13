<?php

namespace App\Models;

use App\Services\ServiceGateway\ResponseBodyAnalyzer;
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
 * @property string $direction 'outbound', 'inbound', or 'internal'
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
 * @property bool $is_test
 * @property int|null $output_configuration_id
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
        'is_test',
        'output_configuration_id',
        'notification_sent_at',
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
        'is_test' => 'boolean',
        'output_configuration_id' => 'integer',
        'notification_sent_at' => 'datetime',
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

    /**
     * Get the output configuration this exchange belongs to.
     */
    public function outputConfiguration(): BelongsTo
    {
        return $this->belongsTo(ServiceOutputConfiguration::class);
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
     * Scope to filter by internal direction (enrichment, audio processing).
     */
    public function scopeInternal($query)
    {
        return $query->where('direction', 'internal');
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

    /**
     * Scope to filter test webhook deliveries.
     */
    public function scopeTest($query)
    {
        return $query->where('is_test', true);
    }

    /**
     * Scope to filter real (non-test) webhook deliveries.
     */
    public function scopeReal($query)
    {
        return $query->where('is_test', false);
    }

    /**
     * Scope to filter by output configuration.
     */
    public function scopeForConfiguration($query, int $configurationId)
    {
        return $query->where('output_configuration_id', $configurationId);
    }

    // =========================================================================
    // Accessors & Helpers
    // =========================================================================

    /**
     * Check if response contains a semantic error.
     *
     * Semantic errors occur when HTTP status is 2xx/3xx but the response body
     * contains error information (e.g., {"error": "Invalid HMAC", "status": 401}).
     *
     * @return bool True if semantic error is present
     */
    public function hasSemanticError(): bool
    {
        // If error_class is already set and starts with SemanticError: → detected at write time
        if (!is_null($this->error_class)) {
            return str_starts_with($this->error_class, 'SemanticError:');
        }

        // Fallback: Re-analyze response body (for legacy logs before this feature)
        if (!$this->response_body_redacted || !is_array($this->response_body_redacted)) {
            return false;
        }

        return app(ResponseBodyAnalyzer::class)->hasError($this->response_body_redacted);
    }

    /**
     * Get the semantic error message if present.
     *
     * @return string|null The error message or null if no semantic error
     */
    public function getSemanticErrorMessage(): ?string
    {
        // If already stored
        if ($this->error_message && str_starts_with($this->error_class ?? '', 'SemanticError:')) {
            return $this->error_message;
        }

        // Fallback: Re-analyze response body (for legacy logs)
        if ($this->response_body_redacted && is_array($this->response_body_redacted)) {
            [, $message] = app(ResponseBodyAnalyzer::class)->analyze($this->response_body_redacted);
            return $message;
        }

        return null;
    }

    /**
     * Get the overall status for UI display.
     *
     * @return string One of: 'success', 'http_error', 'semantic_error', 'exception'
     */
    public function getOverallStatus(): string
    {
        // PHP Exception (connection error, timeout, etc.)
        if ($this->error_class && !str_starts_with($this->error_class, 'SemanticError:')) {
            return 'exception';
        }

        // Semantic error (HTTP 200 but error in body)
        if ($this->hasSemanticError()) {
            return 'semantic_error';
        }

        // HTTP error (4xx, 5xx)
        if ($this->status_code && $this->status_code >= 400) {
            return 'http_error';
        }

        return 'success';
    }

    /**
     * Check if exchange was successful (considers semantic errors).
     *
     * @return bool True only if HTTP successful AND no semantic error
     */
    public function isSuccessful(): bool
    {
        return $this->getOverallStatus() === 'success';
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

    // =========================================================================
    // Export Methods (Partner Communication)
    // =========================================================================

    /**
     * Generate cURL command for this exchange log.
     * Allows partners to reproduce the exact webhook call.
     *
     * Note: Redacted headers (containing ***REDACTED***) are excluded.
     */
    public function toCurlCommand(): string
    {
        $headers = $this->headers_redacted ?? [];
        $body = $this->request_body_redacted;

        $curl = "curl -X {$this->http_method} '{$this->endpoint}'";

        foreach ($headers as $key => $value) {
            // Skip sensitive headers that are redacted
            if (is_string($value) && str_contains($value, '***REDACTED***')) {
                continue;
            }
            // Handle array values (shouldn't happen, but safety first)
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $curl .= " \\\n  -H '{$key}: {$value}'";
        }

        if ($body && $this->http_method !== 'GET') {
            $jsonBody = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            // Escape single quotes in JSON for shell safety
            $jsonBody = str_replace("'", "'\\''", $jsonBody);
            $curl .= " \\\n  -d '{$jsonBody}'";
        }

        return $curl;
    }

    /**
     * Export as structured JSON for partner communication.
     * Includes all relevant data with metadata for audit trail.
     */
    public function toExportJson(): array
    {
        return [
            'id' => $this->event_id,
            'timestamp' => $this->created_at?->toIso8601String(),
            'direction' => $this->direction,
            'request' => [
                'method' => $this->http_method,
                'url' => $this->endpoint,
                'headers' => $this->headers_redacted,
                'body' => $this->request_body_redacted,
            ],
            'response' => [
                'status_code' => $this->status_code,
                'body' => $this->response_body_redacted,
                'latency_ms' => $this->duration_ms,
            ],
            'metadata' => [
                'attempt' => $this->attempt_no,
                'max_attempts' => $this->max_attempts,
                'is_test' => $this->is_test,
                'is_successful' => $this->isSuccessful(),
                'error_class' => $this->error_class,
                'error_message' => $this->error_message,
                'correlation_id' => $this->correlation_id,
                'exported_at' => now()->toIso8601String(),
                'exported_by' => 'AskProAI Gateway',
            ],
        ];
    }

    /**
     * Get a shortened event ID for display/filenames.
     */
    public function getShortEventIdAttribute(): string
    {
        return substr($this->event_id, 0, 8);
    }
}
