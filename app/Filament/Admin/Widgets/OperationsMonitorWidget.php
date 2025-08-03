<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\Call;
use App\Models\Appointment;
use App\Services\MetricsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OperationsMonitorWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.operations-monitor-widget-v2';
    protected int | string | array $columnSpan = [
        'default' => 'full',  // Mobile: volle Breite
        'sm' => 'full',       // Small tablets: volle Breite
        'md' => 'full',       // Tablets: volle Breite
        'lg' => 'full',       // Desktop: volle Breite (wichtige Übersicht)
        'xl' => 'full',       // Large screens: volle Breite
    ];
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s'; // Live updates every 5 seconds
    
    public ?int $companyId = null;
    
    protected function getListeners(): array
    {
        return [
            'branchFilterUpdated' => 'handleBranchFilterUpdate',
        ];
    }
    
    public function mount(): void
    {
        $this->companyId = auth()->user()->company_id;
    }
    
    public function handleBranchFilterUpdate($branchId): void
    {
        // Handle branch filter updates
    }
    
    protected function getViewData(): array
    {
        $cacheKey = "operations_monitor_{$this->companyId}_" . now()->format('Y-m-d-H-i');
        
        $data = Cache::remember($cacheKey, 5, function () {
            return [
                'systemHealth' => $this->getSystemHealth(),
                'liveCalls' => $this->getLiveCallMetrics(),
                'conversion' => $this->getConversionMetrics(),
                'costPerBooking' => $this->getCostPerBooking(),
                'anomalies' => $this->detectAnomalies(),
            ];
        });
        
        return $data;
    }
    
    protected function getSystemHealth(): array
    {
        $health = [
            'calcom' => $this->checkCalcomHealth(),
            'retell' => $this->checkRetellHealth(),
            'database' => $this->checkDatabaseHealth(),
            'overall' => 'operational',
        ];
        
        // Determine overall health
        if (!$health['calcom']['status'] || !$health['retell']['status']) {
            $health['overall'] = 'degraded';
        }
        
        if (!$health['calcom']['status'] && !$health['retell']['status']) {
            $health['overall'] = 'down';
        }
        
        return $health;
    }
    
    protected function checkCalcomHealth(): array
    {
        try {
            $start = microtime(true);
            
            // Quick health check to Cal.com API
            $response = Http::timeout(2)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.calcom.api_key'),
                ])
                ->get('https://api.cal.com/v1/me');
            
            $responseTime = round((microtime(true) - $start) * 1000); // in ms
            
            return [
                'status' => $response->successful(),
                'responseTime' => $responseTime,
                'message' => $response->successful() ? 'Operational' : 'API Error',
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'responseTime' => 0,
                'message' => 'Connection failed',
            ];
        }
    }
    
    protected function checkRetellHealth(): array
    {
        try {
            $start = microtime(true);
            
            // Quick health check to Retell.ai API
            $response = Http::timeout(2)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.retell.api_key'),
                ])
                ->get('https://api.retellai.com/agent/list');
            
            $responseTime = round((microtime(true) - $start) * 1000); // in ms
            
            return [
                'status' => $response->successful(),
                'responseTime' => $responseTime,
                'message' => $response->successful() ? 'Operational' : 'API Error',
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'responseTime' => 0,
                'message' => 'Connection failed',
            ];
        }
    }
    
    protected function checkDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            \DB::select('SELECT 1');
            $responseTime = round((microtime(true) - $start) * 1000);
            
            return [
                'status' => true,
                'responseTime' => $responseTime,
                'message' => 'Connected',
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'responseTime' => 0,
                'message' => 'Connection failed',
            ];
        }
    }
    
    protected function getLiveCallMetrics(): array
    {
        $now = Carbon::now();
        
        // Active calls (calls without end_timestamp or ended within last minute)
        $activeCalls = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->where(function ($q) use ($now) {
                $q->whereNull('end_timestamp')
                    ->orWhere('end_timestamp', '>', $now->subMinute());
            })
            ->count();
        
        // Average call duration today
        $avgDuration = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereDate('created_at', today())
            ->whereNotNull('duration_sec')
            ->avg('duration_sec') ?? 0;
        
        // Find anomalies (calls > 3 minutes)
        $longCalls = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereDate('created_at', today())
            ->where('duration_sec', '>', 180) // 3 minutes
            ->with('branch')
            ->get()
            ->map(fn($call) => [
                'branch' => $call->branch?->name ?? 'Unknown',
                'duration' => gmdate('i:s', $call->duration_sec),
                'cost' => $call->cost ?? 0,
            ]);
        
        return [
            'active' => $activeCalls,
            'avgDuration' => gmdate('i:s', $avgDuration),
            'avgDurationSeconds' => $avgDuration,
            'anomalies' => $longCalls,
        ];
    }
    
    protected function getConversionMetrics(): array
    {
        $today = Carbon::today();
        
        // Total calls today
        $totalCalls = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereDate('created_at', $today)
            ->count();
        
        // Calls that resulted in appointments
        $convertedCalls = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereDate('created_at', $today)
            ->whereNotNull('appointment_id')
            ->count();
        
        $conversionRate = $totalCalls > 0 
            ? round(($convertedCalls / $totalCalls) * 100, 1)
            : 0;
        
        // Compare with yesterday
        $yesterdayRate = $this->getYesterdayConversionRate();
        $trend = $conversionRate - $yesterdayRate;
        
        return [
            'rate' => $conversionRate,
            'trend' => $trend,
            'totalCalls' => $totalCalls,
            'convertedCalls' => $convertedCalls,
        ];
    }
    
    protected function getYesterdayConversionRate(): float
    {
        $yesterday = Carbon::yesterday();
        
        $totalCalls = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereDate('created_at', $yesterday)
            ->count();
        
        $convertedCalls = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereDate('created_at', $yesterday)
            ->whereNotNull('appointment_id')
            ->count();
        
        return $totalCalls > 0 
            ? round(($convertedCalls / $totalCalls) * 100, 1)
            : 0;
    }
    
    protected function getCostPerBooking(): array
    {
        $today = Carbon::today();
        
        // Total call costs today
        $totalCosts = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereDate('created_at', $today)
            ->sum('cost') ?? 0;
        
        // Total bookings today
        $totalBookings = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereDate('created_at', $today)
            ->whereNotNull('appointment_id')
            ->count();
        
        $costPerBooking = $totalBookings > 0 
            ? round($totalCosts / $totalBookings, 2)
            : 0;
        
        return [
            'cost' => $costPerBooking,
            'totalCosts' => $totalCosts,
            'totalBookings' => $totalBookings,
        ];
    }
    
    protected function detectAnomalies(): array
    {
        $anomalies = [];
        
        // Check for branches with high average call duration
        $branchStats = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereDate('created_at', today())
            ->whereNotNull('branch_id')
            ->with('branch')
            ->selectRaw('branch_id, AVG(duration_sec) as avg_duration, COUNT(*) as call_count')
            ->groupBy('branch_id')
            ->having('avg_duration', '>', 180) // More than 3 minutes average
            ->get();
        
        foreach ($branchStats as $stat) {
            if ($stat->branch) {
                $anomalies[] = [
                    'type' => 'high_duration',
                    'severity' => 'warning',
                    'branch' => $stat->branch->name,
                    'value' => gmdate('i:s', $stat->avg_duration),
                    'message' => "Durchschnittliche Anrufdauer über 3 Minuten",
                ];
            }
        }
        
        // Check for low conversion branches
        $branchConversions = \DB::table('calls')
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereDate('created_at', today())
            ->whereNotNull('branch_id')
            ->selectRaw('
                branch_id,
                COUNT(*) as total_calls,
                SUM(CASE WHEN appointment_id IS NOT NULL THEN 1 ELSE 0 END) as converted_calls
            ')
            ->groupBy('branch_id')
            ->get();
        
        foreach ($branchConversions as $stat) {
            $conversionRate = $stat->total_calls > 0 
                ? ($stat->converted_calls / $stat->total_calls) * 100
                : 0;
                
            if ($conversionRate < 25 && $stat->total_calls >= 5) {
                $branch = \App\Models\Branch::find($stat->branch_id);
                if ($branch) {
                    $anomalies[] = [
                        'type' => 'low_conversion',
                        'severity' => 'critical',
                        'branch' => $branch->name,
                        'value' => round($conversionRate, 1) . '%',
                        'message' => "Conversion Rate unter 25%",
                    ];
                }
            }
        }
        
        return $anomalies;
    }
}