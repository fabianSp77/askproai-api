<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CalcomMigrationService;
use App\Services\CalcomService;
use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CalcomMigrateToV2 extends Command
{
    protected $signature = 'calcom:migrate-to-v2 
                            {--method= : Specific method to migrate}
                            {--test : Run in test mode}
                            {--rollback : Rollback to V1}
                            {--status : Show migration status}
                            {--all : Migrate all methods}';
    
    protected $description = 'Manage Cal.com V1 to V2 API migration';
    
    protected CalcomMigrationService $migrationService;
    
    protected array $methods = [
        'getEventTypes' => 'Get Event Types',
        'getAvailableSlots' => 'Get Available Slots',
        'bookAppointment' => 'Create Booking',
        'cancelBooking' => 'Cancel Booking',
        'getBooking' => 'Get Booking Details'
    ];
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function handle()
    {
        $this->migrationService = app(CalcomMigrationService::class);
        
        if ($this->option('status')) {
            $this->showStatus();
            return 0;
        }
        
        if ($this->option('rollback')) {
            $this->rollback();
            return 0;
        }
        
        if ($this->option('test')) {
            $this->runTests();
            return 0;
        }
        
        if ($this->option('all')) {
            $this->migrateAll();
            return 0;
        }
        
        if ($method = $this->option('method')) {
            $this->migrateMethod($method);
            return 0;
        }
        
        // Interactive mode
        $this->interactive();
        return 0;
    }
    
    protected function showStatus(): void
    {
        $this->info('Cal.com V1 to V2 Migration Status');
        $this->line('================================');
        
        $status = $this->migrationService->getMigrationStatus();
        
        $this->table(
            ['Setting', 'Value'],
            [
                ['Global V2 Enabled', $status['global_v2_enabled'] ? '✅ Yes' : '❌ No'],
                ['V2 API Base URL', config('services.calcom.v2_base_url', 'Not configured')],
                ['Circuit Breaker', config('services.calcom.circuit_breaker_enabled') ? '✅ Enabled' : '❌ Disabled'],
            ]
        );
        
        $this->line("\nMethod Status:");
        $this->table(
            ['Method', 'V2 Enabled', 'Mandatory', 'Cache Override'],
            collect($status['methods'])->map(function ($info, $method) {
                return [
                    $this->methods[$method] ?? $method,
                    $info['v2_enabled'] ? '✅ Yes' : '❌ No',
                    $info['v2_mandatory'] ? '🔒 Yes' : '🔓 No',
                    $info['cache_override'] ? '⚡ Active' : '- None',
                ];
            })->toArray()
        );
    }
    
    protected function runTests(): void
    {
        $this->info('Running Cal.com V1 vs V2 comparison tests...');
        
        $v1Service = app(CalcomService::class);
        $v2Service = app(CalcomV2Service::class);
        
        // Test 1: Get Event Types
        $this->line("\n1. Testing getEventTypes...");
        try {
            $v1Start = microtime(true);
            $v1EventTypes = $v1Service->getEventTypes();
            $v1Time = round((microtime(true) - $v1Start) * 1000, 2);
            
            $v2Start = microtime(true);
            $v2EventTypes = $v2Service->getEventTypes(config('services.calcom.team_slug'));
            $v2Time = round((microtime(true) - $v2Start) * 1000, 2);
            
            $this->info("✅ Event Types Test Passed");
            $this->table(
                ['API', 'Count', 'Response Time'],
                [
                    ['V1', count($v1EventTypes), "{$v1Time}ms"],
                    ['V2', count($v2EventTypes['data'] ?? []), "{$v2Time}ms"],
                ]
            );
            
            if ($v2Time < $v1Time) {
                $improvement = round((($v1Time - $v2Time) / $v1Time) * 100, 1);
                $this->info("🚀 V2 is {$improvement}% faster!");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Event Types Test Failed: " . $e->getMessage());
        }
        
        // Test 2: Get Available Slots
        $this->line("\n2. Testing getAvailableSlots...");
        try {
            $eventTypeId = $this->ask('Enter Event Type ID to test slots', config('services.calcom.default_event_type_id'));
            $startDate = now()->format('Y-m-d');
            $endDate = now()->addDays(7)->format('Y-m-d');
            
            $v1Start = microtime(true);
            $v1Slots = $v1Service->getAvailableSlots($eventTypeId, $startDate, $endDate);
            $v1Time = round((microtime(true) - $v1Start) * 1000, 2);
            
            $v2Start = microtime(true);
            $v2Slots = $v2Service->getSlots($eventTypeId, $startDate, $endDate);
            $v2Time = round((microtime(true) - $v2Start) * 1000, 2);
            
            $this->info("✅ Available Slots Test Passed");
            $this->table(
                ['API', 'Slots Found', 'Response Time'],
                [
                    ['V1', count($v1Slots['slots'] ?? []), "{$v1Time}ms"],
                    ['V2', $this->countV2Slots($v2Slots), "{$v2Time}ms"],
                ]
            );
            
        } catch (\Exception $e) {
            $this->error("❌ Available Slots Test Failed: " . $e->getMessage());
        }
    }
    
    protected function migrateMethod(string $method): void
    {
        if (!array_key_exists($method, $this->methods)) {
            $this->error("Invalid method: {$method}");
            $this->line("Available methods: " . implode(', ', array_keys($this->methods)));
            return;
        }
        
        $this->info("Enabling V2 for: {$this->methods[$method]}");
        
        // Enable for 1 hour initially
        $this->migrationService->enableV2ForMethod($method, 3600);
        
        $this->info("✅ V2 enabled for {$method} (1 hour trial)");
        $this->line("Monitor logs for any issues. To make permanent, update config/services.php");
    }
    
    protected function migrateAll(): void
    {
        if (!$this->confirm('This will enable V2 for ALL methods. Continue?')) {
            return;
        }
        
        foreach (array_keys($this->methods) as $method) {
            $this->migrationService->enableV2ForMethod($method, 86400); // 24 hours
            $this->info("✅ Enabled V2 for {$method}");
        }
        
        $this->info("\n🎉 All methods migrated to V2 (24 hour trial)");
        $this->line("Monitor performance and errors. Update config to make permanent.");
    }
    
    protected function rollback(): void
    {
        $method = $this->option('method');
        
        if ($method) {
            $this->migrationService->disableV2ForMethod($method);
            $this->info("✅ Rolled back {$method} to V1");
        } else {
            if (!$this->confirm('Rollback ALL methods to V1?')) {
                return;
            }
            
            foreach (array_keys($this->methods) as $method) {
                $this->migrationService->disableV2ForMethod($method);
            }
            
            $this->info("✅ All methods rolled back to V1");
        }
    }
    
    protected function interactive(): void
    {
        $this->info('Cal.com V1 to V2 Migration Tool');
        $this->line('==============================');
        
        $choice = $this->choice(
            'What would you like to do?',
            [
                'status' => 'Show migration status',
                'test' => 'Run comparison tests',
                'migrate-single' => 'Migrate single method',
                'migrate-all' => 'Migrate all methods',
                'rollback' => 'Rollback to V1',
                'exit' => 'Exit'
            ],
            'status'
        );
        
        switch ($choice) {
            case 'status':
                $this->showStatus();
                break;
                
            case 'test':
                $this->runTests();
                break;
                
            case 'migrate-single':
                $method = $this->choice('Select method to migrate', array_keys($this->methods));
                $this->migrateMethod($method);
                break;
                
            case 'migrate-all':
                $this->migrateAll();
                break;
                
            case 'rollback':
                $this->rollback();
                break;
        }
    }
    
    protected function countV2Slots(array $v2Response): int
    {
        if (!isset($v2Response['data'])) {
            return 0;
        }
        
        $count = 0;
        foreach ($v2Response['data'] as $date => $slots) {
            $count += count($slots);
        }
        
        return $count;
    }
}