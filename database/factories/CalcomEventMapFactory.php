<?php

namespace Database\Factories;

use App\Models\CalcomEventMap;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CalcomEventMap>
 */
class CalcomEventMapFactory extends Factory
{
    protected $model = CalcomEventMap::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $service = Service::factory()->create(['company_id' => $company->id]);
        $staff = Staff::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        $segmentKey = fake()->optional()->randomElement(['A', 'B', 'C']);

        return [
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'segment_key' => $segmentKey,
            'event_type_id' => fake()->numberBetween(1000, 9999),
            'event_name_pattern' => $this->generateEventNamePattern($segmentKey),
            'sync_status' => fake()->randomElement(['pending', 'synced', 'error']),
            'external_changes' => fake()->randomElement(['warn', 'accept', 'reset']),
            'last_sync_at' => fake()->optional()->dateTimeBetween('-7 days', 'now'),
            'sync_error' => null,
            'drift_detected_at' => null,
            'drift_data' => null,
        ];
    }

    /**
     * Generate event name pattern based on segment
     */
    private function generateEventNamePattern(?string $segmentKey = null): string
    {
        $pattern = strtoupper(fake()->lexify('???')) . '-' .
                   strtoupper(fake()->lexify('???')) . '-' .
                   strtoupper(fake()->lexify('???'));

        if ($segmentKey) {
            $pattern .= '-' . $segmentKey;
        }

        $pattern .= '-' . strtoupper(fake()->lexify('???'));

        return $pattern;
    }

    /**
     * Indicate that the mapping is synced.
     */
    public function synced(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_status' => 'synced',
            'last_sync_at' => now(),
            'sync_error' => null,
        ]);
    }

    /**
     * Indicate that the mapping has an error.
     */
    public function withError(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_status' => 'error',
            'sync_error' => fake()->randomElement([
                'Event type not found in Cal.com',
                'Invalid credentials',
                'Rate limit exceeded',
                'Network timeout',
                'Duplicate event name',
            ]),
            'last_sync_at' => now(),
        ]);
    }

    /**
     * Indicate that the mapping is pending sync.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_status' => 'pending',
            'last_sync_at' => null,
            'sync_error' => null,
        ]);
    }

    /**
     * Indicate that drift has been detected.
     */
    public function withDrift(): static
    {
        return $this->state(fn (array $attributes) => [
            'drift_detected_at' => now(),
            'drift_data' => [
                'type' => fake()->randomElement(['modified', 'deleted', 'unauthorized']),
                'differences' => [
                    'duration' => [
                        'local' => 60,
                        'remote' => fake()->randomElement([30, 45, 90, 120]),
                    ],
                    'hidden' => [
                        'local' => true,
                        'remote' => fake()->boolean(),
                    ],
                    'name' => [
                        'local' => $attributes['event_name_pattern'],
                        'remote' => 'Modified ' . $attributes['event_name_pattern'],
                    ],
                ],
                'detected_by' => 'drift_detection_service',
                'severity' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            ],
        ]);
    }

    /**
     * Indicate that this is for a composite service segment.
     */
    public function forSegment(string $key): static
    {
        return $this->state(fn (array $attributes) => [
            'segment_key' => $key,
            'event_name_pattern' => $this->generateEventNamePattern($key),
        ]);
    }

    /**
     * Indicate auto-resolve policy.
     */
    public function autoResolve(): static
    {
        return $this->state(fn (array $attributes) => [
            'external_changes' => 'accept',
        ]);
    }

    /**
     * Indicate reset policy.
     */
    public function resetOnChange(): static
    {
        return $this->state(fn (array $attributes) => [
            'external_changes' => 'reset',
        ]);
    }

    /**
     * Indicate warn policy (default).
     */
    public function warnOnChange(): static
    {
        return $this->state(fn (array $attributes) => [
            'external_changes' => 'warn',
        ]);
    }
}