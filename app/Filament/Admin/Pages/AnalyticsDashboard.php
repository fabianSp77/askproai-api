<?php

namespace App\Filament\Admin\Pages;

use App\Models\Call;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\PhoneNumber;
use Filament\Pages\Page;
use Filament\Pages\Actions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class AnalyticsDashboard extends Page
{
    protected static ?string $navigationGroup = "📊 Analytics";
    protected static ?int $navigationSort = 10;
    
    protected static ?string $navigationIcon = "heroicon-o-presentation-chart-bar";
    protected static ?string $navigationLabel = "Haupt-Dashboard";
    protected static string $view = 'filament.admin.pages.analytics-dashboard-professional';
    
    // Filter properties
    public ?int $companyId = null;
    public string $period = 'week';
    
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole(['Super Admin', 'super_admin', 'Admin', 'Manager']) || $user->email === 'dev@askproai.de');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
    
    public static function getNavigationGroup(): ?string
    {
        return static::$navigationGroup ?? '📊 Analytics';
    }
    
    public static function getNavigationSort(): ?int
    {
        return static::$navigationSort ?? 10;
    }

    // Real-time stats
    public array $realtimeStats = [
        'active_calls' => 0,
        'today_appointments' => 0,
        'completed_today' => 0,
        'active_companies' => 0,
        'active_phones' => 0,
        'total_revenue' => 0,
        'conversion_rate' => 0,
    ];

    // Business metrics
    public array $businessMetrics = [
        'daily_revenue' => [],
        'service_performance' => [],
        'recent_activities' => [],
    ];
    
    public bool $autoRefresh = true;
    public int $refreshInterval = 30; // seconds

    public function mount(): void
    {
        // Check if user has permission
        if (!static::canAccess()) {
            abort(403);
        }
        $this->loadAllMetrics();
    }

    public function loadAllMetrics(): void
    {
        try {
            $this->loadRealtimeStats();
            $this->loadBusinessMetrics();
        } catch (\Exception $e) {
            \Log::error("Failed to load analytics metrics", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function loadRealtimeStats(): void
    {
        $now = now();
        $today = today();

        // Cache key for performance
        $cacheKey = 'analytics_realtime_stats_' . $today->format('Y-m-d');

        $this->realtimeStats = Cache::remember($cacheKey, 60, function () use ($now, $today) {
            // Active calls - bypass tenant scope for admin monitoring
            $activeCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('status', 'in_progress')
                ->where('created_at', '>=', $now->subHours(1))
                ->count();

            // Today's appointments - bypass tenant scope
            $todayAppointments = Appointment::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->whereDate('starts_at', $today)
                ->count();

            // Completed appointments today - bypass tenant scope
            $completedToday = Appointment::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->whereDate('starts_at', $today)
                ->where('status', 'completed')
                ->count();

            // Active companies
            $activeCompanies = Company::where('subscription_status', 'active')
                ->orWhere('subscription_status', 'trial')
                ->count();

            // Active phone numbers - bypass tenant scope
            $activePhones = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('is_active', true)
                ->whereHas('retellAgent', function ($query) {
                    $query->withoutGlobalScope(\App\Scopes\TenantScope::class);
                })
                ->count();

            // Calculate total revenue (mock data for now)
            $totalRevenue = $this->calculateTotalRevenue($today);

            // Calculate conversion rate
            $conversionRate = $this->calculateConversionRate($today);

            return [
                'active_calls' => $activeCalls,
                'today_appointments' => $todayAppointments,
                'completed_today' => $completedToday,
                'active_companies' => $activeCompanies,
                'active_phones' => $activePhones,
                'total_revenue' => $totalRevenue,
                'conversion_rate' => $conversionRate,
            ];
        });
    }

    protected function loadBusinessMetrics(): void
    {
        // Load daily revenue data for the past 30 days
        $this->businessMetrics['daily_revenue'] = $this->getDailyRevenueData();
        
        // Load service performance data
        $this->businessMetrics['service_performance'] = $this->getServicePerformanceData();
        
        // Load recent activities
        $this->businessMetrics['recent_activities'] = $this->getRecentActivities();
    }

    protected function calculateTotalRevenue($date): float
    {
        // This is a placeholder calculation
        // In a real application, you would calculate from actual payment/invoice data
        $completedAppointments = Appointment::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereDate('starts_at', $date)
            ->where('status', 'completed')
            ->count();

        // Durchschnittlicher Umsatz pro Termin: 150€ (für deutsche Dienstleister)
        return $completedAppointments * 150.00;
    }

    protected function calculateConversionRate($date): float
    {
        $totalCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereDate('created_at', $date)
            ->count();

        $successfulAppointments = Appointment::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereDate('starts_at', $date)
            ->whereIn('status', ['scheduled', 'completed'])
            ->count();

        if ($totalCalls === 0) {
            return 0;
        }

        return round(($successfulAppointments / $totalCalls) * 100, 1);
    }

    protected function getDailyRevenueData(): array
    {
        // Generate sample data for the past 30 days
        // In production, this would query actual revenue data
        $data = [];
        $baseRevenue = 1000;
        
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $variance = rand(-200, 400);
            $revenue = max(500, $baseRevenue + $variance);
            
            $data[] = [
                'date' => $date->format('M j'),
                'revenue' => $revenue,
            ];
        }

        return $data;
    }

    protected function getServicePerformanceData(): array
    {
        // Generate sample service performance data
        // In production, this would query actual appointment data by service type
        return [
            ['service' => 'Consultation', 'appointments' => rand(50, 80)],
            ['service' => 'Therapy', 'appointments' => rand(30, 60)],
            ['service' => 'Check-up', 'appointments' => rand(60, 90)],
            ['service' => 'Surgery', 'appointments' => rand(5, 20)],
            ['service' => 'Emergency', 'appointments' => rand(3, 15)],
            ['service' => 'Follow-up', 'appointments' => rand(15, 40)],
        ];
    }

    protected function getRecentActivities(): array
    {
        // Get recent calls and appointments
        $recentCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->with(['customer', 'company'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($call) {
                return [
                    'type' => 'call',
                    'time' => $call->created_at->format('g:i A'),
                    'customer' => $call->customer->name ?? 'Unknown',
                    'status' => $call->status,
                    'revenue' => $call->status === 'completed' ? rand(100, 300) : 0,
                    'id' => $call->id,
                ];
            });

        $recentAppointments = Appointment::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->with(['customer', 'company'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($appointment) {
                return [
                    'type' => 'appointment',
                    'time' => $appointment->created_at->format('g:i A'),
                    'customer' => $appointment->customer->name ?? 'Unknown',
                    'status' => $appointment->status,
                    'revenue' => $appointment->status === 'completed' ? rand(150, 400) : ($appointment->status === 'scheduled' ? rand(100, 300) : 0),
                    'id' => $appointment->id,
                ];
            });

        // Merge and sort by time
        $activities = $recentCalls->concat($recentAppointments)
            ->sortByDesc('time')
            ->take(5)
            ->values()
            ->all();

        return $activities;
    }

    public function refresh(): void
    {
        // Clear cache to force fresh data
        $cacheKey = 'analytics_realtime_stats_' . today()->format('Y-m-d');
        Cache::forget($cacheKey);
        
        $this->loadAllMetrics();
        $this->dispatch('metricsUpdated');
        
        // Show success notification
        \Filament\Notifications\Notification::make()
            ->title('Dashboard Refreshed')
            ->body('Analytics data has been updated with the latest information.')
            ->success()
            ->send();
    }

    #[On('toggle-auto-refresh')]
    public function toggleAutoRefresh(): void
    {
        $this->autoRefresh = !$this->autoRefresh;
        
        $status = $this->autoRefresh ? 'enabled' : 'disabled';
        
        \Filament\Notifications\Notification::make()
            ->title('Auto-refresh ' . $status)
            ->body('Dashboard auto-refresh has been ' . $status . '.')
            ->success()
            ->send();
    }

    public function exportData(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = [
            'exported_at' => now()->toIso8601String(),
            'realtime_stats' => $this->realtimeStats,
            'business_metrics' => $this->businessMetrics,
        ];

        $filename = 'analytics-dashboard-' . now()->format('Y-m-d-H-i-s') . '.json';
        
        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT);
        }, $filename);
    }

    protected function getActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh Data')
                ->tooltip('Update dashboard with latest data')
                ->icon('heroicon-o-arrow-path')
                ->action('refresh'),
                
            Actions\Action::make('export')
                ->label('Export Data')
                ->tooltip('Export analytics data as JSON')
                ->icon('heroicon-o-arrow-down-tray')
                ->action('exportData'),
                
            Actions\Action::make('toggle-refresh')
                ->label('Toggle Auto-refresh')
                ->tooltip('Enable/disable automatic data refresh')
                ->icon('heroicon-o-clock')
                ->action('toggleAutoRefresh'),
        ];
    }

    protected function handleMetricError(string $metric, \Exception $e): void
    {
        \Log::error("Analytics dashboard metric error: {$metric}", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Send notification to admin if critical metric fails
        if (in_array($metric, ['realtime_stats', 'business_metrics'])) {
            \Filament\Notifications\Notification::make()
                ->title('Analytics Error')
                ->body("Failed to load {$metric} data")
                ->danger()
                ->send();
        }
    }

    public function getViewData(): array
    {
        return [
            'realtimeStats' => $this->realtimeStats,
            'businessMetrics' => $this->businessMetrics,
            'autoRefresh' => $this->autoRefresh,
            'refreshInterval' => $this->refreshInterval,
        ];
    }
}