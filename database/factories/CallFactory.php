<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Customer;

class CallFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'call_id' => $this->faker->uuid,
            'external_id' => $this->faker->uuid,
            'conversation_id' => $this->faker->uuid,
            'call_status' => $this->faker->randomElement(['active', 'ended', 'failed']),
            'call_successful' => $this->faker->boolean,
            'retell_call_id' => $this->faker->uuid,
            'from_number' => $this->faker->phoneNumber,
            'to_number' => $this->faker->phoneNumber,
            'duration_sec' => $this->faker->numberBetween(10, 600),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
