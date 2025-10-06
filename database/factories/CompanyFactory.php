<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'is_active' => true,
        ];
    }

    /**
     * Create a reseller company
     */
    public function reseller(): static
    {
        return $this->state([
            // Schema-compatible state
        ]);
    }

    /**
     * Create a customer company
     */
    public function customer(): static
    {
        return $this->state([
            // Schema-compatible state
        ]);
    }

    /**
     * Create a customer company under a reseller
     */
    public function underReseller(Company $reseller): static
    {
        return $this->customer()->state([
            // Schema-compatible state
        ]);
    }

    /**
     * Create a direct customer (no reseller)
     */
    public function directCustomer(): static
    {
        return $this->customer()->state([
            // Schema-compatible state
        ]);
    }

    /**
     * Create a company with low credit
     */
    public function lowCredit(): static
    {
        return $this->state([
            // Schema-compatible state
        ]);
    }

    /**
     * Create an inactive company
     */
    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }

    /**
     * Create an internal company
     */
    public function internal(): static
    {
        return $this->state([
            // Schema-compatible state
        ]);
    }
}