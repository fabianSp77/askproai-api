<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;

class SecureCompanyPricingTier extends Model
{
    use HasFactory;

    protected $table = 'company_pricing_tiers';

    /**
     * SECURITY: Explicitly define fillable fields to prevent mass assignment
     * Company ID and child company ID are NOT fillable - must be set explicitly
     */
    protected $fillable = [
        'pricing_type',
        'cost_price',
        'sell_price',
        'setup_fee',
        'monthly_fee',
        'included_minutes',
        'overage_rate',
        'is_active',
        'metadata'
    ];

    /**
     * SECURITY: Fields that should never be mass assigned
     */
    protected $guarded = [
        'id',
        'company_id',
        'child_company_id',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'cost_price' => 'decimal:4',
        'sell_price' => 'decimal:4',
        'setup_fee' => 'decimal:2',
        'monthly_fee' => 'decimal:2',
        'overage_rate' => 'decimal:4',
        'is_active' => 'boolean',
        'metadata' => 'array'
    ];

    /**
     * Validation rules for the model
     */
    public static $rules = [
        'pricing_type' => 'required|in:inbound,outbound,sms,monthly,setup',
        'cost_price' => 'required|numeric|min:0|max:999.9999',
        'sell_price' => 'required|numeric|min:0|max:999.9999',
        'setup_fee' => 'nullable|numeric|min:0|max:99999.99',
        'monthly_fee' => 'nullable|numeric|min:0|max:99999.99',
        'included_minutes' => 'nullable|integer|min:0|max:999999',
        'overage_rate' => 'nullable|numeric|min:0|max:999.9999',
        'is_active' => 'boolean'
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);

        // SECURITY: Additional validation on save
        static::saving(function (self $pricingTier) {
            // Validate business logic
            if ($pricingTier->sell_price < $pricingTier->cost_price) {
                throw new \InvalidArgumentException('Sell price cannot be lower than cost price');
            }

            // Ensure overage rate is set
            if (!$pricingTier->overage_rate) {
                $pricingTier->overage_rate = $pricingTier->sell_price;
            }

            // Prevent negative values
            $numericFields = ['cost_price', 'sell_price', 'setup_fee', 'monthly_fee', 'overage_rate'];
            foreach ($numericFields as $field) {
                if ($pricingTier->$field < 0) {
                    throw new \InvalidArgumentException("Field {$field} cannot be negative");
                }
            }

            // Validate company relationships if setting them
            if ($pricingTier->isDirty('company_id') || $pricingTier->isDirty('child_company_id')) {
                $pricingTier->validateCompanyRelationships();
            }
        });
    }

    /**
     * SECURITY: Validate company relationships
     */
    private function validateCompanyRelationships(): void
    {
        if (!$this->company_id) {
            throw new \InvalidArgumentException('Company ID is required');
        }

        // If child company is set, validate the relationship
        if ($this->child_company_id) {
            $childCompany = Company::find($this->child_company_id);
            
            if (!$childCompany) {
                throw new \InvalidArgumentException('Invalid child company');
            }

            if ($childCompany->parent_company_id !== $this->company_id) {
                throw new \InvalidArgumentException('Child company does not belong to parent company');
            }
        }
    }

    /**
     * SECURITY: Scope to ensure users only see their own pricing
     */
    public function scopeForCurrentUser(Builder $query): Builder
    {
        $user = auth()->user();
        
        if (!$user) {
            return $query->whereRaw('1 = 0'); // No results
        }

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        // Resellers see their own pricing
        if ($user->hasRole(['reseller_owner', 'reseller_admin'])) {
            return $query->where('company_id', $user->company_id);
        }

        // Regular users don't see cost prices
        return $query->whereRaw('1 = 0');
    }

    /**
     * Get the reseller company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the client company
     */
    public function childCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'child_company_id');
    }

    /**
     * Get pricing margins
     */
    public function margins(): HasMany
    {
        return $this->hasMany(PricingMargin::class);
    }

    /**
     * Calculate margin for this pricing tier with validation
     */
    public function calculateMargin(): array
    {
        // Validate data integrity
        if ($this->cost_price < 0 || $this->sell_price < 0) {
            throw new \RuntimeException('Invalid pricing data');
        }

        $marginAmount = $this->sell_price - $this->cost_price;
        $marginPercentage = $this->cost_price > 0 
            ? ($marginAmount / $this->cost_price) * 100 
            : 0;

        return [
            'amount' => round($marginAmount, 4),
            'percentage' => round($marginPercentage, 2)
        ];
    }

    /**
     * Calculate cost for given minutes with overflow protection
     */
    public function calculateCost(float $minutes): array
    {
        // Validate input
        if ($minutes < 0) {
            throw new \InvalidArgumentException('Minutes cannot be negative');
        }

        // Prevent overflow
        $minutes = min($minutes, 999999999);
        
        $billableMinutes = max(0, $minutes - $this->included_minutes);
        
        if ($billableMinutes <= 0) {
            return [
                'base_cost' => 0,
                'sell_cost' => 0,
                'margin' => 0,
                'included_minutes_used' => $minutes
            ];
        }

        // Use BCMath for precise calculations
        $baseCost = bcmul((string)$billableMinutes, (string)$this->cost_price, 4);
        $sellCost = bcmul((string)$billableMinutes, (string)$this->sell_price, 4);
        $margin = bcsub($sellCost, $baseCost, 4);

        return [
            'base_cost' => round((float)$baseCost, 2),
            'sell_cost' => round((float)$sellCost, 2),
            'margin' => round((float)$margin, 2),
            'included_minutes_used' => $this->included_minutes,
            'billable_minutes' => $billableMinutes
        ];
    }

    /**
     * SECURITY: Create with explicit authorization check
     */
    public static function createForCompany(Company $company, array $data): self
    {
        $user = auth()->user();
        
        if (!$user || !$user->can('manage_pricing', $company)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Unauthorized to create pricing');
        }

        // Filter allowed fields only
        $allowedData = array_intersect_key($data, array_flip((new self)->fillable));
        
        $pricingTier = new self($allowedData);
        $pricingTier->company_id = $company->id;
        
        if (isset($data['child_company_id'])) {
            // Validate child company belongs to parent
            $childCompany = Company::findOrFail($data['child_company_id']);
            if ($childCompany->parent_company_id !== $company->id) {
                throw new \InvalidArgumentException('Invalid child company');
            }
            $pricingTier->child_company_id = $childCompany->id;
        }
        
        $pricingTier->save();
        
        return $pricingTier;
    }
}