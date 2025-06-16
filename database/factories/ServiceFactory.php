<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'name' => $this->faker->randomElement(['Haircut', 'Hair Coloring', 'Massage', 'Consultation', 'Cleaning', 'Repair']),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 10, 200),
            'default_duration_minutes' => $this->faker->randomElement([30, 45, 60, 90, 120]),
            'active' => true,
            'category' => $this->faker->randomElement(['beauty', 'health', 'consulting', 'maintenance']),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'min_staff_required' => 1,
            'max_bookings_per_day' => $this->faker->numberBetween(5, 20),
            'buffer_time_minutes' => $this->faker->randomElement([0, 15, 30]),
            'is_online_bookable' => true,
        ];
    }

    public function inactive(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'active' => false,
            ];
        });
    }
}