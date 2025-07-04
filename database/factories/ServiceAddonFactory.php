<?php

namespace Database\Factories;

use App\Models\ServiceAddon;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceAddonFactory extends Factory
{
    protected $model = ServiceAddon::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['recurring', 'one_time']);
        $isMetered = $this->faker->boolean(20);
        
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->randomElement([
                'SMS Notifications',
                'Priority Support',
                'API Access',
                'Custom Branding',
                'Additional User',
                'WhatsApp Integration',
                'Advanced Analytics',
                'Backup Service',
            ]),
            'description' => $this->faker->sentence(),
            'type' => $type,
            'price' => !$isMetered ? $this->faker->randomFloat(2, 5, 99) : 0,
            'currency' => 'EUR',
            'billing_interval' => $type === 'recurring' ? 'monthly' : null,
            'category' => $this->faker->randomElement(['features', 'support', 'integrations', 'usage']),
            'is_active' => true,
            'is_metered' => $isMetered,
            'meter_unit' => $isMetered ? $this->faker->randomElement(['sms', 'email', 'api_call']) : null,
            'meter_unit_price' => $isMetered ? $this->faker->randomFloat(4, 0.01, 0.50) : null,
            'features' => $this->faker->randomElements([
                'feature_1',
                'feature_2',
                'feature_3',
                'feature_4',
            ], 2),
            'requirements' => [],
            'sort_order' => $this->faker->numberBetween(0, 10),
            'metadata' => [],
        ];
    }

    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'recurring',
            'billing_interval' => $this->faker->randomElement(['monthly', 'quarterly', 'yearly']),
        ]);
    }

    public function oneTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'one_time',
            'billing_interval' => null,
        ]);
    }

    public function metered(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_metered' => true,
            'price' => 0,
            'meter_unit' => $this->faker->randomElement(['sms', 'email', 'api_call']),
            'meter_unit_price' => $this->faker->randomFloat(4, 0.01, 0.50),
        ]);
    }
}