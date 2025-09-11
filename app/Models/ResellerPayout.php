<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ResellerPayout extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'reseller_tenant_id',
        'amount_cents',
        'commission_entries_count',
        'period_start',
        'period_end',
        'status',
        'payout_method',
        'payout_reference',
        'payout_details',
        'total_gross_cents',
        'total_platform_cost_cents',
        'total_commission_cents',
        'average_commission_rate',
        'processed_at'
    ];
    
    protected $casts = [
        'payout_details' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
        'processed_at' => 'datetime',
        'average_commission_rate' => 'decimal:2'
    ];
    
    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    
    /**
     * Payout method constants
     */
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_STRIPE = 'stripe';
    const METHOD_MANUAL = 'manual';
    
    /**
     * Relationships
     */
    public function resellerTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'reseller_tenant_id');
    }
    
    /**
     * Get commission entries for this payout period
     */
    public function commissionEntries()
    {
        return CommissionLedger::where('reseller_tenant_id', $this->reseller_tenant_id)
            ->whereBetween('created_at', [$this->period_start, $this->period_end->endOfDay()])
            ->where('status', CommissionLedger::STATUS_APPROVED);
    }
    
    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
    
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
    
    public function scopeForPeriod($query, $start, $end)
    {
        return $query->where('period_start', '>=', $start)
                    ->where('period_end', '<=', $end);
    }
    
    /**
     * Calculate payout from commission entries
     */
    public function calculateFromCommissions(): void
    {
        $entries = $this->commissionEntries()->get();
        
        $this->commission_entries_count = $entries->count();
        $this->total_gross_cents = $entries->sum('gross_amount_cents');
        $this->total_platform_cost_cents = $entries->sum('platform_cost_cents');
        $this->total_commission_cents = $entries->sum('commission_cents');
        $this->amount_cents = $this->total_commission_cents;
        
        if ($this->total_gross_cents > 0) {
            $this->average_commission_rate = ($this->total_commission_cents / $this->total_gross_cents) * 100;
        }
        
        $this->save();
    }
    
    /**
     * Process the payout
     */
    public function process(): bool
    {
        try {
            $this->update(['status' => self::STATUS_PROCESSING]);
            
            // Process based on payout method
            switch ($this->payout_method) {
                case self::METHOD_STRIPE:
                    $success = $this->processStripeTransfer();
                    break;
                case self::METHOD_BANK_TRANSFER:
                    $success = $this->processBankTransfer();
                    break;
                case self::METHOD_MANUAL:
                    $success = true; // Manual processing
                    break;
                default:
                    $success = false;
            }
            
            if ($success) {
                $this->update([
                    'status' => self::STATUS_COMPLETED,
                    'processed_at' => now()
                ]);
                
                // Mark all commission entries as paid
                $this->commissionEntries()->update([
                    'status' => CommissionLedger::STATUS_PAID,
                    'paid_at' => now(),
                    'payout_reference' => $this->payout_reference
                ]);
                
                return true;
            }
            
            $this->update(['status' => self::STATUS_FAILED]);
            return false;
            
        } catch (\Exception $e) {
            $this->update([
                'status' => self::STATUS_FAILED,
                'payout_details' => array_merge($this->payout_details ?? [], [
                    'error' => $e->getMessage()
                ])
            ]);
            return false;
        }
    }
    
    /**
     * Process Stripe transfer
     */
    protected function processStripeTransfer(): bool
    {
        // TODO: Implement Stripe transfer logic
        // This would create a Stripe Transfer to the connected account
        return false;
    }
    
    /**
     * Process bank transfer
     */
    protected function processBankTransfer(): bool
    {
        // TODO: Implement bank transfer via SEPA or other method
        // This would integrate with banking API
        return false;
    }
    
    /**
     * Get formatted amounts
     */
    public function getFormattedAmount(): string
    {
        return number_format($this->amount_cents / 100, 2) . ' €';
    }
    
    public function getFormattedTotalGross(): string
    {
        return number_format($this->total_gross_cents / 100, 2) . ' €';
    }
    
    public function getFormattedTotalCommission(): string
    {
        return number_format($this->total_commission_cents / 100, 2) . ' €';
    }
    
    /**
     * Get status label
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Ausstehend',
            self::STATUS_PROCESSING => 'In Bearbeitung',
            self::STATUS_COMPLETED => 'Abgeschlossen',
            self::STATUS_FAILED => 'Fehlgeschlagen',
            default => ucfirst($this->status)
        };
    }
    
    /**
     * Get payout method label
     */
    public function getMethodLabel(): string
    {
        return match($this->payout_method) {
            self::METHOD_BANK_TRANSFER => 'Banküberweisung',
            self::METHOD_STRIPE => 'Stripe Transfer',
            self::METHOD_MANUAL => 'Manuelle Auszahlung',
            default => ucfirst(str_replace('_', ' ', $this->payout_method))
        };
    }
}