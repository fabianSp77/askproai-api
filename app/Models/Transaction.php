<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Transaction extends Model
{
    use HasFactory, LogsActivity;
    
    protected $fillable = [
        'tenant_id',
        'type',
        'amount_cents',
        'balance_before_cents',
        'balance_after_cents',
        'description',
        'metadata',
        'topup_id',
        'call_id',
        'appointment_id',
        // Multi-tier billing fields
        'reseller_tenant_id',
        'commission_amount_cents',
        'base_cost_cents',
        'reseller_revenue_cents',
        'parent_transaction_id'
    ];
    
    protected $casts = [
        'metadata' => 'array'
    ];
    
    /**
     * Transaction types
     */
    const TYPE_TOPUP = 'topup';
    const TYPE_USAGE = 'usage';
    const TYPE_REFUND = 'refund';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_BONUS = 'bonus';
    const TYPE_FEE = 'fee';
    
    /**
     * Get the tenant for this transaction
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    
    /**
     * Get the related topup if exists
     */
    public function topup(): BelongsTo
    {
        return $this->belongsTo(BalanceTopup::class, 'topup_id');
    }
    
    /**
     * Get the related call if exists
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class, 'call_id');
    }
    
    /**
     * Get the related appointment if exists
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }
    
    /**
     * Multi-tier billing relationships
     */
    public function resellerTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'reseller_tenant_id');
    }
    
    public function parentTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'parent_transaction_id');
    }
    
    public function childTransactions()
    {
        return $this->hasMany(Transaction::class, 'parent_transaction_id');
    }
    
    /**
     * Check if this transaction involves a reseller
     */
    public function hasReseller(): bool
    {
        return $this->reseller_tenant_id !== null;
    }
    
    /**
     * Get the commission amount formatted
     */
    public function getFormattedCommission(): string
    {
        if (!$this->commission_amount_cents) {
            return '0.00 €';
        }
        return number_format($this->commission_amount_cents / 100, 2) . ' €';
    }
    
    /**
     * Get the billing chain type
     */
    public function getBillingChainType(): string
    {
        if (!isset($this->metadata['billing_chain'])) {
            return 'unknown';
        }
        return $this->metadata['billing_chain'];
    }
    
    /**
     * Get formatted amount for display
     */
    public function getFormattedAmount(): string
    {
        $amount = $this->amount_cents / 100;
        $prefix = $this->amount_cents > 0 ? '+' : '';
        return $prefix . number_format($amount, 2) . ' €';
    }
    
    /**
     * Get formatted balance for display
     */
    public function getFormattedBalanceAfter(): string
    {
        return number_format($this->balance_after_cents / 100, 2) . ' €';
    }
    
    /**
     * Check if this is a credit transaction
     */
    public function isCredit(): bool
    {
        return $this->amount_cents > 0;
    }
    
    /**
     * Check if this is a debit transaction
     */
    public function isDebit(): bool
    {
        return $this->amount_cents < 0;
    }
    
    /**
     * Scope for credit transactions
     */
    public function scopeCredits($query)
    {
        return $query->where('amount_cents', '>', 0);
    }
    
    /**
     * Scope for debit transactions
     */
    public function scopeDebits($query)
    {
        return $query->where('amount_cents', '<', 0);
    }
    
    /**
     * Scope for usage transactions
     */
    public function scopeUsage($query)
    {
        return $query->where('type', self::TYPE_USAGE);
    }
    
    /**
     * Create a usage transaction for a call
     */
    public static function createForCall(Tenant $tenant, Call $call, int $costCents, string $description = null): self
    {
        $balanceBefore = $tenant->balance_cents;
        $tenant->decrement('balance_cents', $costCents);
        
        return self::create([
            'tenant_id' => $tenant->id,
            'type' => self::TYPE_USAGE,
            'amount_cents' => -$costCents,
            'balance_before_cents' => $balanceBefore,
            'balance_after_cents' => $balanceBefore - $costCents,
            'description' => $description ?? "Anruf #{$call->id}",
            'call_id' => $call->id,
            'metadata' => [
                'duration_seconds' => $call->duration_seconds,
                'phone_number' => $call->phone_number
            ]
        ]);
    }
    
    /**
     * Configure activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Transaktion erstellt',
                'updated' => 'Transaktion aktualisiert',
                'deleted' => 'Transaktion gelöscht',
                default => "Transaktion {$eventName}"
            })
            ->useLogName('transactions');
    }
    
    /**
     * Get the display name for activity logs
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        $type = $this->type ? $this->getTypeLabel() : 'Transaktion';
        $amount = $this->getFormattedAmount();
        
        return match($eventName) {
            'created' => "{$type} über {$amount} wurde erstellt",
            'updated' => "{$type} über {$amount} wurde aktualisiert",
            'deleted' => "{$type} über {$amount} wurde gelöscht",
            default => "{$type} über {$amount} wurde {$eventName}"
        };
    }
    
    /**
     * Get type label in German
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_TOPUP => 'Aufladung',
            self::TYPE_USAGE => 'Verbrauch',
            self::TYPE_REFUND => 'Erstattung',
            self::TYPE_ADJUSTMENT => 'Anpassung',
            self::TYPE_BONUS => 'Bonus',
            self::TYPE_FEE => 'Gebühr',
            default => ucfirst($this->type)
        };
    }
}