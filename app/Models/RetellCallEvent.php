<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetellCallEvent extends Model
{
    protected $table = 'retell_call_events';

    protected $fillable = [
        'call_session_id',
        'correlation_id',
        'event_type',
        'occurred_at',
        'call_offset_ms',
        'function_name',
        'function_arguments',
        'function_response',
        'response_time_ms',
        'function_status',
        'transcript_text',
        'transcript_role',
        'from_node',
        'to_node',
        'transition_trigger',
        'error_code',
        'error_message',
        'error_context',
        'metadata',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'function_arguments' => 'array',
        'function_response' => 'array',
        'error_context' => 'array',
        'metadata' => 'array',
        'call_offset_ms' => 'integer',
        'response_time_ms' => 'integer',
    ];

    /**
     * Get the call session that owns this event.
     */
    public function callSession(): BelongsTo
    {
        return $this->belongsTo(RetellCallSession::class, 'call_session_id');
    }

    /**
     * Scope to filter by event type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope to filter function call events.
     */
    public function scopeFunctionCalls($query)
    {
        return $query->where('event_type', 'function_call');
    }

    /**
     * Scope to filter transcript events.
     */
    public function scopeTranscripts($query)
    {
        return $query->where('event_type', 'transcript');
    }

    /**
     * Scope to filter flow transition events.
     */
    public function scopeFlowTransitions($query)
    {
        return $query->where('event_type', 'flow_transition');
    }

    /**
     * Scope to filter error events.
     */
    public function scopeErrors($query)
    {
        return $query->where('event_type', 'error');
    }

    /**
     * Scope to order by timeline.
     */
    public function scopeTimeline($query)
    {
        return $query->orderBy('occurred_at');
    }

    /**
     * Check if this is a function call event.
     */
    public function isFunctionCall(): bool
    {
        return $this->event_type === 'function_call';
    }

    /**
     * Check if this is a transcript event.
     */
    public function isTranscript(): bool
    {
        return $this->event_type === 'transcript';
    }

    /**
     * Check if this is an error event.
     */
    public function isError(): bool
    {
        return $this->event_type === 'error';
    }

    /**
     * Check if this is a flow transition event.
     */
    public function isFlowTransition(): bool
    {
        return $this->event_type === 'flow_transition';
    }

    /**
     * Get formatted timestamp with offset.
     */
    public function getFormattedTimestamp(): string
    {
        return $this->occurred_at->format('H:i:s.u') .
               ' (+' . $this->call_offset_ms . 'ms)';
    }
}
