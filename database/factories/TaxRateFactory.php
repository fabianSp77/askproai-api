<?php

namespace Database\Factories;

use App\Models\TaxRate;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxRateFactory extends Factory
{
    protected $model = TaxRate::class;

    public function definition()
    {
        return [
            'name' => $this->faker->randomElement(['Standard', 'Reduced', 'Zero Rate', 'Reverse Charge']),
            'rate' => $this->faker->randomElement([0, 7, 19]),
            'description' => $this->faker->sentence(),
            'is_system' => $this->faker->boolean(70),
            'is_default' => false,
            'company_id' => $this->faker->boolean(30) ? Company::factory() : null,
        ];
    }

    public function system()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_system' => true,
                'company_id' => null,
            ];
        });
    }

    public function standard()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Standard',
                'rate' => 19,
                'is_default' => true,
            ];
        });
    }

    public function smallBusiness()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Kleinunternehmer',
                'rate' => 0,
                'description' => 'Gemäß § 19 UStG',
                'is_system' => true,
            ];
        });
    }
}