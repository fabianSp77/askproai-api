<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company . ' Filiale',
            'company_id' => \App\Models\Company::factory(),
            'customer_id' => null,
            'slug' => $this->faker->slug,
            'city' => $this->faker->city,
            'phone_number' => $this->faker->phoneNumber,
            'active' => true,
        ];
    }
}
