<?php

namespace Database\Factories;

use App\Models\CompanyPricing;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyPricingFactory extends Factory
{
    protected $model = CompanyPricing::class;

    public function definition()
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->randomElement(['Basic', 'Professional', 'Enterprise']),
            'monthly_base_fee' => $this->faker->randomFloat(2, 0, 500),
            'included_minutes' => $this->faker->numberBetween(0, 1000),
            'price_per_minute' => $this->faker->randomFloat(2, 0.10, 1.00),
            'overage_price_per_minute' => $this->faker->randomFloat(2, 0.15, 1.50),
            'is_active' => true,
            'valid_from' => now()->subMonths(rand(1, 6)),
            'valid_until' => null,
        ];
    }

    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    public function basic()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Basic',
                'monthly_base_fee' => 99,
                'included_minutes' => 100,
                'price_per_minute' => 0.50,
                'overage_price_per_minute' => 0.60,
            ];
        });
    }
}