<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Policies\PolicyConfigurationService;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Company;

/**
 * Warm Performance Caches Command
 *
 * Pre-loads frequently accessed data into cache to ensure optimal performance
 * Should be run on application deployment and periodically
 */
class WarmPerformanceCaches extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:warm-performance
                          {--type=all : Cache type to warm (all|policies|notifications)}
                          {--clear : Clear caches before warming}';

    /**
     * The console command description.
     */
    protected $description = 'Warm performance-critical caches (policies, notifications, configs)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');
        $shouldClear = $this->option('clear');

        $this->info('🔥 Warming Performance Caches');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if ($shouldClear) {
            $this->warn('🧹 Clearing existing caches...');
            \Cache::flush();
            $this->info('✅ Caches cleared');
        }

        $startTime = microtime(true);
        $totalWarmed = 0;

        // Warm policy caches
        if ($type === 'all' || $type === 'policies') {
            $totalWarmed += $this->warmPolicyCaches();
        }

        // Warm notification configs (if implemented)
        if ($type === 'all' || $type === 'notifications') {
            $totalWarmed += $this->warmNotificationCaches();
        }

        $duration = (microtime(true) - $startTime) * 1000;

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("✅ Cache warming complete!");
        $this->line("   Warmed: {$totalWarmed} cache entries");
        $this->line("   Duration: " . number_format($duration, 2) . "ms");

        return Command::SUCCESS;
    }

    /**
     * Warm policy configuration caches
     */
    protected function warmPolicyCaches(): int
    {
        $this->line('');
        $this->info('📋 Warming Policy Caches...');

        $service = app(PolicyConfigurationService::class);
        $policyTypes = ['cancellation', 'reschedule', 'recurring'];
        $warmed = 0;

        // Warm Company policies
        $companies = Company::all();
        $this->line("   Companies: {$companies->count()}");
        foreach ($companies as $company) {
            $warmed += $service->warmCache($company, $policyTypes);
        }

        // Warm Branch policies
        $branches = Branch::all();
        $this->line("   Branches: {$branches->count()}");
        foreach ($branches as $branch) {
            $warmed += $service->warmCache($branch, $policyTypes);
        }

        // Warm Service policies
        $services = Service::all();
        $this->line("   Services: {$services->count()}");
        foreach ($services as $service_entity) {
            $warmed += $service->warmCache($service_entity, $policyTypes);
        }

        // Warm Staff policies (optional - can be many)
        $staff = Staff::where('is_active', true)->get();
        $this->line("   Staff (active): {$staff->count()}");
        foreach ($staff as $staffMember) {
            $warmed += $service->warmCache($staffMember, $policyTypes);
        }

        $this->info("   ✅ Warmed {$warmed} policy cache entries");

        return $warmed;
    }

    /**
     * Warm notification configuration caches
     */
    protected function warmNotificationCaches(): int
    {
        $this->line('');
        $this->info('📧 Warming Notification Config Caches...');

        // Notification configs are cached on-demand during resolution
        // Could implement pre-warming here if needed

        $warmed = 0;

        $this->line("   ℹ️  Notification configs cached on-demand");

        return $warmed;
    }
}
