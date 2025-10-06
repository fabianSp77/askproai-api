<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingPlan extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'name',
        'internal_name',
        'category',
        'tagline',
        'description',
        'long_description',
        'billing_period',
        'price_monthly',
        'price_yearly',
        'yearly_discount_percentage',
        'setup_fee',
        'minutes_included',
        'sms_included',
        'price_per_minute',
        'price_per_sms',
        'unlimited_minutes',
        'fair_use_policy',
        'features',
        'max_users',
        'max_agents',
        'max_campaigns',
        'storage_gb',
        'api_calls_per_month',
        'retention_days',
        'available_from',
        'available_until',
        'target_countries',
        'customer_types',
        'min_contract_months',
        'notice_period_days',
        'is_active',
        'is_visible',
        'is_popular',
        'is_new',
        'requires_approval',
        'auto_upgrade_eligible',
        'stripe_product_id',
        'stripe_price_id',
        'tax_category',
        'metadata',
        'welcome_email_template',
        'send_usage_alerts',
        'usage_alert_threshold',
        'sort_order',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'yearly_discount_percentage' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'minutes_included' => 'integer',
        'sms_included' => 'integer',
        'price_per_minute' => 'decimal:3',
        'price_per_sms' => 'decimal:3',
        'unlimited_minutes' => 'boolean',
        'fair_use_policy' => 'boolean',
        'features' => 'array',
        'max_users' => 'integer',
        'max_agents' => 'integer',
        'max_campaigns' => 'integer',
        'storage_gb' => 'integer',
        'api_calls_per_month' => 'integer',
        'retention_days' => 'integer',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'target_countries' => 'array',
        'customer_types' => 'array',
        'min_contract_months' => 'integer',
        'notice_period_days' => 'integer',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
        'is_popular' => 'boolean',
        'is_new' => 'boolean',
        'requires_approval' => 'boolean',
        'auto_upgrade_eligible' => 'boolean',
        'metadata' => 'array',
        'send_usage_alerts' => 'boolean',
        'usage_alert_threshold' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * The relationships that should not be serialized.
     */
    protected $hidden = [
        'tenants',
        'activeSubscriptions'
    ];

    /**
     * Prevent recursion when converting to array.
     */
    public function toArray()
    {
        $array = parent::toArray();
        unset($array['tenants']);
        unset($array['activeSubscriptions']);
        return $array;
    }

    /**
     * Get tenants using this pricing plan.
     */
    public function tenants()
    {
        return $this->hasMany(Tenant::class, 'pricing_plan', 'internal_name');
    }

    /**
     * Get active subscriptions for this plan.
     */
    public function activeSubscriptions()
    {
        return $this->hasMany(Tenant::class, 'pricing_plan', 'internal_name')->where('status', 'active');
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price_monthly, 2) . ' €';
    }

    /**
     * Get formatted per minute price.
     */
    public function getFormattedPerMinutePriceAttribute()
    {
        return number_format($this->price_per_minute, 3) . ' €';
    }
}