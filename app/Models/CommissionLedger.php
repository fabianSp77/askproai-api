<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CommissionLedger extends Model
{
    use HasFactory;
    
    protected $table = 'commission_ledger';
    
    protected $fillable = [
        'reseller_tenant_id',
        'customer_tenant_id',
        'transaction_id',
        'gross_amount_cents',
        'platform_cost_cents',
        'commission_cents',
        'commission_rate',
        'status',
        'approved_at',
        'paid_at',
        'payout_reference',
        'description',
        'metadata'
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'commission_rate' => 'decimal:2'
    ];
    
    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_PAID = 'paid';
    const STATUS_DISPUTED = 'disputed';
    
    /**
     * Relationships
     */
    public function resellerTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'reseller_tenant_id');
    }
    
    public function customerTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'customer_tenant_id');
    }
    
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
    
    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
    
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
    
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }
    
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }
    
    /**
     * Mark as approved
     */
    public function approve(): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now()
        ]);
    }
    
    /**
     * Mark as paid
     */
    public function markAsPaid(string $reference = null): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'payout_reference' => $reference
        ]);
    }
    
    /**
     * Get formatted amounts
     */
    public function getFormattedGrossAmount(): string
    {
        return number_format($this->gross_amount_cents / 100, 2) . ' €';
    }
    
    public function getFormattedCommission(): string
    {
        return number_format($this->commission_cents / 100, 2) . ' €';
    }
    
    public function getFormattedPlatformCost(): string
    {
        return number_format($this->platform_cost_cents / 100, 2) . ' €';
    }
    
    /**
     * Calculate net profit margin
     */
    public function getProfitMargin(): float
    {
        if ($this->gross_amount_cents == 0) {
            return 0;
        }
        
        return ($this->commission_cents / $this->gross_amount_cents) * 100;
    }
}