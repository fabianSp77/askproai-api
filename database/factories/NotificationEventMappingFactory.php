<?php

namespace Database\Factories;

use App\Models\NotificationEventMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationEventMapping>
 */
class NotificationEventMappingFactory extends Factory
{
    protected $model = NotificationEventMapping::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $eventType = $this->faker->unique()->slug(3);
        $category = $this->faker->randomElement([
            'booking',
            'reminder',
            'modification',
            'callback',
            'system',
        ]);

        return [
            'event_type' => $eventType,
            'event_label' => $this->faker->sentence(3),
            'event_category' => $category,
            'default_channels' => $this->getDefaultChannelsForCategory($category),
            'description' => $this->faker->paragraph(),
            'is_system_event' => $this->faker->boolean(70), // 70% system events
            'is_active' => $this->faker->boolean(90), // 90% active
            'metadata' => [
                'variables' => $this->getVariablesForCategory($category),
                'timing' => $this->faker->randomElement(['immediate', 'scheduled']),
                'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Create a booking event.
     */
    public function booking(): static
    {
        return $this->state([
            'event_category' => 'booking',
            'default_channels' => ['email', 'sms'],
            'is_system_event' => true,
            'is_active' => true,
            'metadata' => [
                'variables' => ['customer_name', 'appointment_date', 'appointment_time', 'service_name', 'branch_address'],
                'timing' => 'immediate',
                'priority' => 'high',
            ],
        ]);
    }

    /**
     * Create a reminder event.
     */
    public function reminder(): static
    {
        return $this->state([
            'event_category' => 'reminder',
            'default_channels' => ['email', 'sms', 'whatsapp'],
            'is_system_event' => true,
            'is_active' => true,
            'metadata' => [
                'variables' => ['customer_name', 'appointment_date', 'appointment_time', 'service_name', 'cancel_url', 'reschedule_url'],
                'timing' => 'scheduled',
                'schedule_offset_hours' => -24,
                'priority' => 'high',
            ],
        ]);
    }

    /**
     * Create a modification event.
     */
    public function modification(): static
    {
        return $this->state([
            'event_category' => 'modification',
            'default_channels' => ['email', 'sms'],
            'is_system_event' => true,
            'is_active' => true,
            'metadata' => [
                'variables' => ['customer_name', 'old_date', 'old_time', 'new_date', 'new_time', 'service_name'],
                'timing' => 'immediate',
                'priority' => 'high',
            ],
        ]);
    }

    /**
     * Create a callback event.
     */
    public function callback(): static
    {
        return $this->state([
            'event_category' => 'callback',
            'default_channels' => ['email', 'sms'],
            'is_system_event' => true,
            'is_active' => true,
            'metadata' => [
                'variables' => ['customer_name', 'request_time', 'expected_callback_time', 'staff_name'],
                'timing' => 'immediate',
                'priority' => 'medium',
            ],
        ]);
    }

    /**
     * Create a system event.
     */
    public function system(): static
    {
        return $this->state([
            'event_category' => 'system',
            'default_channels' => ['email'],
            'is_system_event' => true,
            'is_active' => true,
            'metadata' => [
                'variables' => ['customer_name', 'service_name', 'date', 'time'],
                'timing' => 'immediate',
                'priority' => $this->faker->randomElement(['low', 'medium']),
            ],
        ]);
    }

    /**
     * Create a custom (non-system) event.
     */
    public function custom(): static
    {
        return $this->state([
            'is_system_event' => false,
        ]);
    }

    /**
     * Create an inactive event.
     */
    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }

    /**
     * Get default channels based on category.
     */
    protected function getDefaultChannelsForCategory(string $category): array
    {
        return match ($category) {
            'booking' => ['email', 'sms'],
            'reminder' => ['email', 'sms', 'whatsapp'],
            'modification' => ['email', 'sms'],
            'callback' => ['email', 'sms'],
            'system' => ['email'],
            default => ['email'],
        };
    }

    /**
     * Get typical variables based on category.
     */
    protected function getVariablesForCategory(string $category): array
    {
        return match ($category) {
            'booking' => ['customer_name', 'appointment_date', 'appointment_time', 'service_name', 'branch_address', 'staff_name'],
            'reminder' => ['customer_name', 'appointment_date', 'appointment_time', 'service_name', 'cancel_url', 'reschedule_url'],
            'modification' => ['customer_name', 'old_date', 'old_time', 'new_date', 'new_time', 'service_name'],
            'callback' => ['customer_name', 'request_time', 'expected_callback_time', 'preferred_contact_method', 'staff_name'],
            'system' => ['customer_name', 'service_name', 'date', 'time'],
            default => ['customer_name', 'date', 'time'],
        };
    }
}
