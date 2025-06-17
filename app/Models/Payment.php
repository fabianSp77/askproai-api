<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'stripe_payment_id',
        'payment_method',
        'amount',
        'currency',
        'status',
        'payment_date',
        'reference_number',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    // Payment methods
    const METHOD_STRIPE = 'stripe';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_CASH = 'cash';

    // Payment statuses
    const STATUS_SUCCEEDED = 'succeeded';
    const STATUS_PENDING = 'pending';
    const STATUS_FAILED = 'failed';

    /**
     * Get the invoice that owns the payment.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the company through invoice.
     */
    public function company()
    {
        return $this->invoice->company();
    }

    /**
     * Check if payment is successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCEEDED;
    }

    /**
     * Get payment method label.
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            self::METHOD_STRIPE => 'Stripe',
            self::METHOD_BANK_TRANSFER => 'Überweisung',
            self::METHOD_CASH => 'Bar',
            default => ucfirst($this->payment_method),
        };
    }

    /**
     * Get status color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_SUCCEEDED => 'success',
            self::STATUS_PENDING => 'warning',
            self::STATUS_FAILED => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_SUCCEEDED => 'Erfolgreich',
            self::STATUS_PENDING => 'Ausstehend',
            self::STATUS_FAILED => 'Fehlgeschlagen',
            default => ucfirst($this->status),
        };
    }

    /**
     * Scope for successful payments.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_SUCCEEDED);
    }

    /**
     * Scope for pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for failed payments.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }
}