<?php

namespace Database\Factories;

use App\Models\AggregateInvoice;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for AggregateInvoice model.
 *
 * Used for billing tests to create invoice test data.
 */
class AggregateInvoiceFactory extends Factory
{
    protected $model = AggregateInvoice::class;

    public function definition(): array
    {
        $periodStart = Carbon::now()->startOfMonth()->subMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        return [
            'partner_company_id' => Company::factory(),
            'invoice_number' => 'AGG-' . date('Y-m') . '-' . str_pad($this->faker->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'billing_period_start' => $periodStart,
            'billing_period_end' => $periodEnd,
            'subtotal_cents' => $this->faker->numberBetween(1000, 50000),
            'discount_cents' => 0,
            'discount_description' => null,
            'tax_cents' => fn (array $attrs) => (int) ($attrs['subtotal_cents'] * 0.19),
            'total_cents' => fn (array $attrs) => $attrs['subtotal_cents'] + (int) ($attrs['subtotal_cents'] * 0.19),
            'currency' => 'EUR',
            'tax_rate' => 19.00,
            'status' => AggregateInvoice::STATUS_DRAFT,
            'due_at' => $periodEnd->copy()->addDays(14),
            'metadata' => [],
        ];
    }

    /**
     * Set a discount on the invoice.
     */
    public function withDiscount(int $discountCents, ?string $description = null): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_cents' => $discountCents,
            'discount_description' => $description,
            // Recalculate totals with discount
            'tax_cents' => fn (array $attrs) => (int) (($attrs['subtotal_cents'] - $discountCents) * 0.19),
            'total_cents' => fn (array $attrs) => ($attrs['subtotal_cents'] - $discountCents) + (int) (($attrs['subtotal_cents'] - $discountCents) * 0.19),
        ]);
    }

    /**
     * Set invoice as draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AggregateInvoice::STATUS_DRAFT,
            'finalized_at' => null,
            'sent_at' => null,
            'paid_at' => null,
        ]);
    }

    /**
     * Set invoice as sent/open status.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AggregateInvoice::STATUS_OPEN,
            'finalized_at' => now(),
            'sent_at' => now(),
            'paid_at' => null,
        ]);
    }

    /**
     * Set invoice as paid status.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AggregateInvoice::STATUS_PAID,
            'finalized_at' => now()->subDays(7),
            'sent_at' => now()->subDays(7),
            'paid_at' => now(),
        ]);
    }

    /**
     * Set invoice as void status.
     */
    public function void(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AggregateInvoice::STATUS_VOID,
            'finalized_at' => now()->subDays(7),
        ]);
    }

    /**
     * Link to Stripe.
     */
    public function withStripe(string $invoiceId = null, string $customerId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_invoice_id' => $invoiceId ?? 'in_test_' . $this->faker->uuid(),
            'stripe_customer_id' => $customerId ?? 'cus_test_' . $this->faker->uuid(),
            'stripe_hosted_invoice_url' => 'https://invoice.stripe.com/' . $this->faker->uuid(),
            'stripe_pdf_url' => 'https://invoice.stripe.com/' . $this->faker->uuid() . '/pdf',
        ]);
    }
}
