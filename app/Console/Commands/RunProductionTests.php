<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Testing\ProductionTestService;
use App\Models\Company;

class RunProductionTests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:production 
                            {scenario? : Test scenario to run (simple_booking, complex_booking, no_availability, all)}
                            {--company= : Company ID to test}
                            {--phone= : Specific phone number to test}
                            {--report : Generate detailed report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run production tests for phone booking system';

    protected ProductionTestService $testService;

    /**
     * Create a new command instance.
     */
    public function __construct(ProductionTestService $testService)
    {
        parent::__construct();
        $this->testService = $testService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $scenario = $this->argument('scenario') ?? 'simple_booking';
        $companyId = $this->option('company');
        $phoneNumber = $this->option('phone');
        $generateReport = $this->option('report');
        
        $this->info('========================================');
        $this->info('AskProAI Production Test Suite');
        $this->info('========================================');
        $this->newLine();
        
        // Set company context if needed
        if ($companyId) {
            $company = Company::find($companyId);
            if (!$company) {
                $this->error("Company with ID {$companyId} not found.");
                return 1;
            }
            app()->instance('current_company_id', $company->id);
            $this->info("Testing company: {$company->name}");
        } else {
            // Use first active company
            $company = Company::where('is_active', true)->first();
            if ($company) {
                app()->instance('current_company_id', $company->id);
                $this->info("Using company: {$company->name}");
            }
        }
        
        $options = [
            'company_id' => $companyId,
            'phone_number' => $phoneNumber,
        ];
        
        // Run tests
        if ($scenario === 'all') {
            $this->runAllScenarios($options, $generateReport);
        } else {
            $this->runSingleScenario($scenario, $options, $generateReport);
        }
        
        return 0;
    }
    
    /**
     * Run a single test scenario
     */
    protected function runSingleScenario(string $scenario, array $options, bool $generateReport): void
    {
        $this->info("Running scenario: {$scenario}");
        $this->newLine();
        
        $progressBar = $this->output->createProgressBar(5);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        
        $progressBar->setMessage('Initializing test...');
        $progressBar->start();
        
        $result = $this->testService->runTestScenario($scenario, $options);
        
        $progressBar->setMessage('Test completed');
        $progressBar->finish();
        $this->newLine(2);
        
        // Display results
        $this->displayTestResult($result);
        
        // Generate report if requested
        if ($generateReport) {
            $this->generateTestReport($result);
        }
    }
    
    /**
     * Run all test scenarios
     */
    protected function runAllScenarios(array $options, bool $generateReport): void
    {
        $this->info("Running all test scenarios...");
        $this->newLine();
        
        $result = $this->testService->runAllScenarios($options);
        
        // Display summary
        $this->info($result['summary']);
        
        // Display individual results
        $this->newLine();
        $this->info('Individual Results:');
        $this->info('==================');
        
        foreach ($result['results'] as $scenario => $scenarioResult) {
            $this->newLine();
            $this->info("Scenario: {$scenario}");
            $this->displayTestResult($scenarioResult);
        }
        
        // Generate report if requested
        if ($generateReport) {
            $this->generateOverallReport($result);
        }
    }
    
    /**
     * Display test result
     */
    protected function displayTestResult(array $result): void
    {
        $status = $result['success'] ? '✅ PASSED' : '❌ FAILED';
        $this->line("Status: {$status}");
        $this->line("Duration: {$result['duration']}s");
        
        if (isset($result['error'])) {
            $this->error("Error: {$result['error']}");
        }
        
        if (isset($result['verification']['results'])) {
            $this->newLine();
            $this->info('Verification Results:');
            
            $headers = ['Check', 'Expected', 'Actual', 'Result'];
            $rows = [];
            
            foreach ($result['verification']['results'] as $check => $checkResult) {
                $rows[] = [
                    $check,
                    $this->formatValue($checkResult['expected']),
                    $this->formatValue($checkResult['actual']),
                    $checkResult['passed'] ? '✅' : '❌',
                ];
            }
            
            $this->table($headers, $rows);
        }
        
        if (isset($result['summary'])) {
            $this->newLine();
            $this->line($result['summary']);
        }
    }
    
    /**
     * Format value for display
     */
    protected function formatValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_null($value)) {
            return 'null';
        }
        
        if (is_array($value)) {
            return json_encode($value);
        }
        
        return (string) $value;
    }
    
    /**
     * Generate detailed test report
     */
    protected function generateTestReport(array $result): void
    {
        $filename = sprintf(
            'production-test-%s-%s.json',
            $result['scenario'] ?? 'unknown',
            now()->format('Y-m-d-H-i-s')
        );
        
        $path = storage_path('app/test-reports/' . $filename);
        
        // Ensure directory exists
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        file_put_contents($path, json_encode($result, JSON_PRETTY_PRINT));
        
        $this->newLine();
        $this->info("Report saved to: {$path}");
    }
    
    /**
     * Generate overall report
     */
    protected function generateOverallReport(array $result): void
    {
        $filename = sprintf(
            'production-test-all-%s.json',
            now()->format('Y-m-d-H-i-s')
        );
        
        $path = storage_path('app/test-reports/' . $filename);
        
        // Ensure directory exists
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        file_put_contents($path, json_encode($result, JSON_PRETTY_PRINT));
        
        $this->newLine();
        $this->info("Overall report saved to: {$path}");
    }
}