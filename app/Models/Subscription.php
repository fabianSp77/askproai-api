<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\BelongsToCompany;

class Subscription extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'pricing_plan_id',
        'custom_price',
        'custom_features',
        'stripe_subscription_id',
        'stripe_customer_id',
        'name',
        'stripe_status',
        'stripe_price_id',
        'quantity',
        'trial_ends_at',
        'ends_at',
        'next_billing_date',
        'billing_interval',
        'billing_interval_count',
        'current_period_start',
        'current_period_end',
        'cancel_at_period_end',
        'metadata',
    ];

    protected $casts = [
        'custom_price' => 'decimal:2',
        'custom_features' => 'array',
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
        'next_billing_date' => 'date',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancel_at_period_end' => 'boolean',
        'metadata' => 'array',
        'quantity' => 'integer',
        'billing_interval_count' => 'integer',
    ];

    /**
     * Subscription statuses from Stripe
     */
    const STATUS_INCOMPLETE = 'incomplete';
    const STATUS_INCOMPLETE_EXPIRED = 'incomplete_expired';
    const STATUS_TRIALING = 'trialing';
    const STATUS_ACTIVE = 'active';
    const STATUS_PAST_DUE = 'past_due';
    const STATUS_CANCELED = 'canceled';
    const STATUS_UNPAID = 'unpaid';
    const STATUS_PAUSED = 'paused';

    /**
     * Get the company that owns the subscription
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the subscription items
     */
    public function items(): HasMany
    {
        return $this->hasMany(SubscriptionItem::class);
    }

    /**
     * Get the invoices for this subscription
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'stripe_subscription_id', 'stripe_subscription_id');
    }

    /**
     * Get the pricing plan
     */
    public function pricingPlan(): BelongsTo
    {
        return $this->belongsTo(PricingPlan::class);
    }

    /**
     * Get the service addons
     */
    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(ServiceAddon::class, 'subscription_addons')
            ->withPivot(['price_override', 'quantity', 'start_date', 'end_date', 'status', 'metadata'])
            ->withTimestamps();
    }

    /**
     * Get active addons
     */
    public function activeAddons(): BelongsToMany
    {
        return $this->addons()
            ->wherePivot('status', 'active')
            ->where(function ($query) {
                $query->whereNull('subscription_addons.end_date')
                    ->orWhere('subscription_addons.end_date', '>=', now()->startOfDay());
            });
    }

    /**
     * Determine if the subscription is active
     */
    public function active(): bool
    {
        return in_array($this->stripe_status, [
            self::STATUS_ACTIVE,
            self::STATUS_TRIALING,
        ]) && !$this->hasEnded();
    }

    /**
     * Determine if the subscription is on trial
     */
    public function onTrial(): bool
    {
        return $this->stripe_status === self::STATUS_TRIALING ||
               ($this->trial_ends_at && $this->trial_ends_at->isFuture());
    }

    /**
     * Determine if the subscription has ended
     */
    public function hasEnded(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    /**
     * Determine if the subscription is past due
     */
    public function pastDue(): bool
    {
        return $this->stripe_status === self::STATUS_PAST_DUE;
    }

    /**
     * Determine if the subscription is incomplete
     */
    public function incomplete(): bool
    {
        return in_array($this->stripe_status, [
            self::STATUS_INCOMPLETE,
            self::STATUS_INCOMPLETE_EXPIRED,
        ]);
    }

    /**
     * Determine if the subscription is canceled
     */
    public function canceled(): bool
    {
        return $this->stripe_status === self::STATUS_CANCELED;
    }

    /**
     * Determine if the subscription is set to cancel at period end
     */
    public function onGracePeriod(): bool
    {
        return $this->cancel_at_period_end && $this->active();
    }

    /**
     * Get human-readable status
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->stripe_status) {
            self::STATUS_INCOMPLETE => 'Incomplete',
            self::STATUS_INCOMPLETE_EXPIRED => 'Expired',
            self::STATUS_TRIALING => 'Trial',
            self::STATUS_ACTIVE => $this->cancel_at_period_end ? 'Canceling' : 'Active',
            self::STATUS_PAST_DUE => 'Past Due',
            self::STATUS_CANCELED => 'Canceled',
            self::STATUS_UNPAID => 'Unpaid',
            self::STATUS_PAUSED => 'Paused',
            default => 'Unknown'
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->stripe_status) {
            self::STATUS_ACTIVE => $this->cancel_at_period_end ? 'warning' : 'success',
            self::STATUS_TRIALING => 'info',
            self::STATUS_PAST_DUE, self::STATUS_UNPAID => 'danger',
            self::STATUS_CANCELED, self::STATUS_INCOMPLETE_EXPIRED => 'secondary',
            self::STATUS_PAUSED => 'warning',
            default => 'secondary'
        };
    }

    /**
     * Sync with Stripe subscription data
     */
    public function syncWithStripe(array $stripeData): self
    {
        $this->update([
            'stripe_status' => $stripeData['status'],
            'quantity' => $stripeData['quantity'] ?? 1,
            'trial_ends_at' => isset($stripeData['trial_end']) 
                ? now()->createFromTimestamp($stripeData['trial_end'])
                : null,
            'current_period_start' => now()->createFromTimestamp($stripeData['current_period_start']),
            'current_period_end' => now()->createFromTimestamp($stripeData['current_period_end']),
            'cancel_at_period_end' => $stripeData['cancel_at_period_end'] ?? false,
            'ends_at' => isset($stripeData['ended_at']) 
                ? now()->createFromTimestamp($stripeData['ended_at'])
                : null,
            'metadata' => $stripeData['metadata'] ?? [],
        ]);

        return $this;
    }

    /**
     * Scope for active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->whereIn('stripe_status', [self::STATUS_ACTIVE, self::STATUS_TRIALING])
                     ->where(function ($q) {
                         $q->whereNull('ends_at')
                           ->orWhere('ends_at', '>', now());
                     });
    }

    /**
     * Scope for subscriptions needing attention
     */
    public function scopeNeedsAttention($query)
    {
        return $query->whereIn('stripe_status', [
            self::STATUS_PAST_DUE,
            self::STATUS_UNPAID,
            self::STATUS_INCOMPLETE,
        ]);
    }

    /**
     * Calculate days until renewal
     */
    public function daysUntilRenewal(): ?int
    {
        if (!$this->current_period_end) {
            return null;
        }

        return now()->diffInDays($this->current_period_end, false);
    }

    /**
     * Calculate remaining trial days
     */
    public function trialDaysRemaining(): ?int
    {
        if (!$this->onTrial()) {
            return null;
        }

        return now()->diffInDays($this->trial_ends_at, false);
    }
}