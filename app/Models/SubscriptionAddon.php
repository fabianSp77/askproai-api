<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'service_addon_id',
        'price_override',
        'quantity',
        'start_date',
        'end_date',
        'status',
        'metadata',
    ];

    protected $casts = [
        'price_override' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the subscription.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the service addon.
     */
    public function serviceAddon(): BelongsTo
    {
        return $this->belongsTo(ServiceAddon::class);
    }

    /**
     * Get the effective price.
     */
    public function getEffectivePriceAttribute(): float
    {
        if ($this->price_override !== null) {
            return $this->price_override;
        }

        return $this->serviceAddon->calculatePrice($this->quantity);
    }

    /**
     * Check if addon is active.
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $today = now()->startOfDay();
        
        if ($this->end_date && $today->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Cancel the addon.
     */
    public function cancel(bool $immediately = false): void
    {
        if ($immediately) {
            $this->update([
                'status' => 'cancelled',
                'end_date' => now()->startOfDay(),
            ]);
        } else {
            // Cancel at end of current billing period
            $subscription = $this->subscription;
            $endDate = $subscription->next_billing_date ?? $subscription->ends_at;
            
            $this->update([
                'status' => 'cancelled',
                'end_date' => $endDate,
            ]);
        }
    }

    /**
     * Scope for active addons.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->startOfDay());
            });
    }
}