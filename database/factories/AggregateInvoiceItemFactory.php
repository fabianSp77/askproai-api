<?php

namespace Database\Factories;

use App\Models\AggregateInvoice;
use App\Models\AggregateInvoiceItem;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for AggregateInvoiceItem model.
 */
class AggregateInvoiceItemFactory extends Factory
{
    protected $model = AggregateInvoiceItem::class;

    public function definition(): array
    {
        return [
            'aggregate_invoice_id' => AggregateInvoice::factory(),
            'company_id' => Company::factory(),
            'item_type' => $this->faker->randomElement([
                AggregateInvoiceItem::TYPE_CALL_MINUTES,
                AggregateInvoiceItem::TYPE_MONTHLY_SERVICE,
                AggregateInvoiceItem::TYPE_CUSTOM,
            ]),
            'description' => $this->faker->sentence(3),
            'description_detail' => $this->faker->optional()->sentence(5),
            'quantity' => $this->faker->randomFloat(2, 1, 100),
            'unit' => $this->faker->randomElement(['Minuten', 'Monat', 'Stück', null]),
            'unit_price_cents' => $this->faker->numberBetween(100, 5000),
            'amount_cents' => fn (array $attrs) => (int) ($attrs['quantity'] * $attrs['unit_price_cents']),
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'metadata' => [],
        ];
    }

    /**
     * Call minutes item.
     */
    public function callMinutes(float $minutes = 10, int $ratePerMinute = 25): static
    {
        return $this->state(fn (array $attributes) => [
            'item_type' => AggregateInvoiceItem::TYPE_CALL_MINUTES,
            'description' => 'Call-Minuten',
            'description_detail' => sprintf('%.2f Minuten', $minutes),
            'quantity' => $minutes,
            'unit' => 'Minuten',
            'unit_price_cents' => $ratePerMinute,
            'amount_cents' => (int) ($minutes * $ratePerMinute),
        ]);
    }

    /**
     * Monthly service item.
     */
    public function monthlyService(string $serviceName = 'Standard Plan', int $amountCents = 9900): static
    {
        return $this->state(fn (array $attributes) => [
            'item_type' => AggregateInvoiceItem::TYPE_MONTHLY_SERVICE,
            'description' => 'Monatliche Servicegebühr',
            'description_detail' => $serviceName,
            'quantity' => 1,
            'unit' => 'Monat',
            'unit_price_cents' => $amountCents,
            'amount_cents' => $amountCents,
        ]);
    }

    /**
     * Custom item.
     */
    public function custom(string $description, int $amountCents): static
    {
        return $this->state(fn (array $attributes) => [
            'item_type' => AggregateInvoiceItem::TYPE_CUSTOM,
            'description' => $description,
            'description_detail' => null,
            'quantity' => 1,
            'unit' => null,
            'unit_price_cents' => $amountCents,
            'amount_cents' => $amountCents,
        ]);
    }
}
