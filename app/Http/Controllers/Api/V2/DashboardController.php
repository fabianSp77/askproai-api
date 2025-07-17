<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        $companyId = $user->company_id;
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $thisWeek = Carbon::now()->startOfWeek();
        $lastWeek = Carbon::now()->subWeek()->startOfWeek();

        // Today's appointments
        $todayAppointments = Appointment::where('company_id', $companyId)
            ->whereDate('start_time', $today)
            ->count();

        $yesterdayAppointments = Appointment::where('company_id', $companyId)
            ->whereDate('start_time', $yesterday)
            ->count();

        // Today's calls
        $todayCalls = Call::where('company_id', $companyId)
            ->whereDate('created_at', $today)
            ->count();

        $yesterdayCalls = Call::where('company_id', $companyId)
            ->whereDate('created_at', $yesterday)
            ->count();

        // New customers this week
        $newCustomersThisWeek = Customer::where('company_id', $companyId)
            ->where('created_at', '>=', $thisWeek)
            ->count();

        $newCustomersLastWeek = Customer::where('company_id', $companyId)
            ->whereBetween('created_at', [$lastWeek, $thisWeek])
            ->count();

        // Revenue (mock data for now)
        $todayRevenue = Appointment::where('company_id', $companyId)
            ->whereDate('start_time', $today)
            ->where('status', 'completed')
            ->sum('price') ?? 0;

        $yesterdayRevenue = Appointment::where('company_id', $companyId)
            ->whereDate('start_time', $yesterday)
            ->where('status', 'completed')
            ->sum('price') ?? 0;

        // Success rate
        $totalCallsToday = $todayCalls ?: 1; // Prevent division by zero
        $successfulCallsToday = Call::where('company_id', $companyId)
            ->whereDate('created_at', $today)
            ->where('appointment_booked', true)
            ->count();

        $successRate = ($successfulCallsToday / $totalCallsToday) * 100;

        // Average call duration
        $avgCallDuration = Call::where('company_id', $companyId)
            ->whereDate('created_at', $today)
            ->whereNotNull('duration')
            ->avg('duration') ?? 0;

        return response()->json([
            'appointments' => [
                'today' => $todayAppointments,
                'change' => $this->calculatePercentageChange($todayAppointments, $yesterdayAppointments),
                'changeType' => $todayAppointments >= $yesterdayAppointments ? 'increase' : 'decrease',
            ],
            'calls' => [
                'today' => $todayCalls,
                'change' => $this->calculatePercentageChange($todayCalls, $yesterdayCalls),
                'changeType' => $todayCalls >= $yesterdayCalls ? 'increase' : 'decrease',
            ],
            'customers' => [
                'new' => $newCustomersThisWeek,
                'change' => $this->calculatePercentageChange($newCustomersThisWeek, $newCustomersLastWeek),
                'changeType' => $newCustomersThisWeek >= $newCustomersLastWeek ? 'increase' : 'decrease',
            ],
            'revenue' => [
                'today' => round($todayRevenue, 2),
                'change' => $this->calculatePercentageChange($todayRevenue, $yesterdayRevenue),
                'changeType' => $todayRevenue >= $yesterdayRevenue ? 'increase' : 'decrease',
                'currency' => 'EUR',
            ],
            'performance' => [
                'successRate' => round($successRate, 1),
                'avgCallDuration' => round($avgCallDuration),
            ],
        ]);
    }

    /**
     * Get recent activity
     */
    public function recentActivity(Request $request)
    {
        $user = $request->user();
        $companyId = $user->company_id;
        $limit = $request->input('limit', 10);

        // Get recent calls
        $recentCalls = Call::where('company_id', $companyId)
            ->with(['customer:id,first_name,last_name,phone'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'type' => 'call',
                    'description' => 'Anruf von ' . $call->from_number,
                    'detail' => $call->appointment_booked 
                        ? 'Termin gebucht' 
                        : ($call->status === 'no-answer' ? 'Verpasst' : 'Information gegeben'),
                    'status' => $call->appointment_booked ? 'success' : ($call->status === 'no-answer' ? 'error' : 'info'),
                    'time' => $call->created_at,
                    'relativeTime' => $call->created_at->diffForHumans(),
                ];
            });

        // Get recent appointments
        $recentAppointments = Appointment::where('company_id', $companyId)
            ->with(['customer:id,first_name,last_name', 'service:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'type' => 'appointment',
                    'description' => 'Neuer Termin erstellt',
                    'detail' => $appointment->customer->first_name . ' ' . $appointment->customer->last_name . 
                                ' - ' . $appointment->service->name,
                    'status' => 'success',
                    'time' => $appointment->created_at,
                    'relativeTime' => $appointment->created_at->diffForHumans(),
                ];
            });

        // Merge and sort by time
        $activities = $recentCalls->concat($recentAppointments)
            ->sortByDesc('time')
            ->take($limit)
            ->values();

        return response()->json([
            'data' => $activities,
            'total' => $activities->count(),
        ]);
    }

    /**
     * Get analytics data
     */
    public function analytics(Request $request)
    {
        $user = $request->user();
        $companyId = $user->company_id;
        $period = $request->input('period', 'week'); // week, month, year
        
        $startDate = match($period) {
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->subMonth(),
            'year' => Carbon::now()->subYear(),
            default => Carbon::now()->subWeek(),
        };

        // Calls by day
        $callsByDay = Call::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(appointment_booked) as appointments_booked')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'calls' => $item->total,
                    'appointments' => $item->appointments_booked ?? 0,
                ];
            });

        // Peak hours
        $peakHours = Call::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'hour' => $item->hour . ':00',
                    'count' => $item->count,
                ];
            });

        // Service popularity
        $serviceStats = Appointment::where('appointments.company_id', $companyId)
            ->where('appointments.created_at', '>=', $startDate)
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->select(
                'services.name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(appointments.price) as revenue')
            )
            ->groupBy('services.id', 'services.name')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'period' => $period,
            'callsByDay' => $callsByDay,
            'peakHours' => $peakHours,
            'topServices' => $serviceStats,
        ]);
    }

    /**
     * Calculate percentage change
     */
    private function calculatePercentageChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }
}