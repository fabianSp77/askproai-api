<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrepaidTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'prepaid_balance_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
        'user_id',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Transaction types
     */
    const TYPE_TOPUP = 'topup';
    const TYPE_DEDUCTION = 'deduction';
    const TYPE_REFUND = 'refund';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_BONUS = 'bonus';

    /**
     * Get the company that owns the transaction.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the prepaid balance associated with this transaction.
     */
    public function prepaidBalance(): BelongsTo
    {
        return $this->belongsTo(PrepaidBalance::class);
    }

    /**
     * Get the user who initiated the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related model (polymorphic).
     */
    public function reference()
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by transaction type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Check if transaction is a credit.
     */
    public function isCredit(): bool
    {
        return in_array($this->type, [self::TYPE_TOPUP, self::TYPE_REFUND, self::TYPE_BONUS]);
    }

    /**
     * Check if transaction is a debit.
     */
    public function isDebit(): bool
    {
        return in_array($this->type, [self::TYPE_DEDUCTION]);
    }

    /**
     * Get human-readable type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_TOPUP => 'Aufladung',
            self::TYPE_DEDUCTION => 'Verbrauch',
            self::TYPE_REFUND => 'Rückerstattung',
            self::TYPE_ADJUSTMENT => 'Anpassung',
            self::TYPE_BONUS => 'Bonus',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get formatted amount with sign.
     */
    public function getFormattedAmountAttribute(): string
    {
        $sign = $this->isCredit() ? '+' : '-';
        return $sign . number_format(abs($this->amount), 2, ',', '.') . ' €';
    }
}