<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Scopes\TenantScope;
use Carbon\Carbon;

class ApiCallLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'service',
        'endpoint',
        'method',
        'request_headers',
        'request_body',
        'response_status',
        'response_headers',
        'response_body',
        'duration_ms',
        'correlation_id',
        'company_id',
        'user_id',
        'ip_address',
        'user_agent',
        'error_message',
        'requested_at',
        'responded_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'response_headers' => 'array',
        'response_body' => 'array',
        'duration_ms' => 'float',
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);

        // Set requested_at if not provided
        static::creating(function ($log) {
            if (empty($log->requested_at)) {
                $log->requested_at = now();
            }
        });
    }

    /**
     * Get the company that owns the API call log.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope a query to only include logs for a specific service.
     */
    public function scopeForService($query, string $service)
    {
        return $query->where('service', $service);
    }

    /**
     * Scope a query to only include logs for specific endpoints.
     */
    public function scopeForEndpoint($query, string $endpoint)
    {
        return $query->where('endpoint', $endpoint);
    }

    /**
     * Scope a query to only include successful calls.
     */
    public function scopeSuccessful($query)
    {
        return $query->whereBetween('response_status', [200, 299]);
    }

    /**
     * Scope a query to only include failed calls.
     */
    public function scopeFailed($query)
    {
        return $query->where(function ($q) {
            $q->where('response_status', '>=', 400)
              ->orWhereNull('response_status')
              ->orWhereNotNull('error_message');
        });
    }

    /**
     * Scope a query to only include calls with specific correlation ID.
     */
    public function scopeCorrelated($query, string $correlationId)
    {
        return $query->where('correlation_id', $correlationId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('requested_at', [$startDate, $endDate]);
    }

    /**
     * Check if the API call was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->response_status >= 200 && $this->response_status < 300;
    }

    /**
     * Check if the API call failed.
     */
    public function isFailed(): bool
    {
        return !$this->isSuccessful();
    }

    /**
     * Get the response time in milliseconds.
     */
    public function getResponseTime(): ?float
    {
        return $this->duration_ms;
    }

    /**
     * Get the response time in seconds.
     */
    public function getResponseTimeInSeconds(): ?float
    {
        return $this->duration_ms ? $this->duration_ms / 1000 : null;
    }

    /**
     * Mask sensitive data in headers and body.
     */
    public function maskSensitiveData(): void
    {
        $sensitiveKeys = ['authorization', 'api_key', 'secret', 'password', 'token', 'key'];
        
        // Mask request headers
        if ($this->request_headers) {
            $headers = $this->request_headers;
            foreach ($headers as $key => $value) {
                if (in_array(strtolower($key), $sensitiveKeys)) {
                    $headers[$key] = '***MASKED***';
                }
            }
            $this->request_headers = $headers;
        }
        
        // Mask request body
        if ($this->request_body) {
            $this->request_body = $this->maskArrayData($this->request_body, $sensitiveKeys);
        }
        
        // Mask response body
        if ($this->response_body) {
            $this->response_body = $this->maskArrayData($this->response_body, $sensitiveKeys);
        }
    }

    /**
     * Recursively mask sensitive data in arrays.
     */
    private function maskArrayData(array $data, array $sensitiveKeys): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $data[$key] = '***MASKED***';
            } elseif (is_array($value)) {
                $data[$key] = $this->maskArrayData($value, $sensitiveKeys);
            }
        }
        
        return $data;
    }

    /**
     * Create a log entry for an API call.
     */
    public static function logCall(array $data): self
    {
        // Calculate duration if both requested_at and responded_at are provided
        if (isset($data['requested_at']) && isset($data['responded_at'])) {
            $requestedAt = Carbon::parse($data['requested_at']);
            $respondedAt = Carbon::parse($data['responded_at']);
            $data['duration_ms'] = $requestedAt->diffInMilliseconds($respondedAt);
        }
        
        return static::create($data);
    }

    /**
     * Get statistics for a service within a date range.
     */
    public static function getServiceStats(string $service, $startDate = null, $endDate = null): array
    {
        $query = static::forService($service);
        
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }
        
        $total = $query->count();
        $successful = (clone $query)->successful()->count();
        $failed = (clone $query)->failed()->count();
        $avgDuration = (clone $query)->whereNotNull('duration_ms')->avg('duration_ms');
        
        return [
            'service' => $service,
            'total_calls' => $total,
            'successful_calls' => $successful,
            'failed_calls' => $failed,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
            'average_duration_ms' => round($avgDuration ?? 0, 2),
            'average_duration_sec' => round(($avgDuration ?? 0) / 1000, 2),
        ];
    }

    /**
     * Get recent errors for a service.
     */
    public static function getRecentErrors(string $service, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::forService($service)
                    ->failed()
                    ->orderBy('requested_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Clean up old logs.
     */
    public static function cleanupOld(int $daysToKeep = 30): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        return static::where('requested_at', '<', $cutoffDate)->delete();
    }
}