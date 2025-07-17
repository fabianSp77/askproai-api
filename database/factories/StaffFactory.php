<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Branch;

class StaffFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => '+49' . $this->faker->numerify('30#######'),
            'active' => true,
            'company_id' => \App\Models\Company::factory(),
            'branch_id' => Branch::factory(),
            'home_branch_id' => null,
        ];
    }
}
