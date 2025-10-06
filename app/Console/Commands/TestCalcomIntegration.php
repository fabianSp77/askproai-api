<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Services\CalcomService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestCalcomIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calcom:test
                            {--webhook : Test webhook endpoint}
                            {--api : Test API connection}
                            {--sync : Test sync functionality}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Cal.com integration components';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('====================================');
        $this->info(' Cal.com Integration Test');
        $this->info('====================================');

        $testApi = $this->option('api') || (!$this->option('webhook') && !$this->option('sync'));
        $testWebhook = $this->option('webhook');
        $testSync = $this->option('sync');

        $results = [];

        // Test API Connection
        if ($testApi) {
            $results['API'] = $this->testApiConnection();
        }

        // Test Webhook
        if ($testWebhook) {
            $results['Webhook'] = $this->testWebhookEndpoint();
        }

        // Test Sync
        if ($testSync) {
            $results['Sync'] = $this->testSyncFunctionality();
        }

        // Display results
        $this->newLine();
        $this->info('Test Results:');
        $this->info('─────────────');

        $allPassed = true;
        foreach ($results as $test => $passed) {
            if ($passed) {
                $this->info("✅ {$test}: PASSED");
            } else {
                $this->error("❌ {$test}: FAILED");
                $allPassed = false;
            }
        }

        return $allPassed ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Test API connection to Cal.com
     */
    private function testApiConnection(): bool
    {
        $this->info("\n🔍 Testing API Connection...");

        try {
            // Check configuration
            $apiKey = config('services.calcom.api_key');
            $baseUrl = config('services.calcom.base_url');

            if (!$apiKey) {
                $this->error("   ❌ API Key not configured");
                return false;
            }

            if (!$baseUrl) {
                $this->error("   ❌ Base URL not configured");
                return false;
            }

            $this->info("   ✓ Configuration found");
            $this->info("   Base URL: {$baseUrl}");

            // Test API call
            $calcomService = new CalcomService();
            $response = $calcomService->fetchEventTypes();

            if (!$response->successful()) {
                $this->error("   ❌ API call failed: " . $response->status());
                $this->error("   Response: " . $response->body());
                return false;
            }

            $eventTypes = $response->json()['event_types'] ?? [];
            $this->info("   ✓ API call successful");
            $this->info("   Found " . count($eventTypes) . " Event Types");

            // Display Event Types
            if (count($eventTypes) > 0) {
                $this->info("\n   Event Types:");
                foreach (array_slice($eventTypes, 0, 5) as $eventType) {
                    $this->info("   - {$eventType['title']} (ID: {$eventType['id']})");
                }
                if (count($eventTypes) > 5) {
                    $this->info("   ... and " . (count($eventTypes) - 5) . " more");
                }
            }

            return true;

        } catch (\Exception $e) {
            $this->error("   ❌ Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test webhook endpoint
     */
    private function testWebhookEndpoint(): bool
    {
        $this->info("\n🔍 Testing Webhook Endpoint...");

        try {
            // Check multiple possible webhook URLs
            $possibleRoutes = [
                'api/calcom/webhook',
                'webhooks/calcom',
                'api/webhooks/calcom'
            ];

            // Check if route exists
            $routes = app('router')->getRoutes();
            $routeExists = false;
            $foundRoute = null;

            foreach ($routes as $route) {
                if (in_array($route->uri(), $possibleRoutes) && in_array('POST', $route->methods())) {
                    $routeExists = true;
                    $foundRoute = $route->uri();
                    break;
                }
            }

            if ($foundRoute) {
                $webhookUrl = url('/' . $foundRoute);
                $this->info("   Webhook URL: {$webhookUrl}");
            }

            if (!$routeExists) {
                $this->error("   ❌ Webhook route not registered");
                return false;
            }

            $this->info("   ✓ Webhook route registered");

            // Test webhook with sample payload
            $samplePayload = [
                'triggerEvent' => 'EVENT_TYPE.CREATED',
                'payload' => [
                    'id' => 999999,
                    'title' => 'Test Event Type',
                    'slug' => 'test-event-type',
                    'length' => 30,
                    'hidden' => false,
                    'userId' => 1,
                    'teamId' => null,
                    'scheduleId' => null,
                    'price' => 0,
                    'currency' => 'USD',
                    'description' => 'Test event type from CLI',
                    'locations' => [],
                    'metadata' => [],
                    'bookingFields' => []
                ]
            ];

            $this->info("   ✓ Sample payload created");
            $this->warn("   ⚠️  Actual webhook test requires Cal.com to send real webhook");

            return true;

        } catch (\Exception $e) {
            $this->error("   ❌ Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test sync functionality
     */
    private function testSyncFunctionality(): bool
    {
        $this->info("\n🔍 Testing Sync Functionality...");

        try {
            // Check for services with Cal.com IDs
            $servicesWithCalcom = Service::whereNotNull('calcom_event_type_id')->count();
            $servicesWithoutCalcom = Service::whereNull('calcom_event_type_id')->count();

            $this->info("   Services with Cal.com ID: {$servicesWithCalcom}");
            $this->info("   Services without Cal.com ID: {$servicesWithoutCalcom}");

            // Check sync status distribution
            $syncStatuses = Service::selectRaw('sync_status, COUNT(*) as count')
                ->groupBy('sync_status')
                ->pluck('count', 'sync_status')
                ->toArray();

            $this->info("\n   Sync Status Distribution:");
            foreach ($syncStatuses as $status => $count) {
                $emoji = match($status) {
                    'synced' => '✅',
                    'pending' => '⏳',
                    'error' => '❌',
                    'never' => '⚫',
                    default => '❓'
                };
                $this->info("   {$emoji} {$status}: {$count}");
            }

            // Check last sync times
            $recentlySynced = Service::where('sync_status', 'synced')
                ->whereNotNull('last_calcom_sync')
                ->where('last_calcom_sync', '>', now()->subHours(24))
                ->count();

            $this->info("\n   Recently synced (last 24h): {$recentlySynced}");

            // Test sync command availability
            $this->info("\n   Testing sync command...");
            $exitCode = $this->call('calcom:sync-services', ['--check-only' => true]);

            if ($exitCode === 0) {
                $this->info("   ✓ Sync command working");
            } else {
                $this->error("   ❌ Sync command failed");
                return false;
            }

            return true;

        } catch (\Exception $e) {
            $this->error("   ❌ Exception: " . $e->getMessage());
            return false;
        }
    }
}