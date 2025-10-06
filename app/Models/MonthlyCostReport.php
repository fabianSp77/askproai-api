<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyCostReport extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'month',
        'year',
        'retell_cost_cents',
        'twilio_cost_cents',
        'calcom_cost_cents',
        'other_costs_cents',
        'total_external_costs_cents',
        'total_revenue_cents',
        'gross_profit_cents',
        'profit_margin',
        'call_count',
        'total_minutes',
        'average_call_duration',
        'cost_breakdown',
        'finalized_at',
        'notes'
    ];

    protected $casts = [
        'finalized_at' => 'datetime',
        'cost_breakdown' => 'array',
        'profit_margin' => 'decimal:2',
        'average_call_duration' => 'decimal:2'
    ];

    /**
     * Get the company that owns the report.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope to get reports for a specific period
     */
    public function scopePeriod($query, $month, $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    /**
     * Scope to get finalized reports only
     */
    public function scopeFinalized($query)
    {
        return $query->whereNotNull('finalized_at');
    }

    /**
     * Get total external costs in euros
     */
    public function getTotalExternalCostsEurosAttribute(): float
    {
        return $this->total_external_costs_cents / 100;
    }

    /**
     * Get total revenue in euros
     */
    public function getTotalRevenueEurosAttribute(): float
    {
        return $this->total_revenue_cents / 100;
    }

    /**
     * Get gross profit in euros
     */
    public function getGrossProfitEurosAttribute(): float
    {
        return $this->gross_profit_cents / 100;
    }

    /**
     * Calculate and update profit metrics
     */
    public function calculateProfitMetrics(): void
    {
        $this->gross_profit_cents = $this->total_revenue_cents - $this->total_external_costs_cents;

        if ($this->total_revenue_cents > 0) {
            $this->profit_margin = ($this->gross_profit_cents / $this->total_revenue_cents) * 100;
        } else {
            $this->profit_margin = 0;
        }

        $this->save();
    }

    /**
     * Finalize the report
     */
    public function finalize(): void
    {
        $this->finalized_at = now();
        $this->save();
    }

    /**
     * Check if report is finalized
     */
    public function isFinalized(): bool
    {
        return $this->finalized_at !== null;
    }
}