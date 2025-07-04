<?php

namespace Database\Factories;

use App\Models\DunningActivity;
use App\Models\DunningProcess;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class DunningActivityFactory extends Factory
{
    protected $model = DunningActivity::class;

    public function definition(): array
    {
        return [
            'dunning_process_id' => DunningProcess::factory(),
            'activity_type' => $this->faker->randomElement(['payment_retry', 'warning_sent', 'service_paused', 'manual_action']),
            'status' => $this->faker->randomElement(['pending', 'success', 'failed']),
            'details' => [
                'attempt_number' => $this->faker->numberBetween(1, 3),
                'error' => $this->faker->optional()->sentence(),
            ],
            'created_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ];
    }

    public function paymentRetry(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => 'payment_retry',
            'details' => [
                'amount' => $this->faker->randomFloat(2, 50, 500),
                'payment_method' => 'card',
            ],
        ]);
    }

    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'details' => array_merge($attributes['details'] ?? [], [
                'error' => $this->faker->randomElement(['Card declined', 'Insufficient funds', 'Invalid card']),
            ]),
        ]);
    }
}