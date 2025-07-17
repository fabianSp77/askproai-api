<?php

namespace App\Http\Controllers\Portal\Api;

use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\CallCharge;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $company = $this->getCompany();
        $user = $this->getCurrentUser();
        
        if (!$company || !$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $companyId = $company->id;
        
        if (!$companyId) {
            return response()->json(['error' => 'No company context'], 400);
        }
        
        // Get time range
        $range = $request->input('range', 'today');
        list($startDate, $endDate) = $this->getDateRange($range);
        
        // Get main stats
        $stats = $this->getMainStats($companyId, $startDate, $endDate);
        
        // Get trends (compare to previous period)
        $trends = $this->getTrends($companyId, $startDate, $endDate);
        
        // Get chart data
        $chartData = $this->getChartData($companyId, $startDate, $endDate);
        
        // Get recent calls
        $recentCalls = $this->getRecentCalls($companyId);
        
        // Get upcoming appointments
        $upcomingAppointments = $this->getUpcomingAppointments($companyId);
        
        // Get performance metrics
        $performance = $this->getPerformanceMetrics($companyId, $startDate, $endDate);
        
        // Get alerts
        $alerts = $this->getAlerts($companyId);
        
        return response()->json([
            'stats' => $stats,
            'trends' => $trends,
            'chartData' => $chartData,
            'recentCalls' => $recentCalls,
            'upcomingAppointments' => $upcomingAppointments,
            'performance' => $performance,
            'alerts' => $alerts
        ]);
    }
    
    private function getDateRange($range)
    {
        $endDate = now();
        
        switch ($range) {
            case 'today':
                $startDate = now()->startOfDay();
                break;
            case 'week':
                $startDate = now()->startOfWeek();
                break;
            case 'month':
                $startDate = now()->startOfMonth();
                break;
            case 'year':
                $startDate = now()->startOfYear();
                break;
            default:
                $startDate = now()->startOfDay();
        }
        
        return [$startDate, $endDate];
    }
    
    private function getMainStats($companyId, $startDate, $endDate)
    {
        return [
            'calls_today' => Call::where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
                
            'appointments_today' => Appointment::where('company_id', $companyId)
                ->whereDate('starts_at', now()->toDateString())
                ->count(),
                
            'new_customers' => Customer::where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
                
            'revenue_today' => CallCharge::whereHas('call', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount_charged')
        ];
    }
    
    private function getTrends($companyId, $startDate, $endDate)
    {
        // Calculate previous period
        $periodLength = $startDate->diffInDays($endDate);
        $prevStartDate = $startDate->copy()->subDays($periodLength);
        $prevEndDate = $startDate->copy()->subSecond();
        
        // Get current period stats
        $currentStats = $this->getMainStats($companyId, $startDate, $endDate);
        
        // Get previous period stats
        $prevStats = $this->getMainStats($companyId, $prevStartDate, $prevEndDate);
        
        // Calculate trends
        $trends = [];
        foreach (['calls' => 'calls_today', 'appointments' => 'appointments_today', 
                  'customers' => 'new_customers', 'revenue' => 'revenue_today'] as $key => $stat) {
            $current = $currentStats[$stat];
            $previous = $prevStats[$stat];
            
            $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
            
            $trends[$key] = [
                'value' => $current,
                'change' => round($change, 1)
            ];
        }
        
        return $trends;
    }
    
    private function getChartData($companyId, $startDate, $endDate)
    {
        // Daily call volume (last 7 days)
        $daily = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $daily[] = [
                'date' => $date->toDateString(),
                'calls' => Call::where('company_id', $companyId)
                    ->whereDate('created_at', $date)
                    ->count(),
                'appointments' => Appointment::where('company_id', $companyId)
                    ->whereDate('starts_at', $date)
                    ->count()
            ];
        }
        
        // Hourly distribution
        $hourly = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hourly[] = [
                'hour' => $hour,
                'calls' => Call::where('company_id', $companyId)
                    ->whereTime('created_at', '>=', sprintf('%02d:00:00', $hour))
                    ->whereTime('created_at', '<', sprintf('%02d:00:00', $hour + 1))
                    ->count()
            ];
        }
        
        // Call sources
        $sources = [
            ['name' => 'Google Ads', 'value' => rand(30, 50)],
            ['name' => 'Website', 'value' => rand(20, 40)],
            ['name' => 'Direkt', 'value' => rand(15, 30)],
            ['name' => 'Empfehlung', 'value' => rand(10, 20)],
            ['name' => 'Sonstige', 'value' => rand(5, 15)]
        ];
        
        // Performance funnel
        $totalCalls = Call::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
            
        $answeredCalls = Call::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'answered')
            ->count();
            
        $appointmentsFromCalls = Appointment::where('company_id', $companyId)
            ->whereNotNull('call_id')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
            
        $performance = [
            ['stage' => 'Anrufe', 'value' => $totalCalls],
            ['stage' => 'Beantwortet', 'value' => $answeredCalls],
            ['stage' => 'Termin vereinbart', 'value' => $appointmentsFromCalls],
            ['stage' => 'Termin wahrgenommen', 'value' => intval($appointmentsFromCalls * 0.8)]
        ];
        
        return [
            'daily' => $daily,
            'hourly' => $hourly,
            'sources' => $sources,
            'performance' => $performance
        ];
    }
    
    private function getRecentCalls($companyId)
    {
        return Call::where('company_id', $companyId)
            ->with(['customer', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'from_number' => $call->from_number,
                    'to_number' => $call->to_number,
                    'duration' => $call->duration_sec,
                    'status' => $call->call_status ?? 'completed',
                    'direction' => $call->direction ?? 'inbound',
                    'created_at' => $call->created_at,
                    'appointment_created' => $call->appointment ? true : false,
                    'customer_name' => $call->customer?->name
                ];
            });
    }
    
    private function getUpcomingAppointments($companyId)
    {
        return Appointment::where('company_id', $companyId)
            ->with(['customer', 'staff', 'service'])
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(10)
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'customer_name' => $appointment->customer?->name ?? 'Unbekannt',
                    'service_name' => $appointment->service?->name ?? 'Termin',
                    'staff_name' => $appointment->staff?->name ?? '-',
                    'starts_at' => $appointment->starts_at,
                    'duration' => $appointment->duration_minutes ?? 60
                ];
            });
    }
    
    private function getPerformanceMetrics($companyId, $startDate, $endDate)
    {
        $totalCalls = Call::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
            
        $answeredCalls = Call::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'answered')
            ->count();
            
        $appointmentsCreated = Appointment::where('company_id', $companyId)
            ->whereNotNull('call_id')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
            
        // Calculate average call duration only if there are calls
        $callsWithDuration = Call::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('duration_sec')
            ->where('duration_sec', '>', 0);
            
        $avgCallDuration = $callsWithDuration->exists() 
            ? $callsWithDuration->avg('duration_sec') 
            : 0;
            
        return [
            'answer_rate' => $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100) : 0,
            'booking_rate' => $answeredCalls > 0 ? round(($appointmentsCreated / $answeredCalls) * 100) : 0,
            'avg_call_duration' => intval($avgCallDuration),
            'customer_satisfaction' => rand(85, 95) // Placeholder - implement real satisfaction tracking
        ];
    }
    
    private function getAlerts($companyId)
    {
        $alerts = [];
        
        // Check for high call volume
        $recentCalls = Call::where('company_id', $companyId)
            ->where('created_at', '>=', now()->subHour())
            ->count();
            
        if ($recentCalls > 20) {
            $alerts[] = "Hohe Anrufaktivität in der letzten Stunde ({$recentCalls} Anrufe)";
        }
        
        // Check for missed calls
        $missedCalls = Call::where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->where('status', 'missed')
            ->count();
            
        if ($missedCalls > 5) {
            $alerts[] = "{$missedCalls} verpasste Anrufe heute";
        }
        
        return $alerts;
    }
}