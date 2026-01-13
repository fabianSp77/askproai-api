<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * AssignmentGroup Model
 *
 * ServiceNow-style team-based ticket assignment.
 * Groups contain multiple staff members and can be assigned to service cases.
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $description
 * @property string|null $email Group email for notifications
 * @property bool $is_active
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @property-read Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection|Staff[] $members
 * @property-read \Illuminate\Database\Eloquent\Collection|ServiceCase[] $cases
 */
class AssignmentGroup extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'email',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Company this group belongs to (multi-tenant).
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Staff members in this group.
     * Note: Staff uses char(36) UUID for primary key.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'assignment_group_staff', 'assignment_group_id', 'staff_id')
            ->withPivot('is_lead')
            ->withTimestamps()
            ->orderByPivot('is_lead', 'desc');
    }

    /**
     * Team leads (members with is_lead = true).
     */
    public function leads(): BelongsToMany
    {
        return $this->members()->wherePivot('is_lead', true);
    }

    /**
     * Service cases assigned to this group.
     */
    public function cases(): HasMany
    {
        return $this->hasMany(ServiceCase::class, 'assigned_group_id');
    }

    /**
     * Open cases assigned to this group.
     */
    public function openCases(): HasMany
    {
        return $this->cases()->open();
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Only active groups.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Order by sort_order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    // ========================================
    // ACCESSORS & HELPERS
    // ========================================

    /**
     * Get member count.
     */
    public function getMemberCountAttribute(): int
    {
        return $this->members()->count();
    }

    /**
     * Get open case count.
     */
    public function getOpenCaseCountAttribute(): int
    {
        return $this->openCases()->count();
    }

    /**
     * Check if a staff member is in this group.
     */
    public function hasMember(Staff|int $staff): bool
    {
        $staffId = $staff instanceof Staff ? $staff->id : $staff;
        return $this->members()->where('staff_id', $staffId)->exists();
    }

    /**
     * Check if a staff member is a lead in this group.
     */
    public function isLead(Staff|int $staff): bool
    {
        $staffId = $staff instanceof Staff ? $staff->id : $staff;
        return $this->leads()->where('staff_id', $staffId)->exists();
    }

    /**
     * Get notification email (group email or first lead's email).
     */
    public function getNotificationEmailAttribute(): ?string
    {
        if ($this->email) {
            return $this->email;
        }

        // Fallback to first lead's email
        $lead = $this->leads()->first();
        return $lead?->user?->email;
    }

    /**
     * Get workload (open cases / member count).
     */
    public function getWorkloadAttribute(): float
    {
        $memberCount = $this->member_count;
        if ($memberCount === 0) {
            return 0;
        }
        return round($this->open_case_count / $memberCount, 2);
    }
}
