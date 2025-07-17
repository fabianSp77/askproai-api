<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchPricingOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_pricing_id',
        'branch_id',
        'price_per_minute',
        'included_minutes',
        'overage_price_per_minute',
        'is_active',
    ];

    protected $casts = [
        'price_per_minute' => 'decimal:4',
        'included_minutes' => 'integer',
        'overage_price_per_minute' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Get the company pricing this override belongs to.
     */
    public function pricing(): BelongsTo
    {
        return $this->belongsTo(CompanyPricing::class, 'company_pricing_id');
    }

    /**
     * Get the branch this override applies to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the company through branch.
     */
    public function company()
    {
        return $this->branch->company();
    }

    /**
     * Scope active overrides.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the effective price per minute.
     */
    public function getEffectivePricePerMinuteAttribute(): float
    {
        return $this->price_per_minute ?? $this->pricing->price_per_minute;
    }

    /**
     * Get the effective included minutes.
     */
    public function getEffectiveIncludedMinutesAttribute(): int
    {
        return $this->included_minutes ?? $this->pricing->included_minutes;
    }

    /**
     * Get the effective overage price.
     */
    public function getEffectiveOveragePriceAttribute(): float
    {
        return $this->overage_price_per_minute 
            ?? $this->price_per_minute 
            ?? $this->pricing->overage_price_per_minute 
            ?? $this->pricing->price_per_minute;
    }
}