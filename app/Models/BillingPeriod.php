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
        'start_date',
        'end_date',
        'total_minutes',
        'included_minutes',
        'overage_minutes',
        'total_cost',
        'total_revenue',
        'margin',
        'margin_percentage',
        'is_invoiced',
        'invoiced_at',
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
        'total_minutes' => 'integer',
        'included_minutes' => 'integer',
        'overage_minutes' => 'integer',
        'total_cost' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'margin' => 'decimal:2',
        'margin_percentage' => 'decimal:2',
        'is_invoiced' => 'boolean',
        'invoiced_at' => 'datetime',
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
     * Get the invoice for this billing period.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the calls for this billing period.
     * Note: This is not a true relationship but a query method.
     */
    public function getCalls()
    {
        return Call::where('company_id', $this->company_id)
            ->when($this->branch_id, function ($query) {
                return $query->where('branch_id', $this->branch_id);
            })
            ->whereBetween('created_at', [$this->start_date, $this->end_date])
            ->get();
    }
    
    /**
     * Get the calls count for this billing period.
     */
    public function getCallsCountAttribute(): int
    {
        return Call::where('company_id', $this->company_id)
            ->when($this->branch_id, function ($query) {
                return $query->where('branch_id', $this->branch_id);
            })
            ->whereBetween('created_at', [$this->start_date, $this->end_date])
            ->count();
    }

    /**
     * Scope for uninvoiced periods.
     */
    public function scopeUninvoiced($query)
    {
        return $query->where('is_invoiced', false);
    }

    /**
     * Scope for current month.
     */
    public function scopeCurrentMonth($query)
    {
        return $query->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year);
    }
}