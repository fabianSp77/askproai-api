<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'type',
        'billing_interval',
        'interval_count',
        'base_price',
        'currency',
        'included_minutes',
        'included_appointments',
        'included_features',
        'overage_price_per_minute',
        'overage_price_per_appointment',
        'volume_discounts',
        'is_active',
        'is_default',
        'trial_days',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'overage_price_per_minute' => 'decimal:4',
        'overage_price_per_appointment' => 'decimal:2',
        'included_features' => 'array',
        'volume_discounts' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
        
        // Ensure only one default plan per company
        static::saving(function ($plan) {
            if ($plan->is_default) {
                static::where('company_id', $plan->company_id)
                    ->where('id', '!=', $plan->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Get the company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the subscriptions.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the price rules.
     */
    public function priceRules(): HasMany
    {
        return $this->hasMany(PriceRule::class);
    }

    /**
     * Calculate price with volume discount.
     */
    public function calculatePriceWithDiscount(int $quantity, string $type = 'minutes'): float
    {
        if (empty($this->volume_discounts)) {
            return $this->base_price;
        }

        $discount = 0;
        foreach ($this->volume_discounts as $tier) {
            if ($quantity >= ($tier['threshold'] ?? 0)) {
                $discount = $tier['discount_percent'] ?? 0;
            }
        }

        $price = $this->base_price;
        if ($discount > 0) {
            $price = $price * (1 - ($discount / 100));
        }

        return round($price, 2);
    }

    /**
     * Check if a feature is included.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->included_features ?? []);
    }

    /**
     * Get overage cost for usage.
     */
    public function calculateOverageCost(int $usedMinutes, int $usedAppointments): array
    {
        $minuteOverage = max(0, $usedMinutes - $this->included_minutes);
        $appointmentOverage = max(0, $usedAppointments - $this->included_appointments);

        return [
            'minutes' => [
                'overage' => $minuteOverage,
                'cost' => $minuteOverage * ($this->overage_price_per_minute ?? 0),
            ],
            'appointments' => [
                'overage' => $appointmentOverage,
                'cost' => $appointmentOverage * ($this->overage_price_per_appointment ?? 0),
            ],
            'total' => ($minuteOverage * ($this->overage_price_per_minute ?? 0)) + 
                      ($appointmentOverage * ($this->overage_price_per_appointment ?? 0)),
        ];
    }

    /**
     * Get display price (formatted).
     */
    public function getDisplayPriceAttribute(): string
    {
        return number_format($this->base_price, 2) . ' ' . $this->currency;
    }

    /**
     * Get billing period display.
     */
    public function getBillingPeriodDisplayAttribute(): string
    {
        if ($this->interval_count === 1) {
            return $this->billing_interval;
        }
        
        return "every {$this->interval_count} " . str_plural(rtrim($this->billing_interval, 'ly'));
    }
}