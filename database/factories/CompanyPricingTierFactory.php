<?php

namespace Database\Factories;

use App\Models\CompanyPricingTier;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyPricingTierFactory extends Factory
{
    protected $model = CompanyPricingTier::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'child_company_id' => Company::factory(),
            'pricing_type' => $this->faker->randomElement(['inbound', 'outbound', 'sms', 'monthly']),
            'cost_price' => $this->faker->randomFloat(4, 0.10, 0.50),
            'sell_price' => $this->faker->randomFloat(4, 0.20, 0.80),
            'setup_fee' => $this->faker->randomFloat(2, 0, 200),
            'monthly_fee' => $this->faker->randomFloat(2, 0, 100),
            'included_minutes' => $this->faker->numberBetween(0, 2000),
            'overage_rate' => $this->faker->randomFloat(4, 0.20, 0.80),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'metadata' => null,
        ];
    }

    public function inbound(): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_type' => 'inbound',
        ]);
    }

    public function outbound(): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_type' => 'outbound',
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_type' => 'monthly',
            'cost_price' => 0,
            'sell_price' => 0,
            'monthly_fee' => $this->faker->randomFloat(2, 20, 150),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withIncludedMinutes(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'included_minutes' => $minutes,
        ]);
    }

    public function resellerOwn(): static
    {
        return $this->state(fn (array $attributes) => [
            'child_company_id' => null,
        ]);
    }
}