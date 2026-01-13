<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Company Fee Schedule Model
 *
 * Stores company-specific billing configuration including:
 * - Per-second vs per-minute billing mode
 * - Setup fee tracking
 * - Per-minute rate overrides
 * - Discount percentage overrides
 *
 * @property int $id
 * @property int $company_id
 * @property string $billing_mode
 * @property float $setup_fee
 * @property \Carbon\Carbon|null $setup_fee_billed_at
 * @property int|null $setup_fee_transaction_id
 * @property float|null $override_per_minute_rate
 * @property float|null $override_discount_percentage
 * @property array|null $metadata
 * @property string|null $notes
 */
class CompanyFeeSchedule extends Model
{
    use HasFactory;

    // Billing modes
    public const BILLING_MODE_PER_SECOND = 'per_second';
    public const BILLING_MODE_PER_MINUTE = 'per_minute';

    protected $fillable = [
        'company_id',
        'billing_mode',
        'setup_fee',
        'setup_fee_billed_at',
        'setup_fee_transaction_id',
        'override_per_minute_rate',
        'override_discount_percentage',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'setup_fee' => 'decimal:2',
        'setup_fee_billed_at' => 'datetime',
        'override_per_minute_rate' => 'decimal:3',
        'override_discount_percentage' => 'decimal:2',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'billing_mode' => self::BILLING_MODE_PER_SECOND,
        'setup_fee' => 0,
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function setupFeeTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'setup_fee_transaction_id');
    }

    public function serviceChangeFees(): HasMany
    {
        return $this->hasMany(ServiceChangeFee::class, 'company_id', 'company_id');
    }

    // ─────────────────────────────────────────────────────────────
    // Helper Methods
    // ─────────────────────────────────────────────────────────────

    /**
     * Check if setup fee has been billed
     */
    public function isSetupFeeBilled(): bool
    {
        return $this->setup_fee_billed_at !== null;
    }

    /**
     * Check if this company uses per-second billing
     */
    public function usesPerSecondBilling(): bool
    {
        return $this->billing_mode === self::BILLING_MODE_PER_SECOND;
    }

    /**
     * Get effective per-minute rate (override or plan default)
     */
    public function getEffectivePerMinuteRate(?PricingPlan $plan = null): float
    {
        if ($this->override_per_minute_rate !== null) {
            return (float) $this->override_per_minute_rate;
        }

        return (float) ($plan?->price_per_minute ?? 0.10);
    }

    /**
     * Get effective discount percentage (override or zero)
     */
    public function getEffectiveDiscountPercentage(): float
    {
        return (float) ($this->override_discount_percentage ?? 0);
    }

    /**
     * Mark setup fee as billed
     */
    public function markSetupFeeBilled(?Transaction $transaction = null): self
    {
        $this->update([
            'setup_fee_billed_at' => now(),
            'setup_fee_transaction_id' => $transaction?->id,
        ]);

        return $this;
    }

    /**
     * Get setup fee in cents
     */
    public function getSetupFeeCentsAttribute(): int
    {
        return (int) ($this->setup_fee * 100);
    }

    /**
     * Get available billing modes for forms
     */
    public static function getBillingModeOptions(): array
    {
        return [
            self::BILLING_MODE_PER_SECOND => 'Pro Sekunde (empfohlen)',
            self::BILLING_MODE_PER_MINUTE => 'Pro Minute (Legacy)',
        ];
    }
}
