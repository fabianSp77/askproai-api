<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Call;
use App\Models\Transaction;
use App\Models\Appointment;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the customer dashboard
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $tenant = $user->tenant;
        
        // Cache key for dashboard data
        $cacheKey = "dashboard.{$tenant->id}";
        
        // Get dashboard data with caching
        $data = Cache::remember($cacheKey, 300, function () use ($tenant) {
            return [
                'recentCalls' => $this->getRecentCalls($tenant),
                'upcomingAppointments' => $this->getUpcomingAppointments($tenant),
                'monthlyStats' => $this->getMonthlyStats($tenant),
                'balanceHistory' => $this->getBalanceHistory($tenant),
                'quickStats' => $this->getQuickStats($tenant)
            ];
        });
        
        // Add real-time data
        $data['currentBalance'] = $tenant->balance_cents;
        $data['autoTopupEnabled'] = $tenant->settings['auto_topup_enabled'] ?? false;
        $data['autoTopupThreshold'] = $tenant->settings['auto_topup_threshold'] ?? 1000;
        
        return view('customer.dashboard', $data);
    }
    
    /**
     * Get recent calls for dashboard
     */
    protected function getRecentCalls($tenant)
    {
        return Call::where('tenant_id', $tenant->id)
            ->with('customer:id,name,phone')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'customer_name' => $call->customer->name ?? 'Unbekannt',
                    'duration' => $this->formatDuration($call->duration_seconds),
                    'cost' => $call->cost_cents / 100,
                    'created_at' => $call->created_at,
                    'status' => $call->status,
                    'recording_available' => !empty($call->recording_url)
                ];
            });
    }
    
    /**
     * Get upcoming appointments
     */
    protected function getUpcomingAppointments($tenant)
    {
        return Appointment::where('tenant_id', $tenant->id)
            ->where('start_time', '>', now())
            ->with(['customer:id,name', 'service:id,name', 'staff:id,name'])
            ->orderBy('start_time')
            ->limit(5)
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'customer_name' => $appointment->customer->name ?? 'Unbekannt',
                    'service_name' => $appointment->service->name ?? 'Service',
                    'staff_name' => $appointment->staff->name ?? 'Mitarbeiter',
                    'start_time' => $appointment->start_time,
                    'duration' => $appointment->duration_minutes,
                    'status' => $appointment->status
                ];
            });
    }
    
    /**
     * Get monthly statistics
     */
    protected function getMonthlyStats($tenant)
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        // Call statistics
        $callStats = Call::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->selectRaw('COUNT(*) as total_calls')
            ->selectRaw('SUM(duration_seconds) as total_duration')
            ->selectRaw('SUM(cost_cents) as total_cost')
            ->first();
        
        // Transaction statistics
        $topups = Transaction::where('tenant_id', $tenant->id)
            ->where('type', 'topup')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount_cents');
        
        $usage = Transaction::where('tenant_id', $tenant->id)
            ->where('type', 'usage')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount_cents');
        
        return [
            'calls' => [
                'count' => $callStats->total_calls ?? 0,
                'duration' => $callStats->total_duration ?? 0,
                'cost' => ($callStats->total_cost ?? 0) / 100
            ],
            'billing' => [
                'topups' => $topups / 100,
                'usage' => abs($usage) / 100,
                'net' => ($topups - abs($usage)) / 100
            ],
            'period' => [
                'start' => $startOfMonth->format('d.m.Y'),
                'end' => $endOfMonth->format('d.m.Y'),
                'days_remaining' => now()->diffInDays($endOfMonth)
            ]
        ];
    }
    
    /**
     * Get balance history for chart
     */
    protected function getBalanceHistory($tenant)
    {
        $days = 30;
        $history = [];
        
        // Get transactions for the last 30 days
        $transactions = Transaction::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at')
            ->get();
        
        // Group by day and calculate daily balance
        $dailyBalances = [];
        $currentBalance = $tenant->balance_cents;
        
        // Work backwards from current balance
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayTransactions = $transactions->filter(function ($t) use ($date) {
                return $t->created_at->format('Y-m-d') === $date;
            });
            
            $dayChange = $dayTransactions->sum('amount_cents');
            $dailyBalances[$date] = $currentBalance;
            $currentBalance -= $dayChange;
        }
        
        // Format for chart
        foreach (array_reverse($dailyBalances, true) as $date => $balance) {
            $history[] = [
                'date' => Carbon::parse($date)->format('d.m'),
                'balance' => $balance / 100
            ];
        }
        
        return $history;
    }
    
    /**
     * Get quick statistics
     */
    protected function getQuickStats($tenant)
    {
        // Today's statistics
        $todayStart = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();
        
        $todayCalls = Call::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();
        
        $todaySpent = Transaction::where('tenant_id', $tenant->id)
            ->where('type', 'usage')
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->sum('amount_cents');
        
        // This week
        $weekCalls = Call::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();
        
        // Average call duration
        $avgDuration = Call::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->avg('duration_seconds');
        
        return [
            'today_calls' => $todayCalls,
            'today_spent' => abs($todaySpent) / 100,
            'week_calls' => $weekCalls,
            'avg_duration' => $avgDuration ? ceil($avgDuration / 60) : 0,
            'balance_status' => $this->getBalanceStatus($tenant->balance_cents),
            'usage_trend' => $this->getUsageTrend($tenant)
        ];
    }
    
    /**
     * Format duration in seconds to human readable
     */
    protected function formatDuration($seconds)
    {
        if ($seconds < 60) {
            return $seconds . ' Sek';
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes < 60) {
            return $minutes . ':' . str_pad($remainingSeconds, 2, '0', STR_PAD_LEFT) . ' Min';
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return $hours . ':' . str_pad($remainingMinutes, 2, '0', STR_PAD_LEFT) . ' Std';
    }
    
    /**
     * Get balance status indicator
     */
    protected function getBalanceStatus($balanceCents)
    {
        if ($balanceCents < 200) {
            return 'critical';
        } elseif ($balanceCents < 500) {
            return 'low';
        } elseif ($balanceCents < 2000) {
            return 'normal';
        } else {
            return 'healthy';
        }
    }
    
    /**
     * Calculate usage trend
     */
    protected function getUsageTrend($tenant)
    {
        $thisWeek = abs(Transaction::where('tenant_id', $tenant->id)
            ->where('type', 'usage')
            ->where('created_at', '>=', now()->startOfWeek())
            ->sum('amount_cents'));
        
        $lastWeek = abs(Transaction::where('tenant_id', $tenant->id)
            ->where('type', 'usage')
            ->whereBetween('created_at', [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek()
            ])
            ->sum('amount_cents'));
        
        if ($lastWeek == 0) {
            return ['direction' => 'stable', 'percentage' => 0];
        }
        
        $change = (($thisWeek - $lastWeek) / $lastWeek) * 100;
        
        return [
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
            'percentage' => abs(round($change, 1))
        ];
    }
}