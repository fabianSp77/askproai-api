<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 50, 1000);
        $taxRate = 19; // German VAT
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total = $subtotal + $taxAmount;
        
        return [
            'company_id' => Company::factory(),
            'number' => 'INV-' . $this->faker->unique()->numberBetween(10000, 99999),
            'invoice_date' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'due_date' => function (array $attributes) {
                return Carbon::parse($attributes['invoice_date'])->addDays(14);
            },
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'status' => $this->faker->randomElement(['draft', 'sent', 'paid', 'overdue']),
            'currency' => 'EUR',
            'stripe_invoice_id' => $this->faker->optional()->regexify('in_[0-9a-zA-Z]{24}'),
            'sent_at' => null,
            'paid_at' => null,
            'metadata' => [],
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_at' => $this->faker->dateTimeBetween($attributes['invoice_date'], 'now'),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => Carbon::now()->subDays($this->faker->numberBetween(1, 30)),
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => $this->faker->dateTimeBetween($attributes['invoice_date'], 'now'),
        ]);
    }
}