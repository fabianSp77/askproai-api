<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'stripe_price_id',
        'amount',
        'currency',
        'interval',
        'interval_count',
        'trial_period_days',
        'features',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'trial_period_days' => 'integer',
        'features' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the subscriptions for this pricing plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Scope a query to only include active pricing plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the display price.
     */
    public function getDisplayPriceAttribute(): string
    {
        $amount = number_format($this->amount / 100, 2, ',', '.');
        return "{$amount} {$this->currency}";
    }

    /**
     * Get the billing period display.
     */
    public function getBillingPeriodAttribute(): string
    {
        $interval = $this->interval;
        $count = $this->interval_count;
        
        if ($count === 1) {
            return match($interval) {
                'day' => 'täglich',
                'week' => 'wöchentlich',
                'month' => 'monatlich',
                'year' => 'jährlich',
                default => $interval,
            };
        }
        
        return "alle {$count} " . match($interval) {
            'day' => 'Tage',
            'week' => 'Wochen',
            'month' => 'Monate',
            'year' => 'Jahre',
            default => $interval,
        };
    }
}