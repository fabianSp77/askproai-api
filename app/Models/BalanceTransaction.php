<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BalanceTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    // Transaction Types
    const TYPE_TOPUP = 'topup';
    const TYPE_CHARGE = 'charge';
    const TYPE_REFUND = 'refund';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_RESERVATION = 'reservation';
    const TYPE_RELEASE = 'release';

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(PortalUser::class, 'created_by');
    }

    // Dynamic relationship based on reference_type
    public function reference()
    {
        switch ($this->reference_type) {
            case 'call':
                return $this->belongsTo(Call::class, 'reference_id');
            case 'topup':
                return $this->belongsTo(BalanceTopup::class, 'reference_id');
            default:
                return null;
        }
    }

    // Scopes
    public function scopeCredits($query)
    {
        return $query->whereIn('type', [self::TYPE_TOPUP, self::TYPE_REFUND])
                     ->where('amount', '>', 0);
    }

    public function scopeDebits($query)
    {
        return $query->whereIn('type', [self::TYPE_CHARGE])
                     ->where('amount', '<', 0);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return number_format(abs($this->amount), 2, ',', '.') . ' €';
    }

    public function getIsDebitAttribute()
    {
        return $this->amount < 0;
    }

    public function getIsCreditAttribute()
    {
        return $this->amount > 0;
    }

    // Helper Methods
    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_TOPUP => 'Aufladung',
            self::TYPE_CHARGE => 'Belastung',
            self::TYPE_REFUND => 'Erstattung',
            self::TYPE_ADJUSTMENT => 'Anpassung',
            self::TYPE_RESERVATION => 'Reservierung',
            self::TYPE_RELEASE => 'Freigabe',
            default => 'Unbekannt',
        };
    }

    public function getTypeColor(): string
    {
        return match($this->type) {
            self::TYPE_TOPUP => 'green',
            self::TYPE_CHARGE => 'red',
            self::TYPE_REFUND => 'blue',
            self::TYPE_ADJUSTMENT => 'yellow',
            self::TYPE_RESERVATION => 'orange',
            self::TYPE_RELEASE => 'purple',
            default => 'gray',
        };
    }
}