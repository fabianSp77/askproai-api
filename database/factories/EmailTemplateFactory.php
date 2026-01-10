<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->sentence(3),
            'subject' => $this->faker->sentence(6),
            'body_html' => '<p>'.$this->faker->paragraph().'</p>',
            'variables' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the template is inactive.
     */
    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }

    /**
     * Create a template with variables.
     */
    public function withVariables(): static
    {
        return $this->state([
            'variables' => ['customer_name', 'customer_email', 'company_name'],
        ]);
    }
}
