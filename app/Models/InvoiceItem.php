<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'stripe_invoice_item_id',
        'type',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'amount',
        'tax_rate',
        'metadata',
        'pricing_model_id',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'metadata' => 'array',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    // Item types
    const TYPE_USAGE = 'usage';
    const TYPE_SERVICE = 'service';
    const TYPE_SETUP_FEE = 'setup_fee';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_MONTHLY_FEE = 'monthly_fee';

    /**
     * Get the invoice that owns the item.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the pricing model used for this item.
     */
    public function pricingModel(): BelongsTo
    {
        return $this->belongsTo(CompanyPricing::class, 'pricing_model_id');
    }

    /**
     * Calculate tax amount for this item.
     */
    public function getTaxAmountAttribute(): float
    {
        return $this->amount * ($this->tax_rate / 100);
    }

    /**
     * Get total amount including tax.
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->amount + $this->tax_amount;
    }

    /**
     * Get formatted type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_USAGE => 'Nutzung',
            self::TYPE_SERVICE => 'Service',
            self::TYPE_SETUP_FEE => 'Einrichtungsgebühr',
            self::TYPE_ADJUSTMENT => 'Anpassung',
            self::TYPE_MONTHLY_FEE => 'Grundgebühr',
            default => ucfirst($this->type),
        };
    }

    /**
     * Scope for usage items.
     */
    public function scopeUsage($query)
    {
        return $query->where('type', self::TYPE_USAGE);
    }

    /**
     * Scope for service items.
     */
    public function scopeService($query)
    {
        return $query->where('type', self::TYPE_SERVICE);
    }

    /**
     * Format the description with period if applicable.
     */
    public function getFormattedDescriptionAttribute(): string
    {
        if ($this->period_start && $this->period_end) {
            return sprintf(
                '%s (%s - %s)',
                $this->description,
                $this->period_start->format('d.m.Y'),
                $this->period_end->format('d.m.Y')
            );
        }

        return $this->description;
    }
}