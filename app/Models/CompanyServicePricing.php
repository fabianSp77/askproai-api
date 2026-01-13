<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Company Service Pricing - Kundenspezifische Preise mit Zeiträumen
 *
 * Ermöglicht individuelle Preisvereinbarungen pro Kunde:
 * - Abweichende Preise vom Standard-Katalog
 * - Zeitlich begrenzte Sonderkonditionen
 * - Rabattvereinbarungen
 * - Vertragsbezogene Preise
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $template_id
 * @property string|null $custom_code
 * @property string|null $custom_name
 * @property string|null $custom_description
 * @property float $price
 * @property float|null $discount_percentage
 * @property float $final_price (computed)
 * @property \Carbon\Carbon $effective_from
 * @property \Carbon\Carbon|null $effective_until
 * @property string|null $contract_reference
 * @property string|null $notes
 * @property string|null $approved_by_name
 * @property bool $is_active
 * @property int|null $created_by
 */
class CompanyServicePricing extends Model
{
    use HasFactory, BelongsToCompany;

    protected $table = 'company_service_pricing';

    protected $fillable = [
        'company_id',
        'template_id',
        'custom_code',
        'custom_name',
        'custom_description',
        'price',
        'discount_percentage',
        'effective_from',
        'effective_until',
        'contract_reference',
        'notes',
        'approved_by_name',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'final_price' => 'decimal:2',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function template(): BelongsTo
    {
        return $this->belongsTo(ServiceFeeTemplate::class, 'template_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrentlyValid($query, ?\DateTimeInterface $date = null)
    {
        $date = $date ?? now();

        return $query
            ->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $date);
            });
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForTemplate($query, int $templateId)
    {
        return $query->where('template_id', $templateId);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query
            ->whereNotNull('effective_until')
            ->whereBetween('effective_until', [now(), now()->addDays($days)]);
    }

    // ─────────────────────────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────────────────────────

    /**
     * Get the service name (from template or custom)
     */
    public function getNameAttribute(): string
    {
        if ($this->template) {
            return $this->template->name;
        }

        return $this->custom_name ?? 'Unbenannter Service';
    }

    /**
     * Get the service code (from template or custom)
     */
    public function getCodeAttribute(): string
    {
        if ($this->template) {
            return $this->template->code;
        }

        return $this->custom_code ?? 'CUSTOM';
    }

    /**
     * Get description (from template or custom)
     */
    public function getDescriptionAttribute(): ?string
    {
        if ($this->custom_description) {
            return $this->custom_description;
        }

        return $this->template?->description;
    }

    /**
     * Get the default/template price for comparison
     */
    public function getDefaultPriceAttribute(): ?float
    {
        return $this->template?->default_price;
    }

    /**
     * Get the discount amount in EUR
     */
    public function getDiscountAmountAttribute(): float
    {
        if (!$this->discount_percentage) {
            return 0;
        }

        return round($this->price * ($this->discount_percentage / 100), 2);
    }

    /**
     * Get savings compared to default price
     */
    public function getSavingsAttribute(): float
    {
        if (!$this->default_price) {
            return 0;
        }

        return max(0, $this->default_price - $this->final_price);
    }

    /**
     * Get savings percentage compared to default
     */
    public function getSavingsPercentageAttribute(): float
    {
        if (!$this->default_price || $this->default_price == 0) {
            return 0;
        }

        return round(($this->savings / $this->default_price) * 100, 1);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Check if this pricing is currently valid
     */
    public function isCurrentlyValid(?\DateTimeInterface $date = null): bool
    {
        $date = $date ?? now();

        if ($this->effective_from > $date) {
            return false;
        }

        if ($this->effective_until && $this->effective_until < $date) {
            return false;
        }

        return $this->is_active;
    }

    /**
     * Check if this pricing expires soon
     */
    public function expiresSoon(int $days = 30): bool
    {
        if (!$this->effective_until) {
            return false;
        }

        return $this->effective_until->isBetween(now(), now()->addDays($days));
    }

    /**
     * Get validity period as human readable string
     */
    public function getValidityPeriodAttribute(): string
    {
        $from = $this->effective_from->format('d.m.Y');

        if (!$this->effective_until) {
            return "Ab {$from} (unbefristet)";
        }

        $until = $this->effective_until->format('d.m.Y');
        return "{$from} – {$until}";
    }

    /**
     * Check if pricing overlaps with another period for same template
     */
    public function overlapsWithExisting(): bool
    {
        $query = static::query()
            ->where('company_id', $this->company_id)
            ->where('is_active', true)
            ->where('id', '!=', $this->id ?? 0);

        if ($this->template_id) {
            $query->where('template_id', $this->template_id);
        } else {
            $query->where('custom_code', $this->custom_code);
        }

        // Check for overlap
        return $query->where(function ($q) {
            $q->where(function ($q2) {
                // New period starts within existing
                $q2->where('effective_from', '<=', $this->effective_from)
                    ->where(function ($q3) {
                        $q3->whereNull('effective_until')
                            ->orWhere('effective_until', '>=', $this->effective_from);
                    });
            })->orWhere(function ($q2) {
                // New period ends within existing
                if ($this->effective_until) {
                    $q2->where('effective_from', '<=', $this->effective_until)
                        ->where(function ($q3) {
                            $q3->whereNull('effective_until')
                                ->orWhere('effective_until', '>=', $this->effective_until);
                        });
                }
            })->orWhere(function ($q2) {
                // New period completely contains existing
                $q2->where('effective_from', '>=', $this->effective_from)
                    ->where(function ($q3) {
                        if ($this->effective_until) {
                            $q3->where('effective_from', '<=', $this->effective_until);
                        }
                    });
            });
        })->exists();
    }

    // ─────────────────────────────────────────────────────────────
    // Static Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Get current pricing for a company and template
     */
    public static function getCurrentForCompanyAndTemplate(
        int $companyId,
        int $templateId,
        ?\DateTimeInterface $date = null
    ): ?self {
        return static::query()
            ->forCompany($companyId)
            ->forTemplate($templateId)
            ->active()
            ->currentlyValid($date)
            ->orderBy('effective_from', 'desc')
            ->first();
    }

    /**
     * Get all current pricing for a company
     */
    public static function getAllCurrentForCompany(
        int $companyId,
        ?\DateTimeInterface $date = null
    ) {
        return static::query()
            ->forCompany($companyId)
            ->active()
            ->currentlyValid($date)
            ->with('template')
            ->get();
    }
}
