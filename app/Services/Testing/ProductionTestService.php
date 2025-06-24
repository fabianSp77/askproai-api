<?php

namespace App\Services\Testing;

use App\Models\Company;
use App\Models\PhoneNumber;
use App\Models\Call;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Production Testing Service
 * 
 * Orchestrates end-to-end testing of the phone booking system
 * with real API calls to Retell.ai
 */
class ProductionTestService
{
    protected array $testResults = [];
    
    public function __construct()
    {
        // Retell service will be initialized when needed
    }
    
    /**
     * Run a complete test scenario
     */
    public function runTestScenario(string $scenario, array $options = []): array
    {
        $testId = Str::uuid()->toString();
        $startTime = now();
        
        Log::info('Starting production test scenario', [
            'test_id' => $testId,
            'scenario' => $scenario,
            'options' => $options,
        ]);
        
        try {
            // Get test configuration
            $config = $this->getScenarioConfig($scenario);
            
            // Select test phone number
            $phoneNumber = $this->selectTestPhoneNumber($options['company_id'] ?? null);
            
            if (!$phoneNumber) {
                throw new \Exception('No test phone number available');
            }
            
            // Initialize test call
            $callData = $this->initializeTestCall($phoneNumber, $config);
            
            // Execute test conversation
            $conversationResult = $this->executeConversation($callData, $config);
            
            // Wait for webhook processing
            sleep(5); // Give webhooks time to process
            
            // Verify results
            $verificationResult = $this->verifyResults($callData, $config);
            
            // Generate report
            $report = $this->generateReport([
                'test_id' => $testId,
                'scenario' => $scenario,
                'phone_number' => $phoneNumber->number,
                'call_data' => $callData,
                'conversation' => $conversationResult,
                'verification' => $verificationResult,
                'duration' => now()->diffInSeconds($startTime),
            ]);
            
            Log::info('Production test completed', [
                'test_id' => $testId,
                'success' => $report['success'],
                'duration' => $report['duration'],
            ]);
            
            return $report;
            
        } catch (\Exception $e) {
            Log::error('Production test failed', [
                'test_id' => $testId,
                'scenario' => $scenario,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'test_id' => $testId,
                'success' => false,
                'error' => $e->getMessage(),
                'duration' => now()->diffInSeconds($startTime),
            ];
        }
    }
    
    /**
     * Get scenario configuration
     */
    protected function getScenarioConfig(string $scenario): array
    {
        $scenarios = [
            'simple_booking' => [
                'description' => 'Simple appointment booking for next week',
                'conversation_script' => [
                    'greeting_response' => 'Hallo, ich möchte gerne einen Termin buchen.',
                    'service_request' => 'Ich brauche einen Haarschnitt.',
                    'time_preference' => 'Nächste Woche Dienstag nachmittags wäre gut.',
                    'customer_info' => [
                        'name' => 'Max Testmann',
                        'phone' => '+49 170 1234567',
                        'email' => 'test@example.com',
                    ],
                ],
                'expected_outcome' => [
                    'appointment_created' => true,
                    'email_sent' => true,
                    'correct_service' => 'Haarschnitt',
                ],
            ],
            
            'complex_booking' => [
                'description' => 'Complex booking with specific staff and time',
                'conversation_script' => [
                    'greeting_response' => 'Guten Tag, ich hätte gerne einen Termin.',
                    'service_request' => 'Ich möchte eine Dauerwelle bei Maria.',
                    'time_preference' => 'Am liebsten Freitag um 14 Uhr.',
                    'alternative_time' => 'Oder Samstag vormittags.',
                    'customer_info' => [
                        'name' => 'Anna Testfrau',
                        'phone' => '+49 171 2345678',
                        'email' => 'anna.test@example.com',
                    ],
                ],
                'expected_outcome' => [
                    'appointment_created' => true,
                    'staff_assigned' => 'Maria',
                    'service_matched' => 'Dauerwelle',
                ],
            ],
            
            'no_availability' => [
                'description' => 'Booking attempt when no slots available',
                'conversation_script' => [
                    'greeting_response' => 'Hallo, ich brauche dringend einen Termin.',
                    'service_request' => 'Haarschnitt heute noch.',
                    'time_preference' => 'In den nächsten 30 Minuten.',
                    'response_to_unavailable' => 'Okay, dann morgen früh.',
                ],
                'expected_outcome' => [
                    'appointment_created' => false,
                    'alternative_offered' => true,
                ],
            ],
            
            'cancellation' => [
                'description' => 'Cancel existing appointment',
                'conversation_script' => [
                    'greeting_response' => 'Ich möchte einen Termin absagen.',
                    'appointment_info' => 'Mein Termin ist morgen um 10 Uhr.',
                    'customer_verification' => 'Max Mustermann, Telefon endet auf 4567.',
                ],
                'expected_outcome' => [
                    'appointment_cancelled' => true,
                    'confirmation_sent' => true,
                ],
            ],
        ];
        
        if (!isset($scenarios[$scenario])) {
            throw new \InvalidArgumentException("Unknown scenario: {$scenario}");
        }
        
        return $scenarios[$scenario];
    }
    
    /**
     * Select test phone number
     */
    protected function selectTestPhoneNumber(?int $companyId = null): ?PhoneNumber
    {
        $query = PhoneNumber::with(['branch.company'])
            ->where('is_active', true)
            ->whereNotNull('retell_agent_id');
            
        if ($companyId) {
            $query->whereHas('branch', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }
        
        // Prefer test numbers if marked
        $phoneNumber = $query
            ->where('description', 'like', '%test%')
            ->orWhere('number', 'like', '%5555%')
            ->first();
            
        if (!$phoneNumber) {
            // Fall back to any active number
            $phoneNumber = $query->first();
        }
        
        return $phoneNumber;
    }
    
    /**
     * Initialize test call
     */
    protected function initializeTestCall(PhoneNumber $phoneNumber, array $config): array
    {
        return [
            'test_call_id' => 'test_' . Str::uuid()->toString(),
            'phone_number' => $phoneNumber->number,
            'branch_id' => $phoneNumber->branch_id,
            'company_id' => $phoneNumber->branch->company_id,
            'agent_id' => $phoneNumber->retell_agent_id,
            'scenario' => $config['description'],
            'started_at' => now(),
        ];
    }
    
    /**
     * Execute test conversation
     * 
     * Note: This is a placeholder for actual Retell.ai call initiation
     * In production, this would use Retell's API to start a call
     */
    protected function executeConversation(array $callData, array $config): array
    {
        // TODO: Implement actual Retell.ai call initiation
        // For now, we'll simulate the conversation
        
        Log::info('Executing test conversation', [
            'call_id' => $callData['test_call_id'],
            'agent_id' => $callData['agent_id'],
        ]);
        
        // Simulate conversation steps
        $steps = [];
        foreach ($config['conversation_script'] as $step => $content) {
            $steps[] = [
                'step' => $step,
                'content' => $content,
                'timestamp' => now()->toDateTimeString(),
            ];
        }
        
        return [
            'conversation_id' => Str::uuid()->toString(),
            'duration' => rand(60, 180), // 1-3 minutes
            'steps' => $steps,
            'completed' => true,
        ];
    }
    
    /**
     * Verify test results
     */
    protected function verifyResults(array $callData, array $config): array
    {
        $results = [];
        $allPassed = true;
        
        // Check if call was logged
        $call = Call::where('retell_call_id', 'like', '%' . $callData['test_call_id'] . '%')
            ->orWhere('created_at', '>=', $callData['started_at'])
            ->first();
            
        $results['call_logged'] = [
            'expected' => true,
            'actual' => $call !== null,
            'passed' => $call !== null,
        ];
        
        if (!$call) {
            $allPassed = false;
        }
        
        // Check expected outcomes
        foreach ($config['expected_outcome'] as $outcome => $expected) {
            $actual = null;
            $passed = false;
            
            switch ($outcome) {
                case 'appointment_created':
                    if ($call && $call->appointment_id) {
                        $appointment = Appointment::find($call->appointment_id);
                        $actual = $appointment !== null;
                        $passed = $actual === $expected;
                    } else {
                        $actual = false;
                        $passed = $actual === $expected;
                    }
                    break;
                    
                case 'email_sent':
                    if ($call && $call->appointment) {
                        $metadata = $call->appointment->metadata ?? [];
                        $actual = isset($metadata['confirmation_email_sent_at']);
                        $passed = $actual === $expected;
                    } else {
                        $actual = false;
                        $passed = !$expected; // If we don't expect email, this passes
                    }
                    break;
                    
                case 'correct_service':
                    if ($call && $call->appointment && $call->appointment->service) {
                        $actual = $call->appointment->service->name;
                        $passed = stripos($actual, $expected) !== false;
                    } else {
                        $actual = null;
                        $passed = false;
                    }
                    break;
            }
            
            $results[$outcome] = [
                'expected' => $expected,
                'actual' => $actual,
                'passed' => $passed,
            ];
            
            if (!$passed) {
                $allPassed = false;
            }
        }
        
        return [
            'all_passed' => $allPassed,
            'results' => $results,
            'call' => $call ? $call->toArray() : null,
        ];
    }
    
    /**
     * Generate test report
     */
    protected function generateReport(array $data): array
    {
        $success = $data['verification']['all_passed'] ?? false;
        
        return [
            'test_id' => $data['test_id'],
            'scenario' => $data['scenario'],
            'success' => $success,
            'duration' => $data['duration'],
            'phone_number' => $data['phone_number'],
            'conversation' => $data['conversation'],
            'verification' => $data['verification'],
            'timestamp' => now()->toDateTimeString(),
            'summary' => $this->generateSummary($data),
        ];
    }
    
    /**
     * Generate human-readable summary
     */
    protected function generateSummary(array $data): string
    {
        $success = $data['verification']['all_passed'] ?? false;
        $scenario = $data['scenario'];
        
        $summary = "Test scenario '{$scenario}' ";
        $summary .= $success ? "PASSED" : "FAILED";
        $summary .= " in {$data['duration']} seconds.\n";
        
        if (!$success) {
            $summary .= "\nFailed checks:\n";
            foreach ($data['verification']['results'] as $check => $result) {
                if (!$result['passed']) {
                    $summary .= "- {$check}: expected {$result['expected']}, got {$result['actual']}\n";
                }
            }
        }
        
        return $summary;
    }
    
    /**
     * Run all test scenarios
     */
    public function runAllScenarios(array $options = []): array
    {
        $scenarios = ['simple_booking', 'complex_booking', 'no_availability'];
        $results = [];
        
        foreach ($scenarios as $scenario) {
            Log::info("Running scenario: {$scenario}");
            $results[$scenario] = $this->runTestScenario($scenario, $options);
            
            // Wait between tests to avoid overwhelming the system
            sleep(10);
        }
        
        return [
            'total_scenarios' => count($scenarios),
            'passed' => count(array_filter($results, fn($r) => $r['success'])),
            'failed' => count(array_filter($results, fn($r) => !$r['success'])),
            'results' => $results,
            'summary' => $this->generateOverallSummary($results),
        ];
    }
    
    /**
     * Generate overall summary
     */
    protected function generateOverallSummary(array $results): string
    {
        $total = count($results);
        $passed = count(array_filter($results, fn($r) => $r['success']));
        $failed = $total - $passed;
        
        $summary = "Production Test Summary\n";
        $summary .= "======================\n";
        $summary .= "Total scenarios: {$total}\n";
        $summary .= "Passed: {$passed}\n";
        $summary .= "Failed: {$failed}\n";
        $summary .= "Success rate: " . round(($passed / $total) * 100, 2) . "%\n\n";
        
        if ($failed > 0) {
            $summary .= "Failed scenarios:\n";
            foreach ($results as $scenario => $result) {
                if (!$result['success']) {
                    $error = $result['error'] ?? 'Verification failed';
                    $summary .= "- {$scenario}: {$error}\n";
                }
            }
        }
        
        return $summary;
    }
}