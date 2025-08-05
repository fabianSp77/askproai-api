<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrepaidBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'balance',
        'bonus_balance',
        'reserved_balance',
        'low_balance_threshold',
        'last_warning_sent_at',
        'auto_topup_enabled',
        'auto_topup_threshold',
        'auto_topup_amount',
        'stripe_payment_method_id',
        'last_auto_topup_at',
        'auto_topup_daily_count',
        'auto_topup_monthly_limit',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'bonus_balance' => 'decimal:2',
        'reserved_balance' => 'decimal:2',
        'low_balance_threshold' => 'decimal:2',
        'auto_topup_threshold' => 'decimal:2',
        'auto_topup_amount' => 'decimal:2',
        'auto_topup_monthly_limit' => 'decimal:2',
        'auto_topup_enabled' => 'boolean',
        'last_warning_sent_at' => 'datetime',
        'last_auto_topup_at' => 'datetime',
    ];

    /**
     * Get the company that owns the prepaid balance.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all transactions for this prepaid balance.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PrepaidTransaction::class, 'company_id', 'company_id');
    }

    /**
     * Get the effective balance (balance + bonus - reserved)
     */
    public function getEffectiveBalanceAttribute(): float
    {
        return $this->balance + $this->bonus_balance - $this->reserved_balance;
    }

    /**
     * Check if balance is low
     */
    public function isLowBalance(): bool
    {
        return $this->effective_balance < $this->low_balance_threshold;
    }

    /**
     * Check if auto-topup should be triggered
     */
    public function shouldAutoTopup(): bool
    {
        if (!$this->auto_topup_enabled) {
            return false;
        }

        if (!$this->auto_topup_threshold || !$this->auto_topup_amount) {
            return false;
        }

        if (!$this->stripe_payment_method_id) {
            return false;
        }

        return $this->effective_balance <= $this->auto_topup_threshold;
    }

    /**
     * Add balance
     */
    public function addBalance(float $amount, string $description = '', bool $isBonus = false): void
    {
        if ($isBonus) {
            $this->bonus_balance += $amount;
        } else {
            $this->balance += $amount;
        }
        
        $this->save();

        // Log transaction
        if (class_exists(\App\Models\PrepaidTransaction::class)) {
            \App\Models\PrepaidTransaction::create([
                'company_id' => $this->company_id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $this->balance - $amount,
                'balance_after' => $this->balance,
                'description' => $description,
                'is_bonus' => $isBonus,
            ]);
        }
    }

    /**
     * Deduct balance
     */
    public function deductBalance(float $amount, string $description = ''): bool
    {
        if ($this->effective_balance < $amount) {
            return false;
        }

        // First deduct from regular balance
        if ($this->balance >= $amount) {
            $this->balance -= $amount;
        } else {
            // Use bonus balance for the remainder
            $remainder = $amount - $this->balance;
            $this->balance = 0;
            $this->bonus_balance -= $remainder;
        }

        $this->save();

        // Log transaction
        if (class_exists(\App\Models\PrepaidTransaction::class)) {
            \App\Models\PrepaidTransaction::create([
                'company_id' => $this->company_id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $this->effective_balance + $amount,
                'balance_after' => $this->effective_balance,
                'description' => $description,
            ]);
        }

        return true;
    }

    /**
     * Reserve balance
     */
    public function reserveBalance(float $amount): bool
    {
        if ($this->effective_balance < $amount) {
            return false;
        }

        $this->reserved_balance += $amount;
        $this->save();

        return true;
    }

    /**
     * Release reserved balance
     */
    public function releaseBalance(float $amount): void
    {
        $this->reserved_balance = max(0, $this->reserved_balance - $amount);
        $this->save();
    }
}