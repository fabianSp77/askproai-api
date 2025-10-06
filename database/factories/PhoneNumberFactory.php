<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\PhoneNumber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\PhoneNumber>
 */
class PhoneNumberFactory extends Factory
{
    protected $model = PhoneNumber::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'company_id' => Company::factory(),
            'branch_id' => null,
            'number' => $this->faker->unique()->e164PhoneNumber(),
            'phone_number' => $this->faker->unique()->e164PhoneNumber(),
            'is_active' => true,
            'friendly_name' => $this->faker->company . ' Hotline',
            'provider' => 'retellai',
            'provider_id' => 'retell_' . Str::uuid(),
            'country_code' => '+49',
            'monthly_cost' => $this->faker->randomFloat(2, 5, 50),
            'usage_minutes' => $this->faker->numberBetween(0, 500),
            'last_used_at' => now()->subDays($this->faker->numberBetween(0, 10)),
            'label' => $this->faker->word(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (PhoneNumber $phoneNumber) {
            if (!$phoneNumber->company_id) {
                $phoneNumber->company_id = Company::factory()->create()->id;
            }

            if (!$phoneNumber->branch_id) {
                $phoneNumber->branch_id = Branch::factory()->create([
                    'company_id' => $phoneNumber->company_id,
                ])->id;
            }
        })->afterCreating(function (PhoneNumber $phoneNumber) {
            if (!$phoneNumber->branch_id) {
                $branch = Branch::factory()->create([
                    'company_id' => $phoneNumber->company_id,
                ]);

                $phoneNumber->branch_id = $branch->id;
                $phoneNumber->save();
            }
        });
    }
}
