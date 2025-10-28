<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = Carbon::now()->addDays($this->faker->numberBetween(1, 30))->setTime(
            $this->faker->numberBetween(8, 17),
            $this->faker->randomElement([0, 15, 30, 45])
        );

        $duration = $this->faker->randomElement([30, 45, 60, 90, 120]);
        $endsAt = $startsAt->copy()->addMinutes($duration);

        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $service = Service::factory()->create(['company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $staff = Staff::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        return [
            'company_id' => $company->id,
            'branch_id' => $branch->id, // Required for multi-tenant isolation
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'is_composite' => false,
            'composite_group_uid' => null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => fake()->randomElement(['pending', 'booked', 'confirmed', 'completed', 'cancelled', 'no-show']),
            'source' => fake()->randomElement(['api', 'web', 'phone', 'walk-in', 'cal.com']),
            'calcom_booking_id' => null,
            'calcom_v2_booking_id' => null,
            'price' => fake()->randomFloat(2, 20, 500),
            'segments' => null,
            'metadata' => [],
            'notes' => fake()->optional()->sentence(),
            'google_event_id' => null,
            'outlook_event_id' => null,
            'is_recurring' => false,
            'recurring_pattern' => null,
            'external_calendar_source' => null,
            'external_calendar_id' => null,
        ];
    }

    /**
     * Indicate that the appointment is composite.
     */
    public function composite(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_composite' => true,
            'composite_group_uid' => (string) Str::uuid(),
            'segments' => [
                [
                    'key' => 'A',
                    'name' => 'First Treatment',
                    'starts_at' => $attributes['starts_at'],
                    'ends_at' => Carbon::parse($attributes['starts_at'])->addMinutes(60)->toIso8601String(),
                    'staff_id' => Staff::inRandomOrder()->first()->id ?? 1,
                    'calcom_booking_id' => 'cal_seg_' . fake()->uuid()
                ],
                [
                    'key' => 'B',
                    'name' => 'Second Treatment',
                    'starts_at' => Carbon::parse($attributes['starts_at'])->addMinutes(90)->toIso8601String(),
                    'ends_at' => Carbon::parse($attributes['starts_at'])->addMinutes(150)->toIso8601String(),
                    'staff_id' => Staff::inRandomOrder()->first()->id ?? 1,
                    'calcom_booking_id' => 'cal_seg_' . fake()->uuid()
                ]
            ],
        ]);
    }

    /**
     * Indicate that the appointment is booked.
     */
    public function booked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'booked',
            'calcom_v2_booking_id' => 'cal_v2_' . fake()->uuid(),
            'calcom_reschedule_uid' => 'res_' . fake()->uuid(),
        ]);
    }

    /**
     * Indicate that the appointment is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'confirmation_sent' => true,
                'confirmation_sent_at' => now()->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Indicate that the appointment is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'calcom_cancel_uid' => 'cancel_' . fake()->uuid(),
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'cancelled_at' => now()->toIso8601String(),
                'cancellation_reason' => fake()->randomElement([
                    'Customer request',
                    'Staff unavailable',
                    'Schedule conflict',
                    'Weather',
                    'Other'
                ]),
            ]),
        ]);
    }

    /**
     * Indicate that the appointment is in the past.
     */
    public function past(): static
    {
        $startsAt = Carbon::now()->subDays(fake()->numberBetween(1, 30))->setTime(
            fake()->numberBetween(8, 17),
            fake()->randomElement([0, 15, 30, 45])
        );

        return $this->state(fn (array $attributes) => [
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(60),
            'status' => fake()->randomElement(['completed', 'no-show', 'cancelled']),
        ]);
    }

    /**
     * Indicate that the appointment is today.
     */
    public function today(): static
    {
        $startsAt = Carbon::today()->setTime(
            fake()->numberBetween(8, 17),
            fake()->randomElement([0, 15, 30, 45])
        );

        return $this->state(fn (array $attributes) => [
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(60),
        ]);
    }
}