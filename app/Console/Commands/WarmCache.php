<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\Service;
use App\Models\Staff;

class WarmCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up application cache with frequently accessed data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cache warming...');

        $startTime = microtime(true);
        $cached = 0;

        // 1. Cache company data
        try {
            $companies = Company::with(['branches', 'staff'])->get();
            foreach ($companies as $company) {
                Cache::put("company.{$company->id}", $company, 3600);
                $cached++;
            }
            $this->info("✓ Cached {$companies->count()} companies");
        } catch (\Exception $e) {
            $this->error('Failed to cache companies: ' . $e->getMessage());
        }

        // 2. Cache services
        try {
            $services = Service::all();
            Cache::put('services.all', $services, 3600);
            foreach ($services as $service) {
                Cache::put("service.{$service->id}", $service, 3600);
                $cached++;
            }
            $this->info("✓ Cached {$services->count()} services");
        } catch (\Exception $e) {
            $this->error('Failed to cache services: ' . $e->getMessage());
        }

        // 3. Cache staff availability
        try {
            $staff = Staff::with('company')->get();
            foreach ($staff as $member) {
                Cache::put("staff.{$member->id}", $member, 3600);
                $cached++;
            }
            $this->info("✓ Cached {$staff->count()} staff members");
        } catch (\Exception $e) {
            $this->error('Failed to cache staff: ' . $e->getMessage());
        }

        // 4. Cache configuration
        Cache::put('app.config', [
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'currency' => 'EUR',
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i'
        ], 86400);
        $cached++;

        // 5. Cache statistics
        try {
            $stats = [
                'total_companies' => Company::count(),
                'total_branches' => DB::table('branches')->count(),
                'total_staff' => Staff::count(),
                'total_services' => Service::count(),
                'total_appointments' => DB::table('appointments')->count(),
                'cached_at' => now()->toIso8601String()
            ];
            Cache::put('app.stats', $stats, 300);
            $cached++;
            $this->info('✓ Cached application statistics');
        } catch (\Exception $e) {
            $this->error('Failed to cache statistics: ' . $e->getMessage());
        }

        $executionTime = round(microtime(true) - $startTime, 3);

        $this->info("\n✅ Cache warming completed!");
        $this->info("   Cached {$cached} items in {$executionTime}s");

        return Command::SUCCESS;
    }
}
