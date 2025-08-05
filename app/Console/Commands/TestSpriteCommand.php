<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MCP\TestSpriteMCPServer;

class TestSpriteCommand extends Command
{
    protected $signature = 'testsprite:test 
                            {component : The component to test}
                            {--plan : Generate test plan only}
                            {--generate : Generate tests only}
                            {--run : Run tests only}
                            {--diagnose : Diagnose failures}
                            {--coverage : Generate coverage report}';

    protected $description = 'Run TestSprite AI testing operations';

    private TestSpriteMCPServer $testSprite;

    public function __construct()
    {
        parent::__construct();
        $this->testSprite = new TestSpriteMCPServer();
    }

    public function handle()
    {
        $component = $this->argument('component');
        
        $this->info('🧪 TestSprite AI Testing');
        $this->info('Component: ' . $component);
        $this->newLine();

        try {
            if ($this->option('plan')) {
                $this->generateTestPlan($component);
            } elseif ($this->option('generate')) {
                $this->generateTests($component);
            } elseif ($this->option('run')) {
                $this->runTests($component);
            } elseif ($this->option('diagnose')) {
                $this->diagnoseFailures();
            } elseif ($this->option('coverage')) {
                $this->generateCoverage();
            } else {
                // Full workflow
                $this->info('Running complete test workflow...');
                $this->generateTestPlan($component);
                $this->generateTests($component);
                $this->runTests($component);
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function generateTestPlan(string $component): void
    {
        $this->info('📋 Generating test plan...');
        
        $response = $this->testSprite->executeTool('create_test_plan', [
            'requirements' => "Test the {$component} component thoroughly including all methods, edge cases, and integrations",
            'test_type' => 'all'
        ]);
        
        if (!$response['success']) {
            $this->error($response['error']);
            return;
        }

        $this->info('Test Plan Summary:');
        $this->info('Total Cases: ' . $response['summary']['total_cases']);
        foreach ($response['summary']['categories'] as $category) {
            $this->info("- {$category['name']}: {$category['count']} tests");
        }
        $this->newLine();
        $this->info('Priority Tests:');
        foreach ($response['summary']['priority_tests'] as $test) {
            $this->info("• {$test['name']}");
        }
        $this->newLine();
    }

    private function generateTests(string $component): void
    {
        $this->info('🔨 Generating test code...');
        
        $response = $this->testSprite->executeTool('generate_tests', [
            'component' => $component,
            'framework' => 'pest'
        ]);
        
        if (!$response['success']) {
            $this->error($response['error']);
            return;
        }

        $this->info($response['message']);
        $this->info("Generated {$response['test_count']} tests");
        $this->newLine();
    }

    private function runTests(string $component): void
    {
        $this->info('🚀 Running tests...');
        
        $testPath = "tests/Feature/{$component}Test.php";
        
        $response = $this->testSprite->executeTool('run_tests', [
            'test_path' => $testPath,
            'parallel' => true
        ]);
        
        if (!$response['success']) {
            $this->error($response['error']);
            return;
        }

        $stats = $response['stats'];
        $status = $response['exit_code'] === 0 ? '✅ PASSED' : '❌ FAILED';
        
        $this->info("Test Results: {$status}");
        $this->info("Total: {$stats['total']} tests");
        $this->info("Passed: {$stats['passed']}");
        $this->info("Failed: {$stats['failed']}");
        $this->info("Duration: {$stats['duration']}s");
        $this->newLine();
    }

    private function diagnoseFailures(): void
    {
        $this->info('🔍 Diagnosing test failures...');
        
        // Get last test output
        $lastOutput = $this->ask('Paste the test failure output (or press Enter to skip)');
        
        if (empty($lastOutput)) {
            $this->warn('No test output provided');
            return;
        }

        $response = $this->testSprite->executeTool('diagnose_failure', [
            'test_output' => $lastOutput
        ]);
        
        if (!$response['success']) {
            $this->error($response['error']);
            return;
        }

        $diagnosis = $response['diagnosis'];
        
        $this->info("Root Cause: {$diagnosis['root_cause']}");
        $this->newLine();
        $this->info('Suggested Fixes:');
        foreach ($diagnosis['fixes'] as $i => $fix) {
            $this->info(($i + 1) . ". {$fix['description']}");
            if (isset($fix['code'])) {
                $this->info("   Code:");
                $this->info("   ```php");
                $this->info("   {$fix['code']}");
                $this->info("   ```");
            }
        }
        if ($diagnosis['context']) {
            $this->newLine();
            $this->info("Additional Context: {$diagnosis['context']}");
        }
        $this->newLine();
    }

    private function generateCoverage(): void
    {
        $this->info('📊 Generating coverage report...');
        
        $format = $this->choice('Select coverage format', ['text', 'html', 'json'], 'text');
        
        $response = $this->testSprite->executeTool('coverage_report', [
            'format' => $format
        ]);
        
        if (!$response['success']) {
            $this->error($response['error']);
            return;
        }

        $coverage = $response['coverage'];
        
        $this->info("Coverage Report:");
        $this->info("Total Coverage: {$coverage['total']}%");
        $this->info("Lines: {$coverage['lines']}%");
        $this->info("Methods: {$coverage['methods']}%");
        $this->info("Classes: {$coverage['classes']}%");
        
        if ($response['report_path']) {
            $this->newLine();
            $this->info("Report saved to: {$response['report_path']}");
        }
        $this->newLine();
    }
}