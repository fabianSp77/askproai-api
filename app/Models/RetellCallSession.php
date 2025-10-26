<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * RetellCallSession Model
 *
 * Stores complete call session data from Retell AI
 *
 * Security:
 * - Uses BelongsToCompany trait for automatic multi-tenant isolation
 * - All queries automatically filtered by company_id (except super_admins)
 * - Policy-based authorization via RetellCallSessionPolicy
 *
 * @see \App\Policies\RetellCallSessionPolicy
 * @see \App\Traits\BelongsToCompany
 */
class RetellCallSession extends Model
{
    use HasUuids, BelongsToCompany;

    protected $table = 'retell_call_sessions';

    protected $fillable = [
        'call_id',
        'company_id',
        'customer_id',
        'branch_id',
        'phone_number',
        'branch_name',
        'agent_id',
        'agent_version',
        'started_at',
        'ended_at',
        'call_status',
        'disconnection_reason',
        'duration_ms',
        'conversation_flow_id',
        'current_flow_node',
        'flow_state',
        'total_events',
        'function_call_count',
        'transcript_segment_count',
        'error_count',
        'avg_response_time_ms',
        'max_response_time_ms',
        'min_response_time_ms',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'flow_state' => 'array',
        'metadata' => 'array',
        'total_events' => 'integer',
        'function_call_count' => 'integer',
        'transcript_segment_count' => 'integer',
        'error_count' => 'integer',
        'avg_response_time_ms' => 'integer',
        'max_response_time_ms' => 'integer',
        'min_response_time_ms' => 'integer',
        'agent_version' => 'integer',
        'duration_ms' => 'integer',
    ];

    /**
     * Accessors to append to model's array/JSON representation.
     * Required for Livewire serialization in Filament tables.
     *
     * CRITICAL FIX: Without this, accessors like company_branch
     * are not included in toArray(), which Livewire uses for wire:snapshot.
     * This causes Filament columns using these accessors to be silently omitted.
     *
     * NOTE: phone_number is now a real database column, not an accessor!
     */
    protected $appends = [
        'company_branch',
    ];

    /**
     * Get the company that owns the call session.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the customer associated with the call session.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the branch associated with the call session.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    /**
     * Get all events for this call session.
     */
    public function events(): HasMany
    {
        return $this->hasMany(RetellCallEvent::class, 'call_session_id');
    }

    /**
     * Get all function traces for this call session.
     */
    public function functionTraces(): HasMany
    {
        return $this->hasMany(RetellFunctionTrace::class, 'call_session_id');
    }

    /**
     * Get all transcript segments for this call session.
     */
    public function transcriptSegments(): HasMany
    {
        return $this->hasMany(RetellTranscriptSegment::class, 'call_session_id');
    }

    /**
     * Get all errors for this call session.
     */
    public function errors(): HasMany
    {
        return $this->hasMany(RetellErrorLog::class, 'call_session_id');
    }

    /**
     * Get the associated call record via call_id -> external_id.
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class, 'call_id', 'external_id');
    }

    /**
     * Scope to filter by call status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('call_status', $status);
    }

    /**
     * Scope to filter calls with errors.
     */
    public function scopeWithErrors($query)
    {
        return $query->where('error_count', '>', 0);
    }

    /**
     * Scope to filter recent calls.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('started_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Check if call has errors.
     */
    public function hasErrors(): bool
    {
        return $this->error_count > 0;
    }

    /**
     * Check if call is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->call_status === 'in_progress';
    }

    /**
     * Check if call is completed.
     */
    public function isCompleted(): bool
    {
        return $this->call_status === 'completed';
    }

    /**
     * Get duration in seconds.
     */
    public function getDurationSeconds(): ?int
    {
        return $this->duration_ms ? (int) ($this->duration_ms / 1000) : null;
    }

    /**
     * Calculate call offset in milliseconds from start.
     */
    public function getCallOffsetMs(\DateTimeInterface $timestamp): int
    {
        return (int) ($timestamp->getTimestamp() * 1000 + $timestamp->format('v')) -
               (int) ($this->started_at->getTimestamp() * 1000 + $this->started_at->format('v'));
    }

    /**
     * Get company branch information with phone number (for Filament display).
     *
     * This accessor provides the data for the company_branch column in Filament tables.
     * It combines company name, branch name, and phone number in a formatted string.
     *
     * FIX 2025-10-25: Now uses direct database columns instead of relations to ensure
     * the phone number persists after call ends.
     */
    public function getCompanyBranchAttribute(): string
    {
        $companyName = $this->company?->name ?? '-';

        // Prefer stored values, fallback to relation if not yet migrated
        $branchName = $this->getAttributeFromArray('branch_name') ?? $this->call?->branch?->name ?? '-';
        $phoneNumber = $this->getAttributeFromArray('phone_number') ?? $this->call?->branch?->phone_number ?? '-';

        return "{$companyName} / {$branchName} ({$phoneNumber})";
    }

    /**
     * NOTE: phone_number is now a real database column (2025-10-25)
     * No accessor needed - Filament can access it directly!
     *
     * The old accessor has been removed to prevent conflicts with the database column.
     * If phone_number is NULL in the database, it will show NULL in Filament (which is correct).
     */
}
