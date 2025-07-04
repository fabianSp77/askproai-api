<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'rate_per_minute',
        'billing_increment',
        'minimum_charge',
        'is_active',
    ];

    protected $casts = [
        'rate_per_minute' => 'decimal:4',
        'minimum_charge' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Methods
    public function calculateCharge(int $durationSeconds): float
    {
        // Apply billing increment (e.g., 1 second, 6 seconds, 60 seconds)
        $billableSeconds = $durationSeconds;
        if ($this->billing_increment > 1) {
            $billableSeconds = ceil($durationSeconds / $this->billing_increment) * $this->billing_increment;
        }

        // Calculate charge
        $charge = ($billableSeconds / 60) * $this->rate_per_minute;

        // Apply minimum charge if applicable
        return max($charge, $this->minimum_charge);
    }

    public function getMinimumBalanceRequired(): float
    {
        // Mindestens 1 Minute oder Mindestgebühr
        return max($this->rate_per_minute, $this->minimum_charge);
    }

    // Accessors
    public function getFormattedRateAttribute()
    {
        return number_format($this->rate_per_minute, 2, ',', '.') . ' €/Min';
    }

    public function getBillingIncrementLabelAttribute()
    {
        if ($this->billing_increment == 1) {
            return 'Sekundengenau';
        } elseif ($this->billing_increment < 60) {
            return $this->billing_increment . '-Sekunden-Takt';
        } elseif ($this->billing_increment == 60) {
            return 'Minutentakt';
        } else {
            return ($this->billing_increment / 60) . '-Minuten-Takt';
        }
    }

    // Static Methods
    public static function getDefaultRate(): float
    {
        return 0.42; // 0,42€ pro Minute als Standard
    }

    public static function createDefaultForCompany(Company $company): self
    {
        return self::create([
            'company_id' => $company->id,
            'rate_per_minute' => self::getDefaultRate(),
            'billing_increment' => 1, // Sekundengenau
            'minimum_charge' => 0.00,
            'is_active' => true,
        ]);
    }
}