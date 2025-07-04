<?php

namespace Database\Factories;

use App\Models\PricingPlan;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class PricingPlanFactory extends Factory
{
    protected $model = PricingPlan::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['package', 'usage_based', 'hybrid']);
        $basePrice = $this->faker->randomFloat(2, 29, 299);
        
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->randomElement(['Basic', 'Professional', 'Enterprise']) . ' Plan',
            'description' => $this->faker->sentence(),
            'type' => $type,
            'billing_interval' => $this->faker->randomElement(['monthly', 'quarterly', 'yearly']),
            'interval_count' => 1,
            'base_price' => $basePrice,
            'currency' => 'EUR',
            'included_minutes' => $this->faker->numberBetween(0, 1000),
            'included_appointments' => $this->faker->numberBetween(0, 100),
            'included_features' => $this->faker->randomElements([
                'unlimited_users',
                'api_access',
                'priority_support',
                'custom_branding',
                'advanced_analytics',
            ], 3),
            'overage_price_per_minute' => $type !== 'package' ? $this->faker->randomFloat(4, 0.01, 0.10) : null,
            'overage_price_per_appointment' => $type !== 'package' ? $this->faker->randomFloat(2, 1, 5) : null,
            'volume_discounts' => $this->faker->boolean(30) ? [
                ['threshold' => 500, 'discount_percent' => 5],
                ['threshold' => 1000, 'discount_percent' => 10],
                ['threshold' => 2000, 'discount_percent' => 15],
            ] : [],
            'is_active' => true,
            'is_default' => false,
            'trial_days' => $this->faker->randomElement([0, 7, 14, 30]),
            'sort_order' => $this->faker->numberBetween(0, 10),
            'metadata' => [],
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function packageType(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'package',
            'overage_price_per_minute' => null,
            'overage_price_per_appointment' => null,
        ]);
    }

    public function usageBasedType(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'usage_based',
            'included_minutes' => 0,
            'included_appointments' => 0,
            'base_price' => 0,
        ]);
    }
}