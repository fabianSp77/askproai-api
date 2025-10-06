<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory, BelongsToCompany;

    // Transaction types
    const TYPE_TOPUP = 'topup';
    const TYPE_USAGE = 'usage';
    const TYPE_REFUND = 'refund';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_BONUS = 'bonus';
    const TYPE_FEE = 'fee';

    protected $fillable = [
        'tenant_id',
        'type',
        'amount_cents',
        'balance_before_cents',
        'balance_after_cents',
        'description',
        'topup_id',
        'call_id',
        'appointment_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount_cents' => 'integer',
        'balance_before_cents' => 'integer',
        'balance_after_cents' => 'integer',
    ];

    /**
     * Get the tenant that owns the transaction.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the related call if this is a usage transaction.
     */
    public function call()
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Get the related appointment if this is a usage transaction.
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the related topup if this is a topup transaction.
     */
    public function topup()
    {
        return $this->belongsTo(BalanceTopup::class, 'topup_id');
    }

    /**
     * Scope to get only credit transactions.
     */
    public function scopeCredits($query)
    {
        return $query->where('amount_cents', '>', 0);
    }

    /**
     * Scope to get only debit transactions.
     */
    public function scopeDebits($query)
    {
        return $query->where('amount_cents', '<', 0);
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount_cents / 100, 2) . ' €';
    }
}