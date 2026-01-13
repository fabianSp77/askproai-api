<?php

namespace Database\Factories;

use App\Models\PricingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for PricingPlan model.
 *
 * Used for billing tests to create pricing configurations.
 */
class PricingPlanFactory extends Factory
{
    protected $model = PricingPlan::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' Plan',
            'internal_name' => $this->faker->unique()->slug(3),
            'category' => $this->faker->randomElement(['starter', 'professional', 'enterprise']),
            'tagline' => $this->faker->sentence(),
            'long_description' => $this->faker->paragraph(),
            'billing_period' => 'monthly',
            'price_monthly' => $this->faker->randomFloat(2, 29, 299),
            'yearly_discount_percentage' => 10.00,
            'setup_fee' => $this->faker->randomFloat(2, 0, 500),
            'minutes_included' => $this->faker->numberBetween(100, 1000),
            'sms_included' => $this->faker->numberBetween(0, 100),
            'price_per_minute' => $this->faker->randomFloat(3, 0.05, 0.25),
            'price_per_sms' => 0.19,
            'unlimited_minutes' => false,
            'fair_use_policy' => false,
            'features' => ['basic_support', 'analytics'],
            'is_active' => true,
            'is_default' => false,
            'is_visible' => true,
            'min_contract_months' => 1,
            'notice_period_days' => 30,
        ];
    }

    /**
     * Create a basic starter plan.
     */
    public function starter(): static
    {
        return $this->state(fn () => [
            'category' => 'starter',
            'price_monthly' => 29.00,
            'price_per_minute' => 0.15,
            'minutes_included' => 100,
            'setup_fee' => 0,
        ]);
    }

    /**
     * Create a professional plan.
     */
    public function professional(): static
    {
        return $this->state(fn () => [
            'category' => 'professional',
            'price_monthly' => 99.00,
            'price_per_minute' => 0.12,
            'minutes_included' => 500,
            'setup_fee' => 99.00,
        ]);
    }

    /**
     * Create an enterprise plan.
     */
    public function enterprise(): static
    {
        return $this->state(fn () => [
            'category' => 'enterprise',
            'price_monthly' => 299.00,
            'price_per_minute' => 0.08,
            'minutes_included' => 2000,
            'setup_fee' => 299.00,
            'unlimited_minutes' => true,
        ]);
    }

    /**
     * Mark as default plan.
     */
    public function default(): static
    {
        return $this->state(fn () => [
            'is_default' => true,
        ]);
    }
}
