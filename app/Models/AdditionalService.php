<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Scopes\TenantScope;

class AdditionalService extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'type',
        'price',
        'unit',
        'is_active',
        'stripe_price_id',
        'metadata',
    ];

    protected $casts = {
        'metadata' => 'array',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Service types
    const TYPE_ONE_TIME = 'one_time';
    const TYPE_RECURRING = 'recurring';

    protected static function booted()
    {
        // Only apply tenant scope if company_id is not null
        static::addGlobalScope('optional_tenant', function ($builder) {
            $user = auth()->user();
            if ($user && !$user->hasRole('Super Admin') && $user->company_id) {
                $builder->where(function ($query) use ($user) {
                    $query->whereNull('company_id')
                          ->orWhere('company_id', $user->company_id);
                });
            }
        });
    }

    /**
     * Get the company that owns the service.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the customer services using this service.
     */
    public function customerServices(): HasMany
    {
        return $this->hasMany(CustomerService::class, 'service_id');
    }

    /**
     * Check if service is platform-wide.
     */
    public function isPlatformWide(): bool
    {
        return $this->company_id === null;
    }

    /**
     * Get type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_ONE_TIME => 'Einmalig',
            self::TYPE_RECURRING => 'Wiederkehrend',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return sprintf('€%.2f/%s', $this->price, $this->unit);
    }

    /**
     * Scope for active services.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for one-time services.
     */
    public function scopeOneTime($query)
    {
        return $query->where('type', self::TYPE_ONE_TIME);
    }

    /**
     * Scope for recurring services.
     */
    public function scopeRecurring($query)
    {
        return $query->where('type', self::TYPE_RECURRING);
    }

    /**
     * Create Stripe price for recurring services.
     */
    public function createStripePrice(): ?string
    {
        if ($this->type !== self::TYPE_RECURRING || !config('services.stripe.secret')) {
            return null;
        }

        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            
            $price = $stripe->prices->create([
                'currency' => 'eur',
                'unit_amount' => $this->price * 100, // Convert to cents
                'recurring' => [
                    'interval' => 'month',
                ],
                'product_data' => [
                    'name' => $this->name,
                    'description' => $this->description,
                ],
                'metadata' => [
                    'service_id' => $this->id,
                    'company_id' => $this->company_id,
                ],
            ]);

            $this->update(['stripe_price_id' => $price->id]);
            
            return $price->id;
        } catch (\Exception $e) {
            \Log::error('Error creating Stripe price', [
                'service_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }
}