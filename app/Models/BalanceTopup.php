<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BalanceTopup extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'amount',
        'bonus_percentage',
        'bonus_amount',
        'total_credited',
        'refundable_amount',
        'currency',
        'status',
        'stripe_payment_intent_id',
        'stripe_checkout_session_id',
        'stripe_response',
        'metadata',
        'initiated_by',
        'processed_by',
        'approved_by',
        'notes',
        'refunded_amount',
        'used_amount',
        'payment_method',
        'description'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'bonus_percentage' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'total_credited' => 'decimal:2',
        'refundable_amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'used_amount' => 'decimal:2',
        'metadata' => 'array',
        'stripe_response' => 'array',
        'processed_at' => 'datetime',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    /**
     * Get the tenant that owns the topup.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'company_id');
    }

    /**
     * Get the transactions for this topup.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'topup_id');
    }

    /**
     * Get the user who processed this topup.
     */
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the user who approved this topup.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2, ',', '.') . ' €';
    }
}