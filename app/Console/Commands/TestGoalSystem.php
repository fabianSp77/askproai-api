<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\CompanyGoal;
use App\Services\GoalService;
use App\Services\GoalCalculationService;

class TestGoalSystem extends Command
{
    protected $signature = 'goals:test {--company=1 : Company ID to test with}';
    protected $description = 'Test the goal system functionality';

    protected $goalService;
    protected $calculationService;

    public function __construct(GoalService $goalService, GoalCalculationService $calculationService)
    {
        parent::__construct();
        $this->goalService = $goalService;
        $this->calculationService = $calculationService;
    }

    public function handle()
    {
        $companyId = $this->option('company');
        $company = Company::find($companyId);

        if (!$company) {
            $this->error("Company with ID {$companyId} not found.");
            return 1;
        }

        $this->info("Testing goal system for company: {$company->name}");
        $this->line('=' . str_repeat('=', 50));

        // Test 1: Get goal templates
        $this->info("\n1. Available Goal Templates:");
        $templates = $this->goalService->getGoalTemplates();
        foreach ($templates as $template) {
            $this->line("   - {$template['name']}: {$template['description']}");
        }

        // Test 2: Create a test goal
        $this->info("\n2. Creating test goal...");
        $goalData = [
            'name' => 'Test Conversion Goal',
            'description' => 'Testing the goal system',
            'template_type' => CompanyGoal::TEMPLATE_MAX_APPOINTMENTS,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ];

        try {
            $goal = $this->goalService->createGoal($company, $goalData);
            $this->info("   ✓ Goal created successfully (ID: {$goal->id})");
            
            // Show metrics
            $this->info("   Metrics:");
            foreach ($goal->metrics as $metric) {
                $this->line("     - {$metric->metric_name}: Target {$metric->target_value} {$metric->target_unit}");
            }
            
            // Show funnel steps
            $this->info("   Funnel Steps:");
            foreach ($goal->funnelSteps as $step) {
                $this->line("     {$step->step_order}. {$step->step_name}");
            }
        } catch (\Exception $e) {
            $this->error("   ✗ Failed to create goal: " . $e->getMessage());
            return 1;
        }

        // Test 3: Get goal progress
        $this->info("\n3. Checking goal progress...");
        $progress = $this->goalService->getGoalProgress($goal);
        $this->line("   Overall Achievement: " . round($progress['overall_achievement'], 2) . "%");
        
        foreach ($progress['metrics'] as $metricProgress) {
            $metric = $metricProgress['metric'];
            $this->line("   - {$metric->metric_name}: {$metricProgress['current_value']} / {$metricProgress['target_value']} ({$metricProgress['achievement_percentage']}%)");
        }

        // Test 4: Record achievement
        $this->info("\n4. Recording achievement...");
        try {
            $achievement = $this->goalService->recordAchievement($goal);
            $this->info("   ✓ Achievement recorded (" . round($achievement->achievement_percentage, 2) . "%)");
        } catch (\Exception $e) {
            $this->error("   ✗ Failed to record achievement: " . $e->getMessage());
        }

        // Test 5: Get projections
        $this->info("\n5. Calculating projections...");
        $projections = $this->calculationService->calculateProjections($goal);
        if ($projections) {
            $this->line("   Current Achievement: " . round($projections['current_achievement'], 2) . "%");
            $this->line("   Projected Achievement: " . round($projections['projected_achievement'], 2) . "%");
            $this->line("   Days Remaining: {$projections['days_remaining']}");
            $this->line("   On Track: " . ($projections['on_track'] ? 'Yes' : 'No'));
        }

        // Test 6: Get active goals
        $this->info("\n6. Active goals for company:");
        $activeGoals = $this->goalService->getActiveGoals($company);
        foreach ($activeGoals as $activeGoal) {
            $this->line("   - {$activeGoal->name} (" . ($activeGoal->is_expired ? 'Expired' : 'Active') . ")");
        }

        // Cleanup
        $this->info("\n7. Cleaning up test data...");
        $this->goalService->deleteGoal($goal);
        $this->info("   ✓ Test goal deleted");

        $this->info("\n✅ Goal system test completed successfully!");
        return 0;
    }
}