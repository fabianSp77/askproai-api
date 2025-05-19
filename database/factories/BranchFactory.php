<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company . ' Filiale',
            'customer_id' => null, // optional: oder Customer::factory(), falls Pflicht
            'slug' => $this->faker->slug,
            'city' => $this->faker->city,
            'phone_number' => $this->faker->phoneNumber,
            'active' => $this->faker->boolean,
        ];
    }
}
