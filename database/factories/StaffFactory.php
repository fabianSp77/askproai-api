<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Staff>
 */
class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'company_id' => Company::factory(),
            'branch_id' => null,
            'name' => $this->faker->name(),
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Staff $staff) {
            if (!$staff->branch_id) {
                $branch = Branch::factory()->create([
                    'company_id' => $staff->company_id ?? Company::factory()->create()->id,
                ]);

                $staff->branch_id = $branch->id;
                if (!$staff->company_id) {
                    $staff->company_id = $branch->company_id;
                }
            }
        })->afterCreating(function (Staff $staff) {
            if (!$staff->branch_id) {
                $branch = Branch::factory()->create([
                    'company_id' => $staff->company_id ?? Company::factory()->create()->id,
                ]);

                $staff->branch_id = $branch->id;
                $staff->company_id = $branch->company_id;
                $staff->save();
            }
        });
    }
}
