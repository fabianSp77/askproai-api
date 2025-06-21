<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    public function definition(): array
    {
        // Generate valid German phone numbers
        $areaCode = $this->faker->randomElement(['30', '40', '89', '221', '211', '69']);
        $number = $this->faker->numberBetween(1000000, 9999999);
        
        return [
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => '+49' . $areaCode . $number,
            'notes' => $this->faker->optional()->sentence,
            'company_id' => function () {
                // Use current company if set, otherwise create one
                return app()->has('current_company') 
                    ? app('current_company')->id 
                    : \App\Models\Company::factory()->create()->id;
            },
        ];
    }
}
