<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Scopes\TenantScope;

class CompanyPricingTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'child_company_id',
        'pricing_type',
        'cost_price',
        'sell_price',
        'setup_fee',
        'monthly_fee',
        'included_minutes',
        'overage_rate',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'cost_price' => 'decimal:4',
        'sell_price' => 'decimal:4',
        'setup_fee' => 'decimal:2',
        'monthly_fee' => 'decimal:2',
        'overage_rate' => 'decimal:4',
        'is_active' => 'boolean',
        'metadata' => 'array'
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Get the reseller company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the client company
     */
    public function childCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'child_company_id');
    }

    /**
     * Get pricing margins
     */
    public function margins(): HasMany
    {
        return $this->hasMany(PricingMargin::class);
    }

    /**
     * Calculate margin for this pricing tier
     */
    public function calculateMargin(): array
    {
        $marginAmount = $this->sell_price - $this->cost_price;
        $marginPercentage = $this->cost_price > 0 
            ? ($marginAmount / $this->cost_price) * 100 
            : 0;

        return [
            'amount' => round($marginAmount, 4),
            'percentage' => round($marginPercentage, 2)
        ];
    }

    /**
     * Calculate cost for given minutes
     */
    public function calculateCost(float $minutes): array
    {
        // Validate input
        if ($minutes < 0) {
            throw new \InvalidArgumentException('Minutes cannot be negative');
        }
        
        // Prevent overflow with large numbers
        $minutes = min($minutes, 999999999);
        
        $billableMinutes = max(0, $minutes - $this->included_minutes);
        
        if ($billableMinutes <= 0) {
            return [
                'base_cost' => 0,
                'sell_cost' => 0,
                'margin' => 0,
                'included_minutes_used' => min($minutes, $this->included_minutes),
                'billable_minutes' => 0
            ];
        }

        // Prevent overflow in calculations
        $baseCost = min($billableMinutes * $this->cost_price, 999999999.99);
        $sellCost = min($billableMinutes * $this->sell_price, 999999999.99);
        
        return [
            'base_cost' => round($baseCost, 2),
            'sell_cost' => round($sellCost, 2),
            'margin' => round($sellCost - $baseCost, 2),
            'included_minutes_used' => $this->included_minutes,
            'billable_minutes' => $billableMinutes
        ];
    }

    /**
     * Scope for active pricing
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for reseller's own pricing
     */
    public function scopeResellerOwn($query)
    {
        return $query->whereNull('child_company_id');
    }

    /**
     * Scope for client pricing
     */
    public function scopeForClient($query, $clientId)
    {
        return $query->where('child_company_id', $clientId);
    }

    /**
     * Get display name for pricing type
     */
    public function getPricingTypeDisplayAttribute(): string
    {
        return match($this->pricing_type) {
            'inbound' => 'Eingehende Anrufe',
            'outbound' => 'Ausgehende Anrufe',
            'sms' => 'SMS',
            'monthly' => 'Monatliche Gebühr',
            'setup' => 'Einrichtungsgebühr',
            default => ucfirst($this->pricing_type)
        };
    }
}