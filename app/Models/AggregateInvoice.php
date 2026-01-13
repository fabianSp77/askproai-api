<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class AggregateInvoice extends Model
{
    use HasFactory, SoftDeletes;

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_PAID = 'paid';
    public const STATUS_VOID = 'void';
    public const STATUS_UNCOLLECTIBLE = 'uncollectible';

    protected $fillable = [
        'partner_company_id',
        'stripe_invoice_id',
        'stripe_customer_id',
        'stripe_hosted_invoice_url',
        'stripe_pdf_url',
        'invoice_number',
        'billing_period_start',
        'billing_period_end',
        'subtotal_cents',
        'tax_cents',
        'total_cents',
        'currency',
        'tax_rate',
        'status',
        'finalized_at',
        'sent_at',
        'paid_at',
        'due_at',
        'metadata',
        'notes',
        'discount_cents',
        'discount_description',
    ];

    protected $casts = [
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'subtotal_cents' => 'integer',
        'discount_cents' => 'integer',
        'tax_cents' => 'integer',
        'total_cents' => 'integer',
        'tax_rate' => 'decimal:2',
        'finalized_at' => 'datetime',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'due_at' => 'datetime',
        'metadata' => 'json',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * The partner company that receives this invoice.
     */
    public function partnerCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'partner_company_id');
    }

    /**
     * Line items on this invoice.
     */
    public function items(): HasMany
    {
        return $this->hasMany(AggregateInvoiceItem::class);
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Invoices for a specific billing period.
     *
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     */
    public function scopeForPeriod(\Illuminate\Database\Eloquent\Builder $query, Carbon $periodStart, Carbon $periodEnd): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('billing_period_start', $periodStart->format('Y-m-d'))
            ->where('billing_period_end', $periodEnd->format('Y-m-d'));
    }

    /**
     * Invoices for a specific month.
     *
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     */
    public function scopeForMonth(\Illuminate\Database\Eloquent\Builder $query, int $year, int $month): \Illuminate\Database\Eloquent\Builder
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return $query->forPeriod($start, $end);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     */
    public function scopeDraft(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     */
    public function scopeOpen(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     */
    public function scopePaid(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     */
    public function scopeUnpaid(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_OPEN]);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     */
    public function scopeOverdue(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', self::STATUS_OPEN)
            ->where('due_at', '<', now());
    }

    // ========================================
    // ACCESSORS
    // ========================================

    /**
     * Get subtotal as decimal (EUR).
     */
    public function getSubtotalAttribute(): float
    {
        return $this->subtotal_cents / 100;
    }

    /**
     * Get tax amount as decimal (EUR).
     */
    public function getTaxAttribute(): float
    {
        return $this->tax_cents / 100;
    }

    /**
     * Get total as decimal (EUR).
     */
    public function getTotalAttribute(): float
    {
        return $this->total_cents / 100;
    }

    /**
     * Get discount as decimal (EUR).
     */
    public function getDiscountAttribute(): float
    {
        return ($this->discount_cents ?? 0) / 100;
    }

    /**
     * Set discount from decimal (EUR).
     */
    public function setDiscountAttribute(float $value): void
    {
        $this->attributes['discount_cents'] = (int) round($value * 100);
    }

    /**
     * Formatted total for display.
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total, 2, ',', '.') . ' €';
    }

    /**
     * Get billing period display string.
     */
    public function getBillingPeriodDisplayAttribute(): string
    {
        return $this->billing_period_start->format('d.m.Y') .
            ' - ' .
            $this->billing_period_end->format('d.m.Y');
    }

    /**
     * Check if invoice is editable.
     */
    public function getIsEditableAttribute(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if invoice is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === self::STATUS_OPEN &&
            $this->due_at &&
            $this->due_at->isPast();
    }

    // ========================================
    // METHODS
    // ========================================

    /**
     * Generate the next invoice number (race-condition safe).
     *
     * Uses DB transaction with advisory lock to prevent duplicate numbers
     * when multiple invoices are created simultaneously.
     */
    public static function generateInvoiceNumber(): string
    {
        return \Illuminate\Support\Facades\DB::transaction(function () {
            $prefix = 'AGG';
            $year = now()->format('Y');
            $month = now()->format('m');
            $lockKey = "invoice_number_{$year}_{$month}";

            // Advisory lock for this year/month combination
            // MySQL: GET_LOCK, PostgreSQL: pg_advisory_lock
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
            $count = self::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count() + 1;

            $invoiceNumber = sprintf('%s-%s-%s-%03d', $prefix, $year, $month, $count);

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
     * Recalculate totals from items.
     *
     * German VAT Compliance: Tax is calculated on the net amount AFTER discounts.
     * Formula: taxable = subtotal - discount, tax = taxable * rate, total = taxable + tax
     */
    public function calculateTotals(): self
    {
        $subtotal = $this->items()->sum('amount_cents');

        // German VAT: Tax must be calculated AFTER discount is applied
        $discount = $this->discount_cents ?? 0;
        $taxableAmount = max(0, $subtotal - $discount);

        $tax = (int) round($taxableAmount * ($this->tax_rate / 100));
        $total = $taxableAmount + $tax;

        $this->update([
            'subtotal_cents' => $subtotal,
            'tax_cents' => $tax,
            'total_cents' => $total,
        ]);

        return $this;
    }

    /**
     * Get items grouped by company.
     */
    public function getItemsByCompany(): array
    {
        $grouped = [];

        foreach ($this->items()->with('company')->get() as $item) {
            $companyId = $item->company_id;

            if (!isset($grouped[$companyId])) {
                $grouped[$companyId] = [
                    'company' => $item->company,
                    'items' => [],
                    'subtotal_cents' => 0,
                ];
            }

            $grouped[$companyId]['items'][] = $item;
            $grouped[$companyId]['subtotal_cents'] += $item->amount_cents;
        }

        return $grouped;
    }

    /**
     * Mark invoice as finalized.
     */
    public function finalize(): self
    {
        if ($this->status !== self::STATUS_DRAFT) {
            throw new \Exception('Only draft invoices can be finalized');
        }

        $this->calculateTotals();

        $this->update([
            'status' => self::STATUS_OPEN,
            'finalized_at' => now(),
            'due_at' => now()->addDays(
                $this->partnerCompany->partner_payment_terms_days ?? 14
            ),
        ]);

        return $this;
    }

    /**
     * Mark invoice as sent.
     */
    public function markAsSent(): self
    {
        $this->update(['sent_at' => now()]);
        return $this;
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(): self
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
        ]);

        return $this;
    }

    /**
     * Void the invoice.
     */
    public function void(): self
    {
        if ($this->status === self::STATUS_PAID) {
            throw new \Exception('Paid invoices cannot be voided');
        }

        $this->update(['status' => self::STATUS_VOID]);
        return $this;
    }

    /**
     * Mark as uncollectible.
     */
    public function markAsUncollectible(): self
    {
        $this->update(['status' => self::STATUS_UNCOLLECTIBLE]);
        return $this;
    }

    /**
     * Get status badge color for Filament.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_OPEN => $this->is_overdue ? 'danger' : 'warning',
            self::STATUS_PAID => 'success',
            self::STATUS_VOID => 'gray',
            self::STATUS_UNCOLLECTIBLE => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get status label for display.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Entwurf',
            self::STATUS_OPEN => $this->is_overdue ? 'Überfällig' : 'Offen',
            self::STATUS_PAID => 'Bezahlt',
            self::STATUS_VOID => 'Storniert',
            self::STATUS_UNCOLLECTIBLE => 'Uneinbringlich',
            default => $this->status,
        };
    }
}
