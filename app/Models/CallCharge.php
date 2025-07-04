<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_id',
        'company_id',
        'duration_seconds',
        'rate_per_minute',
        'amount_charged',
        'balance_transaction_id',
        'charged_at',
    ];

    protected $casts = [
        'rate_per_minute' => 'decimal:4',
        'amount_charged' => 'decimal:2',
        'charged_at' => 'datetime',
    ];

    // Relationships
    public function call()
    {
        return $this->belongsTo(Call::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function transaction()
    {
        return $this->belongsTo(BalanceTransaction::class, 'balance_transaction_id');
    }

    // Scopes
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('charged_at', [$startDate, $endDate]);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount_charged, 2, ',', '.') . ' €';
    }

    public function getFormattedDurationAttribute()
    {
        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;
        
        if ($minutes > 0) {
            return sprintf('%d:%02d Min', $minutes, $seconds);
        } else {
            return sprintf('%d Sek', $seconds);
        }
    }

    public function getFormattedRateAttribute()
    {
        return number_format($this->rate_per_minute, 2, ',', '.') . ' €/Min';
    }

    // Static Methods
    public static function chargeCall(Call $call): ?self
    {
        // Skip if already charged
        if (self::where('call_id', $call->id)->exists()) {
            return null;
        }

        // Skip if no duration
        if (!$call->duration_sec || $call->duration_sec <= 0) {
            return null;
        }

        // Get company
        $company = $call->company;
        if (!$company || !$company->prepaid_billing_enabled) {
            return null;
        }

        // Get billing rate
        $billingRate = BillingRate::where('company_id', $company->id)
                                 ->active()
                                 ->first();
        
        if (!$billingRate) {
            $billingRate = BillingRate::createDefaultForCompany($company);
        }

        // Calculate charge
        $amountToCharge = $billingRate->calculateCharge($call->duration_sec);

        // Get balance
        $balance = PrepaidBalance::firstOrCreate(
            ['company_id' => $company->id],
            ['balance' => 0, 'reserved_balance' => 0]
        );

        // Deduct from balance
        try {
            $transaction = $balance->deductBalance(
                $amountToCharge,
                sprintf('Anruf %s (%s)', $call->phone_number, gmdate('i:s', $call->duration_sec)),
                'call',
                $call->id
            );

            // Create charge record
            return self::create([
                'call_id' => $call->id,
                'company_id' => $company->id,
                'duration_seconds' => $call->duration_sec,
                'rate_per_minute' => $billingRate->rate_per_minute,
                'amount_charged' => $amountToCharge,
                'balance_transaction_id' => $transaction->id,
                'charged_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Log error
            \Log::error('Failed to charge call', [
                'call_id' => $call->id,
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }
}