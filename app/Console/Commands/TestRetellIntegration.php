<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Testing\RetellCallSimulator;
use Carbon\Carbon;

class TestRetellIntegration extends Command
{
    protected $signature = 'retell:test
                            {--scenario=complete : Test scenario (complete|webhook|appointment|availability)}
                            {--cleanup : Clean up test data after completion}
                            {--validate : Run validation checks}
                            {--from= : From phone number}
                            {--to= : To phone number}
                            {--customer= : Customer name}
                            {--service= : Service name}
                            {--date= : Appointment date (d.m.Y)}
                            {--time= : Appointment time (H:i)}';

    protected $description = 'Test Retell integration with various scenarios';

    public function handle()
    {
        $scenario = $this->option('scenario');
        $this->info('🧪 Starting Retell Integration Test');
        $this->info('Scenario: ' . $scenario);
        $this->info('Timestamp: ' . now()->format('Y-m-d H:i:s'));
        $this->line('');

        $simulator = new RetellCallSimulator();

        try {
            $results = match($scenario) {
                'complete' => $this->runCompleteTest($simulator),
                'webhook' => $this->runWebhookTest($simulator),
                'appointment' => $this->runAppointmentTest($simulator),
                'availability' => $this->runAvailabilityTest($simulator),
                default => $this->error('Unknown scenario: ' . $scenario)
            };

            if ($results) {
                $this->displayResults($results);

                if ($this->option('validate')) {
                    $this->runValidation($simulator);
                }

                if ($this->option('cleanup')) {
                    $simulator->cleanup();
                    $this->info('✅ Test data cleaned up');
                }
            }

        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function runCompleteTest($simulator)
    {
        $this->info('📞 Running complete call flow test...');

        $options = [
            'from_number' => $this->option('from') ?? '+491510' . rand(1000000, 9999999),
            'to_number' => $this->option('to') ?? '+493083793369',
            'customer_name' => $this->option('customer') ?? 'Test Kunde ' . rand(100, 999),
            'service' => $this->option('service') ?? 'Herrenhaarschnitt',
            'date' => $this->option('date') ?? Carbon::tomorrow()->format('d.m.Y'),
            'time' => $this->option('time') ?? '14:00'
        ];

        $this->table(['Parameter', 'Value'], collect($options)->map(function($value, $key) {
            return [str_replace('_', ' ', ucfirst($key)), $value];
        })->toArray());

        return $simulator->simulateCompleteCallFlow($options);
    }

    private function runWebhookTest($simulator)
    {
        $this->info('🔔 Testing webhook endpoints...');

        $fromNumber = $this->option('from') ?? '+491510' . rand(1000000, 9999999);
        $toNumber = $this->option('to') ?? '+493083793369';

        $results = [];

        // Test call.started
        $this->line('Sending call.started webhook...');
        $results['call_started'] = $simulator->sendCallStartedWebhook($fromNumber, $toNumber);
        $this->line($results['call_started']['success'] ? '✅ Success' : '❌ Failed');

        sleep(1);

        // Test call.ended
        $this->line('Sending call.ended webhook...');
        $results['call_ended'] = $simulator->sendCallEndedWebhook(false);
        $this->line($results['call_ended']['success'] ? '✅ Success' : '❌ Failed');

        return $results;
    }

    private function runAppointmentTest($simulator)
    {
        $this->info('📅 Testing appointment booking...');

        $results = [];

        // Test customer check
        $this->line('Checking customer...');
        $results['customer_check'] = $simulator->sendFunctionCall('check_customer', [
            'phone_number' => '+491510' . rand(1000000, 9999999)
        ]);

        // Test appointment collection
        $this->line('Collecting appointment data...');
        $results['collect_appointment'] = $simulator->sendFunctionCall('collect_appointment', [
            'service' => 'Herrenhaarschnitt',
            'customer_phone' => '+491510' . rand(1000000, 9999999),
            'customer_name' => 'Test Kunde',
            'datum' => Carbon::tomorrow()->format('d.m.Y'),
            'uhrzeit' => '14:00'
        ]);

        return $results;
    }

    private function runAvailabilityTest($simulator)
    {
        $this->info('🗓️ Testing availability check...');

        $dates = [
            Carbon::tomorrow(),
            Carbon::tomorrow()->addDay(),
            Carbon::tomorrow()->addDays(7)
        ];

        $results = [];

        foreach ($dates as $date) {
            $this->line('Checking availability for ' . $date->format('d.m.Y') . '...');

            $results[$date->format('Y-m-d')] = $simulator->sendFunctionCall('check_availability', [
                'service' => 'Herrenhaarschnitt',
                'date' => $date->format('Y-m-d'),
                'time' => '14:00'
            ]);

            $status = $results[$date->format('Y-m-d')]['success'] ? '✅' : '❌';
            $this->line($status . ' ' . $date->format('d.m.Y'));
        }

        return $results;
    }

    private function runValidation($simulator)
    {
        $this->info('');
        $this->info('🔍 Running validation checks...');

        $validation = $simulator->validateCallProcessing();

        if ($validation['success']) {
            $this->info('✅ All validations passed!');
        } else {
            $this->warn('⚠️ Some validations failed');
        }

        $this->table(['Check', 'Result'], collect($validation['validations'])->map(function($passed, $check) {
            return [
                str_replace('_', ' ', ucfirst($check)),
                $passed ? '✅ Passed' : '❌ Failed'
            ];
        })->toArray());

        if (isset($validation['call_data'])) {
            $this->info('');
            $this->info('Call Details:');
            $this->table(['Field', 'Value'], collect($validation['call_data'])->map(function($value, $field) {
                return [ucfirst($field), $value];
            })->toArray());
        }
    }

    private function displayResults($results)
    {
        $this->info('');
        $this->info('📊 Test Results:');

        $summary = [];
        $allSuccess = true;

        foreach ($results as $step => $result) {
            $success = $result['success'] ?? false;
            $allSuccess = $allSuccess && $success;

            $summary[] = [
                ucfirst(str_replace('_', ' ', $step)),
                $result['status_code'] ?? 'N/A',
                $success ? '✅ Success' : '❌ Failed'
            ];

            if (!$success && isset($result['response'])) {
                $this->warn('Error in ' . $step . ': ' . json_encode($result['response']));
            }
        }

        $this->table(['Step', 'HTTP Code', 'Status'], $summary);

        $this->info('');
        if ($allSuccess) {
            $this->info('✅ All tests passed successfully!');
        } else {
            $this->error('❌ Some tests failed. Check the details above.');
        }
    }
}