<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItemFlexible extends Model
{
    use HasFactory;

    protected $table = 'invoice_items_flexible';

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
        'tax_rate_id',
        'period_start',
        'period_end',
        'metadata',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'metadata' => 'array',
    ];

    // Item types
    const TYPE_SERVICE = 'service';
    const TYPE_USAGE = 'usage';
    const TYPE_SETUP_FEE = 'setup_fee';
    const TYPE_MONTHLY_FEE = 'monthly_fee';
    const TYPE_CUSTOM = 'custom';

    /**
     * Get the invoice that owns the item.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the tax rate.
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    /**
     * Calculate tax amount.
     */
    public function getTaxAmountAttribute(): float
    {
        return round($this->amount * ($this->tax_rate / 100), 2);
    }

    /**
     * Calculate gross amount.
     */
    public function getGrossAmountAttribute(): float
    {
        return round($this->amount + $this->tax_amount, 2);
    }

    /**
     * Get type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_SERVICE => 'Dienstleistung',
            self::TYPE_USAGE => 'Nutzung',
            self::TYPE_SETUP_FEE => 'Einrichtungsgebühr',
            self::TYPE_MONTHLY_FEE => 'Monatliche Gebühr',
            self::TYPE_CUSTOM => 'Sonstiges',
            default => ucfirst($this->type),
        };
    }

    /**
     * Scope for ordered items.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}