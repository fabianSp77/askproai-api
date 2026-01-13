<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Call;
use App\Models\Company;
use App\Models\RetellCallSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for RetellCallSession model.
 *
 * @extends Factory<RetellCallSession>
 */
class RetellCallSessionFactory extends Factory
{
    protected $model = RetellCallSession::class;

    public function definition(): array
    {
        return [
            'call_id' => Call::factory(),
            'company_id' => Company::factory(),
            'customer_id' => null,
            'branch_id' => null,
            'phone_number' => $this->faker->phoneNumber(),
            'branch_name' => $this->faker->company(),
            'agent_id' => 'agent_' . $this->faker->uuid(),
            'agent_version' => $this->faker->randomNumber(2),
            'started_at' => now()->subMinutes(5),
            'ended_at' => now(),
            'call_status' => $this->faker->randomElement(['ended', 'completed', 'transferred']),
            'disconnection_reason' => 'agent_hangup',
            'duration_ms' => $this->faker->numberBetween(30000, 300000),
            'conversation_flow_id' => null,
            'current_flow_node' => null,
            'flow_state' => null,
            'total_events' => $this->faker->numberBetween(5, 50),
            'function_call_count' => $this->faker->numberBetween(0, 10),
            'transcript_segment_count' => $this->faker->numberBetween(5, 30),
            'error_count' => 0,
            'avg_response_time_ms' => $this->faker->numberBetween(200, 1000),
            'max_response_time_ms' => $this->faker->numberBetween(500, 2000),
            'min_response_time_ms' => $this->faker->numberBetween(100, 300),
            'metadata' => [],
        ];
    }

    /**
     * State for a session with errors.
     */
    public function withErrors(): static
    {
        return $this->state(fn (array $attributes) => [
            'error_count' => $this->faker->numberBetween(1, 5),
        ]);
    }

    /**
     * State for an in-progress session.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'call_status' => 'in_progress',
            'ended_at' => null,
        ]);
    }

    /**
     * State for a completed session.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'call_status' => 'completed',
            'ended_at' => now(),
        ]);
    }
}
