<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterService extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'base_duration',
        'base_price',
        'calcom_event_type_id',
        'retell_service_identifier',
        'active'
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    protected $casts = [
        'base_duration' => 'integer',
        'base_price' => 'decimal:2',
        'active' => 'boolean'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branchOverrides(): HasMany
    {
        return $this->hasMany(BranchServiceOverride::class);
    }

    public function staffAssignments(): HasMany
    {
        return $this->hasMany(StaffServiceAssignment::class);
    }

    public function getEffectiveDurationForBranch($branchId): int
    {
        $override = $this->branchOverrides()
            ->where('branch_id', $branchId)
            ->where('active', true)
            ->first();

        return $override && $override->custom_duration 
            ? $override->custom_duration 
            : $this->base_duration;
    }

    public function getEffectivePriceForBranch($branchId): ?float
    {
        $override = $this->branchOverrides()
            ->where('branch_id', $branchId)
            ->where('active', true)
            ->first();

        return $override && $override->custom_price 
            ? $override->custom_price 
            : $this->base_price;
    }

    public function getEffectiveCalcomEventTypeForBranch($branchId): ?string
    {
        $override = $this->branchOverrides()
            ->where('branch_id', $branchId)
            ->where('active', true)
            ->first();

        return $override && $override->custom_calcom_event_type_id 
            ? $override->custom_calcom_event_type_id 
            : $this->calcom_event_type_id;
    }
}
