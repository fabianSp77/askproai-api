<?php

namespace Database\Factories;

use App\Models\DunningConfiguration;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class DunningConfigurationFactory extends Factory
{
    protected $model = DunningConfiguration::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'is_enabled' => true,
            'max_retry_attempts' => 3,
            'retry_delays' => [3, 5, 7],
            'send_warnings' => true,
            'warning_before_days' => 2,
            'pause_service_on_failure' => true,
            'notification_emails' => ['billing@company.com'],
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }

    public function withCustomRetries(array $delays): static
    {
        return $this->state(fn (array $attributes) => [
            'retry_delays' => $delays,
            'max_retry_attempts' => count($delays),
        ]);
    }
}