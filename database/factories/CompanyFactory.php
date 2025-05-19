<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'email' => $this->faker->companyEmail,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'contact_person' => $this->faker->name,
            'opening_hours' => null,
            'calcom_api_key' => null,
            'calcom_user_id' => null,
            'retell_api_key' => null,
            'active' => $this->faker->boolean,
        ];
    }
}
