<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * SECURITY: company_id is REQUIRED and NEVER NULL
     * This prevents the data integrity issue that caused 31/60 customers
     * to bypass multi-tenant isolation.
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            // Note: phone field removed - use phone_primary or phone_variants instead
        ];
    }

    /**
     * Configure the factory with validation hooks.
     *
     * Prevents NULL company_id from being created during testing.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Customer $customer) {
            // Validate company_id is set before persisting
            if (!$customer->company_id) {
                throw new \RuntimeException(
                    'CRITICAL: CustomerFactory attempted to create customer with NULL company_id. ' .
                    'This violates multi-tenant isolation requirements. ' .
                    'Always provide company_id explicitly or ensure authenticated user context.'
                );
            }
        })->afterCreating(function (Customer $customer) {
            // Double-check after creation (defense in depth)
            if (!$customer->company_id) {
                throw new \RuntimeException(
                    'CRITICAL: Customer created with NULL company_id. ' .
                    'Customer ID: ' . $customer->id . '. ' .
                    'This indicates a serious security issue. ' .
                    'Rolling back transaction.'
                );
            }
        });
    }

    /**
     * Create a customer for a specific company.
     *
     * Usage: Customer::factory()->forCompany($company)->create()
     */
    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
        ]);
    }

    /**
     * Create a customer with NULL company_id (for testing broken state only).
     *
     * WARNING: This should ONLY be used in tests that validate the fix.
     * Never use this in production or regular test scenarios.
     *
     * Usage: Customer::factory()->withNullCompany()->makeRaw()
     */
    public function withNullCompany(): static
    {
        if (!app()->environment(['testing', 'local'])) {
            throw new \RuntimeException(
                'withNullCompany() can only be used in testing/local environments'
            );
        }

        // Skip validation hooks for this specific test scenario
        return $this->state(fn (array $attributes) => [
            'company_id' => null,
        ])->configure(fn () => $this); // Reset configure to skip validation
    }
}

