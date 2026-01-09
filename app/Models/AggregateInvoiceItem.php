<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AggregateInvoiceItem extends Model
{
    use HasFactory;

    // Item type constants
    public const TYPE_CALL_MINUTES = 'call_minutes';
    public const TYPE_MONTHLY_SERVICE = 'monthly_service';
    public const TYPE_SETUP_FEE = 'setup_fee';
    public const TYPE_SERVICE_CHANGE = 'service_change';
    public const TYPE_CUSTOM = 'custom';

    protected $fillable = [
        'aggregate_invoice_id',
        'company_id',
        'stripe_line_item_id',
        'item_type',
        'description',
        'description_detail',
        'quantity',
        'unit',
        'unit_price_cents',
        'amount_cents',
        'reference_type',
        'reference_id',
        'period_start',
        'period_end',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price_cents' => 'integer',
        'amount_cents' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
        'metadata' => 'json',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * The parent aggregate invoice.
     */
    public function aggregateInvoice(): BelongsTo
    {
        return $this->belongsTo(AggregateInvoice::class);
    }

    /**
     * The company this charge belongs to.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Polymorphic reference to source record.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }

    // ========================================
    // ACCESSORS
    // ========================================

    /**
     * Get unit price as decimal (EUR).
     */
    public function getUnitPriceAttribute(): float
    {
        return $this->unit_price_cents / 100;
    }

    /**
     * Get amount as decimal (EUR).
     */
    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;
    }

    /**
     * Formatted amount for display.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2, ',', '.') . ' €';
    }

    /**
     * Get the item type label.
     */
    public function getTypeLabel(): string
    {
        return match ($this->item_type) {
            self::TYPE_CALL_MINUTES => 'Call-Minuten',
            self::TYPE_MONTHLY_SERVICE => 'Monatliche Servicegebühr',
            self::TYPE_SETUP_FEE => 'Einrichtungsgebühr',
            self::TYPE_SERVICE_CHANGE => 'Service-Änderung',
            self::TYPE_CUSTOM => 'Sonstiges',
            default => $this->item_type,
        };
    }

    /**
     * Get icon for item type.
     */
    public function getTypeIcon(): string
    {
        return match ($this->item_type) {
            self::TYPE_CALL_MINUTES => 'heroicon-o-phone',
            self::TYPE_MONTHLY_SERVICE => 'heroicon-o-calendar',
            self::TYPE_SETUP_FEE => 'heroicon-o-cog-6-tooth',
            self::TYPE_SERVICE_CHANGE => 'heroicon-o-wrench-screwdriver',
            self::TYPE_CUSTOM => 'heroicon-o-document-text',
            default => 'heroicon-o-currency-euro',
        };
    }

    // ========================================
    // FACTORY METHODS
    // ========================================

    /**
     * Create a call minutes item.
     */
    public static function createCallMinutesItem(
        int $aggregateInvoiceId,
        int $companyId,
        float $totalMinutes,
        int $callCount,
        int $ratePerMinuteCents,
        \DateTimeInterface $periodStart,
        \DateTimeInterface $periodEnd,
    ): self {
        $amountCents = (int) round($totalMinutes * $ratePerMinuteCents);

        return self::create([
            'aggregate_invoice_id' => $aggregateInvoiceId,
            'company_id' => $companyId,
            'item_type' => self::TYPE_CALL_MINUTES,
            'description' => 'Call-Minuten',
            'description_detail' => sprintf('%d Anrufe, %.2f Minuten', $callCount, $totalMinutes),
            'quantity' => $totalMinutes,
            'unit' => 'Minuten',
            'unit_price_cents' => $ratePerMinuteCents,
            'amount_cents' => $amountCents,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);
    }

    /**
     * Create a monthly service fee item.
     */
    public static function createMonthlyServiceItem(
        int $aggregateInvoiceId,
        int $companyId,
        string $serviceName,
        int $amountCents,
        ?int $servicePricingId = null,
    ): self {
        return self::create([
            'aggregate_invoice_id' => $aggregateInvoiceId,
            'company_id' => $companyId,
            'item_type' => self::TYPE_MONTHLY_SERVICE,
            'description' => 'Monatliche Servicegebühr',
            'description_detail' => $serviceName,
            'quantity' => 1,
            'unit' => 'Monat',
            'unit_price_cents' => $amountCents,
            'amount_cents' => $amountCents,
            'reference_type' => $servicePricingId ? CompanyServicePricing::class : null,
            'reference_id' => $servicePricingId,
        ]);
    }

    /**
     * Create a service change fee item.
     */
    public static function createServiceChangeItem(
        int $aggregateInvoiceId,
        int $companyId,
        string $description,
        int $amountCents,
        ?int $serviceChangeFeeId = null,
    ): self {
        return self::create([
            'aggregate_invoice_id' => $aggregateInvoiceId,
            'company_id' => $companyId,
            'item_type' => self::TYPE_SERVICE_CHANGE,
            'description' => 'Service-Änderung',
            'description_detail' => $description,
            'quantity' => 1,
            'unit' => null,
            'unit_price_cents' => $amountCents,
            'amount_cents' => $amountCents,
            'reference_type' => $serviceChangeFeeId ? ServiceChangeFee::class : null,
            'reference_id' => $serviceChangeFeeId,
        ]);
    }

    /**
     * Create a setup fee item.
     */
    public static function createSetupFeeItem(
        int $aggregateInvoiceId,
        int $companyId,
        string $description,
        int $amountCents,
    ): self {
        return self::create([
            'aggregate_invoice_id' => $aggregateInvoiceId,
            'company_id' => $companyId,
            'item_type' => self::TYPE_SETUP_FEE,
            'description' => 'Einrichtungsgebühr',
            'description_detail' => $description,
            'quantity' => 1,
            'unit' => null,
            'unit_price_cents' => $amountCents,
            'amount_cents' => $amountCents,
        ]);
    }

    /**
     * Create a custom item.
     */
    public static function createCustomItem(
        int $aggregateInvoiceId,
        int $companyId,
        string $description,
        int $amountCents,
        ?string $detail = null,
    ): self {
        return self::create([
            'aggregate_invoice_id' => $aggregateInvoiceId,
            'company_id' => $companyId,
            'item_type' => self::TYPE_CUSTOM,
            'description' => $description,
            'description_detail' => $detail,
            'quantity' => 1,
            'unit' => null,
            'unit_price_cents' => $amountCents,
            'amount_cents' => $amountCents,
        ]);
    }
}
