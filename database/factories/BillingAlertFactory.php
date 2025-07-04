<?php

namespace Database\Factories;

use App\Models\BillingAlert;
use App\Models\BillingAlertConfig;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class BillingAlertFactory extends Factory
{
    protected $model = BillingAlert::class;

    public function definition(): array
    {
        $alertTypes = [
            BillingAlertConfig::TYPE_USAGE_LIMIT,
            BillingAlertConfig::TYPE_PAYMENT_REMINDER,
            BillingAlertConfig::TYPE_SUBSCRIPTION_RENEWAL,
            BillingAlertConfig::TYPE_OVERAGE_WARNING,
            BillingAlertConfig::TYPE_PAYMENT_FAILED,
            BillingAlertConfig::TYPE_BUDGET_EXCEEDED,
        ];

        return [
            'company_id' => Company::factory(),
            'alert_type' => $this->faker->randomElement($alertTypes),
            'severity' => $this->faker->randomElement(['info', 'warning', 'critical']),
            'title' => $this->faker->sentence(6),
            'message' => $this->faker->paragraph(2),
            'status' => $this->faker->randomElement(['pending', 'sent', 'failed', 'acknowledged']),
            'sent_at' => $this->faker->optional()->dateTimeBetween('-7 days', 'now'),
            'acknowledged_at' => null,
            'acknowledged_by' => null,
            'data' => [],
            'threshold_value' => $this->faker->optional()->numberBetween(50, 100),
            'current_value' => $this->faker->optional()->numberBetween(100, 1000),
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => Carbon::now()->subHours(2),
        ]);
    }

    public function acknowledged(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'acknowledged',
            'sent_at' => Carbon::now()->subDays(2),
            'acknowledged_at' => Carbon::now()->subDay(),
            'acknowledged_by' => User::factory(),
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
            'alert_type' => BillingAlertConfig::TYPE_PAYMENT_FAILED,
        ]);
    }
}