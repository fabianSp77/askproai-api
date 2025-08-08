<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingMargin extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_pricing_tier_id',
        'margin_amount',
        'margin_percentage',
        'calculated_date'
    ];

    protected $casts = [
        'margin_amount' => 'decimal:4',
        'margin_percentage' => 'decimal:2',
        'calculated_date' => 'date'
    ];

    /**
     * Get the pricing tier
     */
    public function pricingTier(): BelongsTo
    {
        return $this->belongsTo(CompanyPricingTier::class, 'company_pricing_tier_id');
    }
}