<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'rate',
        'is_default',
        'is_system',
        'description',
        'valid_from',
        'valid_until',
        'stripe_tax_rate_id',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_default' => 'boolean',
        'is_system' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    /**
     * Get the company that owns the tax rate.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope for active tax rates.
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('valid_from')
              ->orWhere('valid_from', '<=', now());
        })->where(function ($q) {
            $q->whereNull('valid_until')
              ->orWhere('valid_until', '>=', now());
        });
    }

    /**
     * Scope for system tax rates.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope for company-specific tax rates.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->orWhere('is_system', true);
        });
    }

    /**
     * Get display name with rate.
     */
    public function getDisplayNameAttribute(): string
    {
        return sprintf('%s (%.2f%%)', $this->name, $this->rate);
    }

    /**
     * Check if this is a zero rate (small business/reverse charge).
     */
    public function isZeroRate(): bool
    {
        return $this->rate == 0;
    }

    /**
     * Check if this is the small business rate.
     */
    public function isSmallBusinessRate(): bool
    {
        return $this->rate == 0 && str_contains(strtolower($this->name), 'kleinunternehmer');
    }
}