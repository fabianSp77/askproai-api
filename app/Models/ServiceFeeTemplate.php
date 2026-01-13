<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Service Fee Template - Preiskatalog für standardisierte Service-Gebühren
 *
 * Definiert verfügbare Services mit Standard-Preisen.
 * Kunden können individuelle Preise über CompanyServicePricing erhalten.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $category
 * @property string|null $subcategory
 * @property float $default_price
 * @property string $pricing_type
 * @property string|null $unit_name
 * @property float|null $min_price
 * @property float|null $max_price
 * @property bool $is_negotiable
 * @property bool $requires_approval
 * @property int $sort_order
 * @property bool $is_active
 * @property bool $is_featured
 * @property array|null $metadata
 */
class ServiceFeeTemplate extends Model
{
    use HasFactory;

    // ─────────────────────────────────────────────────────────────
    // Categories
    // ─────────────────────────────────────────────────────────────

    public const CATEGORY_SETUP = 'setup';
    public const CATEGORY_CHANGE = 'change';
    public const CATEGORY_SUPPORT = 'support';
    public const CATEGORY_CAPACITY = 'capacity';
    public const CATEGORY_INTEGRATION = 'integration';
    public const CATEGORY_TRAINING = 'training';

    // ─────────────────────────────────────────────────────────────
    // Pricing Types
    // ─────────────────────────────────────────────────────────────

    public const PRICING_ONE_TIME = 'one_time';
    public const PRICING_MONTHLY = 'monthly';
    public const PRICING_YEARLY = 'yearly';
    public const PRICING_PER_HOUR = 'per_hour';
    public const PRICING_PER_UNIT = 'per_unit';

    // ─────────────────────────────────────────────────────────────
    // Model Configuration
    // ─────────────────────────────────────────────────────────────

    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'subcategory',
        'default_price',
        'pricing_type',
        'unit_name',
        'min_price',
        'max_price',
        'is_negotiable',
        'requires_approval',
        'sort_order',
        'is_active',
        'is_featured',
        'metadata',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
        'is_negotiable' => 'boolean',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'pricing_type' => self::PRICING_ONE_TIME,
        'is_negotiable' => true,
        'requires_approval' => false,
        'sort_order' => 0,
        'is_active' => true,
        'is_featured' => false,
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function companyPricing(): HasMany
    {
        return $this->hasMany(CompanyServicePricing::class, 'template_id');
    }

    public function fees(): HasMany
    {
        return $this->hasMany(ServiceChangeFee::class, 'template_id');
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('category')->orderBy('sort_order')->orderBy('name');
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Get price for a specific company (with override if exists)
     */
    public function getPriceForCompany(Company $company, ?\DateTimeInterface $date = null): float
    {
        $date = $date ?? now();

        $customPricing = $this->companyPricing()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->where('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $date);
            })
            ->orderBy('effective_from', 'desc')
            ->first();

        if ($customPricing) {
            return (float) $customPricing->final_price;
        }

        return (float) $this->default_price;
    }

    /**
     * Check if company has custom pricing
     */
    public function hasCustomPricingFor(Company $company): bool
    {
        return $this->companyPricing()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get display name with pricing info
     */
    public function getDisplayNameAttribute(): string
    {
        $price = number_format($this->default_price, 2, ',', '.');
        $suffix = match ($this->pricing_type) {
            self::PRICING_MONTHLY => '/Monat',
            self::PRICING_YEARLY => '/Jahr',
            self::PRICING_PER_HOUR => '/Stunde',
            self::PRICING_PER_UNIT => '/' . ($this->unit_name ?? 'Einheit'),
            default => '',
        };

        return "{$this->name} (€{$price}{$suffix})";
    }

    /**
     * Get pricing type label
     */
    public function getPricingTypeLabelAttribute(): string
    {
        return self::getPricingTypeOptions()[$this->pricing_type] ?? $this->pricing_type;
    }

    // ─────────────────────────────────────────────────────────────
    // Static Options
    // ─────────────────────────────────────────────────────────────

    public static function getCategoryOptions(): array
    {
        return [
            self::CATEGORY_SETUP => 'Einrichtung & Setup',
            self::CATEGORY_CHANGE => 'Änderungen & Anpassungen',
            self::CATEGORY_SUPPORT => 'Support & Wartung',
            self::CATEGORY_CAPACITY => 'Kapazität & Skalierung',
            self::CATEGORY_INTEGRATION => 'Integrationen',
            self::CATEGORY_TRAINING => 'Schulung & Beratung',
        ];
    }

    public static function getPricingTypeOptions(): array
    {
        return [
            self::PRICING_ONE_TIME => 'Einmalig',
            self::PRICING_MONTHLY => 'Monatlich',
            self::PRICING_YEARLY => 'Jährlich',
            self::PRICING_PER_HOUR => 'Pro Stunde',
            self::PRICING_PER_UNIT => 'Pro Einheit',
        ];
    }

    public static function getCategoryColors(): array
    {
        return [
            self::CATEGORY_SETUP => 'success',
            self::CATEGORY_CHANGE => 'warning',
            self::CATEGORY_SUPPORT => 'info',
            self::CATEGORY_CAPACITY => 'primary',
            self::CATEGORY_INTEGRATION => 'gray',
            self::CATEGORY_TRAINING => 'secondary',
        ];
    }
}
