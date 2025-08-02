<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use App\Models\Appointment;
use App\Models\ApiCallLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CompactOperationsWidget extends FilterableWidget
{
    protected static string $view = 'filament.admin.widgets.compact-operations-widget';
    protected int | string | array $columnSpan = [
        'default' => 'full',  // Mobile: volle Breite
        'sm' => 'full',       // Small tablets: volle Breite
        'md' => 'full',       // Tablets: volle Breite
        'lg' => 2,            // Desktop: 2 columns (50%)
        'xl' => 2,            // Large screens: 2 columns
    ];
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = null; // Disabled for performance
    
    public function mount(): void
    {
        parent::mount();
        // companyId is now set by parent FilterableWidget
    }
    
    protected function getViewData(): array
    {
        $now = Carbon::now();
        $startOfDay = $now->copy()->startOfDay();
        
        return array_merge(parent::getViewData(), [
            'systemHealth' => $this->getSystemHealth(),
            'liveCalls' => $this->getLiveCallsData(),
            'conversion' => $this->getConversionData($startOfDay, $now),
            'costPerBooking' => $this->getCostPerBookingData($startOfDay, $now),
            'anomalies' => collect(), // Simplified for compact view
        ]);
    }
    
    private function getSystemHealth(): array
    {
        $calcomStatus = Cache::remember('health_check_calcom', 30, function () {
            try {
                $start = microtime(true);
                $response = Http::timeout(5)->get('https://api.cal.com/v1/health');
                $responseTime = round((microtime(true) - $start) * 1000);
                
                return [
                    'status' => $response->successful(),
                    'responseTime' => $responseTime,
                ];
            } catch (\Exception $e) {
                return ['status' => false, 'responseTime' => 0];
            }
        });
        
        $retellStatus = Cache::remember('health_check_retell', 30, function () {
            try {
                $start = microtime(true);
                $response = Http::timeout(5)
                    ->withHeaders(['Authorization' => 'Bearer ' . config('services.retell.api_key')])
                    ->get('https://api.retellai.com/list-agent');
                $responseTime = round((microtime(true) - $start) * 1000);
                
                return [
                    'status' => $response->successful(),
                    'responseTime' => $responseTime,
                ];
            } catch (\Exception $e) {
                return ['status' => false, 'responseTime' => 0];
            }
        });
        
        $overall = ($calcomStatus['status'] && $retellStatus['status']) ? 'operational' 
            : (!$calcomStatus['status'] && !$retellStatus['status'] ? 'offline' : 'degraded');
        
        return [
            'overall' => $overall,
            'calcom' => $calcomStatus,
            'retell' => $retellStatus,
        ];
    }
    
    private function getLiveCallsData(): array
    {
        $query = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId));
            
        // Apply filters
        $query = $this->applyBranchFilter($query);
        
        $activeCalls = $query->whereNull('end_timestamp')->count();
        
        // Get last 10 data points for sparkline
        $sparklineData = Cache::remember('call_sparkline_' . $this->companyId, 60, function () {
            $data = [];
            for ($i = 9; $i >= 0; $i--) {
                $time = Carbon::now()->subMinutes($i * 5);
                $count = Call::query()
                    ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
                    ->where('created_at', '>=', $time->subMinutes(5))
                    ->where('created_at', '<', $time)
                    ->count();
                $data[] = $count;
            }
            
            $max = max($data) ?: 1;
            return array_map(fn($v) => round(($v / $max) * 100), $data);
        });
        
        $avgDuration = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereDate('created_at', today())
            ->whereNotNull('duration_sec')
            ->avg('duration_sec') ?: 0;
        
        $anomalies = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereDate('created_at', today())
            ->where('duration_sec', '>', 300)
            ->with('branch:id,name')
            ->get();
        
        return [
            'active' => $activeCalls,
            'avgDuration' => gmdate('i:s', $avgDuration),
            'sparkline' => $sparklineData,
            'anomalies' => $anomalies,
        ];
    }
    
    private function getConversionData(Carbon $startDate, Carbon $endDate): array
    {
        $totalCalls = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        
        $convertedCalls = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('appointment_id')
            ->count();
        
        $rate = $totalCalls > 0 ? round(($convertedCalls / $totalCalls) * 100, 1) : 0;
        
        // Calculate trend
        $yesterdayTotal = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereDate('created_at', today()->subDay())
            ->count();
        
        $yesterdayConverted = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereDate('created_at', today()->subDay())
            ->whereNotNull('appointment_id')
            ->count();
        
        $yesterdayRate = $yesterdayTotal > 0 ? round(($yesterdayConverted / $yesterdayTotal) * 100, 1) : 0;
        $trend = $rate - $yesterdayRate;
        
        return [
            'rate' => $rate,
            'totalCalls' => $totalCalls,
            'convertedCalls' => $convertedCalls,
            'trend' => round($trend, 1),
        ];
    }
    
    private function getCostPerBookingData(Carbon $startDate, Carbon $endDate): array
    {
        $totalCost = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('cost') ?: 0;
        
        $totalBookings = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('appointment_id')
            ->count();
        
        $costPerBooking = $totalBookings > 0 ? round($totalCost / $totalBookings, 2) : 0;
        
        return [
            'cost' => $costPerBooking,
            'totalCosts' => $totalCost,
            'totalBookings' => $totalBookings,
        ];
    }
}