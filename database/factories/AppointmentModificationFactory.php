<?php

namespace Database\Factories;

use App\Models\AppointmentModification;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\User;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AppointmentModification>
 */
class AppointmentModificationFactory extends Factory
{
    protected $model = AppointmentModification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $appointment = Appointment::factory()->create();
        $modificationType = $this->faker->randomElement(AppointmentModification::MODIFICATION_TYPES);
        $withinPolicy = $this->faker->boolean(70); // 70% within policy

        // Determine modifier (User, Staff, or Customer)
        $modifierTypes = [User::class, Staff::class, Customer::class];
        $modifierType = $this->faker->randomElement($modifierTypes);

        $modifier = match ($modifierType) {
            User::class => User::factory()->create(),
            Staff::class => Staff::factory()->create(),
            Customer::class => $appointment->customer,
        };

        // Calculate fee based on policy compliance
        $feeCharged = $withinPolicy ? 0.00 : $this->faker->randomElement([25.00, 50.00, 75.00, 100.00]);

        return [
            'appointment_id' => $appointment->id,
            'customer_id' => $appointment->customer_id,
            'modification_type' => $modificationType,
            'within_policy' => $withinPolicy,
            'fee_charged' => $feeCharged,
            'reason' => $this->generateReason($modificationType, $withinPolicy),
            'modified_by_type' => $modifierType,
            'modified_by_id' => $modifier->id,
            'metadata' => $this->generateMetadata($modificationType, $withinPolicy),
            'created_at' => Carbon::now()->subDays($this->faker->numberBetween(0, 90)),
        ];
    }

    /**
     * Generate realistic reason based on modification type and policy compliance.
     *
     * @param string $modificationType
     * @param bool $withinPolicy
     * @return string
     */
    private function generateReason(string $modificationType, bool $withinPolicy): string
    {
        if ($modificationType === AppointmentModification::TYPE_CANCEL) {
            $reasons = $withinPolicy
                ? [
                    'Schedule conflict - gave proper notice',
                    'Personal emergency with advance notice',
                    'Doctor advised rest - notified early',
                    'Work commitment - rescheduled in time',
                ]
                : [
                    'Late cancellation - same day',
                    'No-call no-show',
                    'Forgot appointment',
                    'Cancelled within 24 hours',
                ];
        } else {
            $reasons = $withinPolicy
                ? [
                    'Rescheduled with proper notice',
                    'Requested different time slot in advance',
                    'Schedule change - proper notification given',
                    'Prefer different date - within policy',
                ]
                : [
                    'Same day reschedule request',
                    'Exceeded monthly reschedule limit',
                    'Late reschedule - insufficient notice',
                    'Multiple reschedules this month',
                ];
        }

        return $this->faker->randomElement($reasons);
    }

    /**
     * Generate metadata based on modification type and policy compliance.
     *
     * @param string $modificationType
     * @param bool $withinPolicy
     * @return array
     */
    private function generateMetadata(string $modificationType, bool $withinPolicy): array
    {
        $metadata = [
            'notification_sent' => $this->faker->boolean(90),
            'customer_notified_at' => Carbon::now()->subMinutes($this->faker->numberBetween(5, 120))->toIso8601String(),
            'channel' => $this->faker->randomElement(['web', 'phone', 'email', 'sms', 'app']),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];

        if ($modificationType === AppointmentModification::TYPE_CANCEL) {
            $metadata['cancellation_confirmed'] = true;
            $metadata['refund_issued'] = $withinPolicy ? $this->faker->boolean(80) : false;
            $metadata['refund_amount'] = $withinPolicy ? $this->faker->randomFloat(2, 50, 200) : 0;
        } else {
            $metadata['original_date'] = Carbon::now()->addDays($this->faker->numberBetween(1, 30))->toIso8601String();
            $metadata['new_date'] = Carbon::now()->addDays($this->faker->numberBetween(5, 45))->toIso8601String();
            $metadata['staff_changed'] = $this->faker->boolean(30);
        }

        if (!$withinPolicy) {
            $metadata['policy_violation'] = [
                'notice_hours_given' => $this->faker->numberBetween(0, 23),
                'required_notice_hours' => $this->faker->numberBetween(24, 72),
                'violation_type' => $this->faker->randomElement(['insufficient_notice', 'limit_exceeded', 'blackout_period']),
            ];
        }

        return $metadata;
    }

    /**
     * Create a cancellation modification.
     */
    public function cancellation(): static
    {
        return $this->state(fn (array $attributes) => [
            'modification_type' => AppointmentModification::TYPE_CANCEL,
            'reason' => $this->generateReason(AppointmentModification::TYPE_CANCEL, $attributes['within_policy']),
        ]);
    }

    /**
     * Create a reschedule modification.
     */
    public function reschedule(): static
    {
        return $this->state(fn (array $attributes) => [
            'modification_type' => AppointmentModification::TYPE_RESCHEDULE,
            'reason' => $this->generateReason(AppointmentModification::TYPE_RESCHEDULE, $attributes['within_policy']),
        ]);
    }

    /**
     * Create a modification within policy.
     */
    public function withinPolicy(): static
    {
        return $this->state(fn (array $attributes) => [
            'within_policy' => true,
            'fee_charged' => 0.00,
        ]);
    }

    /**
     * Create a modification outside policy with fee.
     */
    public function outsidePolicy(): static
    {
        return $this->state(fn (array $attributes) => [
            'within_policy' => false,
            'fee_charged' => $this->faker->randomElement([25.00, 50.00, 75.00, 100.00]),
        ]);
    }

    /**
     * Create a recent modification (within 30 days).
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => Carbon::now()->subDays($this->faker->numberBetween(0, 29)),
        ]);
    }

    /**
     * Create an old modification (30+ days ago).
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => Carbon::now()->subDays($this->faker->numberBetween(30, 180)),
        ]);
    }
}
