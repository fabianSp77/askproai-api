<?php

namespace Database\Factories;

use App\Models\NotificationConfiguration;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationConfiguration>
 */
class NotificationConfigurationFactory extends Factory
{
    protected $model = NotificationConfiguration::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $channels = ['email', 'sms', 'whatsapp', 'push'];
        $fallbackChannels = [...$channels, 'none'];
        $eventTypes = [
            'booking_confirmed',
            'booking_pending',
            'reminder_24h',
            'reminder_2h',
            'reminder_1week',
            'cancellation',
            'reschedule_confirmed',
            'appointment_modified',
            'callback_request_received',
            'callback_scheduled',
            'no_show',
            'appointment_completed',
            'payment_received',
        ];

        $channel = $this->faker->randomElement($channels);

        return [
            'configurable_type' => $this->faker->randomElement([
                Company::class,
                Branch::class,
                Service::class,
                Staff::class,
            ]),
            'configurable_id' => $this->faker->numberBetween(1, 100),
            'event_type' => $this->faker->randomElement($eventTypes),
            'channel' => $channel,
            'fallback_channel' => $this->faker->randomElement(
                array_filter($fallbackChannels, fn($c) => $c !== $channel)
            ),
            'is_enabled' => $this->faker->boolean(85), // 85% enabled
            'retry_count' => $this->faker->numberBetween(1, 5),
            'retry_delay_minutes' => $this->faker->randomElement([5, 10, 15, 30, 60]),
            'template_override' => $this->faker->boolean(20) ? $this->faker->slug(3) : null,
            'metadata' => [
                'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
                'custom_from_name' => $this->faker->boolean(30) ? $this->faker->company() : null,
                'track_opens' => $this->faker->boolean(60),
                'track_clicks' => $this->faker->boolean(60),
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Create a company-level configuration.
     */
    public function forCompany(?Company $company = null): static
    {
        return $this->state([
            'configurable_type' => Company::class,
            'configurable_id' => $company?->id ?? Company::factory(),
        ]);
    }

    /**
     * Create a branch-level configuration.
     */
    public function forBranch(?Branch $branch = null): static
    {
        return $this->state([
            'configurable_type' => Branch::class,
            'configurable_id' => $branch?->id ?? Branch::factory(),
        ]);
    }

    /**
     * Create a service-level configuration.
     */
    public function forService(?Service $service = null): static
    {
        return $this->state([
            'configurable_type' => Service::class,
            'configurable_id' => $service?->id ?? Service::factory(),
        ]);
    }

    /**
     * Create a staff-level configuration.
     */
    public function forStaff(?Staff $staff = null): static
    {
        return $this->state([
            'configurable_type' => Staff::class,
            'configurable_id' => $staff?->id ?? Staff::factory(),
        ]);
    }

    /**
     * Create an email configuration.
     */
    public function email(): static
    {
        return $this->state([
            'channel' => 'email',
            'fallback_channel' => $this->faker->randomElement(['sms', 'none']),
            'retry_count' => 3,
            'retry_delay_minutes' => 10,
            'metadata' => [
                'priority' => 'medium',
                'custom_from_name' => $this->faker->company(),
                'track_opens' => true,
                'track_clicks' => true,
                'attachments_allowed' => $this->faker->boolean(40),
            ],
        ]);
    }

    /**
     * Create an SMS configuration.
     */
    public function sms(): static
    {
        return $this->state([
            'channel' => 'sms',
            'fallback_channel' => $this->faker->randomElement(['email', 'none']),
            'retry_count' => 2,
            'retry_delay_minutes' => 5,
            'metadata' => [
                'priority' => 'high',
                'max_length' => 160,
                'unicode_enabled' => $this->faker->boolean(50),
                'delivery_report' => true,
            ],
        ]);
    }

    /**
     * Create a WhatsApp configuration.
     */
    public function whatsapp(): static
    {
        return $this->state([
            'channel' => 'whatsapp',
            'fallback_channel' => $this->faker->randomElement(['sms', 'email']),
            'retry_count' => 3,
            'retry_delay_minutes' => 15,
            'metadata' => [
                'priority' => 'high',
                'template_approved' => true,
                'media_enabled' => $this->faker->boolean(70),
                'interactive_buttons' => $this->faker->boolean(50),
            ],
        ]);
    }

    /**
     * Create a push notification configuration.
     */
    public function push(): static
    {
        return $this->state([
            'channel' => 'push',
            'fallback_channel' => $this->faker->randomElement(['email', 'sms', 'none']),
            'retry_count' => 2,
            'retry_delay_minutes' => 5,
            'metadata' => [
                'priority' => 'high',
                'badge_count' => $this->faker->numberBetween(1, 10),
                'sound' => $this->faker->randomElement(['default', 'alert', 'silent']),
                'action_buttons' => $this->faker->boolean(60),
            ],
        ]);
    }

    /**
     * Create a disabled configuration.
     */
    public function disabled(): static
    {
        return $this->state([
            'is_enabled' => false,
        ]);
    }

    /**
     * Create a configuration for booking confirmations.
     */
    public function bookingConfirmed(): static
    {
        return $this->state([
            'event_type' => 'booking_confirmed',
            'is_enabled' => true,
            'retry_count' => 3,
            'metadata' => [
                'priority' => 'high',
                'immediate_delivery' => true,
            ],
        ]);
    }

    /**
     * Create a configuration for reminders.
     */
    public function reminder24h(): static
    {
        return $this->state([
            'event_type' => 'reminder_24h',
            'is_enabled' => true,
            'retry_count' => 2,
            'metadata' => [
                'priority' => 'high',
                'scheduled_delivery' => true,
            ],
        ]);
    }
}
