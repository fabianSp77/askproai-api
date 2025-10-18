<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToCompany;

/**
 * CalcomHostMapping Model
 *
 * Maps Cal.com host IDs to internal staff records for automated staff assignment
 *
 * SECURITY: Multi-tenant isolated via BelongsToCompany trait
 * All queries automatically scoped to current company
 *
 * @property int $id
 * @property int $company_id
 * @property int $staff_id
 * @property int $calcom_host_id
 * @property string $calcom_name
 * @property string $calcom_email
 * @property string|null $calcom_username
 * @property string|null $calcom_timezone
 * @property string $mapping_source
 * @property int $confidence_score
 * @property \Carbon\Carbon|null $last_synced_at
 * @property bool $is_active
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class CalcomHostMapping extends Model
{
    use BelongsToCompany;
    protected $fillable = [
        'company_id',       // 🔧 FIX 2025-10-13: Added to prevent "Field 'company_id' doesn't have a default value" error
        'staff_id',
        'calcom_host_id',
        'calcom_name',
        'calcom_email',
        'calcom_username',
        'calcom_timezone',
        'mapping_source',
        'confidence_score',
        'last_synced_at',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'staff_id' => 'string',  // UUID
        'metadata' => 'array',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'confidence_score' => 'integer',
        'calcom_host_id' => 'integer'
    ];

    /**
     * Get the staff record this mapping belongs to
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get all audit records for this mapping
     */
    public function audits(): HasMany
    {
        return $this->hasMany(CalcomHostMappingAudit::class, 'mapping_id');
    }

    /**
     * Scope to only active mappings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to mappings for a specific company
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->whereHas('staff', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        });
    }
}
