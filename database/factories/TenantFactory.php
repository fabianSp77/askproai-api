<?php // database/factories/TenantFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str; // Import Str für random Strings
use App\Models\Tenant; // Import Tenant Model

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
            'name' => $this->faker->company(), // Generiere einen Firmennamen
            'api_key' => Str::random(40), // Generiere einen zufälligen API Key (Länge 40)
            'is_active' => true, // Standardmäßig aktiv
            // Füge hier ggf. Defaults für Cal.com-Felder hinzu, falls diese existieren
            // 'calcom_api_key_encrypted' => null,
            // 'calcom_event_type_id' => null,
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
}
