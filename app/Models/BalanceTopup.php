<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BalanceTopup extends Model
{
    use HasFactory;
    
    protected $table = 'balance_topups';
    
    protected $fillable = [
        'tenant_id',
        'amount',
        'currency',
        'status',
        'stripe_payment_intent_id',
        'stripe_checkout_session_id',
        'stripe_response',
        'metadata',
        'initiated_by',
        'paid_at',
        'payment_method',
        'bonus_amount',
        'bonus_reason'
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'stripe_response' => 'array',
        'metadata' => 'array',
        'paid_at' => 'datetime'
    ];
    
    /**
     * Get the tenant for this topup
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    
    /**
     * Get the user who initiated the topup
     */
    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }
    
    /**
     * Mark topup as succeeded and credit tenant
     */
    public function markAsSucceeded(): void
    {
        $this->update([
            'status' => 'succeeded',
            'paid_at' => now()
        ]);
        
        // Credit tenant balance
        $totalAmount = ($this->amount + $this->bonus_amount) * 100; // Convert to cents
        $this->tenant->addCredit($totalAmount, "Aufladung #{$this->id}");
    }
    
    /**
     * Mark topup as failed
     */
    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'metadata' => array_merge($this->metadata ?? [], [
                'failure_reason' => $reason,
                'failed_at' => now()->toISOString()
            ])
        ]);
    }
    
    /**
     * Calculate total amount including bonus
     */
    public function getTotalAmount(): float
    {
        return $this->amount + $this->bonus_amount;
    }
    
    /**
     * Get formatted total for display
     */
    public function getFormattedTotal(): string
    {
        return number_format($this->getTotalAmount(), 2) . ' ' . $this->currency;
    }
    
    /**
     * Scope for pending topups
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    
    /**
     * Scope for succeeded topups
     */
    public function scopeSucceeded($query)
    {
        return $query->where('status', 'succeeded');
    }
}