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
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'slug' => $this->faker->slug,
            'city' => $this->faker->city,
            'country' => 'Deutschland',
            'address' => $this->faker->streetAddress,
            'postal_code' => $this->faker->numerify('#####'),
            'phone_number' => '+49' . $this->faker->numerify('30#######'),
            'notification_email' => $this->faker->safeEmail,
            'active' => true,
            'is_active' => true,
        ];
    }
}
