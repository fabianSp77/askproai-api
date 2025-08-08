<?php

namespace Database\Factories;

use App\Models\PricingMargin;
use App\Models\CompanyPricingTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class PricingMarginFactory extends Factory
{
    protected $model = PricingMargin::class;

    public function definition(): array
    {
        $marginAmount = $this->faker->randomFloat(4, -10, 50);
        $marginPercentage = $this->faker->randomFloat(2, -20, 200);

        return [
            'company_pricing_tier_id' => CompanyPricingTier::factory(),
            'margin_amount' => $marginAmount,
            'margin_percentage' => $marginPercentage,
            'calculated_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ];
    }

    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'margin_amount' => $this->faker->randomFloat(4, 1, 50),
            'margin_percentage' => $this->faker->randomFloat(2, 5, 200),
        ]);
    }

    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'margin_amount' => $this->faker->randomFloat(4, -10, -0.01),
            'margin_percentage' => $this->faker->randomFloat(2, -20, -0.01),
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'calculated_date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
        ]);
    }
}