<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class TestCompanyFactory extends Factory
{
    protected $model = Company::class;
    
    public function definition(): array
    {
        $name = $this->faker->company();
        
        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'contact_person' => $this->faker->name(),
            'active' => true,
            'is_small_business' => false,
            'revenue_ytd' => $this->faker->randomFloat(2, 0, 50000),
            'revenue_previous_year' => $this->faker->randomFloat(2, 0, 50000),
            'currency' => 'EUR',
            'timezone' => 'Europe/Berlin',
            'country' => 'DE',
            'trial_ends_at' => now()->addDays(14),
        ];
    }
    
    public function smallBusiness(): self
    {
        return $this->state([
            'is_small_business' => true,
            'revenue_ytd' => $this->faker->randomFloat(2, 0, 20000),
            'revenue_previous_year' => $this->faker->randomFloat(2, 0, 20000),
        ]);
    }
    
    public function withStripe(): self
    {
        return $this->state([
            'stripe_customer_id' => 'cus_' . $this->faker->uuid(),
        ]);
    }
}