<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\Company;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\BalanceTopup;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amountCents = $this->faker->numberBetween(-10000, 10000);
        $balanceBeforeCents = $this->faker->numberBetween(0, 50000);
        $balanceAfterCents = $balanceBeforeCents + $amountCents;

        return [
            'company_id' => Company::factory(),
            'type' => $this->faker->randomElement([
                Transaction::TYPE_TOPUP,
                Transaction::TYPE_USAGE,
                Transaction::TYPE_REFUND,
                Transaction::TYPE_ADJUSTMENT,
                Transaction::TYPE_BONUS,
                Transaction::TYPE_FEE,
            ]),
            'amount_cents' => $amountCents,
            'balance_before_cents' => $balanceBeforeCents,
            'balance_after_cents' => $balanceAfterCents,
            'description' => $this->faker->sentence(),
            'topup_id' => null,
            'call_id' => null,
            'appointment_id' => null,
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the transaction is a topup
     */
    public function topup(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Transaction::TYPE_TOPUP,
            'amount_cents' => $this->faker->numberBetween(1000, 50000),
            'topup_id' => BalanceTopup::factory(),
        ]);
    }

    /**
     * Indicate that the transaction is usage
     */
    public function usage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Transaction::TYPE_USAGE,
            'amount_cents' => -$this->faker->numberBetween(100, 5000),
            'call_id' => Call::factory(),
        ]);
    }

    /**
     * Indicate that the transaction is a refund
     */
    public function refund(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Transaction::TYPE_REFUND,
            'amount_cents' => $this->faker->numberBetween(100, 5000),
            'description' => 'Refund: ' . $this->faker->sentence(3),
        ]);
    }

    /**
     * Indicate that the transaction is an adjustment
     */
    public function adjustment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Transaction::TYPE_ADJUSTMENT,
            'amount_cents' => $this->faker->randomElement([-1000, 1000]),
            'description' => 'Balance adjustment: ' . $this->faker->sentence(3),
        ]);
    }

    /**
     * Indicate that the transaction is a bonus
     */
    public function bonus(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Transaction::TYPE_BONUS,
            'amount_cents' => $this->faker->numberBetween(500, 5000),
            'description' => 'Bonus: ' . $this->faker->sentence(3),
        ]);
    }

    /**
     * Indicate that the transaction is a fee
     */
    public function fee(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Transaction::TYPE_FEE,
            'amount_cents' => -$this->faker->numberBetween(50, 500),
            'description' => 'Fee: ' . $this->faker->sentence(3),
        ]);
    }

    /**
     * Create a credit transaction (positive amount)
     */
    public function credit(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount_cents' => $this->faker->numberBetween(100, 10000),
        ]);
    }

    /**
     * Create a debit transaction (negative amount)
     */
    public function debit(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount_cents' => -$this->faker->numberBetween(100, 10000),
        ]);
    }
}
