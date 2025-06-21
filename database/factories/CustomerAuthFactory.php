<?php

namespace Database\Factories;

use App\Models\CustomerAuth;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CustomerAuthFactory extends Factory
{
    protected $model = CustomerAuth::class;

    public function definition()
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'password' => bcrypt('password'),
            'portal_enabled' => $this->faker->boolean(50),
            'portal_access_token' => null,
            'portal_token_expires_at' => null,
            'last_portal_login_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'preferred_language' => $this->faker->randomElement(['de', 'en']),
            'email_verified_at' => $this->faker->optional(0.8)->dateTime(),
            'remember_token' => Str::random(10),
        ];
    }

    public function verified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => now(),
            ];
        });
    }

    public function portalEnabled()
    {
        return $this->state(function (array $attributes) {
            return [
                'portal_enabled' => true,
                'email_verified_at' => now(),
            ];
        });
    }
}