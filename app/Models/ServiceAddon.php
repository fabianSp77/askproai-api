<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ServiceAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'type',
        'price',
        'currency',
        'billing_interval',
        'category',
        'is_active',
        'is_metered',
        'meter_unit',
        'meter_unit_price',
        'features',
        'requirements',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'meter_unit_price' => 'decimal:4',
        'features' => 'array',
        'requirements' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_metered' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
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
    public function subscriptions(): BelongsToMany
    {
        return $this->belongsToMany(Subscription::class, 'subscription_addons')
            ->withPivot(['price_override', 'quantity', 'start_date', 'end_date', 'status', 'metadata'])
            ->withTimestamps();
    }

    /**
     * Check if addon is compatible with a pricing plan.
     */
    public function isCompatibleWith(PricingPlan $plan): bool
    {
        if (empty($this->requirements)) {
            return true;
        }

        $requirements = $this->requirements;
        
        // Check plan type requirement
        if (isset($requirements['plan_types']) && !in_array($plan->type, $requirements['plan_types'])) {
            return false;
        }

        // Check required features
        if (isset($requirements['features'])) {
            foreach ($requirements['features'] as $feature) {
                if (!$plan->hasFeature($feature)) {
                    return false;
                }
            }
        }

        // Check minimum price requirement
        if (isset($requirements['min_plan_price']) && $plan->base_price < $requirements['min_plan_price']) {
            return false;
        }

        return true;
    }

    /**
     * Calculate price for quantity (for metered add-ons).
     */
    public function calculatePrice(int $quantity = 1): float
    {
        if ($this->is_metered && $this->meter_unit_price) {
            return round($quantity * $this->meter_unit_price, 2);
        }

        return round($this->price * $quantity, 2);
    }

    /**
     * Get display price.
     */
    public function getDisplayPriceAttribute(): string
    {
        if ($this->is_metered && $this->meter_unit_price) {
            return number_format($this->meter_unit_price, 2) . ' ' . $this->currency . ' per ' . $this->meter_unit;
        }

        $price = number_format($this->price, 2) . ' ' . $this->currency;
        
        if ($this->type === 'recurring' && $this->billing_interval) {
            $price .= ' / ' . $this->billing_interval;
        }

        return $price;
    }

    /**
     * Scope for active add-ons.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope by type.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }
}