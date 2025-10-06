<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'total',
        'tax_rate',
        'tax_amount',
        'discount_percentage',
        'discount_amount',
        'net_amount',
        'product_id',
        'service_id',
        'metadata',
        'position',
        'unit_type',
        'is_taxable',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'metadata' => 'array',
        'position' => 'integer',
        'is_taxable' => 'boolean',
    ];

    /**
     * Get the invoice that owns the item.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the product associated with the item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the service associated with the item.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Calculate item totals
     */
    public function calculateTotals(): void
    {
        // Calculate base total
        $this->total = $this->quantity * $this->unit_price;

        // Apply discount
        if ($this->discount_percentage > 0) {
            $this->discount_amount = $this->total * ($this->discount_percentage / 100);
        }
        $afterDiscount = $this->total - ($this->discount_amount ?? 0);

        // Apply tax
        if ($this->is_taxable && $this->tax_rate > 0) {
            $this->tax_amount = $afterDiscount * ($this->tax_rate / 100);
        }

        // Calculate net amount
        $this->net_amount = $afterDiscount + ($this->tax_amount ?? 0);
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (InvoiceItem $item) {
            if (!$item->position) {
                $item->position = $item->invoice->items()->max('position') + 1;
            }
            $item->calculateTotals();
        });

        static::updating(function (InvoiceItem $item) {
            $item->calculateTotals();
        });
    }

    /**
     * Get formatted unit price
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return number_format($this->unit_price, 2, ',', '.') . ' €';
    }

    /**
     * Get formatted total
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total, 2, ',', '.') . ' €';
    }

    /**
     * Get formatted net amount
     */
    public function getFormattedNetAmountAttribute(): string
    {
        return number_format($this->net_amount, 2, ',', '.') . ' €';
    }
}