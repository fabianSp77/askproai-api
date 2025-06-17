<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'period_start',
        'period_end',
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
        'period_start' => 'date',
        'period_end' => 'date',
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
        return $query->whereMonth('period_start', now()->month)
            ->whereYear('period_start', now()->year);
    }
}