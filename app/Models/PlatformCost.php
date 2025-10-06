<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformCost extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'platform',
        'service_type',
        'cost_type',
        'amount_cents',
        'currency',
        'period_start',
        'period_end',
        'usage_quantity',
        'usage_unit',
        'external_reference_id',
        'metadata',
        'notes'
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'metadata' => 'array',
        'usage_quantity' => 'decimal:4'
    ];

    /**
     * Get the company that owns the platform cost.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope to filter by platform
     */
    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('period_start', [$start, $end]);
    }

    /**
     * Get formatted amount in euros
     */
    public function getAmountEurosAttribute(): float
    {
        return $this->amount_cents / 100;
    }

    /**
     * Calculate monthly cost for a specific service
     */
    public static function getMonthlyTotal($companyId, $platform, $month, $year)
    {
        return self::where('company_id', $companyId)
            ->where('platform', $platform)
            ->whereMonth('period_start', $month)
            ->whereYear('period_start', $year)
            ->sum('amount_cents');
    }
}