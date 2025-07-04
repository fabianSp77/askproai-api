<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'pricing_plan_id',
        'name',
        'description',
        'type',
        'conditions',
        'modification_type',
        'modification_value',
        'valid_from',
        'valid_until',
        'is_active',
        'priority',
        'metadata',
    ];

    protected $casts = [
        'conditions' => 'array',
        'modification_value' => 'decimal:2',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'metadata' => 'array',
        'is_active' => 'boolean',
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
     * Get the pricing plan.
     */
    public function pricingPlan(): BelongsTo
    {
        return $this->belongsTo(PricingPlan::class);
    }

    /**
     * Check if rule is currently valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();
        
        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        return true;
    }

    /**
     * Check if rule applies to given context.
     */
    public function appliesTo(array $context): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        switch ($this->type) {
            case 'time_based':
                return $this->checkTimeBasedConditions($context);
            
            case 'location_based':
                return $this->checkLocationBasedConditions($context);
            
            case 'customer_segment':
                return $this->checkCustomerSegmentConditions($context);
            
            case 'promotional':
                return $this->checkPromotionalConditions($context);
            
            default:
                return false;
        }
    }

    /**
     * Apply price modification.
     */
    public function applyToPrice(float $originalPrice): float
    {
        switch ($this->modification_type) {
            case 'percentage':
                // Discount percentage
                return $originalPrice * (1 - ($this->modification_value / 100));
            
            case 'fixed_amount':
                // Fixed discount amount
                return max(0, $originalPrice - $this->modification_value);
            
            case 'multiplier':
                // Price multiplier (can be > 1 for surcharges)
                return $originalPrice * $this->modification_value;
            
            default:
                return $originalPrice;
        }
    }

    /**
     * Check time-based conditions.
     */
    protected function checkTimeBasedConditions(array $context): bool
    {
        $conditions = $this->conditions;
        $now = Carbon::now();

        // Check day of week
        if (isset($conditions['day_of_week'])) {
            $currentDay = strtolower($now->format('l'));
            if (!in_array($currentDay, $conditions['day_of_week'])) {
                return false;
            }
        }

        // Check time range
        if (isset($conditions['time_range'])) {
            $currentTime = $now->format('H:i');
            $startTime = $conditions['time_range'][0] ?? '00:00';
            $endTime = $conditions['time_range'][1] ?? '23:59';
            
            if ($currentTime < $startTime || $currentTime > $endTime) {
                return false;
            }
        }

        // Check specific dates
        if (isset($conditions['dates'])) {
            $currentDate = $now->format('Y-m-d');
            if (!in_array($currentDate, $conditions['dates'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check location-based conditions.
     */
    protected function checkLocationBasedConditions(array $context): bool
    {
        if (!isset($context['branch_id'])) {
            return false;
        }

        $conditions = $this->conditions;
        
        if (isset($conditions['branch_ids']) && !in_array($context['branch_id'], $conditions['branch_ids'])) {
            return false;
        }

        if (isset($conditions['cities']) && isset($context['city'])) {
            if (!in_array($context['city'], $conditions['cities'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check customer segment conditions.
     */
    protected function checkCustomerSegmentConditions(array $context): bool
    {
        if (!isset($context['customer'])) {
            return false;
        }

        $conditions = $this->conditions;
        $customer = $context['customer'];
        
        // Check customer tags
        if (isset($conditions['tags'])) {
            $customerTags = explode(',', $customer->tags ?? '');
            $hasRequiredTag = false;
            
            foreach ($conditions['tags'] as $tag) {
                if (in_array($tag, $customerTags)) {
                    $hasRequiredTag = true;
                    break;
                }
            }
            
            if (!$hasRequiredTag) {
                return false;
            }
        }

        // Check customer lifetime value
        if (isset($conditions['min_lifetime_value'])) {
            // Assuming we have a method to get lifetime value
            $lifetimeValue = $customer->appointments()->sum('total_price');
            if ($lifetimeValue < $conditions['min_lifetime_value']) {
                return false;
            }
        }

        // Check customer age (months since first appointment)
        if (isset($conditions['min_customer_months'])) {
            $firstAppointment = $customer->appointments()->orderBy('scheduled_at')->first();
            if (!$firstAppointment) {
                return false;
            }
            
            $monthsSince = Carbon::parse($firstAppointment->scheduled_at)->diffInMonths(now());
            if ($monthsSince < $conditions['min_customer_months']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check promotional conditions.
     */
    protected function checkPromotionalConditions(array $context): bool
    {
        $conditions = $this->conditions;
        
        // Check promo code
        if (isset($conditions['promo_code']) && isset($context['promo_code'])) {
            if ($conditions['promo_code'] !== $context['promo_code']) {
                return false;
            }
        }

        // Check usage limit
        if (isset($conditions['max_uses'])) {
            // This would need a separate tracking table in real implementation
            // For now, we'll assume it's tracked in metadata
            $currentUses = $this->metadata['usage_count'] ?? 0;
            if ($currentUses >= $conditions['max_uses']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Scope for active rules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for currently valid rules.
     */
    public function scopeCurrentlyValid($query)
    {
        $now = Carbon::now();
        
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $now);
            });
    }
}