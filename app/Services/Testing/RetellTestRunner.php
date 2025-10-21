<?php

namespace App\Services\Testing;

use App\Models\SystemTestRun;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process as SymfonyProcess;
use Carbon\Carbon;

/**
 * Retell AI Test Runner
 *
 * Executes all Retell AI tests and stores results
 * Used by SystemTestingDashboard for admin@askproai.de
 */
class RetellTestRunner
{
    private ?RetellCallSimulator $simulator = null;

    /**
     * Get or create test runner instance
     */
    private function getSimulator(): RetellCallSimulator
    {
        if (!$this->simulator) {
            $this->simulator = new RetellCallSimulator();
        }
        return $this->simulator;
    }

    /**
     * Run specific test type
     */
    public function runTest(string $testType, array $companyConfig = []): SystemTestRun
    {
        $testRun = SystemTestRun::create([
            'user_id' => auth()->id(),
            'test_type' => $testType,
            'status' => SystemTestRun::STATUS_RUNNING,
            'started_at' => now(),
            'metadata' => [
                'company' => $companyConfig['name'] ?? 'N/A',
                'team_id' => $companyConfig['team_id'] ?? null,
                'event_ids' => $companyConfig['event_ids'] ?? []
            ]
        ]);

        try {
            $output = $this->executeTest($testType, $companyConfig);
            $testRun->markCompleted($output);
        } catch (\Exception $e) {
            Log::error('Retell test execution failed', [
                'test_type' => $testType,
                'company' => $companyConfig['name'] ?? 'N/A',
                'error' => $e->getMessage()
            ]);
            $testRun->markCompleted([], $e->getMessage());
        }

        return $testRun;
    }

    /**
     * Execute test and return output
     */
    public function executeTest(string $testType, array $companyConfig = []): array
    {
        try {
            return match($testType) {
                // Webhooks
                SystemTestRun::TEST_RETELL_WEBHOOK_CALL_STARTED => $this->runWebhookCallStartedTest(),
                SystemTestRun::TEST_RETELL_WEBHOOK_CALL_ENDED => $this->runWebhookCallEndedTest(),

                // Function Calls
                SystemTestRun::TEST_RETELL_FUNCTION_CHECK_CUSTOMER => $this->runCheckCustomerTest(),
                SystemTestRun::TEST_RETELL_FUNCTION_CHECK_AVAILABILITY => $this->runCheckAvailabilityTest(),
                SystemTestRun::TEST_RETELL_FUNCTION_COLLECT_APPOINTMENT => $this->runCollectAppointmentTest(),
                SystemTestRun::TEST_RETELL_FUNCTION_BOOK_APPOINTMENT => $this->runBookAppointmentTest(),
                SystemTestRun::TEST_RETELL_FUNCTION_CANCEL_APPOINTMENT => $this->runCancelAppointmentTest(),
                SystemTestRun::TEST_RETELL_FUNCTION_RESCHEDULE_APPOINTMENT => $this->runRescheduleAppointmentTest(),

                // Policies
                SystemTestRun::TEST_RETELL_POLICY_CANCELLATION => $this->runCancellationPolicyTest(),
                SystemTestRun::TEST_RETELL_POLICY_RESCHEDULE => $this->runReschedulePolicyTest(),

                // Performance
                SystemTestRun::TEST_RETELL_PERFORMANCE_E2E => $this->runPerformanceTest(),

                // Hidden/Anonymous Numbers
                SystemTestRun::TEST_RETELL_HIDDEN_NUMBER_QUERY => $this->runHiddenNumberQueryTest(),
                SystemTestRun::TEST_RETELL_ANONYMOUS_CALL_HANDLING => $this->runAnonymousCallHandlingTest(),

                default => ['error' => 'Unknown test type: ' . $testType]
            };
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    /**
     * 1. Webhook: call.started Test
     */
    private function runWebhookCallStartedTest(): array
    {
        return $this->runPestTest('tests/Feature/RetellIntegration/WebhookCallStartedTest.php');
    }

    /**
     * 2. Webhook: call.ended Test
     */
    private function runWebhookCallEndedTest(): array
    {
        return $this->runPestTest('tests/Feature/RetellIntegration/WebhookCallEndedTest.php');
    }

    /**
     * 3. Function: check_customer Test
     */
    private function runCheckCustomerTest(): array
    {
        return $this->runPestTest('tests/Feature/RetellIntegration/FunctionCheckCustomerTest.php');
    }

    /**
     * 4. Function: check_availability Test
     */
    private function runCheckAvailabilityTest(): array
    {
        return $this->runPestTest('tests/Feature/RetellIntegration/FunctionCheckAvailabilityTest.php');
    }

    /**
     * 5. Function: collect_appointment Test
     */
    private function runCollectAppointmentTest(): array
    {
        return $this->runPestTest('tests/Feature/RetellIntegration/FunctionCollectAppointmentTest.php');
    }

    /**
     * 6. Function: book_appointment Test
     */
    private function runBookAppointmentTest(): array
    {
        return $this->runPestTest('tests/Feature/RetellIntegration/FunctionBookAppointmentTest.php');
    }

    /**
     * 7. Function: cancel_appointment Test
     */
    private function runCancelAppointmentTest(): array
    {
        return $this->runPestTest('tests/Feature/RetellIntegration/FunctionCancelAppointmentTest.php');
    }

    /**
     * 8. Function: reschedule_appointment Test
     */
    private function runRescheduleAppointmentTest(): array
    {
        return $this->runPestTest('tests/Feature/RetellIntegration/FunctionRescheduleAppointmentTest.php');
    }

    /**
     * 9. Policy: Cancellation Rules Test
     */
    private function runCancellationPolicyTest(): array
    {
        return $this->runPestTest('tests/Feature/RetellIntegration/PolicyCancellationTest.php');
    }

    /**
     * 10. Policy: Reschedule Rules Test
     */
    private function runReschedulePolicyTest(): array
    {
        return $this->runPestTest('tests/Feature/RetellIntegration/PolicyRescheduleTest.php');
    }

    /**
     * 11. Performance: E2E Latency Test
     */
    private function runPerformanceTest(): array
    {
        return $this->runPestTest('tests/Feature/RetellIntegration/PerformanceE2ETest.php');
    }

    /**
     * Run Pest test and capture output
     */
    private function runPestTest(string $testPath): array
    {
        try {
            $process = new SymfonyProcess([
                'vendor/bin/pest',
                $testPath
            ], base_path());

            $process->setTimeout(300); // 5 minutes timeout
            $process->run();

            $output = $process->getOutput();
            $errors = $process->getErrorOutput();

            // Parse Pest output to extract test results
            $results = $this->parsePestOutput($output);

            return [
                'success' => $process->isSuccessful(),
                'output' => $results,
                'raw_output' => $output,
                'error' => $errors,
                'exit_code' => $process->getExitCode()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'exit_code' => -1
            ];
        }
    }

    /**
     * Parse Pest test output and extract summary
     */
    private function parsePestOutput(string $output): array
    {
        // Extract test summary line like "Tests:    2 failed, 3 passed (9 assertions)"
        $summaryPattern = '/Tests:\s*(.+)/i';
        if (preg_match($summaryPattern, $output, $matches)) {
            return [
                'summary' => trim($matches[1]),
                'test_output' => $output
            ];
        }

        return [
            'summary' => 'Test output captured',
            'test_output' => $output
        ];
    }

    /**
     * 12. Hidden Number: Query Appointment Test
     */
    private function runHiddenNumberQueryTest(): array
    {
        return $this->runPestTest('tests/Feature/RetellIntegration/HiddenNumberTest.php');
    }

    /**
     * 13. Anonymous: Complete Call Flow Test
     */
    private function runAnonymousCallHandlingTest(): array
    {
        return $this->runPestTest('tests/Feature/RetellIntegration/AnonymousCallHandlingTest.php');
    }

    /**
     * Run all Retell tests
     */
    public function runAllTests(array $companyConfig = []): array
    {
        $testTypes = [
            // Webhooks
            SystemTestRun::TEST_RETELL_WEBHOOK_CALL_STARTED,
            SystemTestRun::TEST_RETELL_WEBHOOK_CALL_ENDED,

            // Function Calls
            SystemTestRun::TEST_RETELL_FUNCTION_CHECK_CUSTOMER,
            SystemTestRun::TEST_RETELL_FUNCTION_CHECK_AVAILABILITY,
            SystemTestRun::TEST_RETELL_FUNCTION_COLLECT_APPOINTMENT,
            SystemTestRun::TEST_RETELL_FUNCTION_BOOK_APPOINTMENT,
            SystemTestRun::TEST_RETELL_FUNCTION_CANCEL_APPOINTMENT,
            SystemTestRun::TEST_RETELL_FUNCTION_RESCHEDULE_APPOINTMENT,

            // Policies
            SystemTestRun::TEST_RETELL_POLICY_CANCELLATION,
            SystemTestRun::TEST_RETELL_POLICY_RESCHEDULE,

            // Performance
            SystemTestRun::TEST_RETELL_PERFORMANCE_E2E,

            // Hidden/Anonymous Numbers
            SystemTestRun::TEST_RETELL_HIDDEN_NUMBER_QUERY,
            SystemTestRun::TEST_RETELL_ANONYMOUS_CALL_HANDLING,
        ];

        $results = [];

        foreach ($testTypes as $testType) {
            $testRun = $this->runTest($testType, $companyConfig);
            $results[] = [
                'test_type' => $testType,
                'label' => $testRun->getTestTypeLabel(),
                'status' => $testRun->status,
                'succeeded' => $testRun->succeeded(),
                'duration' => $testRun->getDurationSeconds(),
                'error' => $testRun->error_message
            ];
        }

        return $results;
    }
}
