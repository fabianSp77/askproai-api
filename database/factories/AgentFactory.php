<?php

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'agent_id' => 'agent_' . $this->faker->uuid(),
            'type' => $this->faker->randomElement(['retell', 'custom']),
            'config' => [
                'voice' => $this->faker->randomElement(['male', 'female']),
                'language' => $this->faker->randomElement(['en', 'de', 'es', 'fr']),
                'greeting' => $this->faker->sentence(),
            ],
            'active' => true,
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