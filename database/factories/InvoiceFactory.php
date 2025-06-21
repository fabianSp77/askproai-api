<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition()
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'invoice_number' => 'INV-' . $this->faker->year . '-' . str_pad($this->faker->numberBetween(1, 9999), 5, '0', STR_PAD_LEFT),
            'status' => $this->faker->randomElement([Invoice::STATUS_DRAFT, Invoice::STATUS_OPEN, Invoice::STATUS_PAID]),
            'subtotal' => $this->faker->randomFloat(2, 10, 1000),
            'tax_amount' => $this->faker->randomFloat(2, 0, 190),
            'total' => $this->faker->randomFloat(2, 10, 1190),
            'currency' => 'EUR',
            'invoice_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'due_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'billing_reason' => $this->faker->randomElement([Invoice::REASON_SUBSCRIPTION_CYCLE, Invoice::REASON_MANUAL, Invoice::REASON_SUBSCRIPTION_UPDATE]),
            'period_start' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'period_end' => $this->faker->dateTimeBetween('now', '+1 month'),
            'manual_editable' => $this->faker->boolean(20),
            'auto_advance' => $this->faker->boolean(80),
            'metadata' => [],
            'audit_log' => [],
        ];
    }

    public function draft()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Invoice::STATUS_DRAFT,
                'manual_editable' => true,
                'finalized_at' => null,
            ];
        });
    }

    public function paid()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Invoice::STATUS_PAID,
                'paid_at' => now(),
                'manual_editable' => false,
                'finalized_at' => now()->subDays(rand(1, 30)),
            ];
        });
    }
}