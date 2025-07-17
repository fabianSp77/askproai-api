<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class BillingBonusRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'min_amount',
        'max_amount',
        'bonus_percentage',
        'max_bonus_amount',
        'is_first_time_only',
        'is_active',
        'priority',
        'valid_from',
        'valid_until',
        'times_used',
        'total_bonus_given',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'bonus_percentage' => 'decimal:2',
        'max_bonus_amount' => 'decimal:2',
        'is_first_time_only' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'times_used' => 'integer',
        'total_bonus_given' => 'decimal:2',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeValid(Builder $query): Builder
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
        });
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('company_id');
    }

    public function scopeForCompany(Builder $query, $companyId): Builder
    {
        return $query->where(function ($q) use ($companyId) {
            $q->whereNull('company_id')->orWhere('company_id', $companyId);
        });
    }

    public function scopeApplicableForAmount(Builder $query, float $amount): Builder
    {
        return $query->where('min_amount', '<=', $amount)
                    ->where(function ($q) use ($amount) {
                        $q->whereNull('max_amount')->orWhere('max_amount', '>=', $amount);
                    });
    }

    // Helper Methods
    public function calculateBonus(float $amount): float
    {
        $bonus = $amount * ($this->bonus_percentage / 100);
        
        if ($this->max_bonus_amount !== null && $bonus > $this->max_bonus_amount) {
            $bonus = $this->max_bonus_amount;
        }
        
        return round($bonus, 2);
    }

    public function isApplicable(float $amount, bool $isFirstTopup = false): bool
    {
        // Check if active and valid
        if (!$this->is_active) {
            return false;
        }
        
        // Check validity period
        $now = now();
        if ($this->valid_from && $this->valid_from > $now) {
            return false;
        }
        if ($this->valid_until && $this->valid_until < $now) {
            return false;
        }
        
        // Check amount range
        if ($amount < $this->min_amount) {
            return false;
        }
        if ($this->max_amount !== null && $amount > $this->max_amount) {
            return false;
        }
        
        // Check first time only
        if ($this->is_first_time_only && !$isFirstTopup) {
            return false;
        }
        
        return true;
    }

    public function recordUsage(float $bonusAmount): void
    {
        $this->increment('times_used');
        $this->increment('total_bonus_given', $bonusAmount);
    }
}