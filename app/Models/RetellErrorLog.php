<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetellErrorLog extends Model
{
    protected $table = 'retell_error_log';

    protected $fillable = [
        'call_session_id',
        'event_id',
        'function_trace_id',
        'error_code',
        'error_type',
        'severity',
        'occurred_at',
        'call_offset_ms',
        'error_message',
        'stack_trace',
        'error_context',
        'call_terminated',
        'booking_failed',
        'affected_function',
        'resolved',
        'resolution_notes',
        'resolved_at',
        'metadata',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'resolved_at' => 'datetime',
        'error_context' => 'array',
        'metadata' => 'array',
        'call_offset_ms' => 'integer',
        'call_terminated' => 'boolean',
        'booking_failed' => 'boolean',
        'resolved' => 'boolean',
    ];

    /**
     * Get the call session that owns this error.
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
     * Get the related function trace.
     */
    public function functionTrace(): BelongsTo
    {
        return $this->belongsTo(RetellFunctionTrace::class, 'function_trace_id');
    }

    /**
     * Scope to filter by error code.
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('error_code', $code);
    }

    /**
     * Scope to filter by error type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('error_type', $type);
    }

    /**
     * Scope to filter by severity.
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to filter critical errors.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Scope to filter high severity errors.
     */
    public function scopeHigh($query)
    {
        return $query->where('severity', 'high');
    }

    /**
     * Scope to filter unresolved errors.
     */
    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    /**
     * Scope to filter resolved errors.
     */
    public function scopeResolved($query)
    {
        return $query->where('resolved', true);
    }

    /**
     * Scope to filter errors that terminated calls.
     */
    public function scopeCallTerminating($query)
    {
        return $query->where('call_terminated', true);
    }

    /**
     * Scope to filter errors that caused booking failures.
     */
    public function scopeBookingFailures($query)
    {
        return $query->where('booking_failed', true);
    }

    /**
     * Scope to filter recent errors.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('occurred_at', '>=', now()->subHours($hours));
    }

    /**
     * Check if error is critical.
     */
    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    /**
     * Check if error is high severity.
     */
    public function isHigh(): bool
    {
        return $this->severity === 'high';
    }

    /**
     * Check if error is resolved.
     */
    public function isResolved(): bool
    {
        return $this->resolved;
    }

    /**
     * Check if error terminated the call.
     */
    public function terminatedCall(): bool
    {
        return $this->call_terminated;
    }

    /**
     * Check if error caused booking failure.
     */
    public function causedBookingFailure(): bool
    {
        return $this->booking_failed;
    }

    /**
     * Mark error as resolved.
     */
    public function markResolved(string $notes = null): void
    {
        $this->update([
            'resolved' => true,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Get formatted timestamp with offset.
     */
    public function getFormattedTimestamp(): string
    {
        return $this->occurred_at->format('H:i:s.u') .
               ' (+' . $this->call_offset_ms . 'ms)';
    }

    /**
     * Get severity badge color for UI.
     */
    public function getSeverityColor(): string
    {
        return match ($this->severity) {
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => 'primary',
            'low' => 'success',
            default => 'secondary',
        };
    }
}
