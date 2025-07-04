<?php

namespace Database\Factories;

use App\Models\BillingPeriod;
use App\Models\Company;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class BillingPeriodFactory extends Factory
{
    protected $model = BillingPeriod::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-3 months', 'now');
        $endDate = Carbon::parse($startDate)->endOfMonth();
        
        return [
            'company_id' => Company::factory(),
            'branch_id' => null,
            'subscription_id' => null,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $this->faker->randomElement(['pending', 'active', 'processed', 'invoiced']),
            'total_minutes' => $this->faker->numberBetween(100, 5000),
            'used_minutes' => $this->faker->numberBetween(100, 5000),
            'included_minutes' => $this->faker->numberBetween(500, 2000),
            'overage_minutes' => 0,
            'price_per_minute' => $this->faker->randomFloat(4, 0.05, 0.20),
            'base_fee' => $this->faker->randomElement([49.00, 99.00, 149.00]),
            'overage_cost' => 0,
            'total_cost' => function (array $attributes) {
                return $attributes['base_fee'];
            },
            'total_revenue' => function (array $attributes) {
                return $attributes['total_cost'];
            },
            'margin' => function (array $attributes) {
                return $attributes['total_cost'] * 0.7;
            },
            'margin_percentage' => 70,
            'currency' => 'EUR',
            'is_prorated' => false,
            'proration_factor' => 1.0,
            'is_invoiced' => false,
            'invoiced_at' => null,
            'invoice_id' => null,
            'stripe_invoice_id' => null,
            'stripe_invoice_created_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => Carbon::now()->endOfMonth(),
        ]);
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processed',
            'start_date' => Carbon::now()->subMonth()->startOfMonth(),
            'end_date' => Carbon::now()->subMonth()->endOfMonth(),
        ]);
    }

    public function invoiced(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'invoiced',
            'is_invoiced' => true,
            'invoiced_at' => Carbon::now()->subDays(5),
        ]);
    }
}