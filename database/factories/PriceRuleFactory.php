<?php

namespace Database\Factories;

use App\Models\PriceRule;
use App\Models\Company;
use App\Models\PricingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class PriceRuleFactory extends Factory
{
    protected $model = PriceRule::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['time_based', 'location_based', 'customer_segment', 'promotional']);
        
        $conditions = match($type) {
            'time_based' => [
                'day_of_week' => $this->faker->randomElements(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'], 2),
                'time_range' => ['18:00', '22:00'],
            ],
            'location_based' => [
                'branch_ids' => [$this->faker->uuid(), $this->faker->uuid()],
            ],
            'customer_segment' => [
                'tags' => ['vip', 'gold'],
                'min_lifetime_value' => 1000,
            ],
            'promotional' => [
                'promo_code' => strtoupper($this->faker->lexify('??????')),
                'max_uses' => 100,
            ],
        };
        
        return [
            'company_id' => Company::factory(),
            'pricing_plan_id' => $this->faker->boolean(50) ? PricingPlan::factory() : null,
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'type' => $type,
            'conditions' => $conditions,
            'modification_type' => $this->faker->randomElement(['percentage', 'fixed_amount', 'multiplier']),
            'modification_value' => $this->faker->randomFloat(2, 5, 25),
            'valid_from' => $this->faker->boolean(70) ? Carbon::now()->subDays($this->faker->numberBetween(0, 30)) : null,
            'valid_until' => $this->faker->boolean(70) ? Carbon::now()->addDays($this->faker->numberBetween(30, 365)) : null,
            'is_active' => true,
            'priority' => $this->faker->numberBetween(0, 10),
            'metadata' => [],
        ];
    }

    public function timeBased(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'time_based',
            'conditions' => [
                'day_of_week' => ['saturday', 'sunday'],
                'time_range' => ['18:00', '22:00'],
            ],
        ]);
    }

    public function promotional(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'promotional',
            'conditions' => [
                'promo_code' => strtoupper($this->faker->lexify('PROMO????')),
                'max_uses' => 50,
            ],
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_until' => Carbon::now()->subDays($this->faker->numberBetween(1, 30)),
        ]);
    }
}