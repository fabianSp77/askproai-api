<?php

namespace Database\Factories;

use App\Models\DunningProcess;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class DunningProcessFactory extends Factory
{
    protected $model = DunningProcess::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'invoice_id' => Invoice::factory(),
            'invoice_amount' => $this->faker->randomFloat(2, 50, 500),
            'status' => $this->faker->randomElement(['active', 'recovered', 'failed', 'cancelled']),
            'retry_count' => $this->faker->numberBetween(0, 3),
            'next_retry_date' => $this->faker->dateTimeBetween('now', '+7 days'),
            'failure_reason' => $this->faker->randomElement(['insufficient_funds', 'card_declined', 'expired_card']),
            'recovered_at' => null,
            'cancelled_at' => null,
            'service_paused_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'next_retry_date' => Carbon::now()->addDays(3),
        ]);
    }

    public function recovered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'recovered',
            'recovered_at' => Carbon::now()->subDays(1),
            'next_retry_date' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'retry_count' => 3,
            'next_retry_date' => null,
        ]);
    }
}