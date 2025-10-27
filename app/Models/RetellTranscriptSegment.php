<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetellTranscriptSegment extends Model
{
    protected $table = 'retell_transcript_segments';

    protected $fillable = [
        'call_session_id',
        'event_id',
        'occurred_at',
        'call_offset_ms',
        'segment_sequence',
        'role',
        'text',
        'word_count',
        'duration_ms',
        'related_function_trace_id',
        'sentiment',
        'metadata',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'metadata' => 'array',
        'call_offset_ms' => 'integer',
        'segment_sequence' => 'integer',
        'word_count' => 'integer',
        'duration_ms' => 'integer',
    ];

    /**
     * Get the call session that owns this segment.
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
     * Scope to filter by role.
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to filter agent messages.
     */
    public function scopeAgentMessages($query)
    {
        return $query->where('role', 'agent');
    }

    /**
     * Scope to filter user messages.
     */
    public function scopeUserMessages($query)
    {
        return $query->where('role', 'user');
    }

    /**
     * Scope to order by timeline.
     */
    public function scopeTimeline($query)
    {
        return $query->orderBy('segment_sequence');
    }

    /**
     * Check if this is an agent message.
     */
    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    /**
     * Check if this is a user message.
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
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
