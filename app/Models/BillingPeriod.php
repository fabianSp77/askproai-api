<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'subscription_id',
        'invoice_id',
        'start_date',
        'end_date',
        'status',
        'total_minutes',
        'used_minutes',
        'included_minutes',
        'overage_minutes',
        'price_per_minute',
        'base_fee',
        'overage_cost',
        'total_cost',
        'total_revenue',
        'margin',
        'margin_percentage',
        'currency',
        'is_prorated',
        'proration_factor',
        'is_invoiced',
        'invoiced_at',
        'stripe_invoice_id',
        'stripe_invoice_created_at',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_minutes' => 'decimal:2',
        'used_minutes' => 'decimal:2',
        'included_minutes' => 'integer',
        'overage_minutes' => 'integer',
        'price_per_minute' => 'decimal:4',
        'base_fee' => 'decimal:2',
        'overage_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'margin' => 'decimal:2',
        'margin_percentage' => 'decimal:2',
        'proration_factor' => 'decimal:4',
        'is_prorated' => 'boolean',
        'is_invoiced' => 'boolean',
        'invoiced_at' => 'datetime',
        'stripe_invoice_created_at' => 'datetime',
    ];

    /**
     * Get the company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the subscription.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the invoice for this billing period.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the calls for this billing period.
     */
    public function calls(): HasMany
    {
        $query = $this->hasMany(Call::class, 'company_id', 'company_id');
        
        if ($this->start_date && $this->end_date) {
            $query->whereBetween('start_timestamp', [
                $this->start_date->startOfDay(), 
                $this->end_date->copy()->endOfDay()
            ]);
        }
        
        return $query;
    }

    /**
     * Scope for active periods.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Scope for periods ready to process.
     */
    public function scopeReadyToProcess($query)
    {
        return $query->where('status', 'active')
            ->where('end_date', '<', now());
    }

    /**
     * Scope for uninvoiced periods.
     */
    public function scopeUninvoiced($query)
    {
        return $query->where('is_invoiced', false)
            ->where('status', 'processed');
    }

    /**
     * Check if period is current.
     */
    public function isCurrent(): bool
    {
        return $this->status === 'active' 
            && $this->start_date->lte(now()) 
            && $this->end_date->gte(now());
    }

    /**
     * Check if period can be processed.
     */
    public function canBeProcessed(): bool
    {
        return $this->status === 'active' && $this->end_date->lt(now());
    }

    /**
     * Check if period can be invoiced.
     */
    public function canBeInvoiced(): bool
    {
        return $this->status === 'processed' && !$this->is_invoiced;
    }

    /**
     * Scope for current month.
     */
    public function scopeCurrentMonth($query)
    {
        return $query->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year);
    }

    /**
     * Scope for pending periods.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for processed periods.
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Calculate overage minutes and cost.
     */
    public function calculateOverage(): void
    {
        $this->overage_minutes = max(0, $this->used_minutes - $this->included_minutes);
        $this->overage_cost = round($this->overage_minutes * $this->price_per_minute, 2);
        $this->total_cost = round($this->base_fee + $this->overage_cost, 2);
    }
}