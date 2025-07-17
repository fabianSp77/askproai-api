<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Customer;
use App\Models\Call;

class CallFactory extends Factory
{
    protected $model = Call::class;
    public function definition(): array
    {
        // Disable events for testing
        Call::unsetEventDispatcher();
        
        $customer = Customer::factory()->create();
        $duration = $this->faker->numberBetween(10, 600);
        
        return [
            'company_id' => $customer->company_id,
            'branch_id' => \App\Models\Branch::factory()->create(['company_id' => $customer->company_id])->id,
            'customer_id' => $customer->id,
            'call_id' => $this->faker->uuid,
            'external_id' => $this->faker->uuid,
            'conversation_id' => $this->faker->uuid,
            'call_status' => $this->faker->randomElement(['completed', 'ended', 'failed']),
            'call_successful' => $this->faker->boolean,
            'retell_call_id' => $this->faker->uuid,
            'from_number' => '+49' . $this->faker->numerify('30#######'),
            'to_number' => '+49' . $this->faker->numerify('30#######'),
            'duration_sec' => $duration,
            'duration_minutes' => round($duration / 60, 2),
            'duration' => $duration,
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
