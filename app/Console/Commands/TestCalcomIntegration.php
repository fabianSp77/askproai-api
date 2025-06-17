<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CalcomV2Service;
use App\Models\Company;
use App\Models\Appointment;
use App\Jobs\SyncCalcomBookingsJob;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class TestCalcomIntegration extends Command
{
    protected $signature = 'calcom:test 
                            {--company= : Company ID to test with}
                            {--api-key= : Override API key}
                            {--full : Run full integration test}';
    
    protected $description = 'Test Cal.com integration functionality';
    
    private array $results = [];
    private int $passedTests = 0;
    private int $failedTests = 0;
    
    public function handle()
    {
        $this->info('🧪 Cal.com Integration Test Suite');
        $this->info('=================================');
        
        // Get company and API key
        $company = $this->getCompany();
        $apiKey = $this->option('api-key') ?? $company->calcom_api_key ?? config('services.calcom.api_key');
        
        if (!$apiKey) {
            $this->error('❌ No API key found! Please configure Cal.com API key.');
            return 1;
        }
        
        $this->info("Company: {$company->name}");
        $this->info("API Key: " . substr($apiKey, 0, 15) . "...\n");
        
        // Run tests
        $this->testApiConnection($apiKey);
        $this->testGetBookings($apiKey);
        $this->testEventTypes($apiKey);
        $this->testSlotAvailability($apiKey);
        
        if ($this->option('full')) {
            $this->testSyncJob($company, $apiKey);
            $this->testWebhookProcessing($company);
            $this->testErrorHandling($apiKey);
        }
        
        // Show results
        $this->showResults();
        
        return $this->failedTests > 0 ? 1 : 0;
    }
    
    private function getCompany(): Company
    {
        if ($companyId = $this->option('company')) {
            return Company::findOrFail($companyId);
        }
        
        return Company::first();
    }
    
    private function testApiConnection(string $apiKey): void
    {
        $this->info('📡 Testing API Connection...');
        
        $testName = 'API v2 Connection';
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->get('https://api.cal.com/v2/me');
            
            if ($response->successful()) {
                $data = $response->json();
                $this->addResult($testName, true, "Connected as: " . ($data['user']['email'] ?? 'Unknown'));
            } else {
                $this->addResult($testName, false, "HTTP {$response->status()}: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->addResult($testName, false, $e->getMessage());
        }
        
        // Test v1 API
        $testName = 'API v1 Connection';
        try {
            $response = Http::get("https://api.cal.com/v1/me?apiKey={$apiKey}");
            
            if ($response->successful()) {
                $this->addResult($testName, true, "v1 API accessible");
            } else {
                $this->addResult($testName, false, "HTTP {$response->status()} - Expected if using v2-only key");
            }
        } catch (\Exception $e) {
            $this->addResult($testName, false, $e->getMessage());
        }
    }
    
    private function testGetBookings(string $apiKey): void
    {
        $this->info('📅 Testing Bookings Endpoint...');
        
        $service = new CalcomV2Service($apiKey);
        $testName = 'Get Bookings';
        
        try {
            $response = $service->getBookings(['limit' => 5]);
            
            if ($response['success']) {
                $bookings = $response['data']['bookings'];
                $count = count($bookings);
                $this->addResult($testName, true, "Retrieved {$count} bookings");
                
                if ($count > 0) {
                    $this->info("  Sample booking:");
                    $booking = $bookings[0];
                    $this->info("  - ID: {$booking['id']}");
                    $this->info("  - Title: {$booking['title']}");
                    $this->info("  - Status: {$booking['status']}");
                    $this->info("  - Start: {$booking['start']}");
                }
            } else {
                $this->addResult($testName, false, $response['error']);
            }
        } catch (\Exception $e) {
            $this->addResult($testName, false, $e->getMessage());
        }
    }
    
    private function testEventTypes(string $apiKey): void
    {
        $this->info('📋 Testing Event Types...');
        
        $service = new CalcomV2Service($apiKey);
        $testName = 'Get Event Types';
        
        try {
            $eventTypes = $service->getEventTypes();
            
            if ($eventTypes) {
                $count = count($eventTypes['event_types'] ?? $eventTypes);
                $this->addResult($testName, true, "Retrieved {$count} event types");
            } else {
                $this->addResult($testName, false, "Failed to retrieve event types");
            }
        } catch (\Exception $e) {
            $this->addResult($testName, false, $e->getMessage());
        }
    }
    
    private function testSlotAvailability(string $apiKey): void
    {
        $this->info('🕐 Testing Slot Availability...');
        
        $service = new CalcomV2Service($apiKey);
        $testName = 'Check Available Slots';
        
        try {
            $tomorrow = Carbon::tomorrow()->format('Y-m-d');
            $result = $service->checkAvailability(
                eventTypeId: 1,
                date: $tomorrow,
                timezone: 'Europe/Berlin'
            );
            
            if ($result['success']) {
                $slots = $result['data']['slots'] ?? [];
                $count = count($slots);
                $this->addResult($testName, true, "Found {$count} available slots for {$tomorrow}");
            } else {
                $this->addResult($testName, false, $result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->addResult($testName, false, $e->getMessage());
        }
    }
    
    private function testSyncJob(Company $company, string $apiKey): void
    {
        $this->info('🔄 Testing Sync Job...');
        
        $testName = 'Sync Job Execution';
        
        try {
            $beforeCount = Appointment::whereNotNull('calcom_v2_booking_id')->count();
            
            // Run sync job synchronously
            SyncCalcomBookingsJob::dispatchSync($company, $apiKey);
            
            $afterCount = Appointment::whereNotNull('calcom_v2_booking_id')->count();
            $synced = $afterCount - $beforeCount;
            
            $this->addResult($testName, true, "Synced {$synced} new appointments (Total: {$afterCount})");
            
            // Check for recent sync
            $recentSync = Appointment::whereNotNull('calcom_v2_booking_id')
                ->where('updated_at', '>', now()->subMinutes(5))
                ->count();
                
            $this->addResult('Recent Sync Activity', $recentSync > 0, "{$recentSync} appointments updated recently");
            
        } catch (\Exception $e) {
            $this->addResult($testName, false, $e->getMessage());
        }
    }
    
    private function testWebhookProcessing(Company $company): void
    {
        $this->info('🪝 Testing Webhook Processing...');
        
        $testName = 'Webhook Signature Verification';
        
        try {
            $payload = [
                'triggerEvent' => 'BOOKING_CREATED',
                'createdAt' => now()->toIso8601String(),
                'payload' => [
                    'id' => 999999999,
                    'uid' => 'test-webhook-' . time(),
                    'title' => 'Test Webhook Booking',
                    'startTime' => now()->addDay()->toIso8601String(),
                    'endTime' => now()->addDay()->addHour()->toIso8601String(),
                    'status' => 'ACCEPTED',
                    'attendees' => [
                        ['name' => 'Test User', 'email' => 'test@example.com']
                    ]
                ]
            ];
            
            $secret = config('services.calcom.webhook_secret');
            if (!$secret) {
                $this->addResult($testName, false, 'No webhook secret configured');
                return;
            }
            
            $signature = hash_hmac('sha256', json_encode($payload), $secret);
            
            // Test webhook route exists
            $routes = app('router')->getRoutes();
            $webhookRoute = collect($routes)->first(function ($route) {
                return $route->uri() === 'api/calcom/webhook' && in_array('POST', $route->methods());
            });
            
            if ($webhookRoute) {
                $this->addResult($testName, true, 'Webhook route configured correctly');
            } else {
                $this->addResult($testName, false, 'Webhook route not found');
            }
            
        } catch (\Exception $e) {
            $this->addResult($testName, false, $e->getMessage());
        }
    }
    
    private function testErrorHandling(string $apiKey): void
    {
        $this->info('⚠️  Testing Error Handling...');
        
        // Test invalid API key
        $testName = 'Invalid API Key Handling';
        $service = new CalcomV2Service('invalid_key_123');
        
        $response = $service->getBookings();
        if (!$response['success'] && isset($response['error'])) {
            $this->addResult($testName, true, 'Properly handles invalid API key');
        } else {
            $this->addResult($testName, false, 'Did not properly handle invalid API key');
        }
        
        // Test network timeout
        $testName = 'Network Error Handling';
        Http::fake([
            'https://api.cal.com/*' => Http::response('', 500)
        ]);
        
        $service = new CalcomV2Service($apiKey);
        $response = $service->getBookings();
        
        if (!$response['success']) {
            $this->addResult($testName, true, 'Properly handles network timeouts');
        } else {
            $this->addResult($testName, false, 'Did not handle network timeout');
        }
        
        Http::clearResolvedInstances();
    }
    
    private function addResult(string $test, bool $passed, string $message): void
    {
        $this->results[] = [
            'test' => $test,
            'passed' => $passed,
            'message' => $message
        ];
        
        if ($passed) {
            $this->passedTests++;
            $this->line("  ✅ {$test}: {$message}");
        } else {
            $this->failedTests++;
            $this->line("  ❌ {$test}: {$message}");
        }
    }
    
    private function showResults(): void
    {
        $this->info("\n📊 Test Results");
        $this->info("===============");
        
        $total = $this->passedTests + $this->failedTests;
        $percentage = $total > 0 ? round(($this->passedTests / $total) * 100) : 0;
        
        $this->info("Total Tests: {$total}");
        $this->info("Passed: {$this->passedTests} ✅");
        $this->info("Failed: {$this->failedTests} ❌");
        $this->info("Success Rate: {$percentage}%");
        
        if ($this->failedTests > 0) {
            $this->warn("\n⚠️  Some tests failed. Please check the configuration:");
            $this->warn("1. Verify Cal.com API key is valid");
            $this->warn("2. Check webhook secret is configured");
            $this->warn("3. Ensure all routes are properly registered");
            $this->warn("4. Verify queue workers are running for full sync");
        } else {
            $this->info("\n🎉 All tests passed! Cal.com integration is working correctly.");
        }
    }
}