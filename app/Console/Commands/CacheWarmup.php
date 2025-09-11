<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\User;

class CacheWarmup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warmup 
                            {--tenant= : Specific tenant ID to warm}
                            {--force : Force refresh even if cache exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up application caches for optimal performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔥 Starting Cache Warmup Process...');
        $startTime = microtime(true);
        
        // Clear if force flag is set
        if ($this->option('force')) {
            $this->info('Force flag detected - clearing existing cache...');
            Cache::flush();
        }

        // Warm tenant caches
        $this->warmTenantCaches();
        
        // Warm service caches
        $this->warmServiceCaches();
        
        // Warm user permission caches
        $this->warmUserPermissionCaches();
        
        // Warm frequently accessed data
        $this->warmFrequentDataCaches();
        
        // Warm config and route caches
        $this->warmSystemCaches();
        
        $duration = round(microtime(true) - $startTime, 2);
        $this->info("✅ Cache warmup completed in {$duration} seconds");
        
        // Display cache statistics
        $this->displayCacheStats();
    }

    /**
     * Warm tenant-specific caches
     */
    private function warmTenantCaches()
    {
        $this->info('📦 Warming tenant caches...');
        
        $tenantId = $this->option('tenant');
        $tenants = $tenantId 
            ? Tenant::where('id', $tenantId)->get() 
            : Tenant::all();
        
        foreach ($tenants as $tenant) {
            // Cache tenant settings
            Cache::remember("tenant:{$tenant->id}:settings", 3600, function () use ($tenant) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'timezone' => $tenant->timezone ?? 'Europe/Berlin',
                    'language' => $tenant->language ?? 'de',
                    'calcom_team_slug' => $tenant->calcom_team_slug,
                    'settings' => $tenant->settings,
                ];
            });
            
            // Cache tenant stats
            Cache::remember("tenant:{$tenant->id}:stats", 600, function () use ($tenant) {
                return [
                    'total_customers' => Customer::where('tenant_id', $tenant->id)->count(),
                    'total_staff' => Staff::where('tenant_id', $tenant->id)->count(),
                    'total_services' => Service::where('tenant_id', $tenant->id)->count(),
                    'active_appointments' => DB::table('appointments')
                        ->where('tenant_id', $tenant->id)
                        ->where('status', 'scheduled')
                        ->count(),
                ];
            });
            
            $this->output->write('.');
        }
        
        $this->info(" Cached {$tenants->count()} tenants");
    }

    /**
     * Warm service-related caches
     */
    private function warmServiceCaches()
    {
        $this->info('🛠️ Warming service caches...');
        
        // Cache all active services grouped by tenant
        $tenants = Tenant::pluck('id');
        
        foreach ($tenants as $tenantId) {
            Cache::remember("services:tenant:{$tenantId}:active", 86400, function () use ($tenantId) {
                return Service::where('tenant_id', $tenantId)
                        ->orderBy('name')
                    ->get()
                    ->map(function ($service) {
                        return [
                            'id' => $service->id,
                            'name' => $service->name,
                            'duration_minutes' => $service->duration_minutes,
                            'price_cents' => $service->price_cents,
                            'description' => $service->description,
                        ];
                    });
            });
            
            $this->output->write('.');
        }
        
        $this->info(" Cached services for {$tenants->count()} tenants");
    }

    /**
     * Warm user permission caches
     */
    private function warmUserPermissionCaches()
    {
        $this->info('🔐 Warming permission caches...');
        
        // Cache permissions for active users
        $users = User::limit(100) // Limit to prevent memory issues
            ->get();
        
        foreach ($users as $user) {
            Cache::remember("user:{$user->id}:permissions", 600, function () use ($user) {
                return $user->getAllPermissions()->pluck('name')->toArray();
            });
            
            Cache::remember("user:{$user->id}:roles", 600, function () use ($user) {
                return $user->getRoleNames()->toArray();
            });
            
            $this->output->write('.');
        }
        
        $this->info(" Cached permissions for {$users->count()} users");
    }

    /**
     * Warm frequently accessed data
     */
    private function warmFrequentDataCaches()
    {
        $this->info('📊 Warming frequently accessed data...');
        
        // Cache dashboard stats
        Cache::remember('dashboard:global:stats', 300, function () {
            return [
                'total_calls_today' => DB::table('calls')
                    ->whereDate('created_at', today())
                    ->count(),
                'total_appointments_today' => DB::table('appointments')
                    ->whereDate('start_time', today())
                    ->count(),
                'active_customers' => Customer::count(),
                'active_staff' => Staff::count(),
            ];
        });
        
        // Cache recent activity
        Cache::remember('activity:recent', 300, function () {
            return DB::table('activity_log')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();
        });
        
        // Cache upcoming appointments for today
        Cache::remember('appointments:today', 600, function () {
            return DB::table('appointments')
                ->whereDate('start_time', today())
                ->where('status', 'scheduled')
                ->orderBy('start_time')
                ->get();
        });
        
        $this->info(' ✓ Cached dashboard and activity data');
    }

    /**
     * Warm system caches
     */
    private function warmSystemCaches()
    {
        $this->info('⚙️ Warming system caches...');
        
        // Cache application config
        $this->call('config:cache', [], $this->output);
        
        // Cache routes
        $this->call('route:cache', [], $this->output);
        
        // Cache views
        $this->call('view:cache', [], $this->output);
        
        // Cache events (if using)
        if (class_exists('App\Providers\EventServiceProvider')) {
            $this->call('event:cache', [], $this->output);
        }
        
        $this->info(' ✓ System caches warmed');
    }

    /**
     * Display cache statistics
     */
    private function displayCacheStats()
    {
        $this->info("\n📈 Cache Statistics:");
        
        // Get Redis stats if available
        try {
            $redis = Cache::getRedis();
            $info = $redis->info('stats');
            
            if (isset($info['keyspace_hits']) && isset($info['keyspace_misses'])) {
                $hits = $info['keyspace_hits'];
                $misses = $info['keyspace_misses'];
                $total = $hits + $misses;
                
                if ($total > 0) {
                    $hitRate = round(($hits / $total) * 100, 2);
                    
                    $this->table(
                        ['Metric', 'Value'],
                        [
                            ['Cache Hits', number_format($hits)],
                            ['Cache Misses', number_format($misses)],
                            ['Hit Rate', "{$hitRate}%"],
                            ['Status', $hitRate > 60 ? '✅ Excellent' : ($hitRate > 30 ? '⚠️ Good' : '❌ Needs Improvement')],
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            $this->warn('Could not retrieve Redis statistics');
        }
    }
}