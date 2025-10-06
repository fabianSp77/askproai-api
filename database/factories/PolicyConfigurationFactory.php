<?php

namespace Database\Factories;

use App\Models\PolicyConfiguration;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PolicyConfiguration>
 */
class PolicyConfigurationFactory extends Factory
{
    protected $model = PolicyConfiguration::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $configurableTypes = [
            Company::class,
            Branch::class,
            Service::class,
            Staff::class,
        ];

        $configurableType = $this->faker->randomElement($configurableTypes);

        // Create the configurable entity
        $configurable = match ($configurableType) {
            Company::class => Company::factory()->create(),
            Branch::class => Branch::factory()->create(),
            Service::class => Service::factory()->create(),
            Staff::class => Staff::factory()->create(),
        };

        $policyType = $this->faker->randomElement(PolicyConfiguration::POLICY_TYPES);

        return [
            'configurable_type' => $configurableType,
            'configurable_id' => $configurable->id,
            'policy_type' => $policyType,
            'config' => $this->generatePolicyConfig($policyType),
            'is_override' => false,
            'overrides_id' => null,
        ];
    }

    /**
     * Generate realistic policy configuration based on type.
     *
     * @param string $policyType
     * @return array
     */
    private function generatePolicyConfig(string $policyType): array
    {
        return match ($policyType) {
            PolicyConfiguration::POLICY_TYPE_CANCELLATION => $this->generateCancellationConfig(),
            PolicyConfiguration::POLICY_TYPE_RESCHEDULE => $this->generateRescheduleConfig(),
            PolicyConfiguration::POLICY_TYPE_RECURRING => $this->generateRecurringConfig(),
            default => [],
        };
    }

    /**
     * Generate cancellation policy configuration.
     *
     * @return array
     */
    private function generateCancellationConfig(): array
    {
        return [
            'min_notice_hours' => $this->faker->randomElement([24, 48, 72]),
            'fee_within_notice' => 0.00,
            'fee_outside_notice' => $this->faker->randomElement([25.00, 50.00, 100.00]),
            'fee_type' => $this->faker->randomElement(['fixed', 'percentage']),
            'fee_percentage' => $this->faker->numberBetween(25, 100),
            'max_free_cancellations_per_period' => $this->faker->numberBetween(1, 3),
            'period_days' => 30,
            'allow_same_day_cancellation' => $this->faker->boolean(30),
            'refund_policy' => $this->faker->randomElement(['full', 'partial', 'none']),
            'exceptions' => [
                'emergency' => true,
                'illness' => $this->faker->boolean(80),
                'weather' => $this->faker->boolean(50),
            ],
        ];
    }

    /**
     * Generate reschedule policy configuration.
     *
     * @return array
     */
    private function generateRescheduleConfig(): array
    {
        return [
            'min_notice_hours' => $this->faker->randomElement([12, 24, 48]),
            'max_reschedules_per_period' => $this->faker->numberBetween(2, 5),
            'period_days' => 30,
            'fee_after_limit' => $this->faker->randomElement([0.00, 10.00, 25.00, 50.00]),
            'allow_same_day_reschedule' => $this->faker->boolean(40),
            'same_day_fee' => $this->faker->randomElement([15.00, 25.00, 35.00]),
            'must_reschedule_within_days' => $this->faker->numberBetween(7, 30),
            'allow_online_reschedule' => $this->faker->boolean(85),
            'require_approval' => $this->faker->boolean(20),
            'restrictions' => [
                'business_hours_only' => $this->faker->boolean(60),
                'same_service_required' => $this->faker->boolean(30),
                'same_staff_preferred' => $this->faker->boolean(70),
            ],
        ];
    }

    /**
     * Generate recurring appointment policy configuration.
     *
     * @return array
     */
    private function generateRecurringConfig(): array
    {
        return [
            'allowed_frequencies' => $this->faker->randomElements(['daily', 'weekly', 'biweekly', 'monthly'], $this->faker->numberBetween(2, 4)),
            'min_occurrences' => $this->faker->numberBetween(3, 5),
            'max_occurrences' => $this->faker->numberBetween(12, 52),
            'advance_booking_days' => $this->faker->numberBetween(90, 365),
            'auto_confirm' => $this->faker->boolean(60),
            'require_deposit' => $this->faker->boolean(40),
            'deposit_amount' => $this->faker->randomElement([50.00, 100.00, 150.00]),
            'cancellation_affects_series' => $this->faker->boolean(70),
            'allow_skip_occurrence' => $this->faker->boolean(80),
            'max_skips_allowed' => $this->faker->numberBetween(1, 3),
            'billing_frequency' => $this->faker->randomElement(['per_occurrence', 'monthly', 'upfront']),
            'discount_percentage' => $this->faker->numberBetween(5, 20),
        ];
    }

    /**
     * Create a cancellation policy.
     */
    public function cancellation(): static
    {
        return $this->state(fn (array $attributes) => [
            'policy_type' => PolicyConfiguration::POLICY_TYPE_CANCELLATION,
            'config' => $this->generateCancellationConfig(),
        ]);
    }

    /**
     * Create a reschedule policy.
     */
    public function reschedule(): static
    {
        return $this->state(fn (array $attributes) => [
            'policy_type' => PolicyConfiguration::POLICY_TYPE_RESCHEDULE,
            'config' => $this->generateRescheduleConfig(),
        ]);
    }

    /**
     * Create a recurring policy.
     */
    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'policy_type' => PolicyConfiguration::POLICY_TYPE_RECURRING,
            'config' => $this->generateRecurringConfig(),
        ]);
    }

    /**
     * Create an override policy.
     */
    public function override(?PolicyConfiguration $parent = null): static
    {
        return $this->state(function (array $attributes) use ($parent) {
            $parentPolicy = $parent ?? PolicyConfiguration::factory()->create();

            return [
                'configurable_type' => $parentPolicy->configurable_type,
                'configurable_id' => $parentPolicy->configurable_id,
                'policy_type' => $parentPolicy->policy_type,
                'is_override' => true,
                'overrides_id' => $parentPolicy->id,
                'config' => array_merge(
                    $parentPolicy->config,
                    ['min_notice_hours' => 12] // Override example
                ),
            ];
        });
    }

    /**
     * Create policy for specific company.
     */
    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => [
            'configurable_type' => Company::class,
            'configurable_id' => $company->id,
        ]);
    }

    /**
     * Create policy for specific branch.
     */
    public function forBranch(Branch $branch): static
    {
        return $this->state(fn (array $attributes) => [
            'configurable_type' => Branch::class,
            'configurable_id' => $branch->id,
        ]);
    }

    /**
     * Create policy for specific service.
     */
    public function forService(Service $service): static
    {
        return $this->state(fn (array $attributes) => [
            'configurable_type' => Service::class,
            'configurable_id' => $service->id,
        ]);
    }

    /**
     * Create policy for specific staff.
     */
    public function forStaff(Staff $staff): static
    {
        return $this->state(fn (array $attributes) => [
            'configurable_type' => Staff::class,
            'configurable_id' => $staff->id,
        ]);
    }
}
