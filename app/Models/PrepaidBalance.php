<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PrepaidBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'balance',
        'reserved_balance',
        'low_balance_threshold',
        'last_warning_sent_at',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'reserved_balance' => 'decimal:2',
        'low_balance_threshold' => 'decimal:2',
        'last_warning_sent_at' => 'datetime',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function transactions()
    {
        return $this->hasMany(BalanceTransaction::class, 'company_id', 'company_id');
    }

    // Atomic Operations
    public function addBalance(float $amount, string $description, string $referenceType = null, string $referenceId = null)
    {
        return DB::transaction(function () use ($amount, $description, $referenceType, $referenceId) {
            $this->lockForUpdate();
            
            $balanceBefore = $this->balance;
            $this->increment('balance', $amount);
            
            return BalanceTransaction::create([
                'company_id' => $this->company_id,
                'type' => 'topup',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $this->balance,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'created_by' => auth()->guard('portal')->user()->id ?? null,
            ]);
        });
    }

    public function deductBalance(float $amount, string $description, string $referenceType = null, string $referenceId = null)
    {
        return DB::transaction(function () use ($amount, $description, $referenceType, $referenceId) {
            $this->lockForUpdate();
            
            if ($this->getEffectiveBalance() < $amount) {
                throw new \Exception('Insufficient balance');
            }
            
            $balanceBefore = $this->balance;
            $this->decrement('balance', $amount);
            
            return BalanceTransaction::create([
                'company_id' => $this->company_id,
                'type' => 'charge',
                'amount' => -$amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $this->balance,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'created_by' => auth()->guard('portal')->user()->id ?? null,
            ]);
        });
    }

    public function reserveBalance(float $amount): bool
    {
        return DB::transaction(function () use ($amount) {
            $this->lockForUpdate();
            
            if ($this->getEffectiveBalance() < $amount) {
                return false;
            }
            
            $this->increment('reserved_balance', $amount);
            
            BalanceTransaction::create([
                'company_id' => $this->company_id,
                'type' => 'reservation',
                'amount' => -$amount,
                'balance_before' => $this->balance,
                'balance_after' => $this->balance,
                'description' => 'Balance reserved for ongoing call',
                'created_by' => null,
            ]);
            
            return true;
        });
    }

    public function releaseReservedBalance(float $amount)
    {
        return DB::transaction(function () use ($amount) {
            $this->lockForUpdate();
            
            $this->decrement('reserved_balance', min($amount, $this->reserved_balance));
            
            BalanceTransaction::create([
                'company_id' => $this->company_id,
                'type' => 'release',
                'amount' => $amount,
                'balance_before' => $this->balance,
                'balance_after' => $this->balance,
                'description' => 'Reserved balance released',
                'created_by' => null,
            ]);
        });
    }

    // Helper Methods
    public function getEffectiveBalance(): float
    {
        return $this->balance - $this->reserved_balance;
    }

    public function isLowBalance(): bool
    {
        $threshold = ($this->low_balance_threshold / 100) * $this->balance;
        return $this->getEffectiveBalance() <= $threshold;
    }

    public function hasInsufficientBalance(float $requiredAmount): bool
    {
        return $this->getEffectiveBalance() < $requiredAmount;
    }

    public function getBalancePercentage(): float
    {
        if ($this->balance <= 0) {
            return 0;
        }
        
        return ($this->getEffectiveBalance() / $this->balance) * 100;
    }

    // Scopes
    public function scopeLowBalance($query)
    {
        return $query->whereRaw('(balance - reserved_balance) <= (low_balance_threshold / 100) * balance');
    }

    public function scopeNeedsWarning($query)
    {
        return $query->lowBalance()
            ->where(function ($q) {
                $q->whereNull('last_warning_sent_at')
                  ->orWhere('last_warning_sent_at', '<', now()->subHours(24));
            });
    }
}