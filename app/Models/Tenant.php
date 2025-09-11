<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class Tenant extends Model
{
    use HasFactory;
    
    // Using auto-incrementing bigint, not UUID
    public $incrementing = true;
    protected $keyType   = 'int';

    // API key security: store hashed, generate plain
    protected $fillable = [
        'name', 
        'slug',
        'parent_tenant_id',
        'tenant_type',
        'balance_cents',  // Add this to allow setting initial balance
        'commission_rate',
        'base_cost_cents',
        'reseller_markup_cents',
        'can_set_prices',
        'min_markup_percent',
        'max_markup_percent',
        'billing_mode',
        'auto_commission_payout',
        'commission_payout_threshold_cents',
        'pricing_plan_id'  // Also add this for pricing plan assignment
    ];
    
    protected $hidden = ['api_key_hash'];
    
    protected $casts = [
        'can_set_prices' => 'boolean',
        'auto_commission_payout' => 'boolean',
        'commission_rate' => 'decimal:2'
    ];
    
    // Transient attribute for plain API key (not saved to database)
    public $plain_api_key;

    /* -------------------------------------------------------------------- */
    protected static function booted(): void
    {
        static::creating(function (self $tenant) {
            // Auto-increment ID is handled by database, no need to set it
            $tenant->slug ??= Str::slug($tenant->name);
            
            // Generate plain API key only on creation
            if (empty($tenant->api_key_hash)) {
                $plainApiKey = 'ask_' . Str::random(32);
                $tenant->api_key_hash = Hash::make($plainApiKey);
                
                // Store plain key temporarily as a transient property (not an attribute)
                // This won't be saved to database
                $tenant->plain_api_key = $plainApiKey;
            }
        });
        
        static::saving(function (self $tenant) {
            // Ensure plain_api_key is never saved to database
            unset($tenant->attributes['plain_api_key']);
        });
    }

    /**
     * Verify API key against hash
     */
    public function verifyApiKey(string $plainKey): bool
    {
        return Hash::check($plainKey, $this->api_key_hash);
    }

    /**
     * Generate new API key (returns plain key, stores hash)
     */
    public function regenerateApiKey(): string
    {
        $plainApiKey = 'ask_' . Str::random(32);
        $this->api_key_hash = Hash::make($plainApiKey);
        $this->save();
        
        return $plainApiKey;
    }

    /**
     * Find tenant by API key (secure lookup)
     */
    public static function findByApiKey(string $plainKey): ?self
    {
        // Get all tenants and verify hash (secure but not scalable)
        foreach (self::all() as $tenant) {
            if ($tenant->verifyApiKey($plainKey)) {
                return $tenant;
            }
        }
        
        return null;
    }

    /* -------------------------------------------------------------------- */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
    
    /**
     * Billing relationships
     */
    public function pricingPlan()
    {
        return $this->belongsTo(PricingPlan::class);
    }
    
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
    
    public function topups()
    {
        return $this->hasMany(BalanceTopup::class);
    }
    
    /**
     * Multi-tier relationships
     */
    public function parentTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'parent_tenant_id');
    }
    
    public function childTenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'parent_tenant_id');
    }
    
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
    
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
    
    public function assignedCustomers(): HasMany
    {
        return $this->hasMany(Customer::class, 'assigned_by_tenant_id');
    }
    
    public function commissionLedger(): HasMany
    {
        return $this->hasMany(CommissionLedger::class, 'reseller_tenant_id');
    }
    
    public function payouts(): HasMany
    {
        return $this->hasMany(ResellerPayout::class, 'reseller_tenant_id');
    }
    
    /**
     * Check if this tenant is a reseller
     */
    public function isReseller(): bool
    {
        return $this->tenant_type === 'reseller';
    }
    
    /**
     * Check if this tenant is a platform
     */
    public function isPlatform(): bool
    {
        return $this->tenant_type === 'platform';
    }
    
    /**
     * Check if this tenant has a reseller
     */
    public function hasReseller(): bool
    {
        return $this->parent_tenant_id !== null && 
               $this->tenant_type === 'reseller_customer';
    }
    
    /**
     * Get the effective price for a service considering reseller markup
     */
    public function getEffectivePrice(string $service, int $baseCostCents = null): int
    {
        // If customer of a reseller, get reseller's price
        if ($this->hasReseller() && $this->parentTenant) {
            $baseCost = $baseCostCents ?? $this->parentTenant->base_cost_cents;
            $markup = $this->parentTenant->reseller_markup_cents ?? 0;
            return $baseCost + $markup;
        }
        
        // Direct customer - use standard pricing
        if ($this->pricingPlan) {
            switch ($service) {
                case 'call':
                    return $this->pricingPlan->price_per_minute_cents;
                case 'api':
                    return $this->pricingPlan->price_per_call_cents;
                case 'appointment':
                    return $this->pricingPlan->price_per_appointment_cents;
            }
        }
        
        // Fallback to config defaults
        return config('billing.price_per_second_cents', 3) * 60; // Default 3 cents/sec
    }
    
    /**
     * Calculate commission for a transaction amount
     */
    public function calculateCommission(int $amountCents): array
    {
        if (!$this->isReseller()) {
            return [
                'platform_cost' => $amountCents,
                'commission' => 0,
                'reseller_revenue' => 0
            ];
        }
        
        $commissionCents = (int) round($amountCents * ($this->commission_rate / 100));
        $platformCostCents = $amountCents - $commissionCents;
        
        return [
            'platform_cost' => $platformCostCents,
            'commission' => $commissionCents,
            'reseller_revenue' => $commissionCents
        ];
    }
    
    /**
     * Add credit to tenant balance
     */
    public function addCredit(int $cents, string $description = null): Transaction
    {
        $balanceBefore = $this->balance_cents ?? 0;
        $this->increment('balance_cents', $cents);
        
        return Transaction::create([
            'tenant_id' => $this->id,
            'type' => Transaction::TYPE_TOPUP,
            'amount_cents' => $cents,
            'balance_before_cents' => $balanceBefore,
            'balance_after_cents' => $balanceBefore + $cents,
            'description' => $description ?? 'Gutschrift'
        ]);
    }
    
    /**
     * Deduct from tenant balance
     */
    public function deductBalance(int $cents, string $description = null): Transaction
    {
        $balanceBefore = $this->balance_cents ?? 0;
        
        if ($balanceBefore < $cents) {
            throw new \Exception('Insufficient balance');
        }
        
        $this->decrement('balance_cents', $cents);
        
        return Transaction::create([
            'tenant_id' => $this->id,
            'type' => Transaction::TYPE_USAGE,
            'amount_cents' => -$cents,
            'balance_before_cents' => $balanceBefore,
            'balance_after_cents' => $balanceBefore - $cents,
            'description' => $description ?? 'Verbrauch'
        ]);
    }
    
    /**
     * Get formatted balance
     */
    public function getFormattedBalance(): string
    {
        return number_format(($this->balance_cents ?? 0) / 100, 2) . ' €';
    }
    
    /**
     * Check if tenant has sufficient balance
     */
    public function hasSufficientBalance(int $cents): bool
    {
        return ($this->balance_cents ?? 0) >= $cents;
    }
}
