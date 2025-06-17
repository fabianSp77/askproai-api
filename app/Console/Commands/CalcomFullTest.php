<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CalcomV2Service;
use App\Models\Company;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class CalcomFullTest extends Command
{
    protected $signature = 'calcom:full-test 
                            {--api-key= : Cal.com API key to test}
                            {--event-type= : Event type ID for booking test}
                            {--skip-booking : Skip booking creation test}
                            {--v1 : Use API v1 instead of v2}';
    
    protected $description = 'Run comprehensive Cal.com API tests including booking';
    
    private array $results = [];
    private ?string $lastBookingId = null;
    
    public function handle()
    {
        $this->info('🧪 Cal.com Comprehensive Integration Test');
        $this->info('========================================');
        
        $apiKey = $this->option('api-key') ?? Company::first()->calcom_api_key ?? config('services.calcom.api_key');
        
        if (!$apiKey) {
            $this->error('No API key provided!');
            return 1;
        }
        
        $useV1 = $this->option('v1');
        $this->info('Using API: ' . ($useV1 ? 'v1' : 'v2'));
        $this->info('API Key: ' . substr($apiKey, 0, 15) . '...');
        $this->newLine();
        
        // Run all tests
        $this->testApiConnection($apiKey, $useV1);
        $this->testEventTypes($apiKey, $useV1);
        $this->testUsers($apiKey, $useV1);
        $this->testTeams($apiKey, $useV1);
        $this->testSchedules($apiKey, $useV1);
        $this->testWebhooks($apiKey, $useV1);
        $this->testBookings($apiKey, $useV1);
        
        $eventTypeId = $this->option('event-type') ?? $this->getFirstEventTypeId();
        if ($eventTypeId) {
            $this->testEventTypeDetails($apiKey, $eventTypeId, $useV1);
            $this->testAvailability($apiKey, $eventTypeId, $useV1);
            
            if (!$this->option('skip-booking')) {
                $this->testCreateBooking($apiKey, $eventTypeId, $useV1);
                
                if ($this->lastBookingId) {
                    $this->testUpdateBooking($apiKey, $this->lastBookingId, $useV1);
                    $this->testCancelBooking($apiKey, $this->lastBookingId, $useV1);
                }
            }
        }
        
        $this->showSummary();
        
        return 0;
    }
    
    private function testApiConnection(string $apiKey, bool $useV1): void
    {
        $this->info('📡 Testing API Connection...');
        
        try {
            if ($useV1) {
                $response = Http::get("https://api.cal.com/v1/me?apiKey={$apiKey}");
            } else {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'cal-api-version' => '2024-08-13',
                ])->get('https://api.cal.com/v2/me');
            }
            
            if ($response->successful()) {
                $data = $response->json();
                $user = $useV1 ? ($data['user'] ?? $data) : ($data['data'] ?? $data);
                
                $this->results['connection'] = 'success';
                $this->info("✅ Connected successfully");
                $this->info("   User: " . ($user['email'] ?? 'Unknown'));
                $this->info("   Name: " . ($user['name'] ?? 'Unknown'));
            } else {
                $this->results['connection'] = 'failed';
                $this->error("❌ Connection failed: " . $response->status());
                $this->error("   " . substr($response->body(), 0, 200));
            }
        } catch (\Exception $e) {
            $this->results['connection'] = 'error';
            $this->error("❌ Exception: " . $e->getMessage());
        }
        
        $this->newLine();
    }
    
    private function testEventTypes(string $apiKey, bool $useV1): void
    {
        $this->info('📋 Testing Event Types...');
        
        try {
            $service = new CalcomV2Service($apiKey);
            
            if ($useV1) {
                $data = $service->getEventTypes();
                $eventTypes = $data['event_types'] ?? $data ?? [];
            } else {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'cal-api-version' => '2024-08-13',
                ])->get('https://api.cal.com/v2/event-types');
                
                if ($response->successful()) {
                    $data = $response->json();
                    $eventTypes = $data['data'] ?? [];
                } else {
                    // Fallback to v1
                    $data = $service->getEventTypes();
                    $eventTypes = $data['event_types'] ?? $data ?? [];
                }
            }
            
            $count = count($eventTypes);
            $this->results['event_types'] = $count;
            
            if ($count > 0) {
                $this->info("✅ Found {$count} event types:");
                
                foreach (array_slice($eventTypes, 0, 3) as $type) {
                    $this->info("   - ID: {$type['id']} | {$type['title']} ({$type['length']}min)");
                    
                    // Store first event type ID
                    if (!isset($this->results['first_event_type_id'])) {
                        $this->results['first_event_type_id'] = $type['id'];
                    }
                }
                
                if ($count > 3) {
                    $this->info("   ... and " . ($count - 3) . " more");
                }
            } else {
                $this->warn("⚠️  No event types found");
            }
        } catch (\Exception $e) {
            $this->results['event_types'] = 'error';
            $this->error("❌ Exception: " . $e->getMessage());
        }
        
        $this->newLine();
    }
    
    private function testUsers(string $apiKey, bool $useV1): void
    {
        $this->info('👥 Testing Users/Team...');
        
        try {
            $service = new CalcomV2Service($apiKey);
            $data = $service->getUsers();
            $users = $data['users'] ?? $data ?? [];
            
            $count = count($users);
            $this->results['users'] = $count;
            
            if ($count > 0) {
                $this->info("✅ Found {$count} users:");
                
                foreach (array_slice($users, 0, 3) as $user) {
                    $this->info("   - {$user['name']} ({$user['email']})");
                }
            } else {
                $this->warn("⚠️  No users found");
            }
        } catch (\Exception $e) {
            $this->results['users'] = 'error';
            $this->error("❌ Exception: " . $e->getMessage());
        }
        
        $this->newLine();
    }
    
    private function testBookings(string $apiKey, bool $useV1): void
    {
        $this->info('📅 Testing Bookings Retrieval...');
        
        try {
            $service = new CalcomV2Service($apiKey);
            $result = $service->getBookings(['limit' => 10]);
            
            if ($result['success']) {
                $bookings = $result['data']['bookings'];
                $count = count($bookings);
                $this->results['bookings'] = $count;
                
                $this->info("✅ Retrieved {$count} bookings");
                
                if ($count > 0) {
                    $this->info("   Recent bookings:");
                    foreach (array_slice($bookings, 0, 3) as $booking) {
                        $status = strtoupper($booking['status']);
                        $date = Carbon::parse($booking['start'] ?? $booking['startTime'])->format('Y-m-d H:i');
                        $this->info("   - [{$status}] {$booking['title']} on {$date}");
                    }
                    
                    // Store last booking ID for later tests
                    $this->lastBookingId = $bookings[0]['id'];
                }
            } else {
                $this->results['bookings'] = 'failed';
                $this->error("❌ Failed: " . $result['error']);
            }
        } catch (\Exception $e) {
            $this->results['bookings'] = 'error';
            $this->error("❌ Exception: " . $e->getMessage());
        }
        
        $this->newLine();
    }
    
    private function testAvailability(string $apiKey, int $eventTypeId, bool $useV1): void
    {
        $this->info('🕐 Testing Availability Check...');
        $this->info("   Event Type ID: {$eventTypeId}");
        
        try {
            $service = new CalcomV2Service($apiKey);
            $tomorrow = Carbon::tomorrow()->format('Y-m-d');
            
            $result = $service->checkAvailability($eventTypeId, $tomorrow, 'Europe/Berlin');
            
            if ($result['success']) {
                $slots = $result['data']['slots'] ?? [];
                $count = count($slots);
                $this->results['availability'] = $count;
                
                $this->info("✅ Found {$count} available slots for {$tomorrow}:");
                
                if ($count > 0) {
                    $displaySlots = array_slice($slots, 0, 5);
                    foreach ($displaySlots as $slot) {
                        $time = Carbon::parse($slot)->format('H:i');
                        $this->info("   - {$time}");
                    }
                    
                    if ($count > 5) {
                        $this->info("   ... and " . ($count - 5) . " more slots");
                    }
                } else {
                    $this->warn("   No available slots found");
                }
            } else {
                $this->results['availability'] = 'failed';
                $this->error("❌ Failed: " . $result['error']);
            }
        } catch (\Exception $e) {
            $this->results['availability'] = 'error';
            $this->error("❌ Exception: " . $e->getMessage());
        }
        
        $this->newLine();
    }
    
    private function testCreateBooking(string $apiKey, int $eventTypeId, bool $useV1): void
    {
        $this->info('➕ Testing Booking Creation...');
        
        if (!$this->confirm('Create a test booking?', true)) {
            $this->info('   Skipped');
            return;
        }
        
        try {
            $service = new CalcomV2Service($apiKey);
            $tomorrow = Carbon::tomorrow();
            $startTime = $tomorrow->copy()->setTime(14, 0)->toIso8601String();
            $endTime = $tomorrow->copy()->setTime(15, 0)->toIso8601String();
            
            $customerData = [
                'name' => 'Test Customer ' . now()->format('His'),
                'email' => 'test-' . time() . '@example.com',
                'phone' => '+49 151 ' . rand(10000000, 99999999),
            ];
            
            $result = $service->bookAppointment(
                $eventTypeId,
                $startTime,
                $endTime,
                $customerData,
                'Test booking created by calcom:full-test command'
            );
            
            if ($result) {
                $this->results['create_booking'] = 'success';
                $this->lastBookingId = $result['id'] ?? $result['uid'] ?? null;
                
                $this->info("✅ Booking created successfully!");
                $this->info("   ID: " . $this->lastBookingId);
                $this->info("   Customer: {$customerData['name']}");
                $this->info("   Time: " . $tomorrow->format('Y-m-d 14:00'));
            } else {
                $this->results['create_booking'] = 'failed';
                $this->error("❌ Failed to create booking");
            }
        } catch (\Exception $e) {
            $this->results['create_booking'] = 'error';
            $this->error("❌ Exception: " . $e->getMessage());
        }
        
        $this->newLine();
    }
    
    private function testUpdateBooking(string $apiKey, string $bookingId, bool $useV1): void
    {
        $this->info('✏️  Testing Booking Update...');
        
        if (!$this->confirm('Update the test booking?', true)) {
            $this->info('   Skipped');
            return;
        }
        
        try {
            $url = $useV1 
                ? "https://api.cal.com/v1/bookings/{$bookingId}?apiKey={$apiKey}"
                : "https://api.cal.com/v2/bookings/{$bookingId}";
                
            $headers = $useV1 ? [] : [
                'Authorization' => 'Bearer ' . $apiKey,
                'cal-api-version' => '2024-08-13',
            ];
            
            $response = Http::withHeaders($headers)->patch($url, [
                'title' => 'Updated: Test Booking',
                'description' => 'Updated by calcom:full-test at ' . now()->format('Y-m-d H:i:s')
            ]);
            
            if ($response->successful()) {
                $this->results['update_booking'] = 'success';
                $this->info("✅ Booking updated successfully!");
            } else {
                $this->results['update_booking'] = 'failed';
                $this->error("❌ Failed: " . $response->status() . " - " . $response->body());
            }
        } catch (\Exception $e) {
            $this->results['update_booking'] = 'error';
            $this->error("❌ Exception: " . $e->getMessage());
        }
        
        $this->newLine();
    }
    
    private function testCancelBooking(string $apiKey, string $bookingId, bool $useV1): void
    {
        $this->info('🚫 Testing Booking Cancellation...');
        
        if (!$this->confirm('Cancel the test booking?', true)) {
            $this->info('   Skipped');
            return;
        }
        
        try {
            $url = $useV1 
                ? "https://api.cal.com/v1/bookings/{$bookingId}?apiKey={$apiKey}"
                : "https://api.cal.com/v2/bookings/{$bookingId}";
                
            $headers = $useV1 ? [] : [
                'Authorization' => 'Bearer ' . $apiKey,
                'cal-api-version' => '2024-08-13',
            ];
            
            $response = Http::withHeaders($headers)->delete($url, [
                'cancellationReason' => 'Test cancellation by calcom:full-test'
            ]);
            
            if ($response->successful()) {
                $this->results['cancel_booking'] = 'success';
                $this->info("✅ Booking cancelled successfully!");
            } else {
                $this->results['cancel_booking'] = 'failed';
                $this->error("❌ Failed: " . $response->status() . " - " . $response->body());
            }
        } catch (\Exception $e) {
            $this->results['cancel_booking'] = 'error';
            $this->error("❌ Exception: " . $e->getMessage());
        }
        
        $this->newLine();
    }
    
    private function testTeams(string $apiKey, bool $useV1): void
    {
        $this->info('👥 Testing Teams...');
        
        if ($useV1) {
            $this->info("   ⏭️  Skipped (v2 only feature)");
            $this->newLine();
            return;
        }
        
        try {
            $service = new CalcomV2Service($apiKey);
            $result = $service->getTeams();
            
            if ($result['success']) {
                $teams = $result['data']['data'] ?? $result['data'] ?? [];
                $count = count($teams);
                $this->results['teams'] = $count;
                
                $this->info("✅ Found {$count} teams:");
                
                foreach (array_slice($teams, 0, 3) as $team) {
                    $this->info("   - {$team['name']} (ID: {$team['id']})");
                    
                    // Store first team ID
                    if (!isset($this->results['first_team_id'])) {
                        $this->results['first_team_id'] = $team['id'];
                    }
                }
            } else {
                $this->results['teams'] = 'failed';
                $this->error("❌ Failed: " . $result['error']);
            }
        } catch (\Exception $e) {
            $this->results['teams'] = 'error';
            $this->error("❌ Exception: " . $e->getMessage());
        }
        
        $this->newLine();
    }
    
    private function testSchedules(string $apiKey, bool $useV1): void
    {
        $this->info('📅 Testing Schedules...');
        
        if ($useV1) {
            $this->info("   ⏭️  Skipped (v2 only feature)");
            $this->newLine();
            return;
        }
        
        try {
            $service = new CalcomV2Service($apiKey);
            $result = $service->getSchedules();
            
            if ($result['success']) {
                $schedules = $result['data']['data'] ?? $result['data'] ?? [];
                $count = count($schedules);
                $this->results['schedules'] = $count;
                
                $this->info("✅ Found {$count} schedules:");
                
                foreach (array_slice($schedules, 0, 3) as $schedule) {
                    $this->info("   - {$schedule['name']} (ID: {$schedule['id']})");
                }
            } else {
                $this->results['schedules'] = 'failed';
                $this->error("❌ Failed: " . $result['error']);
            }
        } catch (\Exception $e) {
            $this->results['schedules'] = 'error';
            $this->error("❌ Exception: " . $e->getMessage());
        }
        
        $this->newLine();
    }
    
    private function testWebhooks(string $apiKey, bool $useV1): void
    {
        $this->info('🪝 Testing Webhooks...');
        
        if ($useV1) {
            $this->info("   ⏭️  Skipped (v2 only feature)");
            $this->newLine();
            return;
        }
        
        try {
            $service = new CalcomV2Service($apiKey);
            $result = $service->getWebhooks();
            
            if ($result['success']) {
                $webhooks = $result['data']['data'] ?? $result['data'] ?? [];
                $count = count($webhooks);
                $this->results['webhooks'] = $count;
                
                $this->info("✅ Found {$count} webhooks:");
                
                foreach (array_slice($webhooks, 0, 3) as $webhook) {
                    $active = $webhook['active'] ? '✓' : '✗';
                    $this->info("   - [{$active}] {$webhook['subscriberUrl']}");
                    $this->info("     Triggers: " . implode(', ', $webhook['triggers'] ?? []));
                }
                
                // Check if our webhook is registered
                $ourWebhookUrl = url('/api/calcom/webhook');
                $hasOurWebhook = collect($webhooks)->contains(function ($webhook) use ($ourWebhookUrl) {
                    return $webhook['subscriberUrl'] === $ourWebhookUrl;
                });
                
                if (!$hasOurWebhook) {
                    $this->warn("   ⚠️  AskProAI webhook not found: {$ourWebhookUrl}");
                } else {
                    $this->info("   ✓ AskProAI webhook is registered");
                }
            } else {
                $this->results['webhooks'] = 'failed';
                $this->error("❌ Failed: " . $result['error']);
            }
        } catch (\Exception $e) {
            $this->results['webhooks'] = 'error';
            $this->error("❌ Exception: " . $e->getMessage());
        }
        
        $this->newLine();
    }
    
    private function testEventTypeDetails(string $apiKey, int $eventTypeId, bool $useV1): void
    {
        $this->info("📋 Testing Event Type Details (ID: {$eventTypeId})...");
        
        if ($useV1) {
            $this->info("   ⏭️  Skipped (v2 endpoint)");
            $this->newLine();
            return;
        }
        
        try {
            $service = new CalcomV2Service($apiKey);
            $result = $service->getEventTypeDetails($eventTypeId);
            
            if ($result['success']) {
                $eventType = $result['data']['data'] ?? $result['data'] ?? null;
                
                if ($eventType) {
                    $this->results['event_type_details'] = 'success';
                    $this->info("✅ Event Type Details:");
                    $this->info("   - Title: " . ($eventType['title'] ?? 'N/A'));
                    $this->info("   - Duration: " . ($eventType['length'] ?? 'N/A') . " minutes");
                    $this->info("   - Scheduling Type: " . ($eventType['schedulingType'] ?? 'N/A'));
                    
                    // Show hosts/users
                    $hosts = $eventType['hosts'] ?? $eventType['users'] ?? [];
                    if (count($hosts) > 0) {
                        $this->info("   - Hosts (" . count($hosts) . "):");
                        foreach ($hosts as $host) {
                            $this->info("     • " . ($host['name'] ?? $host['username'] ?? 'Unknown') . 
                                       " (" . ($host['email'] ?? 'N/A') . ")");
                        }
                    }
                    
                    // Show team if applicable
                    if (isset($eventType['team'])) {
                        $this->info("   - Team: " . $eventType['team']['name']);
                    }
                } else {
                    $this->warn("   ⚠️  No event type data returned");
                }
            } else {
                $this->results['event_type_details'] = 'failed';
                $this->error("❌ Failed: " . $result['error']);
            }
        } catch (\Exception $e) {
            $this->results['event_type_details'] = 'error';
            $this->error("❌ Exception: " . $e->getMessage());
        }
        
        $this->newLine();
    }
    
    private function getFirstEventTypeId(): ?int
    {
        return $this->results['first_event_type_id'] ?? null;
    }
    
    private function showSummary(): void
    {
        $this->info('📊 Test Summary');
        $this->info('==============');
        
        $table = [];
        $passed = 0;
        $failed = 0;
        
        $tests = [
            'API Connection' => $this->results['connection'] ?? 'not run',
            'Event Types' => $this->results['event_types'] ?? 'not run',
            'Event Type Details' => $this->results['event_type_details'] ?? 'not run',
            'Users/Team' => $this->results['users'] ?? 'not run',
            'Teams' => $this->results['teams'] ?? 'not run',
            'Schedules' => $this->results['schedules'] ?? 'not run',
            'Webhooks' => $this->results['webhooks'] ?? 'not run',
            'Bookings List' => $this->results['bookings'] ?? 'not run',
            'Availability' => $this->results['availability'] ?? 'not run',
            'Create Booking' => $this->results['create_booking'] ?? 'not run',
            'Update Booking' => $this->results['update_booking'] ?? 'not run',
            'Cancel Booking' => $this->results['cancel_booking'] ?? 'not run',
        ];
        
        foreach ($tests as $test => $result) {
            $status = match($result) {
                'success' => '✅ Passed',
                'failed', 'error' => '❌ Failed',
                'not run' => '⏭️  Skipped',
                default => is_numeric($result) ? "✅ Found: {$result}" : '❓ Unknown'
            };
            
            if (str_contains($status, '✅')) $passed++;
            elseif (str_contains($status, '❌')) $failed++;
            
            $table[] = [$test, $status];
        }
        
        $this->table(['Test', 'Result'], $table);
        
        $this->newLine();
        $this->info("Total: " . count($tests) . " tests");
        $this->info("Passed: {$passed} ✅");
        $this->info("Failed: {$failed} ❌");
        
        if ($failed === 0) {
            $this->info("\n🎉 All tests passed! Cal.com integration is working correctly.");
        } else {
            $this->warn("\n⚠️  Some tests failed. Please check your configuration.");
        }
    }
}