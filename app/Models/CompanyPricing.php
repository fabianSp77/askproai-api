<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyPricing extends Model
{
    use HasFactory;

    protected $table = 'company_pricing';

    protected $fillable = [
        'company_id',
        'price_per_minute',
        'setup_fee',
        'monthly_base_fee',
        'included_minutes',
        'overage_price_per_minute',
        'is_active',
        'valid_from',
        'valid_until',
        'notes',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    protected $casts = [
        'price_per_minute' => 'decimal:4',
        'setup_fee' => 'decimal:2',
        'monthly_base_fee' => 'decimal:2',
        'overage_price_per_minute' => 'decimal:4',
        'included_minutes' => 'integer',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    /**
     * Get the company that owns the pricing.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch overrides for this pricing.
     */
    public function branchOverrides(): HasMany
    {
        return $this->hasMany(BranchPricingOverride::class);
    }

    /**
     * Scope to get active pricing.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', now());
            });
    }

    /**
     * Get the current active pricing for a company.
     */
    public static function getCurrentForCompany($companyId)
    {
        return static::where('company_id', $companyId)
            ->active()
            ->orderBy('valid_from', 'desc')
            ->first();
    }

    /**
     * Calculate the price for a given duration.
     */
    public function calculatePrice($durationSeconds, $currentMonthMinutes = 0)
    {
        try {
            $minutes = $durationSeconds / 60;
            
            \Log::debug('CompanyPricing: Calculating price', [
                'pricing_id' => $this->id,
                'duration_seconds' => $durationSeconds,
                'minutes' => $minutes,
                'current_month_minutes' => $currentMonthMinutes,
                'included_minutes' => $this->included_minutes,
                'price_per_minute' => $this->price_per_minute,
                'overage_price' => $this->overage_price_per_minute
            ]);
            
            // Check if within included minutes
            if ($currentMonthMinutes < $this->included_minutes) {
                $includedMinutesRemaining = $this->included_minutes - $currentMonthMinutes;
                
                if ($minutes <= $includedMinutesRemaining) {
                    // All minutes are included
                    \Log::debug('CompanyPricing: All minutes within included allowance', [
                        'included_remaining' => $includedMinutesRemaining,
                        'price' => 0
                    ]);
                    return 0;
                } else {
                    // Some minutes are included, some are overage
                    $overageMinutes = $minutes - $includedMinutesRemaining;
                    $pricePerMinute = $this->overage_price_per_minute ?? $this->price_per_minute;
                    $price = $overageMinutes * $pricePerMinute;
                    
                    \Log::debug('CompanyPricing: Partial overage', [
                        'included_used' => $includedMinutesRemaining,
                        'overage_minutes' => $overageMinutes,
                        'price_per_minute' => $pricePerMinute,
                        'total_price' => $price
                    ]);
                    
                    return $price;
                }
            }
            
            // All minutes are overage
            $pricePerMinute = $this->overage_price_per_minute ?? $this->price_per_minute;
            $price = $minutes * $pricePerMinute;
            
            \Log::debug('CompanyPricing: All minutes are overage', [
                'minutes' => $minutes,
                'price_per_minute' => $pricePerMinute,
                'total_price' => $price
            ]);
            
            return $price;
            
        } catch (\Exception $e) {
            \Log::error('CompanyPricing: Error calculating price', [
                'pricing_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback to simple calculation
            return ($durationSeconds / 60) * $this->price_per_minute;
        }
    }
}