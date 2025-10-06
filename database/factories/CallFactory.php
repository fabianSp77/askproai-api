<?php

namespace Database\Factories;

use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PhoneNumber;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Call>
 */
class CallFactory extends Factory
{
    protected $model = Call::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $duration = $this->faker->numberBetween(10, 600);
        $baseCost = $this->calculateBaseCost($duration);
        $resellerCost = $baseCost * 1.2; // 20% markup
        $customerCost = $resellerCost * 1.5; // 50% markup

        return [
            'external_id' => 'call_' . $this->faker->unique()->uuid(),
            'retell_call_id' => 'retell_' . $this->faker->unique()->uuid(),
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'phone_number_id' => null,
            'appointment_id' => null,
            'from_number' => $this->faker->phoneNumber(),
            'to_number' => $this->faker->phoneNumber(),
            'direction' => $this->faker->randomElement(['inbound', 'outbound']),
            'call_status' => $this->faker->randomElement(['completed', 'missed', 'failed']),
            'call_time' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'duration_sec' => $duration,
            'duration_ms' => $duration * 1000,
            'call_successful' => $this->faker->boolean(80),
            'appointment_made' => $this->faker->boolean(30),
            'sentiment' => $this->faker->randomElement(['positive', 'neutral', 'negative']),
            'sentiment_score' => $this->faker->randomFloat(2, -1, 1),
            'summary' => $this->faker->paragraph(),
            'analysis' => [
                'topics' => $this->faker->words(3),
                'intent' => $this->faker->word(),
                'satisfaction' => $this->faker->numberBetween(1, 10)
            ],
            'cost' => $customerCost,
            'cost_cents' => $customerCost,
            'base_cost' => $baseCost,
            'reseller_cost' => $resellerCost,
            'customer_cost' => $customerCost,
            'platform_profit' => $resellerCost - $baseCost,
            'reseller_profit' => $customerCost - $resellerCost,
            'total_profit' => $customerCost - $baseCost,
            'profit_margin_platform' => round((($resellerCost - $baseCost) / $baseCost) * 100, 2),
            'profit_margin_reseller' => round((($customerCost - $resellerCost) / $resellerCost) * 100, 2),
            'profit_margin_total' => round((($customerCost - $baseCost) / $baseCost) * 100, 2),
            'cost_calculation_method' => 'standard',
            'cost_breakdown' => [
                'retell_api' => $baseCost * 0.7,
                'infrastructure' => $baseCost * 0.2,
                'tokens' => $baseCost * 0.1
            ],
            'recording_url' => $this->faker->boolean(60) ? $this->faker->url() : null,
            'transcript' => $this->faker->boolean(40) ? $this->faker->paragraphs(3, true) : null,
            'notes' => $this->faker->boolean(20) ? $this->faker->sentence() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Call $call) {
            if (!$call->phone_number_id) {
                $phoneNumber = PhoneNumber::factory()->create([
                    'company_id' => $call->company_id ?? Company::factory()->create()->id,
                ]);

                $call->phone_number_id = $phoneNumber->id;
            }
        })->afterCreating(function (Call $call) {
            if (!$call->phone_number_id) {
                $phoneNumber = PhoneNumber::factory()->create([
                    'company_id' => $call->company_id ?? Company::factory()->create()->id,
                ]);

                $call->phone_number_id = $phoneNumber->id;
                $call->save();
            }
        });
    }

    /**
     * Calculate base cost based on duration
     */
    private function calculateBaseCost(int $duration): int
    {
        $minutes = ceil($duration / 60);
        $baseCost = ($minutes * 10) + 5; // 10 cents per minute + 5 cents base
        return $baseCost;
    }

    /**
     * Create a call with no profit (break-even)
     */
    public function breakEven(): static
    {
        return $this->state(function (array $attributes) {
            $baseCost = $attributes['base_cost'];
            return [
                'reseller_cost' => $baseCost,
                'customer_cost' => $baseCost,
                'platform_profit' => 0,
                'reseller_profit' => 0,
                'total_profit' => 0,
                'profit_margin_platform' => 0,
                'profit_margin_reseller' => 0,
                'profit_margin_total' => 0,
            ];
        });
    }

    /**
     * Create a call with negative profit (loss)
     */
    public function loss(): static
    {
        return $this->state(function (array $attributes) {
            $baseCost = $attributes['base_cost'];
            $customerCost = (int)($baseCost * 0.8); // 20% loss
            return [
                'reseller_cost' => (int)($baseCost * 0.9),
                'customer_cost' => $customerCost,
                'platform_profit' => -($baseCost * 0.1),
                'reseller_profit' => -($baseCost * 0.1),
                'total_profit' => -($baseCost * 0.2),
                'profit_margin_platform' => -10,
                'profit_margin_reseller' => -11.11,
                'profit_margin_total' => -20,
            ];
        });
    }

    /**
     * Create a high-profit call
     */
    public function highProfit(): static
    {
        return $this->state(function (array $attributes) {
            $baseCost = $attributes['base_cost'];
            $resellerCost = $baseCost * 2; // 100% markup
            $customerCost = $resellerCost * 2; // Another 100% markup
            return [
                'reseller_cost' => $resellerCost,
                'customer_cost' => $customerCost,
                'platform_profit' => $resellerCost - $baseCost,
                'reseller_profit' => $customerCost - $resellerCost,
                'total_profit' => $customerCost - $baseCost,
                'profit_margin_platform' => 100,
                'profit_margin_reseller' => 100,
                'profit_margin_total' => 300,
            ];
        });
    }

    /**
     * Create a call for a reseller customer
     */
    public function forResellerCustomer(Company $resellerCompany, Company $customerCompany): static
    {
        return $this->state([
            'company_id' => $customerCompany->id,
            'cost_calculation_method' => 'reseller',
        ]);
    }

    /**
     * Create a call for a direct customer (no reseller)
     */
    public function forDirectCustomer(Company $company): static
    {
        return $this->state(function (array $attributes) use ($company) {
            $baseCost = $attributes['base_cost'];
            $customerCost = $baseCost * 1.5; // 50% markup direct
            return [
                'company_id' => $company->id,
                'reseller_cost' => $baseCost,
                'customer_cost' => $customerCost,
                'platform_profit' => $customerCost - $baseCost,
                'reseller_profit' => 0,
                'total_profit' => $customerCost - $baseCost,
                'profit_margin_platform' => 50,
                'profit_margin_reseller' => 0,
                'profit_margin_total' => 50,
                'cost_calculation_method' => 'direct',
            ];
        });
    }

    /**
     * Create a call from today
     */
    public function today(): static
    {
        return $this->state([
            'call_time' => now(),
            'created_at' => now(),
        ]);
    }

    /**
     * Create a call from specific date
     */
    public function onDate($date): static
    {
        return $this->state([
            'call_time' => $date,
            'created_at' => $date,
        ]);
    }
}