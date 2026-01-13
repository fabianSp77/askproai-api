<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyFeeSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for CompanyFeeSchedule model.
 */
class CompanyFeeScheduleFactory extends Factory
{
    protected $model = CompanyFeeSchedule::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'billing_mode' => 'per_minute',
            'setup_fee' => 0,
            'setup_fee_billed_at' => null,
            'setup_fee_transaction_id' => null,
            'override_per_minute_rate' => null,
            'override_discount_percentage' => null,
            'metadata' => [],
            'notes' => null,
        ];
    }

    /**
     * Per-second billing mode.
     */
    public function perSecond(): static
    {
        return $this->state(fn () => [
            'billing_mode' => 'per_second',
        ]);
    }

    /**
     * Per-minute billing mode.
     */
    public function perMinute(): static
    {
        return $this->state(fn () => [
            'billing_mode' => 'per_minute',
        ]);
    }

    /**
     * With setup fee.
     */
    public function withSetupFee(int $cents = 5000): static
    {
        return $this->state(fn () => [
            'setup_fee' => $cents,
        ]);
    }

    /**
     * With custom per-minute rate.
     */
    public function withCustomRate(int $centsPerMinute = 20): static
    {
        return $this->state(fn () => [
            'override_per_minute_rate' => $centsPerMinute,
        ]);
    }
}
