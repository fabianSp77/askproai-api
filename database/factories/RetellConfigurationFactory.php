<?php

namespace Database\Factories;

use App\Models\RetellConfiguration;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class RetellConfigurationFactory extends Factory
{
    protected $model = RetellConfiguration::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'agent_id' => 'agent_' . $this->faker->uuid(),
            'webhook_url' => $this->faker->url(),
            'custom_functions' => [
                'collect_appointment_information',
                'change_appointment_details',
                'cancel_appointment'
            ],
            'prompt_template' => $this->faker->paragraph(),
            'settings' => [
                'voice' => 'en-US-Standard-A',
                'language' => 'en',
                'greeting' => $this->faker->sentence()
            ],
            'last_synced_at' => $this->faker->dateTimeThisMonth(),
        ];
    }
}