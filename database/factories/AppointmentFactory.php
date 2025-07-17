<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Customer;

class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = $this->faker->dateTimeBetween('-30 days', '+30 days');
        $endsAt = clone $startsAt;
        $endsAt->modify('+30 minutes');
        
        return [
            'customer_id' => Customer::factory(),
            'branch_id' => \App\Models\Branch::factory(),
            'staff_id' => \App\Models\Staff::factory(),
            'service_id' => \App\Models\Service::factory(),
            'company_id' => \App\Models\Company::factory(),
            'external_id' => $this->faker->uuid,
            'starts_at'   => $startsAt,
            'ends_at'     => $endsAt,
            'payload'     => [],
            'status'      => $this->faker->randomElement(['scheduled', 'confirmed', 'completed', 'cancelled']),
        ];
    }
}
