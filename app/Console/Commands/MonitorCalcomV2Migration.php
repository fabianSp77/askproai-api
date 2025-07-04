<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Calcom\CalcomV2Client;
use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MonitorCalcomV2Migration extends Command
{
    protected $signature = 'calcom:v2-status 
                            {--test : Test V2 endpoints}
                            {--compare : Compare V1 and V2 results}';
    
    protected $description = 'Monitor Cal.com V2 API migration status';

    public function handle()
    {
        $this->info('📅 Cal.com V2 Migration Status');
        $this->info('===============================');
        
        $this->checkConfiguration();
        $this->checkApiUsage();
        $this->checkDeprecatedServices();
        
        if ($this->option('test')) {
            $this->testV2Endpoints();
        }
        
        if ($this->option('compare')) {
            $this->compareV1V2Results();
        }
        
        return Command::SUCCESS;
    }
    
    private function checkConfiguration()
    {
        $this->info("\n📋 Configuration:");
        
        $configs = [
            'V2 Enabled' => config('calcom-v2.enabled') ? '✅ Yes' : '❌ No',
            'API Version' => config('calcom-v2.api_version', '2024-08-13'),
            'Base URL' => config('services.calcom.base_url_v2', 'https://api.cal.com/v2'),
            'Force V2' => config('services.calcom.force_v2') ? '✅ Yes' : '❌ No',
        ];
        
        foreach ($configs as $key => $value) {
            $this->info("   {$key}: {$value}");
        }
        
        // Check enabled methods
        $this->info("\n   V2 Enabled Methods:");
        $methods = config('calcom-v2.enabled_methods', []);
        foreach ($methods as $method => $enabled) {
            $status = $enabled ? '✅' : '❌';
            $this->info("     {$status} {$method}");
        }
    }
    
    private function checkApiUsage()
    {
        $this->info("\n📊 API Usage (Last 24h):");
        
        try {
            // Count V1 API calls from logs
            $v1Calls = DB::table('api_call_logs')
                ->where('service', 'like', '%calcom%')
                ->where('endpoint', 'like', '%/v1/%')
                ->where('created_at', '>=', Carbon::now()->subDay())
                ->count();
            
            // Count V2 API calls
            $v2Calls = DB::table('api_call_logs')
                ->where('service', 'like', '%calcom%')
                ->where('endpoint', 'like', '%/v2/%')
                ->where('created_at', '>=', Carbon::now()->subDay())
                ->count();
            
            $total = $v1Calls + $v2Calls;
            $v2Percentage = $total > 0 ? round(($v2Calls / $total) * 100, 1) : 0;
            
            $this->info("   V1 API Calls: {$v1Calls}");
            $this->info("   V2 API Calls: {$v2Calls}");
            $this->info("   V2 Adoption: {$v2Percentage}%");
            
            // Show breakdown by endpoint
            $this->info("\n   Top V1 Endpoints Still in Use:");
            $v1Endpoints = DB::table('api_call_logs')
                ->select('endpoint', DB::raw('COUNT(*) as count'))
                ->where('service', 'like', '%calcom%')
                ->where('endpoint', 'like', '%/v1/%')
                ->where('created_at', '>=', Carbon::now()->subDay())
                ->groupBy('endpoint')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get();
            
            foreach ($v1Endpoints as $endpoint) {
                $this->warn("     {$endpoint->endpoint}: {$endpoint->count} calls");
            }
            
        } catch (\Exception $e) {
            $this->warn("   Unable to fetch API usage stats: " . $e->getMessage());
        }
    }
    
    private function checkDeprecatedServices()
    {
        $this->info("\n🗂️ Service Usage:");
        
        $services = [
            'CalcomService' => 'app/Services/CalcomService.php',
            'CalcomServiceV1Legacy' => 'app/Services/CalcomServiceV1Legacy.php',
            'CalcomV2Service' => 'app/Services/CalcomV2Service.php',
            'CalcomV2Client' => 'app/Services/Calcom/CalcomV2Client.php',
            'CalcomBackwardsCompatibility' => 'app/Services/Calcom/CalcomBackwardsCompatibility.php',
        ];
        
        foreach ($services as $service => $path) {
            if (file_exists(base_path($path))) {
                // Count usages
                $usages = shell_exec("grep -r '{$service}' " . base_path('app') . " --include='*.php' | wc -l");
                $usages = trim($usages);
                
                $status = $service === 'CalcomV2Client' ? '✅' : '⚠️';
                $this->info("   {$status} {$service}: {$usages} usages");
            }
        }
    }
    
    private function testV2Endpoints()
    {
        $this->info("\n🧪 Testing V2 Endpoints:");
        
        try {
            $client = new CalcomV2Client();
            
            // Test 1: Get Event Types
            try {
                $start = microtime(true);
                $eventTypes = $client->getEventTypes();
                $duration = round((microtime(true) - $start) * 1000);
                
                $count = count($eventTypes);
                $this->info("   ✅ Get Event Types: Success ({$count} types, {$duration}ms)");
            } catch (\Exception $e) {
                $this->error("   ❌ Get Event Types: Failed - " . $e->getMessage());
            }
            
            // Test 2: Get Current User (Me endpoint)
            try {
                $start = microtime(true);
                $response = $client->request('GET', '/me');
                $duration = round((microtime(true) - $start) * 1000);
                
                if (isset($response['profile'])) {
                    $this->info("   ✅ Get Current User: Success (User: " . ($response['profile']['username'] ?? 'N/A') . ", {$duration}ms)");
                } else {
                    $this->warn("   ⚠️ Get Current User: Success but unexpected response format ({$duration}ms)");
                }
            } catch (\Exception $e) {
                $this->error("   ❌ Get Current User: Failed - " . $e->getMessage());
            }
            
            // Test 3: Check Slots (if we have an event type)
            if (!empty($eventTypes) && isset($eventTypes[0]['id'])) {
                try {
                    $eventTypeId = $eventTypes[0]['id'];
                    $start = microtime(true);
                    
                    $slots = $client->getAvailableSlots([
                        'eventTypeId' => $eventTypeId,
                        'startTime' => Carbon::now()->toIso8601String(),
                        'endTime' => Carbon::now()->addDays(7)->toIso8601String(),
                        'timeZone' => 'Europe/Berlin'
                    ]);
                    
                    $duration = round((microtime(true) - $start) * 1000);
                    $count = count($slots);
                    
                    $this->info("   ✅ Get Available Slots: Success ({$count} slots, {$duration}ms)");
                } catch (\Exception $e) {
                    $this->error("   ❌ Get Available Slots: Failed - " . $e->getMessage());
                }
            }
            
        } catch (\Exception $e) {
            $this->error("   ❌ V2 Client initialization failed: " . $e->getMessage());
        }
    }
    
    private function compareV1V2Results()
    {
        $this->info("\n🔄 Comparing V1 vs V2 Results:");
        $this->warn("   This feature is not yet implemented");
        
        // TODO: Implement comparison logic
        // 1. Call same endpoint with V1 and V2
        // 2. Compare response structure
        // 3. Compare response data
        // 4. Log differences
    }
}