<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CacheService;
use App\Models\Company;
use App\Models\Staff;

class CacheManageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:manage 
                            {action : Action to perform (clear, warm, status)}
                            {--type= : Cache type (company, staff, customer, appointments, event_types, all)}
                            {--id= : Specific ID for company/staff/customer}
                            {--date= : Specific date for appointments (Y-m-d format)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage application caches - clear, warm, or check status';

    protected CacheService $cacheService;

    /**
     * Create a new command instance.
     */
    public function __construct(CacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        
        switch ($action) {
            case 'clear':
                return $this->handleClear();
            case 'warm':
                return $this->handleWarm();
            case 'status':
                return $this->handleStatus();
            default:
                $this->error("Unknown action: {$action}. Use 'clear', 'warm', or 'status'.");
                return Command::FAILURE;
        }
    }

    /**
     * Handle cache clearing
     */
    protected function handleClear(): int
    {
        $type = $this->option('type') ?? 'all';
        $id = $this->option('id');
        $date = $this->option('date');

        $this->info("Clearing {$type} cache...");

        try {
            switch ($type) {
                case 'company':
                    if ($id) {
                        $this->cacheService->clearCompanyCache((int) $id);
                        $this->info("Cleared cache for company ID: {$id}");
                    } else {
                        $this->error("Company ID required for clearing company cache.");
                        return Command::FAILURE;
                    }
                    break;

                case 'staff':
                    if ($id) {
                        $this->cacheService->clearStaffCache((int) $id);
                        $this->info("Cleared cache for staff ID: {$id}");
                    } else {
                        $this->error("Staff ID required for clearing staff cache.");
                        return Command::FAILURE;
                    }
                    break;

                case 'customer':
                    if ($id) {
                        $this->cacheService->clearCustomerCache($id);
                        $this->info("Cleared cache for customer: {$id}");
                    } else {
                        $this->error("Customer ID/phone required for clearing customer cache.");
                        return Command::FAILURE;
                    }
                    break;

                case 'appointments':
                    if ($date) {
                        $this->cacheService->clearAppointmentsCache($date);
                        $this->info("Cleared appointments cache for date: {$date}");
                    } else {
                        $this->error("Date required for clearing appointments cache.");
                        return Command::FAILURE;
                    }
                    break;

                case 'event_types':
                    $this->cacheService->clearEventTypesCache();
                    $this->info("Cleared all event types cache");
                    break;

                case 'all':
                    if ($this->confirm('This will clear ALL caches. Are you sure?')) {
                        $this->call('cache:clear');
                        $this->info("All caches cleared successfully");
                    }
                    break;

                default:
                    $this->error("Unknown cache type: {$type}");
                    return Command::FAILURE;
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error clearing cache: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Handle cache warming
     */
    protected function handleWarm(): int
    {
        $type = $this->option('type') ?? 'all';
        $id = $this->option('id');

        $this->info("Warming {$type} cache...");

        if ($type === 'all' || $type === 'company') {
            if ($id) {
                $this->call('cache:warm', [
                    '--company' => $id,
                    '--async' => false
                ]);
            } else {
                $this->call('cache:warm', ['--async' => false]);
            }
        } else {
            $this->info("Use 'cache:warm' command for detailed warming options.");
        }

        return Command::SUCCESS;
    }

    /**
     * Handle cache status
     */
    protected function handleStatus(): int
    {
        $this->info("Cache Status Report");
        $this->info("==================");
        
        // Get cache configuration
        $this->table(
            ['Configuration', 'Value'],
            [
                ['Default Driver', config('cache.default')],
                ['API Cache Enabled', config('cache-strategy.api_response.enabled') ? 'Yes' : 'No'],
                ['Cache Warming Enabled', config('cache-strategy.warming.enabled') ? 'Yes' : 'No'],
            ]
        );

        $this->newLine();
        $this->info("Cache TTL Settings (seconds):");
        $this->table(
            ['Cache Type', 'TTL'],
            [
                ['Event Types', config('cache-strategy.ttl.event_types', 300)],
                ['Customer Lookup', config('cache-strategy.ttl.customer_lookup', 600)],
                ['Availability', config('cache-strategy.ttl.availability', 120)],
                ['Company Settings', config('cache-strategy.ttl.company_settings', 1800)],
                ['Staff Schedules', config('cache-strategy.ttl.staff_schedules', 300)],
                ['Service Lists', config('cache-strategy.ttl.service_lists', 3600)],
            ]
        );

        // Show cache statistics if available
        if (config('cache.default') === 'redis') {
            $this->newLine();
            $this->info("Redis Cache Statistics:");
            try {
                $redis = app('redis')->connection('cache');
                $info = $redis->info();
                
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Used Memory', $info['used_memory_human'] ?? 'N/A'],
                        ['Connected Clients', $info['connected_clients'] ?? 'N/A'],
                        ['Total Commands Processed', $info['total_commands_processed'] ?? 'N/A'],
                        ['Instantaneous Ops/Sec', $info['instantaneous_ops_per_sec'] ?? 'N/A'],
                    ]
                );
            } catch (\Exception $e) {
                $this->warn("Could not retrieve Redis statistics: " . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}