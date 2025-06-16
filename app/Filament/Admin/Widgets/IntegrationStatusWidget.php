<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Integration;
use App\Models\Call;
use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class IntegrationStatusWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '60s';
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    
    protected function getStats(): array
    {
        return [
            $this->getActiveIntegrationsStat(),
            $this->getApiUsageStat(),
            $this->getIntegrationHealthStat(),
            $this->getSyncStatusStat(),
        ];
    }
    
    private function getActiveIntegrationsStat(): Stat
    {
        $totalIntegrations = Integration::count();
        $activeIntegrations = Integration::where('active', true)->count();
        
        // Count by type
        $byType = Integration::where('active', true)
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
        
        $description = [];
        foreach ($byType as $type => $count) {
            $description[] = match($type) {
                'calendar' => "📅 $count",
                'phone_ai' => "📞 $count",
                'payment' => "💳 $count",
                'email' => "✉️ $count",
                default => "$count"
            };
        }
        
        return Stat::make('🔌 Aktive Integrationen', $activeIntegrations . ' von ' . $totalIntegrations)
            ->description(implode(' • ', $description))
            ->chart($this->getIntegrationActivityChart())
            ->color($activeIntegrations > 0 ? 'success' : 'danger');
    }
    
    private function getApiUsageStat(): Stat
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        
        // Simulate API usage based on calls and appointments
        $todayUsage = Call::whereDate('created_at', $today)->count() * 2 + // Retell API calls
                      Appointment::whereDate('created_at', $today)->count() * 3; // Cal.com API calls
        
        $monthUsage = Call::where('created_at', '>=', $thisMonth)->count() * 2 +
                      Appointment::where('created_at', '>=', $thisMonth)->count() * 3;
        
        // Check if approaching any limits
        $monthlyLimit = 10000; // Example limit
        $usagePercentage = ($monthUsage / $monthlyLimit) * 100;
        
        return Stat::make('📊 API-Nutzung', number_format($todayUsage) . ' heute')
            ->description(sprintf(
                'Monat: %s von %s (%.1f%%)',
                number_format($monthUsage),
                number_format($monthlyLimit),
                $usagePercentage
            ))
            ->chart($this->getDailyApiUsageChart())
            ->color($usagePercentage < 80 ? 'success' : ($usagePercentage < 95 ? 'warning' : 'danger'));
    }
    
    private function getIntegrationHealthStat(): Stat
    {
        $healthy = Integration::where('health_status', 'healthy')->where('active', true)->count();
        $warning = Integration::where('health_status', 'warning')->where('active', true)->count();
        $error = Integration::where('health_status', 'error')->where('active', true)->count();
        $total = Integration::where('active', true)->count();
        
        $healthRate = $total > 0 ? round(($healthy / $total) * 100, 1) : 0;
        
        // Recent errors
        $recentErrors = Integration::where('health_status', 'error')
            ->where('active', true)
            ->where('updated_at', '>=', Carbon::now()->subHours(24))
            ->count();
        
        return Stat::make('🏥 System-Gesundheit', $healthRate . '% OK')
            ->description(sprintf(
                '✅ %d OK • ⚠️ %d Warnung • ❌ %d Fehler',
                $healthy,
                $warning,
                $error
            ))
            ->chart($this->getHealthTrendChart())
            ->color($healthRate > 90 ? 'success' : ($healthRate > 70 ? 'warning' : 'danger'))
            ->extraAttributes([
                'class' => $error > 0 ? 'ring-2 ring-danger-500/20' : ''
            ]);
    }
    
    private function getSyncStatusStat(): Stat
    {
        $lastHour = Carbon::now()->subHour();
        $last24Hours = Carbon::now()->subHours(24);
        
        // Count recent syncs
        $recentSyncs = Integration::where('last_sync', '>=', $lastHour)->count();
        $todaySyncs = Integration::where('last_sync', '>=', $last24Hours)->count();
        
        // Find oldest unsync'd integration
        $oldestUnsync = Integration::where('active', true)
            ->whereNotNull('last_sync')
            ->orderBy('last_sync')
            ->first();
        
        $syncAge = $oldestUnsync ? $oldestUnsync->last_sync->diffForHumans() : 'N/A';
        
        return Stat::make('🔄 Synchronisation', $recentSyncs . ' in letzter Stunde')
            ->description(sprintf(
                'Heute: %d • Älteste: %s',
                $todaySyncs,
                $syncAge
            ))
            ->chart($this->getSyncActivityChart())
            ->color($recentSyncs > 0 ? 'success' : 'warning');
    }
    
    // Chart generation methods
    private function getIntegrationActivityChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            // Count API calls per day
            $calls = Call::whereDate('created_at', $date)->count();
            $appointments = Appointment::whereDate('created_at', $date)->count();
            $data[] = $calls + $appointments;
        }
        return $data;
    }
    
    private function getDailyApiUsageChart(): array
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $usage = Call::whereDate('created_at', $date)->count() * 2 +
                    Appointment::whereDate('created_at', $date)->count() * 3;
            $data[] = $usage;
        }
        return $data;
    }
    
    private function getHealthTrendChart(): array
    {
        // Simulated health trend
        $data = [];
        for ($i = 23; $i >= 0; $i--) {
            $hour = Carbon::now()->subHours($i);
            $healthy = Integration::where('active', true)
                ->where('health_status', 'healthy')
                ->count();
            $total = Integration::where('active', true)->count();
            $data[] = $total > 0 ? round(($healthy / $total) * 100) : 0;
        }
        return $data;
    }
    
    private function getSyncActivityChart(): array
    {
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $hour = Carbon::now()->subHours($i);
            $syncs = Integration::whereBetween('last_sync', [
                $hour->copy()->startOfHour(),
                $hour->copy()->endOfHour()
            ])->count();
            $data[] = $syncs;
        }
        return $data;
    }
}