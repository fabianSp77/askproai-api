<?php

namespace Database\Factories;

use App\Models\CallbackRequest;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CallbackRequest>
 */
class CallbackRequestFactory extends Factory
{
    protected $model = CallbackRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $branch = Branch::factory()->create();
        $customer = $this->faker->boolean(80) ? Customer::factory()->create(['company_id' => $branch->company_id]) : null;
        $service = $this->faker->boolean(70) ? Service::factory()->create(['company_id' => $branch->company_id]) : null;
        $staff = $this->faker->boolean(60) ? Staff::factory()->create(['company_id' => $branch->company_id, 'branch_id' => $branch->id]) : null;

        $priority = $this->faker->randomElement(CallbackRequest::PRIORITIES);
        $status = $this->faker->randomElement(CallbackRequest::STATUSES);

        // Generate expiration based on priority
        $expiresAt = $this->generateExpirationTime($priority);

        return [
            'customer_id' => $customer?->id,
            'branch_id' => $branch->id,
            'service_id' => $service?->id,
            'staff_id' => $staff?->id,
            'phone_number' => $this->faker->phoneNumber(),
            'customer_name' => $customer?->name ?? $this->faker->name(),
            'preferred_time_window' => $this->generatePreferredTimeWindow(),
            'priority' => $priority,
            'status' => $status,
            'assigned_to' => $this->generateAssignedTo($status, $branch),
            'notes' => $this->faker->optional(0.7)->sentence(),
            'metadata' => $this->generateMetadata(),
            'assigned_at' => $this->generateAssignedAt($status),
            'contacted_at' => $this->generateContactedAt($status),
            'completed_at' => $this->generateCompletedAt($status),
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Generate preferred time window.
     *
     * @return array
     */
    private function generatePreferredTimeWindow(): array
    {
        $preferences = [
            ['day' => 'morning', 'time_range' => '9:00 AM - 12:00 PM'],
            ['day' => 'afternoon', 'time_range' => '12:00 PM - 5:00 PM'],
            ['day' => 'evening', 'time_range' => '5:00 PM - 8:00 PM'],
            ['day' => 'any', 'time_range' => 'anytime'],
        ];

        $selectedPreference = $this->faker->randomElement($preferences);

        return [
            'preferred_days' => $this->faker->randomElements(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'], $this->faker->numberBetween(1, 3)),
            'preferred_time' => $selectedPreference['day'],
            'time_range' => $selectedPreference['time_range'],
            'timezone' => $this->faker->timezone(),
            'flexible' => $this->faker->boolean(60),
        ];
    }

    /**
     * Generate metadata.
     *
     * @return array
     */
    private function generateMetadata(): array
    {
        return [
            'source' => $this->faker->randomElement(['phone', 'web', 'chat', 'email', 'walk-in']),
            'language' => $this->faker->randomElement(['en', 'es', 'fr', 'de']),
            'previous_attempts' => $this->faker->numberBetween(0, 3),
            'last_contact_attempt' => $this->faker->optional(0.5)->dateTimeBetween('-7 days', 'now')?->format('Y-m-d H:i:s'),
            'urgency_reason' => $this->faker->optional(0.3)->sentence(),
            'special_requirements' => $this->faker->optional(0.4)->sentence(),
            'referral_source' => $this->faker->optional(0.3)->randomElement(['google', 'friend', 'returning_customer', 'social_media']),
        ];
    }

    /**
     * Generate expiration time based on priority.
     *
     * @param string $priority
     * @return Carbon
     */
    private function generateExpirationTime(string $priority): Carbon
    {
        return match ($priority) {
            CallbackRequest::PRIORITY_URGENT => Carbon::now()->addHours($this->faker->numberBetween(2, 4)),
            CallbackRequest::PRIORITY_HIGH => Carbon::now()->addHours($this->faker->numberBetween(8, 24)),
            CallbackRequest::PRIORITY_NORMAL => Carbon::now()->addDays($this->faker->numberBetween(1, 3)),
            default => Carbon::now()->addDays(2),
        };
    }

    /**
     * Generate assigned_to based on status.
     *
     * @param string $status
     * @param Branch $branch
     * @return string|null
     */
    private function generateAssignedTo(string $status, Branch $branch): ?string
    {
        if (in_array($status, [CallbackRequest::STATUS_ASSIGNED, CallbackRequest::STATUS_CONTACTED, CallbackRequest::STATUS_COMPLETED])) {
            return Staff::factory()->create(['company_id' => $branch->company_id, 'branch_id' => $branch->id])->id;
        }

        return null;
    }

    /**
     * Generate assigned_at timestamp.
     *
     * @param string $status
     * @return Carbon|null
     */
    private function generateAssignedAt(string $status): ?Carbon
    {
        if (in_array($status, [CallbackRequest::STATUS_ASSIGNED, CallbackRequest::STATUS_CONTACTED, CallbackRequest::STATUS_COMPLETED])) {
            return Carbon::now()->subMinutes($this->faker->numberBetween(5, 180));
        }

        return null;
    }

    /**
     * Generate contacted_at timestamp.
     *
     * @param string $status
     * @return Carbon|null
     */
    private function generateContactedAt(string $status): ?Carbon
    {
        if (in_array($status, [CallbackRequest::STATUS_CONTACTED, CallbackRequest::STATUS_COMPLETED])) {
            return Carbon::now()->subMinutes($this->faker->numberBetween(2, 120));
        }

        return null;
    }

    /**
     * Generate completed_at timestamp.
     *
     * @param string $status
     * @return Carbon|null
     */
    private function generateCompletedAt(string $status): ?Carbon
    {
        if ($status === CallbackRequest::STATUS_COMPLETED) {
            return Carbon::now()->subMinutes($this->faker->numberBetween(1, 60));
        }

        return null;
    }

    /**
     * Create a pending callback.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CallbackRequest::STATUS_PENDING,
            'assigned_to' => null,
            'assigned_at' => null,
            'contacted_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Create an assigned callback.
     */
    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CallbackRequest::STATUS_ASSIGNED,
            'assigned_at' => Carbon::now()->subMinutes($this->faker->numberBetween(5, 180)),
            'contacted_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Create a contacted callback.
     */
    public function contacted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CallbackRequest::STATUS_CONTACTED,
            'assigned_at' => Carbon::now()->subMinutes($this->faker->numberBetween(30, 240)),
            'contacted_at' => Carbon::now()->subMinutes($this->faker->numberBetween(2, 120)),
            'completed_at' => null,
        ]);
    }

    /**
     * Create a completed callback.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CallbackRequest::STATUS_COMPLETED,
            'assigned_at' => Carbon::now()->subMinutes($this->faker->numberBetween(60, 360)),
            'contacted_at' => Carbon::now()->subMinutes($this->faker->numberBetween(30, 180)),
            'completed_at' => Carbon::now()->subMinutes($this->faker->numberBetween(1, 60)),
        ]);
    }

    /**
     * Create an overdue callback.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $this->faker->randomElement([CallbackRequest::STATUS_PENDING, CallbackRequest::STATUS_ASSIGNED]),
            'expires_at' => Carbon::now()->subHours($this->faker->numberBetween(1, 48)),
        ]);
    }

    /**
     * Create an urgent callback.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => CallbackRequest::PRIORITY_URGENT,
            'expires_at' => Carbon::now()->addHours($this->faker->numberBetween(2, 4)),
        ]);
    }

    /**
     * Create a high priority callback.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => CallbackRequest::PRIORITY_HIGH,
            'expires_at' => Carbon::now()->addHours($this->faker->numberBetween(8, 24)),
        ]);
    }

    /**
     * Create a normal priority callback.
     */
    public function normalPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => CallbackRequest::PRIORITY_NORMAL,
            'expires_at' => Carbon::now()->addDays($this->faker->numberBetween(1, 3)),
        ]);
    }

    /**
     * Create callback for walk-in customer (no customer record).
     */
    public function walkIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => null,
            'customer_name' => $this->faker->name(),
        ]);
    }

    /**
     * Create callback with no service preference.
     */
    public function noServicePreference(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_id' => null,
            'staff_id' => null,
        ]);
    }
}
