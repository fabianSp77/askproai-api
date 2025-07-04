<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BalanceTopup extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'amount',
        'currency',
        'status',
        'stripe_payment_intent_id',
        'stripe_checkout_session_id',
        'stripe_response',
        'initiated_by',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'stripe_response' => 'json',
        'paid_at' => 'datetime',
    ];

    // Status Constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SUCCEEDED = 'succeeded';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function initiatedBy()
    {
        return $this->belongsTo(PortalUser::class, 'initiated_by');
    }

    public function transaction()
    {
        return $this->hasOne(BalanceTransaction::class, 'reference_id')
                    ->where('reference_type', 'topup');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_SUCCEEDED);
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', [self::STATUS_FAILED, self::STATUS_CANCELLED]);
    }

    // Methods
    public function markAsProcessing()
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    public function markAsSucceeded()
    {
        $this->update([
            'status' => self::STATUS_SUCCEEDED,
            'paid_at' => now(),
        ]);

        // Create balance transaction
        $balance = PrepaidBalance::firstOrCreate(
            ['company_id' => $this->company_id],
            ['balance' => 0, 'reserved_balance' => 0]
        );

        $balance->addBalance(
            $this->amount,
            "Guthaben-Aufladung via Stripe",
            'topup',
            $this->id
        );
    }

    public function markAsFailed($reason = null)
    {
        $update = ['status' => self::STATUS_FAILED];
        
        if ($reason) {
            $stripeResponse = $this->stripe_response ?? [];
            $stripeResponse['failure_reason'] = $reason;
            $update['stripe_response'] = $stripeResponse;
        }
        
        $this->update($update);
    }

    public function markAsCancelled()
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Ausstehend',
            self::STATUS_PROCESSING => 'In Bearbeitung',
            self::STATUS_SUCCEEDED => 'Erfolgreich',
            self::STATUS_FAILED => 'Fehlgeschlagen',
            self::STATUS_CANCELLED => 'Abgebrochen',
            default => 'Unbekannt',
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_PROCESSING => 'blue',
            self::STATUS_SUCCEEDED => 'green',
            self::STATUS_FAILED => 'red',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2, ',', '.') . ' ' . $this->currency;
    }

    // Helper Methods
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCEEDED;
    }

    public function hasFailed(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_CANCELLED]);
    }
}