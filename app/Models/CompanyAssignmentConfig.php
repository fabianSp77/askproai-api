<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Company Assignment Configuration Model
 *
 * Defines which staff assignment business model a company uses:
 * - any_staff: First available staff (Model 1)
 * - service_staff: Only qualified staff for service (Model 2)
 *
 * Supports fallback models when primary assignment fails.
 */
class CompanyAssignmentConfig extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'assignment_model',
        'fallback_model',
        'config_metadata',
        'is_active',
    ];

    protected $casts = [
        'config_metadata' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company this configuration belongs to
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the active configuration for a company
     *
     * @param int $companyId
     * @return self|null
     */
    public static function getActiveForCompany(int $companyId): ?self
    {
        return static::where('company_id', $companyId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Check if company uses service-staff restriction model
     *
     * @return bool
     */
    public function usesServiceStaffModel(): bool
    {
        return $this->assignment_model === 'service_staff';
    }

    /**
     * Check if company uses any-staff model
     *
     * @return bool
     */
    public function usesAnyStaffModel(): bool
    {
        return $this->assignment_model === 'any_staff';
    }

    /**
     * Get timeout from metadata (if configured)
     *
     * @param int $default
     * @return int
     */
    public function getAssignmentTimeout(int $default = 30): int
    {
        return $this->config_metadata['assignment_timeout'] ?? $default;
    }
}
