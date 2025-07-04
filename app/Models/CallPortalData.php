<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallPortalData extends Model
{
    use HasFactory;

    protected $table = 'call_portal_data';

    protected $fillable = [
        'call_id',
        'status',
        'assigned_to',
        'priority',
        'tags',
        'next_action_date',
        'internal_notes',
        'follow_up_count',
        'resolution_notes',
        'callback_scheduled_at',
        'callback_scheduled_by',
        'callback_notes',
        'status_history',
        'assigned_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'status_history' => 'array',
        'next_action_date' => 'datetime',
        'callback_scheduled_at' => 'datetime',
        'assigned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const STATUSES = [
        'new' => 'Neu',
        'in_progress' => 'In Bearbeitung',
        'callback_scheduled' => 'Rückruf geplant',
        'not_reached_1' => 'Nicht erreicht (1. Versuch)',
        'not_reached_2' => 'Nicht erreicht (2. Versuch)',
        'not_reached_3' => 'Nicht erreicht (3. Versuch)',
        'completed' => 'Abgeschlossen',
        'abandoned' => 'Abgebrochen',
        'requires_action' => 'Aktion erforderlich',
    ];

    const PRIORITIES = [
        'high' => 'Hoch',
        'medium' => 'Mittel',
        'low' => 'Niedrig',
    ];

    /**
     * Get the call that owns the portal data
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Get the user assigned to this call
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class, 'assigned_to');
    }

    /**
     * Get the user who scheduled the callback
     */
    public function callbackScheduledBy(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class, 'callback_scheduled_by');
    }

    /**
     * Get the status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Get the priority label
     */
    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    /**
     * Check if callback is overdue
     */
    public function getIsCallbackOverdueAttribute(): bool
    {
        if (!$this->callback_scheduled_at) {
            return false;
        }

        return $this->callback_scheduled_at->isPast();
    }

    /**
     * Scope for new calls
     */
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    /**
     * Scope for calls requiring action
     */
    public function scopeRequiresAction($query)
    {
        return $query->where('status', 'requires_action');
    }

    /**
     * Scope for calls with scheduled callbacks
     */
    public function scopeWithScheduledCallbacks($query)
    {
        return $query->where('status', 'callback_scheduled')
                     ->whereNotNull('callback_scheduled_at');
    }

    /**
     * Scope for overdue callbacks
     */
    public function scopeOverdueCallbacks($query)
    {
        return $query->where('status', 'callback_scheduled')
                     ->whereNotNull('callback_scheduled_at')
                     ->where('callback_scheduled_at', '<', now());
    }

    /**
     * Scope for upcoming callbacks
     */
    public function scopeUpcomingCallbacks($query, $hours = 24)
    {
        return $query->where('status', 'callback_scheduled')
                     ->whereNotNull('callback_scheduled_at')
                     ->whereBetween('callback_scheduled_at', [now(), now()->addHours($hours)]);
    }
}