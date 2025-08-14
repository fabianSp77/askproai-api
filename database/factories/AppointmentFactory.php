<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'external_id' => $this->faker->uuid,
            'starts_at' => $this->faker->dateTimeBetween('-30 days', '+30 days'),
            'ends_at' => $this->faker->dateTimeBetween('+31 days', '+60 days'),
            'payload' => [],
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'no-show']),
        ];
    }
}
