<?php

namespace Database\Factories;

use App\Models\CallbackEscalation;
use App\Models\CallbackRequest;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CallbackEscalation>
 */
class CallbackEscalationFactory extends Factory
{
    protected $model = CallbackEscalation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $escalatedAt = $this->faker->dateTimeBetween('-7 days', 'now');
        $isResolved = $this->faker->boolean(60); // 60% chance of being resolved

        return [
            'callback_request_id' => CallbackRequest::factory(),
            'escalation_reason' => $this->faker->randomElement([
                'sla_breach',
                'manual_escalation',
                'multiple_attempts_failed',
            ]),
            'escalated_from' => $this->faker->boolean(70) ? Staff::factory() : null,
            'escalated_to' => Staff::factory(),
            'escalated_at' => $escalatedAt,
            'resolved_at' => $isResolved ? $this->faker->dateTimeBetween($escalatedAt, 'now') : null,
            'resolution_notes' => $isResolved ? $this->faker->paragraph() : null,
            'metadata' => [
                'escalation_level' => $this->faker->numberBetween(1, 3),
                'previous_attempts' => $this->faker->numberBetween(1, 5),
                'urgency' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            ],
            'created_at' => $escalatedAt,
            'updated_at' => now(),
        ];
    }

    /**
     * Create an SLA breach escalation.
     */
    public function slaBreach(): static
    {
        return $this->state([
            'escalation_reason' => 'sla_breach',
            'metadata' => [
                'escalation_level' => 2,
                'sla_deadline' => now()->subHours(2)->toIso8601String(),
                'breach_duration_minutes' => $this->faker->numberBetween(30, 240),
                'urgency' => 'high',
            ],
        ]);
    }

    /**
     * Create a manual escalation.
     */
    public function manual(): static
    {
        return $this->state([
            'escalation_reason' => 'manual_escalation',
            'escalated_from' => Staff::factory(),
            'metadata' => [
                'escalation_level' => 1,
                'reason_details' => $this->faker->sentence(),
                'requested_by' => $this->faker->name(),
                'urgency' => $this->faker->randomElement(['medium', 'high']),
            ],
        ]);
    }

    /**
     * Create a multiple attempts failed escalation.
     */
    public function multipleAttemptsFailed(): static
    {
        return $this->state([
            'escalation_reason' => 'multiple_attempts_failed',
            'metadata' => [
                'escalation_level' => 2,
                'previous_attempts' => $this->faker->numberBetween(3, 8),
                'last_attempt_at' => now()->subHours(1)->toIso8601String(),
                'attempt_methods' => ['phone', 'sms', 'email'],
                'urgency' => 'medium',
            ],
        ]);
    }

    /**
     * Create an unresolved escalation.
     */
    public function unresolved(): static
    {
        return $this->state([
            'resolved_at' => null,
            'resolution_notes' => null,
        ]);
    }

    /**
     * Create a resolved escalation.
     */
    public function resolved(): static
    {
        $escalatedAt = $this->faker->dateTimeBetween('-5 days', '-1 day');

        return $this->state([
            'escalated_at' => $escalatedAt,
            'resolved_at' => $this->faker->dateTimeBetween($escalatedAt, 'now'),
            'resolution_notes' => $this->faker->paragraph(),
            'metadata' => [
                'escalation_level' => $this->faker->numberBetween(1, 2),
                'resolution_method' => $this->faker->randomElement(['callback_completed', 'appointment_booked', 'customer_unavailable']),
                'resolution_duration_minutes' => $this->faker->numberBetween(15, 180),
                'urgency' => $this->faker->randomElement(['low', 'medium', 'high']),
            ],
        ]);
    }

    /**
     * Create a critical escalation.
     */
    public function critical(): static
    {
        return $this->state([
            'escalation_reason' => $this->faker->randomElement(['sla_breach', 'multiple_attempts_failed']),
            'resolved_at' => null,
            'metadata' => [
                'escalation_level' => 3,
                'urgency' => 'critical',
                'requires_manager_approval' => true,
                'customer_complaint' => true,
            ],
        ]);
    }
}
