<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Scopes\TenantScope;
use Illuminate\Support\Facades\Log;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'stripe_invoice_id',
        'invoice_number',
        'status',
        'subtotal',
        'tax_amount',
        'total',
        'currency',
        'invoice_date',
        'due_date',
        'paid_date',
        'payment_method',
        'stripe_payment_intent_id',
        'pdf_url',
        'metadata',
        'notes',
        'billing_reason',
        'auto_advance',
    ];

    protected $casts = [
        'metadata' => 'array',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'auto_advance' => 'boolean',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Invoice statuses
    const STATUS_DRAFT = 'draft';
    const STATUS_OPEN = 'open';
    const STATUS_PAID = 'paid';
    const STATUS_VOID = 'void';
    const STATUS_UNCOLLECTIBLE = 'uncollectible';

    // Billing reasons
    const REASON_SUBSCRIPTION_CYCLE = 'subscription_cycle';
    const REASON_MANUAL = 'manual';
    const REASON_SUBSCRIPTION_UPDATE = 'subscription_update';

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Get the company that owns the invoice.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch that owns the invoice.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the invoice items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the payments for this invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the billing period associated with this invoice.
     */
    public function billingPeriod(): HasOne
    {
        return $this->hasOne(BillingPeriod::class);
    }

    /**
     * Get usage items only.
     */
    public function usageItems(): HasMany
    {
        return $this->items()->where('type', 'usage');
    }

    /**
     * Get service items only.
     */
    public function serviceItems(): HasMany
    {
        return $this->items()->where('type', 'service');
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OPEN && 
               $this->due_date && 
               $this->due_date->isPast();
    }

    /**
     * Get days overdue.
     */
    public function getDaysOverdueAttribute(): ?int
    {
        if (!$this->isOverdue()) {
            return null;
        }

        return $this->due_date->diffInDays(now());
    }

    /**
     * Get the paid amount.
     */
    public function getPaidAmountAttribute(): float
    {
        return $this->payments()
            ->where('status', 'succeeded')
            ->sum('amount');
    }

    /**
     * Get the outstanding amount.
     */
    public function getOutstandingAmountAttribute(): float
    {
        return max(0, $this->total - $this->paid_amount);
    }

    /**
     * Generate next invoice number.
     */
    public static function generateInvoiceNumber(Company $company): string
    {
        try {
            $prefix = $company->invoice_prefix ?: 'INV';
            $number = $company->next_invoice_number ?: 1;
            
            // Format: PREFIX-YYYY-00001
            $invoiceNumber = sprintf(
                '%s-%s-%05d',
                $prefix,
                now()->format('Y'),
                $number
            );
            
            // Increment for next time
            $company->increment('next_invoice_number');
            
            return $invoiceNumber;
        } catch (\Exception $e) {
            Log::error('Error generating invoice number', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);
            
            // Fallback
            return 'INV-' . now()->format('YmdHis');
        }
    }

    /**
     * Sync with Stripe invoice data.
     */
    public function syncWithStripe(array $stripeData): void
    {
        try {
            $this->update([
                'status' => $stripeData['status'] ?? $this->status,
                'paid_date' => isset($stripeData['status_transitions']['paid_at']) 
                    ? \Carbon\Carbon::createFromTimestamp($stripeData['status_transitions']['paid_at'])
                    : null,
                'pdf_url' => $stripeData['invoice_pdf'] ?? $this->pdf_url,
                'metadata' => array_merge($this->metadata ?? [], [
                    'stripe_data' => $stripeData,
                    'last_sync' => now()->toIso8601String(),
                ]),
            ]);
            
            Log::info('Invoice synced with Stripe', [
                'invoice_id' => $this->id,
                'stripe_invoice_id' => $this->stripe_invoice_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error syncing invoice with Stripe', [
                'invoice_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate tax amount based on items.
     */
    public function calculateTaxAmount(): float
    {
        return $this->items->sum(function ($item) {
            return $item->amount * ($item->tax_rate / 100);
        });
    }

    /**
     * Scope for open invoices.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope for paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope for overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->open()
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_OPEN => 'warning',
            self::STATUS_PAID => 'success',
            self::STATUS_VOID => 'danger',
            self::STATUS_UNCOLLECTIBLE => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get formatted status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Entwurf',
            self::STATUS_OPEN => 'Offen',
            self::STATUS_PAID => 'Bezahlt',
            self::STATUS_VOID => 'Storniert',
            self::STATUS_UNCOLLECTIBLE => 'Uneinbringlich',
            default => ucfirst($this->status),
        };
    }
}