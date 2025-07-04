<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_id',
        'user_id',
        'type',
        'content',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const TYPES = [
        'general' => 'Allgemeine Notiz',
        'customer_feedback' => 'Kundenfeedback',
        'internal' => 'Interne Notiz',
        'action_required' => 'Aktion erforderlich',
        'status_change' => 'Statusänderung',
        'assignment' => 'Zuweisung',
        'callback_scheduled' => 'Rückruf geplant',
    ];

    /**
     * Get the call that owns the note
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Get the user who created the note
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class, 'user_id');
    }

    /**
     * Get the type label
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Scope to only general notes (visible to all)
     */
    public function scopeGeneral($query)
    {
        return $query->whereIn('type', ['general', 'customer_feedback', 'status_change', 'assignment', 'callback_scheduled']);
    }

    /**
     * Scope to only internal notes
     */
    public function scopeInternal($query)
    {
        return $query->whereIn('type', ['internal', 'action_required']);
    }
}