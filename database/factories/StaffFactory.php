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
            'phone' => $this->faker->phoneNumber,
            'active' => $this->faker->boolean,
            'branch_id' => Branch::factory(),        // jede/r Mitarbeiter:in bekommt eine Branch
            'home_branch_id' => null,
        ];
    }
}
