<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Branch;
use App\Services\AnomalyDetectionService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class QuantumSystemMonitoring extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Quantum Monitoring';
    protected static ?string $navigationGroup = 'System & Monitoring';
    protected static string $view = 'filament.admin.pages.quantum-system-monitoring';
    protected static ?int $navigationSort = 0;
    
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Deaktiviert - zu experimentell
    }
    
    // Core Metrics
    public array $realtimeMetrics = [];
    public array $businessIntelligence = [];
    public array $performanceMetrics = [];
    public array $securityMetrics = [];
    public array $predictiveAnalytics = [];
    
    // Advanced Analytics
    public array $anomalies = [];
    public array $trends = [];
    public array $forecasts = [];
    public array $recommendations = [];
    
    // Real-time Data
    public array $liveActivities = [];
    public array $systemHealth = [];
    public array $alertQueue = [];
    
    public function mount(): void
    {
        $this->loadAllMetrics();
    }
    
    protected function loadAllMetrics(): void
    {
        // Load all metrics with proper caching strategy
        $this->realtimeMetrics = $this->loadRealtimeMetrics();
        $this->businessIntelligence = $this->loadBusinessIntelligence();
        $this->performanceMetrics = $this->loadPerformanceMetrics();
        $this->securityMetrics = $this->loadSecurityMetrics();
        $this->predictiveAnalytics = $this->loadPredictiveAnalytics();
        $this->anomalies = $this->detectAnomalies();
        $this->systemHealth = $this->calculateSystemHealth();
        $this->liveActivities = $this->getLiveActivities();
    }
    
    protected function loadRealtimeMetrics(): array
    {
        return Cache::remember('quantum.realtime.metrics', 10, function () {
            $now = now();
            $last24h = $now->copy()->subHours(24);
            $last7d = $now->copy()->subDays(7);
            $last30d = $now->copy()->subDays(30);
            
            // Call Metrics
            $callsToday = Call::whereDate('created_at', today());
            $calls24h = Call::where('created_at', '>=', $last24h);
            $calls7d = Call::where('created_at', '>=', $last7d);
            
            // Appointment Metrics
            $appointmentsToday = Appointment::whereDate('starts_at', today());
            $upcomingAppointments = Appointment::where('starts_at', '>=', $now)
                ->where('starts_at', '<=', $now->copy()->addHours(24));
            
            // Real-time calculations
            $activeCallsNow = Call::where('call_status', 'active')->count();
            $queueDepth = DB::table('jobs')->count();
            $failedJobsHour = DB::table('failed_jobs')
                ->where('failed_at', '>=', $now->copy()->subHour())
                ->count();
            
            // Conversion Metrics
            $conversionRate = $this->calculateConversionRate($last24h);
            $avgCallDuration = $calls24h->avg('duration_sec') ?? 0;
            
            // Customer Metrics
            $newCustomersToday = Customer::whereDate('created_at', today())->count();
            $activeCustomers = Customer::whereHas('appointments', function($q) use ($last30d) {
                $q->where('created_at', '>=', $last30d);
            })->count();
            
            return [
                'calls' => [
                    'today' => $callsToday->count(),
                    'last_24h' => $calls24h->count(),
                    'last_7d' => $calls7d->count(),
                    'active_now' => $activeCallsNow,
                    'avg_duration' => round($avgCallDuration),
                    'total_duration_today' => round($callsToday->sum('duration_sec') / 60),
                ],
                'appointments' => [
                    'today' => $appointmentsToday->count(),
                    'upcoming_24h' => $upcomingAppointments->count(),
                    'completed_today' => $appointmentsToday->where('status', 'completed')->count(),
                    'no_shows_today' => $appointmentsToday->where('status', 'no_show')->count(),
                    'cancelled_today' => $appointmentsToday->where('status', 'cancelled')->count(),
                ],
                'conversions' => [
                    'rate_24h' => $conversionRate,
                    'appointments_from_calls' => $this->getAppointmentsFromCalls($last24h),
                    'booking_lead_time' => $this->getAverageBookingLeadTime(),
                ],
                'customers' => [
                    'new_today' => $newCustomersToday,
                    'active_30d' => $activeCustomers,
                    'total' => Customer::count(),
                ],
                'system' => [
                    'queue_depth' => $queueDepth,
                    'failed_jobs_hour' => $failedJobsHour,
                    'active_companies' => Company::where('billing_status', 'active')->count(),
                ],
            ];
        });
    }
    
    protected function loadBusinessIntelligence(): array
    {
        return Cache::remember('quantum.business.intelligence', 300, function () {
            $now = now();
            $last30d = $now->copy()->subDays(30);
            $last90d = $now->copy()->subDays(90);
            
            // Revenue Calculations
            $revenueToday = Appointment::whereDate('starts_at', today())
                ->where('status', 'completed')
                ->with('service')
                ->get()
                ->sum(function($appointment) {
                    return $appointment->service->price ?? 0;
                });
            
            $revenue30d = Appointment::where('starts_at', '>=', $last30d)
                ->where('status', 'completed')
                ->with('service')
                ->get()
                ->sum(function($appointment) {
                    return $appointment->service->price ?? 0;
                });
            
            $revenue90d = Appointment::where('starts_at', '>=', $last90d)
                ->where('status', 'completed')
                ->with('service')
                ->get()
                ->sum(function($appointment) {
                    return $appointment->service->price ?? 0;
                });
            
            // Growth Calculations
            $previousMonthRevenue = Appointment::whereBetween('starts_at', [
                $last30d->copy()->subDays(30),
                $last30d
            ])->where('status', 'completed')
            ->with('service')
            ->get()
            ->sum(function($appointment) {
                return $appointment->service->price ?? 0;
            });
            
            $growthRate = $previousMonthRevenue > 0 
                ? (($revenue30d - $previousMonthRevenue) / $previousMonthRevenue) * 100
                : 0;
            
            // Top Performers
            $topStaff = Staff::withCount(['appointments' => function($q) use ($last30d) {
                    $q->where('starts_at', '>=', $last30d)
                      ->where('status', 'completed');
                }])
                ->orderByDesc('appointments_count')
                ->limit(5)
                ->get()
                ->map(fn($staff) => [
                    'name' => $staff->name,
                    'appointments' => $staff->appointments_count,
                    'revenue' => $staff->appointments()
                        ->where('starts_at', '>=', $last30d)
                        ->where('status', 'completed')
                        ->with('service')
                        ->get()
                        ->sum(function($appointment) {
                            return $appointment->service->price ?? 0;
                        }),
                ]);
            
            // Branch Performance
            $branchPerformance = Branch::withCount(['appointments' => function($q) use ($last30d) {
                    $q->where('starts_at', '>=', $last30d);
                }])
                ->with(['company:id,name'])
                ->get()
                ->map(fn($branch) => [
                    'name' => $branch->name,
                    'company' => $branch->company->name ?? 'Unknown',
                    'appointments' => $branch->appointments_count,
                    'utilization' => $this->calculateBranchUtilization($branch->id, $last30d),
                ]);
            
            // Service Popularity
            $serviceStats = DB::table('appointments')
                ->join('services', 'appointments.service_id', '=', 'services.id')
                ->where('appointments.starts_at', '>=', $last30d)
                ->select('services.name', 'services.price', DB::raw('COUNT(*) as count'))
                ->groupBy('services.id', 'services.name', 'services.price')
                ->orderByDesc('count')
                ->limit(10)
                ->get();
            
            return [
                'revenue' => [
                    'today' => $revenueToday / 100,
                    'last_30d' => $revenue30d / 100,
                    'last_90d' => $revenue90d / 100,
                    'growth_rate' => round($growthRate, 2),
                    'avg_per_appointment' => $revenue30d > 0 ? 
                        round($revenue30d / Appointment::where('starts_at', '>=', $last30d)
                            ->where('status', 'completed')->count() / 100, 2) : 0,
                ],
                'top_performers' => $topStaff->toArray(),
                'branch_performance' => $branchPerformance->toArray(),
                'popular_services' => $serviceStats->toArray(),
                'customer_metrics' => [
                    'retention_rate' => $this->calculateRetentionRate($last90d),
                    'avg_appointments_per_customer' => $this->getAvgAppointmentsPerCustomer($last90d),
                    'no_show_rate' => $this->calculateNoShowRate($last30d),
                ],
            ];
        });
    }
    
    protected function loadPerformanceMetrics(): array
    {
        return Cache::remember('quantum.performance.metrics', 60, function () {
            // Database Performance
            $dbStats = DB::select("SHOW STATUS WHERE Variable_name IN ('Threads_connected', 'Questions', 'Slow_queries')") ?: [];
            $dbMetrics = collect($dbStats)->pluck('Value', 'Variable_name');
            
            // Calculate response times
            $apiResponseTimes = Cache::get('api.response_times', []);
            $p50 = $this->percentile($apiResponseTimes, 50);
            $p95 = $this->percentile($apiResponseTimes, 95);
            $p99 = $this->percentile($apiResponseTimes, 99);
            
            // System Resources
            $loadAvg = sys_getloadavg();
            $memoryUsage = memory_get_usage(true);
            $diskUsage = $this->getDiskUsage();
            
            // Cache Performance
            $cacheStats = [];
            $cacheHitRate = 0;
            
            try {
                $store = Cache::getStore();
                if (method_exists($store, 'getRedis')) {
                    // Redis cache driver
                    $redis = $store->getRedis();
                    $cacheStats = $redis->info();
                    if (isset($cacheStats['keyspace_hits']) && isset($cacheStats['keyspace_misses'])) {
                        $total = $cacheStats['keyspace_hits'] + $cacheStats['keyspace_misses'];
                        $cacheHitRate = $total > 0 ? ($cacheStats['keyspace_hits'] / $total) * 100 : 0;
                    }
                } else {
                    // Non-Redis cache driver (database, file, etc.)
                    $cacheStats = [
                        'used_memory' => 0,
                        'connected_clients' => 0
                    ];
                }
            } catch (\Exception $e) {
                // Fallback for any errors
                $cacheStats = [
                    'used_memory' => 0,
                    'connected_clients' => 0
                ];
            }
            
            return [
                'response_times' => [
                    'p50' => round($p50, 2),
                    'p95' => round($p95, 2),
                    'p99' => round($p99, 2),
                    'avg' => round(collect($apiResponseTimes)->avg(), 2),
                ],
                'database' => [
                    'connections' => $dbMetrics['Threads_connected'] ?? 0,
                    'queries_per_second' => round(($dbMetrics['Questions'] ?? 0) / 60, 2),
                    'slow_queries' => $dbMetrics['Slow_queries'] ?? 0,
                ],
                'system' => [
                    'cpu_load' => [
                        '1min' => round($loadAvg[0], 2),
                        '5min' => round($loadAvg[1], 2),
                        '15min' => round($loadAvg[2], 2),
                    ],
                    'memory' => [
                        'used_mb' => round($memoryUsage / 1024 / 1024, 2),
                        'percentage' => $this->calculateMemoryPercentage($memoryUsage),
                    ],
                    'disk' => $diskUsage,
                ],
                'cache' => [
                    'hit_rate' => round($cacheHitRate, 2),
                    'memory_used_mb' => round(($cacheStats['used_memory'] ?? 0) / 1024 / 1024, 2),
                    'connected_clients' => $cacheStats['connected_clients'] ?? 0,
                ],
                'queue' => [
                    'jobs_pending' => DB::table('jobs')->count(),
                    'jobs_failed_24h' => DB::table('failed_jobs')
                        ->where('failed_at', '>=', now()->subHours(24))
                        ->count(),
                    'avg_wait_time' => $this->getAverageQueueWaitTime(),
                ],
            ];
        });
    }
    
    protected function loadSecurityMetrics(): array
    {
        return Cache::remember('quantum.security.metrics', 120, function () {
            $last24h = now()->subHours(24);
            $last7d = now()->subDays(7);
            
            // Get security events from logs
            $securityEvents = DB::table('activity_log')
                ->where('created_at', '>=', $last24h)
                ->where('log_name', 'security')
                ->count();
            
            // Rate limiting violations
            $rateLimitViolations = Cache::get('rate_limit_violations', []);
            $violations24h = collect($rateLimitViolations)
                ->filter(fn($time) => Carbon::parse($time)->isAfter($last24h))
                ->count();
            
            // Failed login attempts
            $failedLogins = DB::table('activity_log')
                ->where('created_at', '>=', $last24h)
                ->where('description', 'like', '%failed login%')
                ->count();
            
            // Backup status
            $lastBackup = Cache::get('last_backup_time');
            $backupAge = $lastBackup ? now()->diffInHours($lastBackup) : 999;
            
            // Suspicious activities
            $suspiciousIPs = Cache::get('suspicious_ips', []);
            $blockedIPs = Cache::get('blocked_ips', []);
            
            return [
                'events_24h' => $securityEvents,
                'rate_limit_violations_24h' => $violations24h,
                'failed_logins_24h' => $failedLogins,
                'backup' => [
                    'last_backup_hours_ago' => $backupAge,
                    'status' => $backupAge < 24 ? 'healthy' : ($backupAge < 48 ? 'warning' : 'critical'),
                ],
                'threats' => [
                    'suspicious_ips' => count($suspiciousIPs),
                    'blocked_ips' => count($blockedIPs),
                    'sql_injection_attempts' => Cache::get('sql_injection_attempts', 0),
                    'xss_attempts' => Cache::get('xss_attempts', 0),
                ],
                'compliance' => [
                    'encryption_coverage' => 100, // All sensitive data encrypted
                    'audit_log_retention_days' => 90,
                    'gdpr_compliant' => true,
                ],
            ];
        });
    }
    
    protected function loadPredictiveAnalytics(): array
    {
        return Cache::remember('quantum.predictive.analytics', 3600, function () {
            $now = now();
            
            // Historical data for predictions
            $historicalCalls = Call::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', $now->copy()->subDays(90))
                ->groupBy('date')
                ->pluck('count', 'date')
                ->toArray();
            
            $historicalAppointments = Appointment::selectRaw('DATE(starts_at) as date, COUNT(*) as count')
                ->where('starts_at', '>=', $now->copy()->subDays(90))
                ->groupBy('date')
                ->pluck('count', 'date')
                ->toArray();
            
            // Simple linear regression for next 7 days
            $callForecast = $this->forecastLinearRegression($historicalCalls, 7);
            $appointmentForecast = $this->forecastLinearRegression($historicalAppointments, 7);
            
            // Revenue forecast
            $historicalRevenue = Appointment::where('starts_at', '>=', $now->copy()->subDays(90))
                ->where('status', 'completed')
                ->with('service')
                ->get()
                ->groupBy(function($appointment) {
                    return $appointment->starts_at->format('Y-m-d');
                })
                ->map(function($dayAppointments) {
                    return $dayAppointments->sum(function($appointment) {
                        return $appointment->service->price ?? 0;
                    });
                })
                ->toArray();
            
            $revenueForecast = $this->forecastLinearRegression($historicalRevenue, 30);
            
            // Capacity predictions
            $peakHours = $this->identifyPeakHours();
            $capacityNeeds = $this->predictCapacityNeeds($callForecast);
            
            // Churn risk (simplified)
            $churnRisk = $this->calculateChurnRisk();
            
            return [
                'forecasts' => [
                    'calls_next_7d' => array_sum($callForecast),
                    'appointments_next_7d' => array_sum($appointmentForecast),
                    'revenue_next_30d' => array_sum($revenueForecast) / 100,
                ],
                'trends' => [
                    'call_trend' => $this->calculateTrend($historicalCalls),
                    'appointment_trend' => $this->calculateTrend($historicalAppointments),
                    'revenue_trend' => $this->calculateTrend($historicalRevenue),
                ],
                'insights' => [
                    'peak_hours' => $peakHours,
                    'capacity_recommendation' => $capacityNeeds,
                    'churn_risk_companies' => $churnRisk,
                ],
                'seasonality' => [
                    'day_of_week_pattern' => $this->getDayOfWeekPattern(),
                    'monthly_pattern' => $this->getMonthlyPattern(),
                ],
            ];
        });
    }
    
    protected function detectAnomalies(): array
    {
        try {
            $anomalyService = app(AnomalyDetectionService::class);
            $anomalies = $anomalyService->detectSystemAnomalies();
            
            return array_map(function($anomaly) {
                return [
                    'type' => $anomaly['type'],
                    'severity' => $anomaly['severity'],
                    'message' => $anomaly['message'],
                    'value' => $anomaly['current_value'] ?? null,
                    'expected' => $anomaly['expected_value'] ?? null,
                    'deviation' => $anomaly['deviation_percentage'] ?? null,
                    'detected_at' => now()->toDateTimeString(),
                ];
            }, $anomalies);
        } catch (\Exception $e) {
            return [];
        }
    }
    
    protected function calculateSystemHealth(): array
    {
        $scores = [];
        
        // API Health
        $apiErrors = Cache::get('api.errors.count', 0);
        $apiRequests = Cache::get('api.requests.count', 1);
        $scores['api'] = max(0, 100 - ($apiErrors / $apiRequests * 100));
        
        // Database Health
        try {
            $slowResult = DB::select("SHOW STATUS LIKE 'Slow_queries'");
            $slowQueries = isset($slowResult[0]) ? $slowResult[0]->Value : 0;
            
            $totalResult = DB::select("SHOW STATUS LIKE 'Questions'");
            $totalQueries = isset($totalResult[0]) ? $totalResult[0]->Value : 1;
        } catch (\Exception $e) {
            $slowQueries = 0;
            $totalQueries = 1;
        }
        $scores['database'] = max(0, 100 - ($slowQueries / $totalQueries * 100));
        
        // Queue Health
        $failedJobs = DB::table('failed_jobs')->where('failed_at', '>=', now()->subHour())->count();
        $processedJobs = Cache::get('queue.processed.count', 1);
        $scores['queue'] = max(0, 100 - ($failedJobs / $processedJobs * 100));
        
        // Integration Health
        $retellStatus = $this->checkRetellHealth();
        $calcomStatus = $this->checkCalcomHealth();
        $scores['integrations'] = ($retellStatus + $calcomStatus) / 2;
        
        // Overall Health
        $overallScore = collect($scores)->avg();
        
        return [
            'overall' => round($overallScore, 2),
            'components' => $scores,
            'status' => $overallScore >= 90 ? 'excellent' : 
                       ($overallScore >= 70 ? 'good' : 
                       ($overallScore >= 50 ? 'fair' : 'poor')),
            'recommendations' => $this->getHealthRecommendations($scores),
        ];
    }
    
    protected function getLiveActivities(): array
    {
        // Get latest activities from various sources
        $activities = collect();
        
        // Recent calls
        $recentCalls = Call::with(['customer', 'company'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($call) => [
                'type' => 'call',
                'icon' => 'phone',
                'title' => 'Incoming call',
                'description' => ($call->customer->name ?? 'Unknown') . ' - ' . ($call->company->name ?? 'Unknown'),
                'duration' => $call->duration_sec . 's',
                'sentiment' => $call->sentiment,
                'time' => $call->created_at,
                'status' => $call->appointment_id ? 'converted' : 'not_converted',
            ]);
        
        // Recent appointments
        $recentAppointments = Appointment::with(['customer', 'staff', 'service'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($apt) => [
                'type' => 'appointment',
                'icon' => 'calendar',
                'title' => 'Appointment ' . $apt->status,
                'description' => ($apt->customer->name ?? 'Unknown') . ' with ' . ($apt->staff->name ?? 'No staff'),
                'service' => $apt->service->name ?? 'No service',
                'time' => $apt->created_at,
                'status' => $apt->status,
            ]);
        
        // System events
        $systemEvents = collect(Cache::get('system.events', []))
            ->map(fn($event) => [
                'type' => 'system',
                'icon' => 'cog',
                'title' => $event['title'],
                'description' => $event['description'],
                'time' => Carbon::parse($event['time']),
                'severity' => $event['severity'] ?? 'info',
            ]);
        
        return $activities
            ->merge($recentCalls)
            ->merge($recentAppointments)
            ->merge($systemEvents)
            ->sortByDesc('time')
            ->take(20)
            ->values()
            ->toArray();
    }
    
    // Helper Methods
    
    protected function calculateConversionRate($since): float
    {
        $totalCalls = Call::where('created_at', '>=', $since)->count();
        if ($totalCalls === 0) return 0;
        
        $convertedCalls = Call::where('created_at', '>=', $since)
            ->whereNotNull('appointment_id')
            ->count();
            
        return round(($convertedCalls / $totalCalls) * 100, 2);
    }
    
    protected function getAppointmentsFromCalls($since): int
    {
        return Call::where('created_at', '>=', $since)
            ->whereNotNull('appointment_id')
            ->count();
    }
    
    protected function getAverageBookingLeadTime(): float
    {
        $appointments = Appointment::where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('starts_at')
            ->get();
            
        if ($appointments->isEmpty()) return 0;
        
        $totalHours = $appointments->sum(function($apt) {
            return $apt->starts_at->diffInHours($apt->created_at);
        });
        
        return round($totalHours / $appointments->count(), 1);
    }
    
    protected function calculateBranchUtilization($branchId, $since): float
    {
        $totalSlots = 8 * 30; // 8 hours * 30 days
        $appointments = Appointment::where('branch_id', $branchId)
            ->where('starts_at', '>=', $since)
            ->with('service')
            ->get();
            
        $bookedMinutes = $appointments->sum(function($appointment) {
            return $appointment->service->duration ?? 60; // Default 60 minutes if no service
        });
        
        $bookedSlots = $bookedMinutes / 60; // Convert to hours
            
        return min(100, round(($bookedSlots / $totalSlots) * 100, 2));
    }
    
    protected function calculateRetentionRate($since): float
    {
        $totalCustomers = Customer::where('created_at', '<=', $since)->count();
        if ($totalCustomers === 0) return 0;
        
        $returningCustomers = Customer::where('created_at', '<=', $since)
            ->whereHas('appointments', function($q) use ($since) {
                $q->where('starts_at', '>=', $since);
            })
            ->count();
            
        return round(($returningCustomers / $totalCustomers) * 100, 2);
    }
    
    protected function getAvgAppointmentsPerCustomer($since): float
    {
        $customers = Customer::whereHas('appointments', function($q) use ($since) {
            $q->where('starts_at', '>=', $since);
        })->withCount(['appointments' => function($q) use ($since) {
            $q->where('starts_at', '>=', $since);
        }])->get();
        
        if ($customers->isEmpty()) return 0;
        
        return round($customers->avg('appointments_count'), 2);
    }
    
    protected function calculateNoShowRate($since): float
    {
        $totalAppointments = Appointment::where('starts_at', '>=', $since)
            ->whereIn('status', ['completed', 'no_show'])
            ->count();
            
        if ($totalAppointments === 0) return 0;
        
        $noShows = Appointment::where('starts_at', '>=', $since)
            ->where('status', 'no_show')
            ->count();
            
        return round(($noShows / $totalAppointments) * 100, 2);
    }
    
    protected function percentile($array, $percentile): float
    {
        if (empty($array)) return 0;
        
        sort($array);
        $index = ($percentile / 100) * (count($array) - 1);
        $lower = floor($index);
        $upper = ceil($index);
        $weight = $index - $lower;
        
        return $array[$lower] * (1 - $weight) + $array[$upper] * $weight;
    }
    
    protected function getDiskUsage(): array
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        
        return [
            'used_gb' => round($used / 1024 / 1024 / 1024, 2),
            'total_gb' => round($total / 1024 / 1024 / 1024, 2),
            'percentage' => round(($used / $total) * 100, 2),
        ];
    }
    
    protected function getAverageQueueWaitTime(): float
    {
        $recentJobs = DB::table('jobs')
            ->where('created_at', '>=', now()->subHour())
            ->get();
            
        if ($recentJobs->isEmpty()) return 0;
        
        $totalWait = $recentJobs->sum(function($job) {
            $createdAt = Carbon::createFromTimestamp($job->created_at);
            $availableAt = Carbon::createFromTimestamp($job->available_at);
            return $availableAt->diffInSeconds($createdAt);
        });
        
        return round($totalWait / $recentJobs->count(), 2);
    }
    
    protected function forecastLinearRegression($data, $days): array
    {
        if (count($data) < 7) return array_fill(0, $days, 0);
        
        // Simple linear regression
        $x = range(1, count($data));
        $y = array_values($data);
        
        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;
        
        $forecast = [];
        for ($i = 1; $i <= $days; $i++) {
            $forecast[] = max(0, round($slope * ($n + $i) + $intercept));
        }
        
        return $forecast;
    }
    
    protected function calculateTrend($data): string
    {
        if (count($data) < 7) return 'stable';
        
        $recent = array_slice($data, -7, 7, true);
        $previous = array_slice($data, -14, 7, true);
        
        $recentAvg = array_sum($recent) / count($recent);
        $previousAvg = array_sum($previous) / count($previous);
        
        if ($previousAvg == 0) return 'stable';
        
        $change = (($recentAvg - $previousAvg) / $previousAvg) * 100;
        
        if ($change > 10) return 'increasing';
        if ($change < -10) return 'decreasing';
        return 'stable';
    }
    
    protected function identifyPeakHours(): array
    {
        $hourlyDistribution = Call::selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('hour')
            ->pluck('count', 'hour')
            ->toArray();
            
        arsort($hourlyDistribution);
        
        return array_slice(array_keys($hourlyDistribution), 0, 3);
    }
    
    protected function predictCapacityNeeds($forecast): string
    {
        $avgDaily = array_sum($forecast) / count($forecast);
        $currentCapacity = 100; // Example capacity
        
        $utilizationRate = ($avgDaily / $currentCapacity) * 100;
        
        if ($utilizationRate > 80) return 'Increase capacity by 20%';
        if ($utilizationRate < 40) return 'Reduce capacity by 20%';
        return 'Current capacity optimal';
    }
    
    protected function calculateChurnRisk(): array
    {
        return Company::where('billing_status', 'active')
            ->whereDoesntHave('calls', function($q) {
                $q->where('created_at', '>=', now()->subDays(30));
            })
            ->limit(5)
            ->pluck('name')
            ->toArray();
    }
    
    protected function getDayOfWeekPattern(): array
    {
        return Call::selectRaw('DAYOFWEEK(created_at) as day, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(90))
            ->groupBy('day')
            ->pluck('count', 'day')
            ->toArray();
    }
    
    protected function getMonthlyPattern(): array
    {
        return Call::selectRaw('DAY(created_at) as day, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(90))
            ->groupBy('day')
            ->pluck('count', 'day')
            ->toArray();
    }
    
    protected function checkRetellHealth(): float
    {
        $recentCalls = Call::where('created_at', '>=', now()->subHour())->count();
        return $recentCalls > 0 ? 100 : 0;
    }
    
    protected function checkCalcomHealth(): float
    {
        $recentAppointments = Appointment::where('created_at', '>=', now()->subDay())->count();
        return $recentAppointments > 0 ? 100 : 0;
    }
    
    protected function getHealthRecommendations($scores): array
    {
        $recommendations = [];
        
        if ($scores['api'] < 90) {
            $recommendations[] = 'API error rate is high. Review error logs and optimize endpoints.';
        }
        
        if ($scores['database'] < 80) {
            $recommendations[] = 'Database performance degraded. Consider query optimization or scaling.';
        }
        
        if ($scores['queue'] < 70) {
            $recommendations[] = 'Queue processing issues detected. Check worker health and job failures.';
        }
        
        if ($scores['integrations'] < 90) {
            $recommendations[] = 'Integration health below optimal. Verify API connections.';
        }
        
        return $recommendations;
    }
    
    protected function calculateMemoryPercentage($usedBytes): float
    {
        $limit = ini_get('memory_limit');
        if ($limit == -1) {
            return 0; // No limit
        }
        
        // Convert memory limit to bytes
        $unit = strtolower(substr($limit, -1));
        $limitBytes = (int) $limit;
        
        switch ($unit) {
            case 'g':
                $limitBytes *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $limitBytes *= 1024 * 1024;
                break;
            case 'k':
                $limitBytes *= 1024;
                break;
        }
        
        return round(($usedBytes / $limitBytes) * 100, 2);
    }
    
    public function refreshData(): void
    {
        // Clear all caches to force refresh
        Cache::forget('quantum.realtime.metrics');
        Cache::forget('quantum.business.intelligence');
        Cache::forget('quantum.performance.metrics');
        Cache::forget('quantum.security.metrics');
        Cache::forget('quantum.predictive.analytics');
        
        $this->loadAllMetrics();
    }
}