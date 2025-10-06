<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * CallbackEscalation Model
 *
 * Tracks escalations for callback requests when SLA is breached,
 * manual escalation is required, or multiple attempts fail.
 * Multi-tenant isolation via BelongsToCompany trait.
 */
class CallbackEscalation extends Model
{
    use HasFactory, BelongsToCompany;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'callback_request_id',
        'escalation_reason',
        'escalated_from',
        'escalated_to',
        'escalated_at',
        'resolved_at',
        'resolution_notes',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'escalated_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the callback request that was escalated.
     */
    public function callbackRequest(): BelongsTo
    {
        return $this->belongsTo(CallbackRequest::class);
    }

    /**
     * Get the staff member who originally handled the callback.
     */
    public function escalatedFrom(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'escalated_from');
    }

    /**
     * Get the staff member the callback was escalated to.
     */
    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'escalated_to');
    }

    /**
     * Scope to filter unresolved escalations.
     */
    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Scope to filter escalations by reason.
     */
    public function scopeByReason(Builder $query, string $reason): Builder
    {
        return $query->where('escalation_reason', $reason);
    }

    /**
     * Resolve the escalation with notes.
     */
    public function resolve(string $notes): bool
    {
        $this->resolution_notes = $notes;
        $this->resolved_at = now();

        return $this->save();
    }

    /**
     * Check if the escalation is resolved.
     */
    public function getIsResolvedAttribute(): bool
    {
        return $this->resolved_at !== null;
    }

    /**
     * Get the escalation reason enum values.
     */
    public static function getEscalationReasons(): array
    {
        return [
            'sla_breach',
            'manual_escalation',
            'multiple_attempts_failed',
        ];
    }
}
