<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CalcomHostMappingAudit Model
 *
 * Audit trail for all changes to Cal.com host mappings
 *
 * @property int $id
 * @property int $mapping_id
 * @property string $action
 * @property array|null $old_values
 * @property array|null $new_values
 * @property int|null $changed_by
 * @property \Carbon\Carbon $changed_at
 * @property string|null $reason
 */
class CalcomHostMappingAudit extends Model
{
    // Override default timestamp behavior
    const UPDATED_AT = null;
    const CREATED_AT = 'changed_at';

    protected $fillable = [
        'mapping_id',
        'action',
        'old_values',
        'new_values',
        'changed_by',
        'changed_at',
        'reason'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_at' => 'datetime'
    ];

    /**
     * Get the mapping this audit belongs to
     */
    public function mapping(): BelongsTo
    {
        return $this->belongsTo(CalcomHostMapping::class, 'mapping_id');
    }

    /**
     * Get the user who made this change
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Scope to only auto-matched actions
     */
    public function scopeAutoMatched($query)
    {
        return $query->where('action', 'auto_matched');
    }

    /**
     * Scope to manual overrides
     */
    public function scopeManualOverrides($query)
    {
        return $query->where('action', 'manual_override');
    }
}
