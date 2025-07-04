<?php

namespace Database\Factories;

use App\Models\BillingAlertConfig;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class BillingAlertConfigFactory extends Factory
{
    protected $model = BillingAlertConfig::class;

    public function definition(): array
    {
        $alertType = $this->faker->randomElement([
            BillingAlertConfig::TYPE_USAGE_LIMIT,
            BillingAlertConfig::TYPE_PAYMENT_REMINDER,
            BillingAlertConfig::TYPE_SUBSCRIPTION_RENEWAL,
            BillingAlertConfig::TYPE_OVERAGE_WARNING,
            BillingAlertConfig::TYPE_PAYMENT_FAILED,
            BillingAlertConfig::TYPE_BUDGET_EXCEEDED,
        ]);

        return [
            'company_id' => Company::factory(),
            'alert_type' => $alertType,
            'is_enabled' => true,
            'notification_channels' => ['email'],
            'notify_primary_contact' => true,
            'notify_billing_contact' => true,
            'notify_additional_emails' => [],
            'thresholds' => $this->getDefaultThresholds($alertType),
            'advance_days' => $this->getDefaultAdvanceDays($alertType),
            'custom_settings' => [],
        ];
    }

    protected function getDefaultThresholds(string $type): ?array
    {
        return match ($type) {
            BillingAlertConfig::TYPE_USAGE_LIMIT => [80, 90, 100],
            BillingAlertConfig::TYPE_BUDGET_EXCEEDED => [75, 90, 100],
            BillingAlertConfig::TYPE_OVERAGE_WARNING => [100],
            default => null,
        };
    }

    protected function getDefaultAdvanceDays(string $type): ?int
    {
        return match ($type) {
            BillingAlertConfig::TYPE_PAYMENT_REMINDER => 3,
            BillingAlertConfig::TYPE_SUBSCRIPTION_RENEWAL => 7,
            default => null,
        };
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }

    public function forType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => $type,
            'thresholds' => $this->getDefaultThresholds($type),
            'advance_days' => $this->getDefaultAdvanceDays($type),
        ]);
    }
}