<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Service Change Fee Model
 *
 * Tracks professional service fees for configuration changes:
 * - AI Agent prompt/behavior modifications
 * - Call flow changes
 * - Service Gateway configuration
 * - Custom integrations
 *
 * These are manually entered fees, NOT automated event-based charges.
 *
 * @property int $id
 * @property int $company_id
 * @property string $category
 * @property float $amount
 * @property string $currency
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property int|null $invoice_id
 * @property int|null $invoice_item_id
 * @property int|null $transaction_id
 * @property \Carbon\Carbon|null $service_date
 * @property float|null $hours_worked
 * @property float|null $hourly_rate
 * @property string|null $related_entity_type
 * @property int|null $related_entity_id
 * @property int|null $created_by
 * @property int|null $approved_by
 * @property \Carbon\Carbon|null $approved_at
 * @property array|null $metadata
 * @property string|null $internal_notes
 */
class ServiceChangeFee extends Model
{
    use HasFactory, BelongsToCompany;

    // Categories
    public const CATEGORY_MINOR_CHANGE = 'minor_change';        // €250 - Small adjustments
    public const CATEGORY_FLOW_CHANGE = 'flow_change';          // €500 - Call Flow / JSON changes
    public const CATEGORY_AGENT_CHANGE = 'agent_change';        // €500 - AI Agent modifications
    public const CATEGORY_GATEWAY_CONFIG = 'gateway_config';    // €500 - Service Gateway config
    public const CATEGORY_INTEGRATION = 'integration';          // Custom - Integrations
    public const CATEGORY_CPS_UPGRADE = 'cps_upgrade';          // €200/month - Concurrent calls
    public const CATEGORY_SUPPORT = 'support';                  // Hourly - Technical support
    public const CATEGORY_CUSTOM = 'custom';                    // Custom pricing

    // Statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_INVOICED = 'invoiced';
    public const STATUS_PAID = 'paid';
    public const STATUS_WAIVED = 'waived';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id',
        'category',
        'template_id',
        'company_pricing_id',
        'amount',
        'template_price',
        'discount_amount',
        'currency',
        'title',
        'description',
        'status',
        'is_recurring',
        'recurring_interval',
        'recurring_until',
        'invoice_id',
        'invoice_item_id',
        'transaction_id',
        'service_date',
        'hours_worked',
        'hourly_rate',
        'related_entity_type',
        'related_entity_id',
        'created_by',
        'approved_by',
        'approved_at',
        'metadata',
        'internal_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'template_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'hours_worked' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'service_date' => 'date',
        'recurring_until' => 'date',
        'approved_at' => 'datetime',
        'is_recurring' => 'boolean',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'currency' => 'EUR',
        'status' => self::STATUS_PENDING,
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the related entity (polymorphic)
     */
    public function relatedEntity(): MorphTo
    {
        return $this->morphTo('related_entity');
    }

    /**
     * Get the template this fee was created from
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ServiceFeeTemplate::class, 'template_id');
    }

    /**
     * Get the company-specific pricing used
     */
    public function companyPricing(): BelongsTo
    {
        return $this->belongsTo(CompanyServicePricing::class, 'company_pricing_id');
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInvoiced($query)
    {
        return $query->where('status', self::STATUS_INVOICED);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeUnbilled($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING]);
    }

    // ─────────────────────────────────────────────────────────────
    // Helper Methods
    // ─────────────────────────────────────────────────────────────

    /**
     * Get amount in cents
     */
    public function getAmountCentsAttribute(): int
    {
        return (int) ($this->amount * 100);
    }

    /**
     * Check if fee can be edited
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING]);
    }

    /**
     * Check if fee can be invoiced
     */
    public function canBeInvoiced(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Mark as invoiced
     */
    public function markAsInvoiced(Invoice $invoice, ?int $invoiceItemId = null): self
    {
        $this->update([
            'status' => self::STATUS_INVOICED,
            'invoice_id' => $invoice->id,
            'invoice_item_id' => $invoiceItemId,
        ]);

        return $this;
    }

    /**
     * Mark as paid
     */
    public function markAsPaid(?Transaction $transaction = null): self
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'transaction_id' => $transaction?->id,
        ]);

        return $this;
    }

    /**
     * Waive the fee
     */
    public function waive(int $userId, ?string $reason = null): self
    {
        $this->update([
            'status' => self::STATUS_WAIVED,
            'approved_by' => $userId,
            'approved_at' => now(),
            'internal_notes' => $reason
                ? ($this->internal_notes ? $this->internal_notes . "\n\n" : '') . "Waived: {$reason}"
                : $this->internal_notes,
        ]);

        return $this;
    }

    /**
     * Approve the fee
     */
    public function approve(int $userId): self
    {
        $this->update([
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        return $this;
    }

    /**
     * Calculate amount from hours and rate
     */
    public function calculateFromHours(): float
    {
        if ($this->hours_worked && $this->hourly_rate) {
            return round($this->hours_worked * $this->hourly_rate, 2);
        }

        return $this->amount;
    }

    // ─────────────────────────────────────────────────────────────
    // Static Options
    // ─────────────────────────────────────────────────────────────

    public static function getCategoryOptions(): array
    {
        return [
            self::CATEGORY_MINOR_CHANGE => 'Kleine Anpassung (€250)',
            self::CATEGORY_FLOW_CHANGE => 'Call Flow / JSON Änderung (€500)',
            self::CATEGORY_AGENT_CHANGE => 'AI Agent Anpassung (€500)',
            self::CATEGORY_GATEWAY_CONFIG => 'Service Gateway Konfiguration (€500)',
            self::CATEGORY_INTEGRATION => 'Integration (individuell)',
            self::CATEGORY_CPS_UPGRADE => 'CPS-Upgrade: Parallele Gespräche (€200/Monat)',
            self::CATEGORY_SUPPORT => 'Technischer Support (Stundenbasis)',
            self::CATEGORY_CUSTOM => 'Sonstiges (individuell)',
        ];
    }

    /**
     * Get default/suggested prices for each category
     */
    public static function getCategoryDefaultPrices(): array
    {
        return [
            self::CATEGORY_MINOR_CHANGE => 250.00,
            self::CATEGORY_FLOW_CHANGE => 500.00,
            self::CATEGORY_AGENT_CHANGE => 500.00,
            self::CATEGORY_GATEWAY_CONFIG => 500.00,
            self::CATEGORY_INTEGRATION => 500.00,  // Starting price
            self::CATEGORY_CPS_UPGRADE => 200.00,  // Per month
            self::CATEGORY_SUPPORT => 75.00,       // Per hour
            self::CATEGORY_CUSTOM => 0.00,
        ];
    }

    /**
     * Check if category is recurring (monthly)
     */
    public static function isRecurringCategory(string $category): bool
    {
        return in_array($category, [
            self::CATEGORY_CPS_UPGRADE,
        ]);
    }

    /**
     * Get category description/help text
     */
    public static function getCategoryDescriptions(): array
    {
        return [
            self::CATEGORY_MINOR_CHANGE => 'Kleine Änderungen die trotzdem getestet werden müssen',
            self::CATEGORY_FLOW_CHANGE => 'Änderungen am Call Flow, JSON-Daten oder Konfigurationsdateien',
            self::CATEGORY_AGENT_CHANGE => 'Prompt-Anpassungen, Verhaltensänderungen am AI Agent',
            self::CATEGORY_GATEWAY_CONFIG => 'Service Gateway Einstellungen, Webhook-Konfiguration',
            self::CATEGORY_INTEGRATION => 'Neue Integrationen, API-Anbindungen, Custom Development',
            self::CATEGORY_CPS_UPGRADE => 'Erhöhung der parallelen Anrufe pro Sekunde (Standard: 1, Upgrade: 5)',
            self::CATEGORY_SUPPORT => 'Technischer Support auf Stundenbasis',
            self::CATEGORY_CUSTOM => 'Individuelle Leistungen nach Absprache',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Ausstehend',
            self::STATUS_INVOICED => 'In Rechnung gestellt',
            self::STATUS_PAID => 'Bezahlt',
            self::STATUS_WAIVED => 'Erlassen',
            self::STATUS_CANCELLED => 'Storniert',
        ];
    }

    public static function getStatusColors(): array
    {
        return [
            self::STATUS_PENDING => 'warning',
            self::STATUS_INVOICED => 'info',
            self::STATUS_PAID => 'success',
            self::STATUS_WAIVED => 'gray',
            self::STATUS_CANCELLED => 'danger',
        ];
    }
}
