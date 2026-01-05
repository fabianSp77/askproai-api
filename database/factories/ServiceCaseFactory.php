<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceCase>
 */
class ServiceCaseFactory extends Factory
{
    protected $model = ServiceCase::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'category_id' => ServiceCaseCategory::factory(),
            'case_type' => $this->faker->randomElement(ServiceCase::CASE_TYPES),
            'priority' => $this->faker->randomElement(ServiceCase::PRIORITIES),
            'urgency' => $this->faker->randomElement(ServiceCase::PRIORITIES),
            'impact' => $this->faker->randomElement(ServiceCase::PRIORITIES),
            'subject' => $this->faker->sentence(5),
            'description' => $this->faker->paragraph(3),
            'status' => ServiceCase::STATUS_NEW,
            'output_status' => ServiceCase::OUTPUT_PENDING,
        ];
    }

    /**
     * Create a case with a call reference.
     */
    public function withCall(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'call_id' => Call::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id,
            ];
        });
    }

    /**
     * Create a case with a customer.
     */
    public function withCustomer(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'customer_id' => Customer::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id,
            ];
        });
    }

    /**
     * Create a case with audio stored.
     */
    public function withAudio(): static
    {
        return $this->state([
            'audio_object_key' => 'audio/' . $this->faker->uuid() . '.mp3',
            'audio_expires_at' => now()->addDays(60),
        ]);
    }

    /**
     * Create a case with expired audio.
     */
    public function withExpiredAudio(): static
    {
        return $this->state([
            'audio_object_key' => 'audio/' . $this->faker->uuid() . '.mp3',
            'audio_expires_at' => now()->subDays(1),
        ]);
    }

    /**
     * Create a critical priority case.
     */
    public function critical(): static
    {
        return $this->state([
            'priority' => 'critical',
            'urgency' => 'critical',
        ]);
    }

    /**
     * Create a high priority case.
     */
    public function high(): static
    {
        return $this->state([
            'priority' => 'high',
            'urgency' => 'high',
        ]);
    }

    /**
     * Create a resolved case.
     */
    public function resolved(): static
    {
        return $this->state([
            'status' => ServiceCase::STATUS_RESOLVED,
            'output_status' => ServiceCase::OUTPUT_SENT,
            'output_sent_at' => now(),
        ]);
    }
}
