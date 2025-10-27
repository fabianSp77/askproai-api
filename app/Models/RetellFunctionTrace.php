<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetellFunctionTrace extends Model
{
    protected $table = 'retell_function_traces';

    protected $fillable = [
        'call_session_id',
        'event_id',
        'correlation_id',
        'function_name',
        'execution_sequence',
        'started_at',
        'completed_at',
        'duration_ms',
        'input_params',
        'output_result',
        'status',
        'error_details',
        'db_query_count',
        'db_query_time_ms',
        'external_api_calls',
        'external_api_time_ms',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'input_params' => 'array',
        'output_result' => 'array',
        'error_details' => 'array',
        'metadata' => 'array',
        'execution_sequence' => 'integer',
        'duration_ms' => 'integer',
        'db_query_count' => 'integer',
        'db_query_time_ms' => 'integer',
        'external_api_calls' => 'integer',
        'external_api_time_ms' => 'integer',
    ];

    /**
     * Get the call session that owns this function trace.
     */
    public function callSession(): BelongsTo
    {
        return $this->belongsTo(RetellCallSession::class, 'call_session_id');
    }

    /**
     * Get the related event.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(RetellCallEvent::class, 'event_id');
    }

    /**
     * Scope to filter by function name.
     */
    public function scopeForFunction($query, string $functionName)
    {
        return $query->where('function_name', $functionName);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter successful traces.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope to filter failed traces.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'error');
    }

    /**
     * Scope to filter pending traces.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter slow functions (>1000ms).
     */
    public function scopeSlow($query, int $thresholdMs = 1000)
    {
        return $query->where('duration_ms', '>', $thresholdMs);
    }

    /**
     * Scope to order by execution sequence.
     */
    public function scopeInSequence($query)
    {
        return $query->orderBy('execution_sequence');
    }

    /**
     * Scope to get recent function calls.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('started_at', '>=', now()->subHours($hours));
    }

    /**
     * Check if function succeeded.
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if function failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'error';
    }

    /**
     * Check if function is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if function is slow (>1000ms).
     */
    public function isSlow(int $thresholdMs = 1000): bool
    {
        return $this->duration_ms && $this->duration_ms > $thresholdMs;
    }

    /**
     * Get duration in seconds.
     */
    public function getDurationSeconds(): ?float
    {
        return $this->duration_ms ? round($this->duration_ms / 1000, 2) : null;
    }

    /**
     * Get formatted execution time.
     */
    public function getFormattedDuration(): string
    {
        if (!$this->duration_ms) {
            return 'N/A';
        }

        if ($this->duration_ms < 1000) {
            return $this->duration_ms . 'ms';
        }

        return round($this->duration_ms / 1000, 2) . 's';
    }

    /**
     * Get performance summary.
     */
    public function getPerformanceSummary(): array
    {
        return [
            'total_duration_ms' => $this->duration_ms,
            'db_query_count' => $this->db_query_count,
            'db_query_time_ms' => $this->db_query_time_ms,
            'db_percentage' => $this->duration_ms && $this->db_query_time_ms
                ? round(($this->db_query_time_ms / $this->duration_ms) * 100, 1)
                : null,
            'external_api_calls' => $this->external_api_calls,
            'external_api_time_ms' => $this->external_api_time_ms,
            'api_percentage' => $this->duration_ms && $this->external_api_time_ms
                ? round(($this->external_api_time_ms / $this->duration_ms) * 100, 1)
                : null,
        ];
    }

    /**
     * Mark as completed with duration.
     */
    public function markCompleted(array $output, string $status = 'success', ?array $errorDetails = null): void
    {
        $this->update([
            'completed_at' => now(),
            'duration_ms' => now()->diffInMilliseconds($this->started_at),
            'output_result' => $output,
            'status' => $status,
            'error_details' => $errorDetails,
        ]);
    }
}
