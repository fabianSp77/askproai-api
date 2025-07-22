<?php

namespace App\Http\Controllers\Portal\Api;

use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Company;
use App\Models\PrepaidBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardApiControllerEnhanced extends BaseApiController
{
    public function index(Request $request)
    {
        $user = Auth::guard('portal')->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $companyId = $user->company_id;
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        // Get today's stats
        $stats = $this->getStats($companyId, $today);
        
        // Get trends by comparing with yesterday
        $yesterdayStats = $this->getStats($companyId, $yesterday);
        $trends = $this->calculateTrends($stats, $yesterdayStats);
        
        // Get billing info
        $billing = $this->getBillingInfo($companyId);
        
        // Get chart data for last 7 days
        $charts = $this->getChartData($companyId);
        
        // Get recent activity
        $recentActivity = $this->getRecentActivity($companyId);
        
        return response()->json([
            'stats' => [
                'calls_today' => $stats['calls'],
                'calls_trend' => ['value' => $trends['calls']],
                'appointments_today' => $stats['appointments'],
                'appointments_trend' => ['value' => $trends['appointments']],
                'new_customers' => $stats['customers'],
                'customers_trend' => ['value' => $trends['customers']],
            ],
            'billing' => $billing,
            'charts' => $charts,
            'recent_activity' => $recentActivity
        ]);
    }
    
    private function getStats($companyId, $date)
    {
        $endDate = $date->copy()->endOfDay();
        
        return [
            'calls' => Call::where('company_id', $companyId)
                ->whereBetween('created_at', [$date, $endDate])
                ->count(),
                
            'appointments' => Appointment::where('company_id', $companyId)
                ->whereDate('starts_at', $date->toDateString())
                ->count(),
                
            'customers' => Customer::where('company_id', $companyId)
                ->whereBetween('created_at', [$date, $endDate])
                ->count()
        ];
    }
    
    private function calculateTrends($current, $previous)
    {
        $trends = [];
        
        foreach (['calls', 'appointments', 'customers'] as $metric) {
            $currentValue = $current[$metric] ?? 0;
            $previousValue = $previous[$metric] ?? 0;
            
            if ($previousValue > 0) {
                $change = (($currentValue - $previousValue) / $previousValue) * 100;
                $trends[$metric] = round($change);
            } else {
                $trends[$metric] = $currentValue > 0 ? 100 : 0;
            }
        }
        
        return $trends;
    }
    
    private function getBillingInfo($companyId)
    {
        $balance = PrepaidBalance::where('company_id', $companyId)->first();
        
        if (!$balance) {
            return [
                'current_balance' => 0,
                'bonus_balance' => 0,
                'estimated_calls' => 0
            ];
        }
        
        // Estimate calls based on average rate (assuming 0.50€ per call)
        $avgCallCost = 0.50;
        $totalBalance = $balance->balance + $balance->bonus_balance;
        $estimatedCalls = $totalBalance > 0 ? floor($totalBalance / $avgCallCost) : 0;
        
        return [
            'current_balance' => $balance->balance,
            'bonus_balance' => $balance->bonus_balance,
            'estimated_calls' => $estimatedCalls
        ];
    }
    
    private function getChartData($companyId)
    {
        $days = 7;
        $labels = [];
        $callData = [];
        $appointmentData = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('D'); // Mo, Di, Mi, etc.
            
            // Count calls for this day
            $callData[] = Call::where('company_id', $companyId)
                ->whereDate('created_at', $date->toDateString())
                ->count();
                
            // Count appointments for this day
            $appointmentData[] = Appointment::where('company_id', $companyId)
                ->whereDate('starts_at', $date->toDateString())
                ->count();
        }
        
        return [
            'calls' => [
                'labels' => $labels,
                'data' => $callData
            ],
            'appointments' => [
                'labels' => $labels,
                'data' => $appointmentData
            ]
        ];
    }
    
    private function getRecentActivity($companyId, $limit = 10)
    {
        $activities = [];
        
        // Get recent calls
        $recentCalls = Call::where('company_id', $companyId)
            ->with('customer')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        foreach ($recentCalls as $call) {
            $activities[] = [
                'title' => 'Neuer Anruf von ' . ($call->from_number ?? 'Unbekannt'),
                'time' => $call->created_at->toIso8601String(),
                'icon' => 'fas fa-phone',
                'color' => 'bg-blue-100',
                'iconColor' => 'text-blue-600',
                'type' => 'call',
                'id' => $call->id
            ];
        }
        
        // Get recent appointments
        $recentAppointments = Appointment::where('company_id', $companyId)
            ->with(['customer', 'service'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        foreach ($recentAppointments as $appointment) {
            $customerName = $appointment->customer->name ?? 'Unbekannt';
            $time = Carbon::parse($appointment->starts_at)->format('H:i');
            
            $activities[] = [
                'title' => "Termin gebucht: {$customerName} um {$time}",
                'time' => $appointment->created_at->toIso8601String(),
                'icon' => 'fas fa-calendar-check',
                'color' => 'bg-green-100',
                'iconColor' => 'text-green-600',
                'type' => 'appointment',
                'id' => $appointment->id
            ];
        }
        
        // Get recent customers
        $recentCustomers = Customer::where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();
            
        foreach ($recentCustomers as $customer) {
            $activities[] = [
                'title' => 'Neuer Kunde: ' . $customer->name,
                'time' => $customer->created_at->toIso8601String(),
                'icon' => 'fas fa-user-plus',
                'color' => 'bg-purple-100',
                'iconColor' => 'text-purple-600',
                'type' => 'customer',
                'id' => $customer->id
            ];
        }
        
        // Sort by time and limit
        usort($activities, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
        
        return array_slice($activities, 0, $limit);
    }
}