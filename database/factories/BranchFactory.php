<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(), // Branch uses UUIDs, not auto-increment
            'company_id' => Company::factory(),
            'name' => $this->faker->company() . ' Branch',
            'is_active' => true,
        ];
    }
}
