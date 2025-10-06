<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 5000);
        $taxRate = Invoice::TAX_RATE_STANDARD;
        $discountAmount = 0;
        $taxableAmount = $subtotal - $discountAmount;
        $taxAmount = ($taxableAmount * $taxRate) / 100;
        $totalAmount = $taxableAmount + $taxAmount;

        $issueDate = $this->faker->dateTimeBetween('-30 days', 'now');
        $dueDate = (clone $issueDate)->modify('+30 days');

        return [
            'invoice_number' => 'INV-' . date('Y-m') . '-' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'status' => $this->faker->randomElement([
                Invoice::STATUS_DRAFT,
                Invoice::STATUS_PENDING,
                Invoice::STATUS_SENT,
                Invoice::STATUS_PAID,
            ]),
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'balance_due' => $totalAmount,
            'tax_rate' => $taxRate,
            'currency' => Invoice::CURRENCY_EUR,
            'exchange_rate' => 1.0,
            'billing_name' => $this->faker->name(),
            'billing_email' => $this->faker->safeEmail(),
            'billing_phone' => $this->faker->phoneNumber(),
            'payment_method' => $this->faker->randomElement(['bank_transfer', 'credit_card', 'paypal', 'stripe']),
            'line_items' => [
                [
                    'description' => $this->faker->sentence(4),
                    'quantity' => 1,
                    'price' => $subtotal,
                    'total' => $subtotal,
                ]
            ],
            'metadata' => [],
            'is_recurring' => false,
            'reminder_count' => 0,
            'created_by' => null,
        ];
    }

    /**
     * Indicate that the invoice is paid
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_PAID,
            'paid_amount' => $attributes['total_amount'],
            'balance_due' => 0,
            'paid_date' => now(),
            'paid_at' => now(),
        ]);
    }

    /**
     * Indicate that the invoice is overdue
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_OVERDUE,
            'due_date' => now()->subDays(15),
            'paid_amount' => 0,
        ]);
    }

    /**
     * Indicate that the invoice is partially paid
     */
    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_PARTIAL,
            'paid_amount' => $attributes['total_amount'] * 0.5,
            'balance_due' => $attributes['total_amount'] * 0.5,
        ]);
    }

    /**
     * Indicate that the invoice is draft
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_DRAFT,
            'paid_amount' => 0,
            'sent_at' => null,
        ]);
    }

    /**
     * Indicate that the invoice is recurring
     */
    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => true,
            'recurring_period' => $this->faker->randomElement(['weekly', 'monthly', 'quarterly', 'yearly']),
        ]);
    }
}
