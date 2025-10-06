<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Service::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'company_id' => Company::factory(),
            'branch_id' => null,
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'is_active' => true,
            'calcom_event_type_id' => (string) $this->faker->numberBetween(100000, 999999),
        ];
    }

    /**
     * Indicate that the service is synced with Cal.com
     */
    public function synced(): static
    {
        return $this->state(fn (array $attributes) => [
            'calcom_event_type_id' => (string) $this->faker->numberBetween(100000, 999999),
        ]);
    }

    /**
     * Indicate that the service has a sync error
     */
    public function withSyncError(): static
    {
        return $this->state(fn (array $attributes) => [
            'calcom_event_type_id' => null,
        ]);
    }

    /**
     * Indicate that the service is pending sync
     */
    public function pendingSync(): static
    {
        return $this->state(fn (array $attributes) => [
            'calcom_event_type_id' => null,
        ]);
    }
}