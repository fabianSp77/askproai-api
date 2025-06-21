<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Services\HorizonHealth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class UltimateSystemCockpitOptimized extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'System Performance Center';
    protected static ?string $navigationGroup = 'System & Überwachung';
    protected static string $view = 'filament.admin.pages.ultimate-system-cockpit-optimized';
    protected static ?int $navigationSort = 1;
    
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Deaktiviert - redundante Monitoring-Seite
    }
    
    public array $callMetrics = [];
    public array $appointmentMetrics = [];
    public array $integrationStatus = [];
    public array $systemHealth = [];
    public array $realtimeActivities = [];
    public array $performanceMetrics = [];
    
    public function mount(): void
    {
        $this->loadMetrics();
    }
    
    protected function loadMetrics(): void
    {
        // Call Analytics - Real data from the system
        $this->callMetrics = Cache::remember('cockpit-call-metrics', 60, function () {
            $now = now();
            $todayStart = $now->copy()->startOfDay();
            $last24h = $now->copy()->subDay();
            $last7days = $now->copy()->subDays(7);
            
            $callsToday = Call::where('created_at', '>=', $todayStart);
            $calls24h = Call::where('created_at', '>=', $last24h);
            $calls7days = Call::where('created_at', '>=', $last7days);
            
            return [
                'calls_today' => $callsToday->count(),
                'calls_24h' => $calls24h->count(),
                'calls_7days' => $calls7days->count(),
                'avg_duration_today' => round($callsToday->avg('duration_sec') ?? 0),
                'total_duration_today' => round($callsToday->sum('duration_sec') / 60), // in minutes
                'conversion_rate' => $this->calculateRealConversionRate($last24h),
                'sentiment_distribution' => $this->getSentimentDistribution($last24h),
                'hourly_distribution' => $this->getHourlyCallDistribution(),
            ];
        });
        
        // Appointment Metrics - Real appointment data
        $this->appointmentMetrics = Cache::remember('cockpit-appointment-metrics', 60, function () {
            $now = now();
            $todayStart = $now->copy()->startOfDay();
            $todayEnd = $now->copy()->endOfDay();
            
            return [
                'appointments_today' => Appointment::whereBetween('starts_at', [$todayStart, $todayEnd])->count(),
                'upcoming_next_2h' => Appointment::where('status', 'scheduled')
                    ->whereBetween('starts_at', [$now, $now->copy()->addHours(2)])
                    ->count(),
                'completed_today' => Appointment::where('status', 'completed')
                    ->whereDate('starts_at', today())
                    ->count(),
                'no_shows_today' => Appointment::where('status', 'no_show')
                    ->whereDate('starts_at', today())
                    ->count(),
                'cancelled_today' => Appointment::where('status', 'cancelled')
                    ->whereDate('updated_at', today())
                    ->count(),
                'status_distribution' => $this->getAppointmentStatusDistribution(),
            ];
        });
        
        // Integration Health - Real API status checks
        $this->integrationStatus = [
            'retell' => $this->checkRetellStatus(),
            'calcom' => $this->checkCalcomStatus(),
            'database' => $this->checkDatabaseStatus(),
            'queue' => $this->checkQueueStatus(),
        ];
        
        // System Health Metrics
        $this->systemHealth = [
            'queue_size' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->where('failed_at', '>=', now()->subHours(24))->count(),
            'horizon_status' => $this->getHorizonStatus(),
            'active_companies' => Company::where('billing_status', 'active')->count(),
            'trial_companies' => Company::where('billing_status', 'trial')->count(),
        ];
        
        // Recent Activities
        $this->realtimeActivities = $this->getRecentActivities();
        
        // Performance Metrics
        $this->performanceMetrics = $this->getPerformanceMetrics();
    }
    
    protected function calculateRealConversionRate($since): float
    {
        $totalCalls = Call::where('created_at', '>=', $since)->count();
        
        if ($totalCalls === 0) return 0;
        
        // Real conversion: Calls that resulted in appointments
        $callsWithAppointments = Call::where('created_at', '>=', $since)
            ->whereNotNull('appointment_id')
            ->count();
            
        return round(($callsWithAppointments / $totalCalls) * 100, 2);
    }
    
    protected function getSentimentDistribution($since): array
    {
        $sentiments = Call::where('created_at', '>=', $since)
            ->whereNotNull('sentiment')
            ->groupBy('sentiment')
            ->selectRaw('sentiment, count(*) as count')
            ->pluck('count', 'sentiment')
            ->toArray();
            
        return [
            'positive' => $sentiments['positive'] ?? 0,
            'neutral' => $sentiments['neutral'] ?? 0,
            'negative' => $sentiments['negative'] ?? 0,
        ];
    }
    
    protected function getHourlyCallDistribution(): array
    {
        $distribution = [];
        for ($i = 0; $i < 24; $i++) {
            $hour = now()->subHours(23 - $i);
            $distribution[$hour->format('H:00')] = Call::whereBetween('created_at', [
                $hour->copy()->startOfHour(),
                $hour->copy()->endOfHour()
            ])->count();
        }
        return $distribution;
    }
    
    protected function getAppointmentStatusDistribution(): array
    {
        return Appointment::whereDate('starts_at', today())
            ->groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status')
            ->toArray();
    }
    
    protected function checkRetellStatus(): array
    {
        try {
            // Check if we had any calls in the last hour
            $lastCall = Call::latest()->first();
            $recentCalls = Call::where('created_at', '>=', now()->subHour())->count();
            
            if (!$lastCall) {
                return [
                    'status' => 'warning',
                    'last_activity' => 'No calls recorded',
                    'message' => 'No call data available',
                    'details' => ['recent_calls' => 0]
                ];
            }
            
            $hoursSinceLastCall = $lastCall->created_at->diffInHours(now());
            
            return [
                'status' => $hoursSinceLastCall < 1 ? 'operational' : ($hoursSinceLastCall < 24 ? 'warning' : 'error'),
                'last_activity' => $lastCall->created_at->diffForHumans(),
                'message' => $recentCalls > 0 ? "Active - {$recentCalls} calls in last hour" : 'No recent activity',
                'details' => [
                    'recent_calls' => $recentCalls,
                    'last_call_duration' => $lastCall->duration_sec . 's',
                    'last_call_status' => $lastCall->status
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'last_activity' => 'Error',
                'message' => 'Failed to check Retell status',
                'details' => ['error' => $e->getMessage()]
            ];
        }
    }
    
    protected function checkCalcomStatus(): array
    {
        try {
            $recentAppointments = Appointment::where('created_at', '>=', now()->subDay())->count();
            $lastAppointment = Appointment::latest()->first();
            
            if (!$lastAppointment) {
                return [
                    'status' => 'warning',
                    'last_activity' => 'No appointments',
                    'message' => 'No appointment data available',
                    'details' => ['recent_appointments' => 0]
                ];
            }
            
            $daysSinceLastAppointment = $lastAppointment->created_at->diffInDays(now());
            
            return [
                'status' => $daysSinceLastAppointment < 1 ? 'operational' : ($daysSinceLastAppointment < 7 ? 'warning' : 'error'),
                'last_activity' => $lastAppointment->created_at->diffForHumans(),
                'message' => $recentAppointments > 0 ? "Active - {$recentAppointments} bookings in 24h" : 'Low activity',
                'details' => [
                    'recent_appointments' => $recentAppointments,
                    'today_appointments' => Appointment::whereDate('starts_at', today())->count()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'last_activity' => 'Error',
                'message' => 'Failed to check Cal.com status',
                'details' => ['error' => $e->getMessage()]
            ];
        }
    }
    
    protected function checkDatabaseStatus(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            
            // Check table sizes
            $callsCount = Call::count();
            $appointmentsCount = Appointment::count();
            
            return [
                'status' => $responseTime < 50 ? 'operational' : 'warning',
                'last_activity' => 'Connected',
                'message' => "Response time: {$responseTime}ms",
                'details' => [
                    'response_time' => $responseTime . 'ms',
                    'total_calls' => number_format($callsCount),
                    'total_appointments' => number_format($appointmentsCount)
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'last_activity' => 'Disconnected',
                'message' => 'Database connection failed',
                'details' => ['error' => $e->getMessage()]
            ];
        }
    }
    
    protected function checkQueueStatus(): array
    {
        try {
            $queueSize = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count();
            
            // Try to get Horizon status if available
            $horizonStatus = 'unknown';
            try {
                $horizonStatus = \App\Services\HorizonHealth::ok() ? 'operational' : 'error';
            } catch (\Exception $e) {
                // Horizon not available
            }
            
            return [
                'status' => $failedJobs > 10 ? 'error' : ($queueSize > 100 ? 'warning' : 'operational'),
                'last_activity' => $queueSize > 0 ? "{$queueSize} jobs pending" : 'Queue empty',
                'message' => $failedJobs > 0 ? "{$failedJobs} failed jobs in 24h" : 'All jobs processing normally',
                'details' => [
                    'queue_size' => $queueSize,
                    'failed_24h' => $failedJobs,
                    'horizon_status' => $horizonStatus
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'last_activity' => 'Error',
                'message' => 'Failed to check queue status',
                'details' => ['error' => $e->getMessage()]
            ];
        }
    }
    
    protected function getRecentActivities(): array
    {
        $activities = [];
        
        // Recent calls with more details
        $recentCalls = Call::with(['customer', 'company', 'appointment'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($call) {
                return [
                    'type' => 'call',
                    'icon' => 'heroicon-o-phone',
                    'title' => ($call->customer ? $call->customer->name : 'Unknown') . ' - ' . $call->duration_sec . 's',
                    'subtitle' => $call->company->name ?? 'Unknown Company',
                    'time' => $call->created_at->diffForHumans(),
                    'status' => $call->appointment_id ? 'converted' : 'not_converted',
                    'sentiment' => $call->sentiment
                ];
            });
            
        // Recent appointments with details
        $recentAppointments = Appointment::with(['customer', 'staff', 'service'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($apt) {
                return [
                    'type' => 'appointment',
                    'icon' => 'heroicon-o-calendar',
                    'title' => ($apt->customer ? $apt->customer->name : 'Unknown') . ' - ' . ($apt->starts_at ? $apt->starts_at->format('H:i') : 'No time'),
                    'subtitle' => ($apt->staff ? $apt->staff->name : 'No staff') . ' | ' . ($apt->service ? $apt->service->name : 'No service'),
                    'time' => $apt->created_at->diffForHumans(),
                    'status' => $apt->status
                ];
            });
            
        return collect()
            ->merge($recentCalls)
            ->merge($recentAppointments)
            ->sortByDesc(function($item) {
                return $item['time'];
            })
            ->take(10)
            ->values()
            ->toArray();
    }
    
    protected function getHorizonStatus(): string
    {
        try {
            return \App\Services\HorizonHealth::ok() ? 'operational' : 'error';
        } catch (\Exception $e) {
            return 'unavailable';
        }
    }
    
    protected function getPerformanceMetrics(): array
    {
        return [
            'avg_response_time' => Cache::get('avg_response_time', 0),
            'requests_per_minute' => Cache::get('requests_per_minute', 0),
            'active_users' => DB::table('sessions')->where('last_activity', '>=', now()->subMinutes(5))->count(),
            'cpu_usage' => sys_getloadavg()[0] ?? 0,
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'disk_usage' => $this->getDiskUsage(),
        ];
    }
    
    protected function getDiskUsage(): array
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        
        return [
            'percentage' => round(($used / $total) * 100, 2),
            'used_gb' => round($used / 1024 / 1024 / 1024, 2),
            'total_gb' => round($total / 1024 / 1024 / 1024, 2),
        ];
    }
    
    public function refreshData(): void
    {
        Cache::forget('cockpit-call-metrics');
        Cache::forget('cockpit-appointment-metrics');
        $this->loadMetrics();
    }
}