<?php

namespace Database\Seeders;

use App\Services\Retell\RetellPromptTemplateService;
use Illuminate\Database\Seeder;

class RetellTemplateSeeder extends Seeder
{
    /**
     * Run the database seeders.
     */
    public function run(): void
    {
        $templateService = new RetellPromptTemplateService();
        $templateService->seedDefaultTemplates();

        $this->command->info('✅ Retell templates seeded successfully!');
    }
}
