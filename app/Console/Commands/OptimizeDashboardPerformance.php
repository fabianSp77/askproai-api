<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Customer;

class OptimizeDashboardPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:optimize {--analyze : Analyze query performance} {--cache : Warm up caches}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize dashboard performance through query analysis and cache warming';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('analyze')) {
            $this->analyzeQueryPerformance();
        }
        
        if ($this->option('cache')) {
            $this->warmUpCaches();
        }
        
        if (!$this->option('analyze') && !$this->option('cache')) {
            $this->info('Please specify --analyze or --cache option');
        }
    }
    
    /**
     * Analyze query performance for dashboard
     */
    private function analyzeQueryPerformance()
    {
        $this->info('Analyzing Dashboard Query Performance...');
        $this->newLine();
        
        // Enable query log
        DB::enableQueryLog();
        
        // Test appointment revenue query
        $this->info('Testing Appointment Revenue Query...');
        $start = microtime(true);
        
        $revenue = Appointment::query()
            ->where('company_id', 1)
            ->where('status', 'completed')
            ->whereBetween('starts_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->sum('services.price');
            
        $duration = (microtime(true) - $start) * 1000;
        $this->line("Revenue Query: {$duration}ms");
        
        // Test call conversion query
        $this->info('Testing Call Conversion Query...');
        $start = microtime(true);
        
        $totalCalls = Call::where('company_id', 1)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
            
        $successfulCalls = Call::where('company_id', 1)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->whereHas('appointment')
            ->count();
            
        $duration = (microtime(true) - $start) * 1000;
        $this->line("Conversion Query: {$duration}ms");
        
        // Test customer growth query
        $this->info('Testing Customer Growth Query...');
        $start = microtime(true);
        
        $newCustomers = Customer::where('company_id', 1)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
            
        $returningCustomers = Customer::where('company_id', 1)
            ->whereHas('appointments', function($q) {
                $q->where('status', 'completed')
                  ->whereBetween('starts_at', [now()->startOfMonth(), now()->endOfMonth()]);
            }, '>', 1)
            ->count();
            
        $duration = (microtime(true) - $start) * 1000;
        $this->line("Customer Query: {$duration}ms");
        
        // Show query log
        $queries = DB::getQueryLog();
        $this->newLine();
        $this->info('Query Analysis:');
        $this->table(
            ['Query', 'Bindings', 'Time (ms)'],
            collect($queries)->map(function($query) {
                return [
                    str_limit($query['query'], 60),
                    implode(', ', array_slice($query['bindings'], 0, 3)),
                    $query['time']
                ];
            })
        );
        
        // Check for missing indexes
        $this->newLine();
        $this->info('Checking for Missing Indexes...');
        $this->checkIndexes();
    }
    
    /**
     * Check for missing indexes
     */
    private function checkIndexes()
    {
        $missingIndexes = [];
        
        // Check appointments indexes
        $appointmentIndexes = DB::select("SHOW INDEX FROM appointments");
        $indexNames = collect($appointmentIndexes)->pluck('Key_name')->unique();
        
        if (!$indexNames->contains('idx_appointments_revenue_calc')) {
            $missingIndexes[] = 'appointments: idx_appointments_revenue_calc (company_id, status, starts_at, service_id)';
        }
        
        if (!$indexNames->contains('idx_appointments_conversion_track')) {
            $missingIndexes[] = 'appointments: idx_appointments_conversion_track (company_id, call_id, created_at)';
        }
        
        // Check calls indexes
        $callIndexes = DB::select("SHOW INDEX FROM calls");
        $indexNames = collect($callIndexes)->pluck('Key_name')->unique();
        
        if (!$indexNames->contains('idx_calls_company_date')) {
            $missingIndexes[] = 'calls: idx_calls_company_date (company_id, created_at)';
        }
        
        // Check customers indexes
        $customerIndexes = DB::select("SHOW INDEX FROM customers");
        $indexNames = collect($customerIndexes)->pluck('Key_name')->unique();
        
        if (!$indexNames->contains('idx_customers_company_created')) {
            $missingIndexes[] = 'customers: idx_customers_company_created (company_id, created_at)';
        }
        
        if (count($missingIndexes) > 0) {
            $this->warn('Missing Indexes Found:');
            foreach ($missingIndexes as $index) {
                $this->line("  - {$index}");
            }
            $this->newLine();
            $this->info('Run migration to create indexes: php artisan migrate');
        } else {
            $this->info('✓ All required indexes are present');
        }
    }
    
    /**
     * Warm up caches
     */
    private function warmUpCaches()
    {
        $this->info('Warming up Dashboard Caches...');
        
        // Get all companies
        $companies = DB::table('companies')->pluck('id');
        $periods = ['today', 'this_week', 'this_month'];
        
        $bar = $this->output->createProgressBar($companies->count() * count($periods));
        
        foreach ($companies as $companyId) {
            foreach ($periods as $period) {
                $filters = [
                    'company_id' => $companyId,
                    'period' => $period,
                ];
                
                // Cache appointment KPIs
                $cacheKey = 'appointment_kpis_' . md5(serialize($filters));
                Cache::remember($cacheKey, 300, function() use ($filters) {
                    return app(\App\Services\Dashboard\DashboardMetricsService::class)
                        ->getAppointmentKpis($filters);
                });
                
                // Cache call KPIs
                $cacheKey = 'call_kpis_' . md5(serialize($filters));
                Cache::remember($cacheKey, 300, function() use ($filters) {
                    return app(\App\Services\Dashboard\DashboardMetricsService::class)
                        ->getCallKpis($filters);
                });
                
                // Cache customer KPIs
                $cacheKey = 'customer_kpis_' . md5(serialize($filters));
                Cache::remember($cacheKey, 300, function() use ($filters) {
                    return app(\App\Services\Dashboard\DashboardMetricsService::class)
                        ->getCustomerKpis($filters);
                });
                
                $bar->advance();
            }
        }
        
        $bar->finish();
        $this->newLine(2);
        $this->info('✓ Cache warming completed');
        
        // Show cache statistics
        $this->newLine();
        $this->info('Cache Statistics:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Companies', $companies->count()],
                ['Periods', count($periods)],
                ['Total Cache Entries', $companies->count() * count($periods) * 3],
            ]
        );
    }
}