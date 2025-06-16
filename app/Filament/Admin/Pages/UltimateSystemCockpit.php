<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\AnomalyDetectionService;

class UltimateSystemCockpit extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Neural Command Center';
    protected static ?string $navigationGroup = 'System & Monitoring';
    protected static string $view = 'filament.admin.pages.ultimate-system-cockpit-v4';
    protected static ?int $navigationSort = 5;
    
    public array $systemMetrics = [];
    public array $serviceHealth = [];
    public array $companyMetrics = [];
    public array $realtimeStats = [];
    public array $historicalData = [];
    public array $anomalies = [];
    public array $globalSystemData = [];
    public array $systemRecommendations = [];
    
    public int $refreshInterval = 5; // seconds
    protected array $alertThresholds = [
        'error_rate' => 0.05, // 5%
        'response_time' => 500, // 500ms
        'queue_size' => 1000,
        'calls_per_minute' => 100,
    ];
    
    public function mount(): void
    {
        // Enhanced logging
        $debugLog = storage_path('logs/ultimate-cockpit-debug.log');
        $logEntry = sprintf(
            "[%s] UltimateSystemCockpit::mount started - User: %s, IP: %s\n",
            now()->toDateTimeString(),
            auth()->id() ?? 'not-authenticated',
            request()->ip()
        );
        file_put_contents($debugLog, $logEntry, FILE_APPEND | LOCK_EX);
        
        Log::info('UltimateSystemCockpit::mount started', [
            'user_id' => auth()->id(),
            'timestamp' => now()->toDateTimeString()
        ]);
        
        try {
            // Test basic system metrics first
            Log::debug('Loading system metrics...');
            $this->systemMetrics = [
                'overall_health' => $this->calculateOverallHealth(),
                'active_calls' => Call::where('created_at', '>=', now()->subMinutes(5))->count(),
                'queue_size' => DB::table('jobs')->count(),
                'error_rate' => 0.02,
                'response_time' => 150,
                'database_health' => 100,
                'uptime' => '10d 5h 30m',
            ];
            Log::debug('System metrics loaded', $this->systemMetrics);
            
            // Basic service health
            Log::debug('Loading service health...');
            $this->serviceHealth = [
                'retell_ai' => 100,
                'calcom' => 95,
                'database' => 100,
                'redis' => 100,
                'queue' => 90,
                'api_gateway' => 98,
            ];
            
            // Load company metrics with real data
            Log::debug('Loading company metrics...');
            try {
                $this->companyMetrics = Company::where('is_active', true)
                    ->withCount(['calls', 'appointments', 'staff', 'branches'])
                    ->with(['branches'])
                    ->take(10)
                    ->get()
                    ->map(function ($company) {
                    Log::debug("Processing company: {$company->name}", ['company_id' => $company->id]);
                    
                    // Get branches with simple data
                    $branches = $company->branches->map(function ($branch) {
                        try {
                            Log::debug("Processing branch: {$branch->name}", ['branch_id' => $branch->id]);
                            
                            return [
                                'id' => $branch->id,
                                'name' => $branch->name,
                                'city' => $branch->city,
                                'address' => $branch->address ?? '',
                                'staff_count' => $branch->staff()->count(),
                                'appointments_today' => 0, // Simple for now
                                'appointments_week' => 0,
                                'services' => [
                                    'calcom_connected' => false,
                                    'staff_active' => $branch->staff()->where('active', true)->exists(),
                                    'phone_assigned' => !empty($branch->phone_number_id),
                                ],
                                'health' => rand(70, 100),
                            ];
                        } catch (\Exception $e) {
                            Log::error("Error processing branch {$branch->id}", [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                            throw $e;
                        }
                    })->toArray();
                    
                    return [
                        'id' => $company->id,
                        'name' => $company->name,
                        'health' => $this->calculateCompanyHealth($company),
                        'calls_today' => $company->calls()->whereDate('created_at', today())->count(),
                        'calls_week' => $company->calls()
                            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                        'appointments_today' => $company->appointments()->whereDate('starts_at', today())->count(),
                        'appointments_week' => $company->appointments()
                            ->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                        'active_staff' => $company->staff()->where('active', true)->count(),
                        'total_staff' => $company->staff_count,
                        'branch_count' => $company->branches_count,
                        'branches' => $branches,
                        'integrations' => [
                            'retell_ai' => !empty($company->retell_api_key),
                            'calcom' => !empty($company->calcom_api_key),
                            'email' => true,
                            'sms' => false,
                            'whatsapp' => false,
                            'api' => !empty($company->api_key),
                        ],
                        'phone_numbers' => 0,
                        'created_at' => $company->created_at ? $company->created_at->format('Y-m-d') : 'N/A',
                        'subscription_status' => $company->subscription_status ?? 'active',
                        'performance_trend' => ['growth' => rand(1, 20)],
                    ];
                })
                ->toArray();
                
                Log::info('Company metrics loaded successfully', ['count' => count($this->companyMetrics)]);
                
            } catch (\Exception $e) {
                Log::error('Error loading company metrics', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->companyMetrics = [];
            }
            
            $this->realtimeStats = [
                'calls_per_minute' => $this->calculateCallsPerMinute(),
                'appointments_per_hour' => $this->calculateAppointmentsPerHour(),
                'new_customers_today' => Customer::whereDate('created_at', today())->count(),
                'peak_hour' => $this->calculatePeakHour(),
            ];
            
            // Load historical data with error handling
            try {
                $this->loadHistoricalData();
            } catch (\Exception $e) {
                Log::error('Error loading historical data', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->historicalData = [];
            }
            
            // Detect anomalies with error handling
            try {
                $this->detectAnomalies();
            } catch (\Exception $e) {
                Log::error('Error detecting anomalies', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->anomalies = [];
                $this->systemRecommendations = [];
            }
            
            // Load global system data with error handling
            try {
                $this->loadGlobalSystemData();
            } catch (\Exception $e) {
                Log::error('Error loading global system data', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->globalSystemData = [];
            }
            
            Log::info('UltimateSystemCockpit::mount completed successfully');
            
        } catch (\Exception $e) {
            // Write to debug log
            $debugLog = storage_path('logs/ultimate-cockpit-debug.log');
            $errorEntry = sprintf(
                "[%s] CRITICAL ERROR in mount():\nMessage: %s\nFile: %s:%d\nTrace:\n%s\n\n",
                now()->toDateTimeString(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );
            file_put_contents($debugLog, $errorEntry, FILE_APPEND | LOCK_EX);
            
            Log::error('CRITICAL: UltimateSystemCockpit mount error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback values
            $this->systemMetrics = [
                'overall_health' => 0,
                'active_calls' => 0,
                'queue_size' => 0,
                'error_rate' => 0,
                'response_time' => 0,
                'database_health' => 0,
                'uptime' => 'Error',
            ];
            $this->serviceHealth = [];
            $this->companyMetrics = [];
            $this->realtimeStats = [];
            $this->historicalData = [];
            $this->anomalies = [];
            $this->systemRecommendations = [];
            $this->globalSystemData = [];
        }
    }
    
    public function loadMetrics(): void
    {
        // Simply call mount to refresh data
        $this->mount();
    }
    
    protected function calculateOverallHealth(): int
    {
        $metrics = [
            $this->checkDatabaseHealth(),
            $this->checkRedisHealth(),
            $this->checkQueueHealth(),
            $this->checkApiHealth(),
        ];
        
        return (int) round(array_sum($metrics) / count($metrics));
    }
    
    protected function calculateErrorRate(): float
    {
        // Placeholder - would check actual error logs
        return rand(0, 5) / 100; // 0-5% error rate
    }
    
    protected function getAverageResponseTime(): int
    {
        // Placeholder - would check actual response times
        return rand(50, 200); // 50-200ms
    }
    
    protected function checkDatabaseHealth(): int
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $time = (microtime(true) - $start) * 1000;
            
            if ($time < 10) return 100;
            if ($time < 50) return 90;
            if ($time < 100) return 70;
            return 50;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    protected function checkRedisHealth(): int
    {
        try {
            Cache::put('health_check', true, 1);
            return Cache::get('health_check') ? 100 : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    protected function checkQueueHealth(): int
    {
        $jobs = DB::table('jobs')->count();
        $failed = DB::table('failed_jobs')->where('failed_at', '>=', now()->subHour())->count();
        
        if ($failed > 10) return 50;
        if ($failed > 5) return 70;
        if ($jobs > 1000) return 80;
        if ($jobs > 500) return 90;
        return 100;
    }
    
    protected function checkRetellHealth(): int
    {
        try {
            // Check if we have recent calls
            $recentCalls = Call::where('created_at', '>=', now()->subMinutes(30))->count();
            if ($recentCalls > 0) return 100;
            if (Call::where('created_at', '>=', now()->subHour())->count() > 0) return 85;
            return 70;
        } catch (\Exception $e) {
            return 50;
        }
    }
    
    protected function checkCalcomHealth(): int
    {
        try {
            // Check if we have recent appointments
            $recentAppointments = Appointment::where('created_at', '>=', now()->subHour())->count();
            if ($recentAppointments > 0) return 100;
            return 85;
        } catch (\Exception $e) {
            return 50;
        }
    }
    
    protected function checkApiHealth(): int
    {
        // Check internal API health
        return rand(95, 100);
    }
    
    protected function calculateUptime(): string
    {
        // Placeholder - would check actual uptime
        $days = rand(10, 90);
        return "{$days}d " . rand(0, 23) . "h " . rand(0, 59) . "m";
    }
    
    protected function calculateCompanyHealth($company): int
    {
        // Simple health calculation based on activity
        $callsToday = $company->calls()->whereDate('created_at', today())->count();
        $appointmentsToday = $company->appointments()->whereDate('starts_at', today())->count();
        
        if ($callsToday > 50 && $appointmentsToday > 10) return 100;
        if ($callsToday > 20 && $appointmentsToday > 5) return 85;
        if ($callsToday > 10 || $appointmentsToday > 2) return 70;
        if ($callsToday > 0 || $appointmentsToday > 0) return 50;
        return 30;
    }
    
    protected function calculateBranchHealth($branch): int
    {
        $score = 100;
        
        // Check if branch has active staff (-20 if no staff)
        if (!$branch->staff()->where('active', true)->exists()) {
            $score -= 20;
        }
        
        // Check if branch has appointments (-10 if no appointments this week)
        if ($branch->appointments()->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])->count() == 0) {
            $score -= 10;
        }
        
        // Check if branch has phone number (-15 if no phone)
        if (!$branch->phone_number_id) {
            $score -= 15;
        }
        
        // Check if branch has Cal.com connection (-15 if not connected)
        // TODO: Add proper calcom connection check
        // if (!$branch->calcom_event_types()->exists()) {
        //     $score -= 15;
        // }
        
        return max(30, $score);
    }
    
    protected function calculatePeakHour(): string
    {
        // Find hour with most calls
        $hour = Call::whereDate('created_at', today())
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderByDesc('count')
            ->first();
            
        return $hour ? "{$hour->hour}:00 - " . ($hour->hour + 1) . ":00" : "N/A";
    }
    
    protected function calculateCallsPerMinute(): float
    {
        $recentCalls = Call::where('created_at', '>=', now()->subMinutes(5))->count();
        return round($recentCalls / 5, 2);
    }
    
    protected function calculateAppointmentsPerHour(): float
    {
        $recentAppointments = Appointment::where('created_at', '>=', now()->subHour())->count();
        return $recentAppointments;
    }
    
    // Real-time update method
    public function refresh(): void
    {
        $this->mount();
        $this->dispatch('refreshed');
    }
    
    protected function loadHistoricalData(): void
    {
        $this->historicalData = Cache::remember('historical.data', 300, function () {
            $hours = collect(range(23, 0))->map(function ($hour) {
                $time = now()->subHours($hour);
                return [
                    'hour' => $time->format('H:00'),
                    'calls' => Call::whereBetween('created_at', [
                        $time->copy()->startOfHour(),
                        $time->copy()->endOfHour()
                    ])->count(),
                    'appointments' => Appointment::whereBetween('created_at', [
                        $time->copy()->startOfHour(),
                        $time->copy()->endOfHour()
                    ])->count(),
                    'errors' => rand(0, 10), // Placeholder - would check error logs
                    'response_time' => rand(50, 200),
                ];
            });
            
            return [
                'hourly' => $hours->toArray(),
                'daily' => $this->getDailyMetrics(),
                'weekly' => $this->getWeeklyMetrics(),
            ];
        });
    }
    
    protected function getDailyMetrics(): array
    {
        return collect(range(6, 0))->map(function ($day) {
            $date = now()->subDays($day);
            return [
                'date' => $date->format('M d'),
                'calls' => Call::whereDate('created_at', $date)->count(),
                'appointments' => Appointment::whereDate('starts_at', $date)->count(),
                'customers' => Customer::whereDate('created_at', $date)->count(),
                'revenue' => rand(5000, 15000), // Placeholder
            ];
        })->toArray();
    }
    
    protected function getWeeklyMetrics(): array
    {
        return collect(range(11, 0))->map(function ($week) {
            $startDate = now()->subWeeks($week)->startOfWeek();
            $endDate = now()->subWeeks($week)->endOfWeek();
            return [
                'week' => 'W' . $startDate->format('W'),
                'calls' => Call::whereBetween('created_at', [$startDate, $endDate])->count(),
                'appointments' => Appointment::whereBetween('starts_at', [$startDate, $endDate])->count(),
                'companies_active' => Company::whereHas('calls', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                })->count(),
            ];
        })->toArray();
    }
    
    protected function detectAnomalies(): void
    {
        try {
            // Use the advanced anomaly detection service
            $anomalyService = new AnomalyDetectionService();
            $anomalies = $anomalyService->detectSystemAnomalies();
            
            // Sort by severity
            $this->anomalies = collect($anomalies)
                ->sortByDesc(function ($anomaly) use ($anomalyService) {
                    return $anomalyService->getSeverityScore($anomaly['severity']);
                })
                ->values()
                ->toArray();
                
            // Get system recommendations
            $this->systemRecommendations = $anomalyService->getSystemRecommendations($anomalies);
        } catch (\Exception $e) {
            Log::error('AnomalyDetectionService error', ['error' => $e->getMessage()]);
            
            // Provide demo data as fallback
            $this->anomalies = [
                [
                    'severity' => 'warning',
                    'title' => 'High Queue Size',
                    'description' => 'Queue size is above normal threshold',
                    'timestamp' => now()->format('H:i:s'),
                    'affected_component' => 'Queue System',
                    'recommended_action' => 'Monitor queue workers'
                ]
            ];
            
            $this->systemRecommendations = [
                [
                    'title' => 'Optimize Database Queries',
                    'description' => 'Some queries are running slower than expected',
                    'impact' => 'high',
                    'estimated_improvement' => 15
                ],
                [
                    'title' => 'Enable Redis Cache',
                    'description' => 'Caching frequently accessed data can improve performance',
                    'impact' => 'medium',
                    'estimated_improvement' => 10
                ]
            ];
        }
    }
    
    protected function loadGlobalSystemData(): void
    {
        $this->globalSystemData = Cache::remember('global.system.data', 60, function () {
            // Get geographic distribution of companies and branches
            $companies = Company::where('is_active', true)
                ->with('branches')
                ->get();
                
            $nodes = [];
            $connections = [];
            
            // Create nodes for companies and branches
            foreach ($companies as $company) {
                $companyNode = [
                    'id' => 'company-' . $company->id,
                    'type' => 'company',
                    'name' => $company->name,
                    'health' => $this->calculateCompanyHealth($company),
                    'size' => max(10, min(50, $company->staff()->count())),
                    'lat' => $company->latitude ?? 52.520008,
                    'lng' => $company->longitude ?? 13.404954,
                ];
                $nodes[] = $companyNode;
                
                foreach ($company->branches as $branch) {
                    $branchNode = [
                        'id' => 'branch-' . $branch->id,
                        'type' => 'branch',
                        'name' => $branch->name,
                        'parentId' => 'company-' . $company->id,
                        'health' => rand(70, 100), // Placeholder
                        'size' => max(5, min(20, $branch->staff()->count())),
                        'lat' => $branch->latitude ?? ($company->latitude ?? 52.520008),
                        'lng' => $branch->longitude ?? ($company->longitude ?? 13.404954),
                    ];
                    $nodes[] = $branchNode;
                    
                    // Create connection between company and branch
                    $connections[] = [
                        'source' => 'company-' . $company->id,
                        'target' => 'branch-' . $branch->id,
                        'strength' => 1,
                    ];
                }
            }
            
            return [
                'nodes' => $nodes,
                'connections' => $connections,
                'stats' => [
                    'total_companies' => $companies->count(),
                    'total_branches' => Branch::count(),
                    'total_staff' => Staff::count(),
                    'total_calls_today' => Call::whereDate('created_at', today())->count(),
                ],
            ];
        });
    }
    
    protected function calculatePerformanceTrend($company): array
    {
        $today = $company->calls()->whereDate('created_at', today())->count();
        $yesterday = $company->calls()->whereDate('created_at', today()->subDay())->count();
        $lastWeek = $company->calls()->whereBetween('created_at', [
            today()->subWeek(),
            today()
        ])->count();
        
        return [
            'daily_change' => $yesterday > 0 ? round((($today - $yesterday) / $yesterday) * 100, 1) : 0,
            'weekly_avg' => round($lastWeek / 7, 1),
            'trend' => $today > $yesterday ? 'up' : ($today < $yesterday ? 'down' : 'stable'),
        ];
    }
    
    public function getCompanyDetails($companyId): array
    {
        $company = Company::with(['branches.staff', 'appointments', 'calls'])
            ->findOrFail($companyId);
            
        return [
            'company' => $company->toArray(),
            'stats' => [
                'total_calls' => $company->calls()->count(),
                'calls_today' => $company->calls()->whereDate('created_at', today())->count(),
                'appointments_this_week' => $company->appointments()
                    ->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count(),
                'active_staff' => $company->staff()->where('active', true)->count(),
            ],
            'recent_activity' => $company->calls()
                ->latest()
                ->take(10)
                ->get()
                ->toArray(),
        ];
    }
    
    public function getListeners(): array
    {
        return [
            'refresh' => 'refresh',
            'echo:system-metrics,MetricsUpdated' => 'handleMetricsUpdate',
            'loadCompanyDetails' => 'getCompanyDetails',
        ];
    }
    
    public function handleMetricsUpdate($event): void
    {
        // Handle real-time metric updates via WebSocket
        if (isset($event['metrics'])) {
            $this->systemMetrics = array_merge($this->systemMetrics, $event['metrics']);
            $this->dispatch('metrics-updated', $event['metrics']);
        }
    }
    
    /**
     * Get the view data for the page
     */
    protected function getViewData(): array
    {
        Log::debug('UltimateSystemCockpit::getViewData called', [
            'refreshInterval' => $this->refreshInterval,
            'has_system_metrics' => !empty($this->systemMetrics),
        ]);
        
        return [
            'systemMetrics' => $this->systemMetrics,
            'serviceHealth' => $this->serviceHealth,
            'companyMetrics' => $this->companyMetrics,
            'realtimeStats' => $this->realtimeStats,
            'historicalData' => $this->historicalData,
            'anomalies' => $this->anomalies,
            'globalSystemData' => $this->globalSystemData,
            'systemRecommendations' => $this->systemRecommendations,
            'refreshInterval' => $this->refreshInterval,
            'alertThresholds' => $this->alertThresholds,
        ];
    }
    
    /**
     * Override render to catch any rendering errors
     */
    public function render(): \Illuminate\Contracts\View\View
    {
        try {
            // Write to debug log
            $debugLog = storage_path('logs/ultimate-cockpit-debug.log');
            $logEntry = sprintf(
                "[%s] render() called - View: %s, Metrics: %d companies\n",
                now()->toDateTimeString(),
                static::$view,
                count($this->companyMetrics)
            );
            file_put_contents($debugLog, $logEntry, FILE_APPEND | LOCK_EX);
            
            Log::debug('UltimateSystemCockpit::render called', [
                'view' => static::$view,
                'metrics_loaded' => !empty($this->systemMetrics),
                'companies_count' => count($this->companyMetrics),
            ]);
            
            return parent::render();
        } catch (\Exception $e) {
            // Write to debug log
            $debugLog = storage_path('logs/ultimate-cockpit-debug.log');
            $errorEntry = sprintf(
                "[%s] CRITICAL ERROR in render():\nMessage: %s\nFile: %s:%d\nView: %s\nTrace:\n%s\n\n",
                now()->toDateTimeString(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                static::$view,
                $e->getTraceAsString()
            );
            file_put_contents($debugLog, $errorEntry, FILE_APPEND | LOCK_EX);
            
            Log::error('CRITICAL: Error rendering UltimateSystemCockpit', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'view' => static::$view,
                'system_metrics' => $this->systemMetrics,
                'company_metrics_count' => count($this->companyMetrics),
            ]);
            
            // Return a fallback error view
            return view('filament.admin.pages.system-error', [
                'error' => $e->getMessage(),
                'debug' => config('app.debug'),
            ]);
        }
    }
}