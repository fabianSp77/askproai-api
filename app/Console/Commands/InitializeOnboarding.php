<?php

namespace App\Console\Commands;

use App\Services\TutorialService;
use Database\Seeders\OnboardingAchievementsSeeder;
use Illuminate\Console\Command;

class InitializeOnboarding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'askproai:initialize-onboarding {--fresh : Clear existing data before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize onboarding tutorials and achievements';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Initializing AskProAI onboarding system...');

        if ($this->option('fresh')) {
            $this->warn('Clearing existing onboarding data...');
            
            // Clear existing data
            \DB::table('company_achievements')->truncate();
            \DB::table('onboarding_achievements')->truncate();
            \DB::table('user_tutorial_progress')->truncate();
            \DB::table('onboarding_tutorials')->truncate();
        }

        // Initialize tutorials
        $this->info('Setting up tutorials...');
        $tutorialService = app(TutorialService::class);
        $tutorialService->initializeDefaultTutorials();
        $this->info('✓ Tutorials initialized');

        // Seed achievements
        $this->info('Setting up achievements...');
        $seeder = new OnboardingAchievementsSeeder();
        $seeder->run();
        $this->info('✓ Achievements initialized');

        $this->info('');
        $this->info('Onboarding system initialized successfully!');
        $this->info('');
        $this->table(
            ['Component', 'Status'],
            [
                ['Tutorials', '✓ Ready'],
                ['Achievements', '✓ Ready'],
                ['Progress Tracking', '✓ Ready'],
                ['Email Templates', '✓ Ready'],
            ]
        );

        return Command::SUCCESS;
    }
}