<?php

namespace App\Services\Testing;

use App\Models\SystemTestRun;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\Process as SymfonyProcess;

/**
 * Cal.com Integration Test Runner
 *
 * Executes all Cal.com tests and stores results
 * Used by SystemTestingDashboard for admin@askproai.de
 */
class CalcomTestRunner
{
    /**
     * Run specific test type
     */
    public function runTest(string $testType): SystemTestRun
    {
        $testRun = SystemTestRun::create([
            'user_id' => auth()->id(),
            'test_type' => $testType,
            'status' => SystemTestRun::STATUS_RUNNING,
            'started_at' => now()
        ]);

        try {
            $output = $this->executeTest($testType);
            $testRun->markCompleted($output);
        } catch (\Exception $e) {
            Log::error('Test execution failed', [
                'test_type' => $testType,
                'error' => $e->getMessage()
            ]);
            $testRun->markCompleted([], $e->getMessage());
        }

        return $testRun;
    }

    /**
     * Execute test and return output
     */
    public function executeTest(string $testType): array
    {
        return match($testType) {
            SystemTestRun::TEST_EVENT_ID_VERIFICATION => $this->runEventIdVerification(),
            SystemTestRun::TEST_AVAILABILITY_CHECK => $this->runAvailabilityCheck(),
            SystemTestRun::TEST_APPOINTMENT_BOOKING => $this->runAppointmentBooking(),
            SystemTestRun::TEST_APPOINTMENT_RESCHEDULE => $this->runAppointmentReschedule(),
            SystemTestRun::TEST_APPOINTMENT_CANCELLATION => $this->runAppointmentCancellation(),
            SystemTestRun::TEST_APPOINTMENT_QUERY => $this->runAppointmentQuery(),
            SystemTestRun::TEST_BIDIRECTIONAL_SYNC => $this->runBidirectionalSync(),
            SystemTestRun::TEST_V2_API_COMPATIBILITY => $this->runV2ApiCompatibility(),
            SystemTestRun::TEST_MULTI_TENANT_ISOLATION => $this->runMultiTenantIsolation(),
            default => ['error' => 'Unknown test type: ' . $testType]
        };
    }

    /**
     * 1. Event-ID Verification Test
     */
    private function runEventIdVerification(): array
    {
        $output = [];

        // Run verification command for both teams
        $teams = [
            39203 => 'AskProAI',
            34209 => 'Friseur 1'
        ];

        $results = [];

        foreach ($teams as $teamId => $teamName) {
            Artisan::call('calcom:verify-team-events', [
                '--team-id' => $teamId
            ], $output);

            $results[] = [
                'team' => $teamName,
                'team_id' => $teamId,
                'output' => $output
            ];
        }

        return [
            'success' => true,
            'test_name' => 'Event-ID Verification',
            'teams_tested' => count($teams),
            'results' => $results
        ];
    }

    /**
     * 2. Availability Check Test
     */
    private function runAvailabilityCheck(): array
    {
        return $this->runPestTest('tests/Feature/CalcomIntegration/AvailabilityServiceTest.php');
    }

    /**
     * 3. Appointment Booking Test
     */
    private function runAppointmentBooking(): array
    {
        return $this->runPestTest('tests/Feature/CalcomIntegration/AppointmentBookingTest.php');
    }

    /**
     * 4. Appointment Reschedule Test
     */
    private function runAppointmentReschedule(): array
    {
        return $this->runPestTest('tests/Feature/CalcomIntegration/AppointmentRescheduleTest.php');
    }

    /**
     * 5. Appointment Cancellation Test
     */
    private function runAppointmentCancellation(): array
    {
        return $this->runPestTest('tests/Feature/CalcomIntegration/AppointmentCancellationTest.php');
    }

    /**
     * 6. Appointment Query Test
     */
    private function runAppointmentQuery(): array
    {
        return $this->runPestTest('tests/Feature/CalcomIntegration/AppointmentQueryTest.php');
    }

    /**
     * 7. Bidirectional Sync Test
     */
    private function runBidirectionalSync(): array
    {
        return $this->runPestTest('tests/Feature/CalcomIntegration/BidirectionalSyncTest.php');
    }

    /**
     * 8. V2 API Compatibility Test
     */
    private function runV2ApiCompatibility(): array
    {
        return $this->runPestTest('tests/Feature/CalcomIntegration/V2ApiCompatibilityTest.php');
    }

    /**
     * 9. Multi-Tenant Isolation Test
     */
    private function runMultiTenantIsolation(): array
    {
        return $this->runPestTest('tests/Feature/CalcomIntegration/MultiTenantIsolationTest.php');
    }

    /**
     * Run Pest test and capture output
     */
    private function runPestTest(string $testPath): array
    {
        try {
            $process = new SymfonyProcess([
                'php',
                'artisan',
                'pest',
                $testPath,
                '--json',
                '--no-interaction'
            ], base_path());

            $process->setTimeout(300); // 5 minutes timeout
            $process->run();

            $output = $process->getOutput();
            $errors = $process->getErrorOutput();

            // Try to parse JSON output
            $result = json_decode($output, true);

            return [
                'success' => $process->isSuccessful(),
                'output' => $result ?? $output,
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
     * Run all tests
     */
    public function runAllTests(): array
    {
        $testTypes = [
            SystemTestRun::TEST_EVENT_ID_VERIFICATION,
            SystemTestRun::TEST_AVAILABILITY_CHECK,
            SystemTestRun::TEST_APPOINTMENT_BOOKING,
            SystemTestRun::TEST_APPOINTMENT_RESCHEDULE,
            SystemTestRun::TEST_APPOINTMENT_CANCELLATION,
            SystemTestRun::TEST_APPOINTMENT_QUERY,
            SystemTestRun::TEST_BIDIRECTIONAL_SYNC,
            SystemTestRun::TEST_V2_API_COMPATIBILITY,
            SystemTestRun::TEST_MULTI_TENANT_ISOLATION,
        ];

        $results = [];

        foreach ($testTypes as $testType) {
            $testRun = $this->runTest($testType);
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
