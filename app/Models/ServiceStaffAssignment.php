<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Service Staff Assignment Model
 *
 * Defines which staff members are qualified/assigned to perform specific services.
 * Used by the service_staff assignment model (Model 2).
 *
 * Supports:
 * - Priority ordering (lower = higher priority)
 * - Temporal validity (effective_from/until dates)
 * - Active/inactive toggle
 */
class ServiceStaffAssignment extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'service_id',
        'staff_id',
        'priority_order',
        'is_active',
        'effective_from',
        'effective_until',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company this assignment belongs to
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the service this assignment is for
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the staff member assigned
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get qualified staff for a service, ordered by priority
     *
     * @param int $serviceId
     * @param int|null $companyId Optional company filter (for tenant isolation)
     * @return Collection<Staff>
     */
    public static function getQualifiedStaffForService(int $serviceId, ?int $companyId = null): Collection
    {
        $query = static::query()
            ->where('service_id', $serviceId)
            ->where('is_active', true)
            ->whereDate(function ($q) {
                $q->whereNull('effective_from')
                  ->orWhere('effective_from', '<=', now());
            })
            ->whereDate(function ($q) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', now());
            })
            ->orderBy('priority_order', 'asc')
            ->with('staff');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->get()->pluck('staff');
    }

    /**
     * Check if temporal validity is currently active
     *
     * @return bool
     */
    public function isTemporallyValid(): bool
    {
        $now = now();

        if ($this->effective_from && $now->lt($this->effective_from)) {
            return false;
        }

        if ($this->effective_until && $now->gt($this->effective_until)) {
            return false;
        }

        return true;
    }

    /**
     * Scope to only active assignments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only temporally valid assignments
     */
    public function scopeTemporallyValid($query)
    {
        $now = now();

        return $query->where(function ($q) use ($now) {
            $q->whereNull('effective_from')
              ->orWhere('effective_from', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('effective_until')
              ->orWhere('effective_until', '>=', $now);
        });
    }
}
