<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Carbon\Carbon;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'invoice_number',
        'company_id',
        'tenant_id',
        'customer_id',
        'status',
        'issue_date',
        'due_date',
        'paid_date',
        'paid_at',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'balance_due',
        'tax_rate',
        'currency',
        'exchange_rate',
        'billing_name',
        'billing_address',
        'billing_email',
        'billing_phone',
        'billing_tax_id',
        'payment_method',
        'payment_reference',
        'payment_details',
        'notes',
        'terms_conditions',
        'line_items',
        'metadata',
        'pdf_path',
        'stripe_invoice_id',
        'is_recurring',
        'recurring_period',
        'sent_at',
        'reminder_sent_at',
        'reminder_count',
        'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'paid_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'is_recurring' => 'boolean',
        'line_items' => 'array',
        'payment_details' => 'array',
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_PAID = 'paid';
    const STATUS_PARTIAL = 'partial';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    // Currency constants
    const CURRENCY_EUR = 'EUR';
    const CURRENCY_USD = 'USD';
    const CURRENCY_GBP = 'GBP';
    const CURRENCY_CHF = 'CHF';

    // Tax rates for Germany
    const TAX_RATE_STANDARD = 19.00;
    const TAX_RATE_REDUCED = 7.00;
    const TAX_RATE_EXEMPT = 0.00;

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (!$invoice->invoice_number) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }

            // Calculate totals if not set
            if ($invoice->line_items && is_array($invoice->line_items)) {
                $invoice->calculateTotals();
            }
        });

        static::updating(function ($invoice) {
            // Recalculate totals when line items change
            if ($invoice->isDirty('line_items')) {
                $invoice->calculateTotals();
            }

            // Update status based on payment
            if ($invoice->isDirty('paid_amount')) {
                $invoice->updatePaymentStatus();
            }
        });
    }

    /**
     * Generate unique invoice number (race-condition safe).
     *
     * Uses DB transaction with advisory lock to prevent duplicate numbers
     * when multiple invoices are created simultaneously.
     */
    public static function generateInvoiceNumber(): string
    {
        return \Illuminate\Support\Facades\DB::transaction(function () {
            $year = date('Y');
            $month = date('m');
            $lockKey = "invoice_number_{$year}_{$month}";

            // Advisory lock for this year/month combination
            $driver = \Illuminate\Support\Facades\DB::getDriverName();

            if ($driver === 'pgsql') {
                \Illuminate\Support\Facades\DB::statement(
                    "SELECT pg_advisory_xact_lock(hashtext(?))",
                    [$lockKey]
                );
            } else {
                // MySQL/MariaDB - use GET_LOCK with 5 second timeout
                \Illuminate\Support\Facades\DB::statement(
                    "SELECT GET_LOCK(?, 5)",
                    [$lockKey]
                );
            }

            // Now safe to count and generate number
            $count = self::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count() + 1;

            $invoiceNumber = sprintf('INV-%s-%s-%04d', $year, $month, $count);

            // Release MySQL lock (PostgreSQL releases automatically at transaction end)
            if ($driver !== 'pgsql') {
                \Illuminate\Support\Facades\DB::statement(
                    "SELECT RELEASE_LOCK(?)",
                    [$lockKey]
                );
            }

            return $invoiceNumber;
        });
    }

    /**
     * Calculate invoice totals
     */
    public function calculateTotals(): void
    {
        if (!$this->line_items || !is_array($this->line_items)) {
            return;
        }

        $subtotal = 0;

        foreach ($this->line_items as $item) {
            $quantity = $item['quantity'] ?? 1;
            $price = $item['price'] ?? 0;
            $subtotal += $quantity * $price;
        }

        $this->subtotal = $subtotal;

        // Calculate tax
        $taxableAmount = $subtotal - ($this->discount_amount ?? 0);
        $this->tax_amount = ($taxableAmount * ($this->tax_rate ?? self::TAX_RATE_STANDARD)) / 100;

        // Calculate total
        $this->total_amount = $taxableAmount + $this->tax_amount;

        // Calculate balance due
        $this->balance_due = $this->total_amount - ($this->paid_amount ?? 0);
    }

    /**
     * Update payment status based on paid amount
     */
    public function updatePaymentStatus(): void
    {
        if ($this->paid_amount >= $this->total_amount) {
            $this->status = self::STATUS_PAID;
            $this->paid_date = $this->paid_date ?? now();
        } elseif ($this->paid_amount > 0) {
            $this->status = self::STATUS_PARTIAL;
        } elseif ($this->due_date && $this->due_date->isPast()) {
            $this->status = self::STATUS_OVERDUE;
        }
    }

    /**
     * Check if invoice is editable
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING,
        ]);
    }

    /**
     * Check if invoice is payable
     */
    public function isPayable(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_SENT,
            self::STATUS_PARTIAL,
            self::STATUS_OVERDUE,
        ]);
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status !== self::STATUS_PAID &&
               $this->status !== self::STATUS_CANCELLED &&
               $this->due_date &&
               $this->due_date->isPast();
    }

    /**
     * Get days until due
     */
    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Get formatted invoice period
     */
    public function getInvoicePeriodAttribute(): string
    {
        if (!$this->line_items || !is_array($this->line_items)) {
            return $this->issue_date->format('F Y');
        }

        // Try to extract period from line items
        foreach ($this->line_items as $item) {
            if (isset($item['period_start']) && isset($item['period_end'])) {
                $start = Carbon::parse($item['period_start']);
                $end = Carbon::parse($item['period_end']);
                return $start->format('d.m.Y') . ' - ' . $end->format('d.m.Y');
            }
        }

        return $this->issue_date->format('F Y');
    }

    /**
     * Mark invoice as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(float $amount = null, array $paymentDetails = []): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_amount' => $amount ?? $this->total_amount,
            'paid_date' => now(),
            'payment_details' => array_merge($this->payment_details ?? [], $paymentDetails),
        ]);
    }

    /**
     * Cancel invoice
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'metadata' => array_merge($this->metadata ?? [], [
                'cancelled_at' => now()->toIso8601String(),
                'cancelled_reason' => $reason,
            ]),
        ]);
    }

    /**
     * Send reminder
     */
    public function sendReminder(): void
    {
        $this->increment('reminder_count');
        $this->update([
            'reminder_sent_at' => now(),
        ]);
    }

    /**
     * Clone invoice for recurring
     */
    public function cloneForRecurring(): self
    {
        $newInvoice = $this->replicate([
            'invoice_number',
            'status',
            'issue_date',
            'due_date',
            'paid_date',
            'paid_amount',
            'sent_at',
            'reminder_sent_at',
            'reminder_count',
            'pdf_path',
        ]);

        $newInvoice->status = self::STATUS_DRAFT;
        $newInvoice->issue_date = now();
        $newInvoice->due_date = now()->addDays(30);
        $newInvoice->paid_amount = 0;

        // Update period in line items if applicable
        if ($newInvoice->line_items && is_array($newInvoice->line_items)) {
            $lineItems = $newInvoice->line_items;
            foreach ($lineItems as &$item) {
                if (isset($item['period_start']) && isset($item['period_end'])) {
                    $periodStart = Carbon::parse($item['period_start']);
                    $periodEnd = Carbon::parse($item['period_end']);
                    $periodLength = $periodStart->diffInDays($periodEnd);

                    $item['period_start'] = now()->format('Y-m-d');
                    $item['period_end'] = now()->addDays($periodLength)->format('Y-m-d');
                }
            }
            $newInvoice->line_items = $lineItems;
        }

        return $newInvoice;
    }

    /**
     * Relationships
     */

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BalanceTransaction::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Scopes
     */

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_SENT,
            self::STATUS_PARTIAL,
            self::STATUS_OVERDUE,
        ]);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE)
            ->orWhere(function ($q) {
                $q->whereIn('status', [self::STATUS_SENT, self::STATUS_PARTIAL])
                    ->whereDate('due_date', '<', now());
            });
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('issue_date', [$startDate, $endDate]);
    }

    /**
     * Get status options
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Entwurf',
            self::STATUS_PENDING => 'Ausstehend',
            self::STATUS_SENT => 'Versendet',
            self::STATUS_PAID => 'Bezahlt',
            self::STATUS_PARTIAL => 'Teilweise bezahlt',
            self::STATUS_OVERDUE => 'Überfällig',
            self::STATUS_CANCELLED => 'Storniert',
            self::STATUS_REFUNDED => 'Erstattet',
        ];
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_PENDING => 'warning',
            self::STATUS_SENT => 'info',
            self::STATUS_PAID => 'success',
            self::STATUS_PARTIAL => 'primary',
            self::STATUS_OVERDUE => 'danger',
            self::STATUS_CANCELLED => 'gray',
            self::STATUS_REFUNDED => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Get currency symbol
     */
    public function getCurrencySymbolAttribute(): string
    {
        return match($this->currency) {
            self::CURRENCY_EUR => '€',
            self::CURRENCY_USD => '$',
            self::CURRENCY_GBP => '£',
            self::CURRENCY_CHF => 'CHF',
            default => $this->currency,
        };
    }
}