<?php

// database/factories/TenantFactory.php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory; // Import Str für random Strings
use Illuminate\Support\Str; // Import Tenant Model

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Tenant::class; // Korrektes Model zuweisen

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Test Company '.$this->faker->numberBetween(1000, 9999),
            // The api_key_hash will be automatically generated in the model's creating event
            // Don't include 'api_key' here as that column doesn't exist anymore
        ];
    }

    /**
     * Indicate that the tenant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create tenant with a specific API key for testing.
     */
    public function withApiKey(string $plainApiKey): static
    {
        return $this->state(fn (array $attributes) => [
            'api_key_hash' => bcrypt($plainApiKey),
        ]);
    }
}
