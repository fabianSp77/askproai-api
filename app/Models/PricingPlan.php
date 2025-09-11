<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PricingPlan extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_per_minute_cents',
        'price_per_call_cents',
        'price_per_appointment_cents',
        'setup_fee_cents',
        'included_minutes',
        'monthly_fee',
        'overage_rate_cents',
        'volume_discount_percent',
        'volume_threshold_minutes',
        'billing_type',
        'billing_increment_seconds',
        'features',
        'is_active',
        'is_default'
    ];
    
    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'monthly_fee' => 'decimal:2'
    ];
    
    /**
     * Get tenants using this pricing plan
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }
    
    /**
     * Calculate cost for given usage
     */
    public function calculateCost(int $minutes, int $calls = 0, int $appointments = 0): int
    {
        $cost = 0;
        
        // Calculate minute costs
        if ($this->billing_type === 'prepaid') {
            $cost += $minutes * $this->price_per_minute_cents;
        } elseif ($this->billing_type === 'package') {
            if ($minutes > $this->included_minutes) {
                $overage = $minutes - $this->included_minutes;
                $cost += $overage * ($this->overage_rate_cents ?? $this->price_per_minute_cents);
            }
        }
        
        // Add call and appointment costs
        $cost += $calls * $this->price_per_call_cents;
        $cost += $appointments * $this->price_per_appointment_cents;
        
        // Apply volume discount if applicable
        if ($minutes >= $this->volume_threshold_minutes && $this->volume_discount_percent > 0) {
            $discount = $cost * ($this->volume_discount_percent / 100);
            $cost -= $discount;
        }
        
        return (int) round($cost);
    }
    
    /**
     * Get formatted prices for display
     */
    public function getFormattedPricePerMinute(): string
    {
        return number_format($this->price_per_minute_cents / 100, 2) . ' €';
    }
    
    public function getFormattedMonthlyFee(): string
    {
        return number_format($this->monthly_fee, 2) . ' €';
    }
    
    /**
     * Scope for active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Get the default plan
     */
    public static function getDefault(): ?self
    {
        return self::where('is_default', true)->first();
    }
}