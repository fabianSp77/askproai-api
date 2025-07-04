<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'stripe_subscription_item_id',
        'stripe_price_id',
        'stripe_product_id',
        'quantity',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the subscription that owns this item
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Sync with Stripe subscription item data
     */
    public function syncWithStripe(array $stripeData): self
    {
        $this->update([
            'stripe_price_id' => $stripeData['price']['id'] ?? $stripeData['price'],
            'stripe_product_id' => $stripeData['price']['product'] ?? null,
            'quantity' => $stripeData['quantity'] ?? 1,
            'metadata' => $stripeData['metadata'] ?? [],
        ]);

        return $this;
    }
}