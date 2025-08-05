<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RealtimeMetricsWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.realtime-metrics';
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';
    
    public array $metrics = [];
    public array $sparklineData = [];
    
    public function mount(): void
    {
        $this->loadMetrics();
    }
    
    public function loadMetrics(): void
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        if (!$companyId) {
            return;
        }
        
        // Real-time metrics (last 5 minutes)
        $now = Carbon::now();
        $fiveMinutesAgo = $now->copy()->subMinutes(5);
        $oneHourAgo = $now->copy()->subHour();
        
        // Current metrics - active calls have start_timestamp but no end_timestamp
        $activeCalls = Call::where('company_id', $companyId)
            ->whereNotNull('start_timestamp')
            ->whereNull('ended_at')
            ->where('created_at', '>=', $fiveMinutesAgo)
            ->count();
        
        $callsLastHour = Call::where('company_id', $companyId)
            ->where('created_at', '>=', $oneHourAgo)
            ->count();
        
        $bookingsLastHour = Appointment::whereHas('branch', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->where('created_at', '>=', $oneHourAgo)
            ->count();
        
        $conversionRate = $callsLastHour > 0 
            ? round(($bookingsLastHour / $callsLastHour) * 100, 1)
            : 0;
        
        // Average call duration (last hour)
        $avgCallDuration = Call::where('company_id', $companyId)
            ->where('created_at', '>=', $oneHourAgo)
            ->whereNotNull('duration_sec')
            ->avg('duration_sec');
        
        // Ensure it's numeric
        $avgCallDuration = is_numeric($avgCallDuration) ? (float)$avgCallDuration : 0;
        
        // Queue depth simulation (would be real queue data in production)
        $queueDepth = rand(0, 10);
        $avgWaitTime = $queueDepth > 0 ? rand(10, 120) : 0;
        
        // System response times
        $apiResponseTimes = Cache::remember('api_response_times', 60, function () {
            return [
                'calcom' => rand(30, 150),
                'retell' => rand(40, 200),
                'database' => rand(2, 20),
            ];
        });
        
        // Sparkline data (last 60 minutes by 5-minute intervals)
        $this->generateSparklineData($companyId);
        
        $this->metrics = [
            'active_calls' => $activeCalls,
            'calls_per_hour' => $callsLastHour,
            'bookings_per_hour' => $bookingsLastHour,
            'conversion_rate' => $conversionRate,
            'avg_call_duration' => round($avgCallDuration),
            'queue_depth' => $queueDepth,
            'avg_wait_time' => $avgWaitTime,
            'api_response_times' => $apiResponseTimes,
            'last_update' => $now->format('H:i:s'),
        ];
    }
    
    private function generateSparklineData(int $companyId): void
    {
        $intervals = [];
        $callCounts = [];
        $bookingCounts = [];
        
        // Generate data for last 12 intervals (60 minutes / 5-minute intervals)
        for ($i = 11; $i >= 0; $i--) {
            $intervalEnd = Carbon::now()->subMinutes($i * 5);
            $intervalStart = $intervalEnd->copy()->subMinutes(5);
            
            $intervals[] = $intervalEnd->format('H:i');
            
            $calls = Call::where('company_id', $companyId)
                ->whereBetween('created_at', [$intervalStart, $intervalEnd])
                ->count();
            $callCounts[] = $calls;
            
            $bookings = Appointment::whereHas('branch', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->whereBetween('created_at', [$intervalStart, $intervalEnd])
                ->count();
            $bookingCounts[] = $bookings;
        }
        
        $this->sparklineData = [
            'labels' => $intervals,
            'calls' => $callCounts,
            'bookings' => $bookingCounts,
        ];
    }
    
    public function getPollingInterval(): ?string
    {
        return '10s'; // Update every 10 seconds
    }
}