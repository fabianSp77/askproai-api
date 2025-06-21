<?php

namespace Database\Factories;

use App\Models\BillingPeriod;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class BillingPeriodFactory extends Factory
{
    protected $model = BillingPeriod::class;

    public function definition()
    {
        $start = Carbon::parse($this->faker->dateTimeBetween('-3 months', '-1 month'))->startOfMonth();
        $end = $start->copy()->endOfMonth();
        
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'period_start' => $start,
            'period_end' => $end,
            'total_minutes' => $this->faker->numberBetween(0, 5000),
            'total_calls' => $this->faker->numberBetween(0, 100),
            'is_invoiced' => false,
            'invoice_id' => null,
            'pricing_model_id' => null,
        ];
    }

    public function invoiced()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_invoiced' => true,
            ];
        });
    }

    public function currentMonth()
    {
        return $this->state(function (array $attributes) {
            return [
                'period_start' => now()->startOfMonth(),
                'period_end' => now()->endOfMonth(),
            ];
        });
    }
}