<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SimpleAnalyticsController extends Controller
{
    /**
     * Display analytics dashboard
     */
    public function index(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            abort(401, 'Unauthorized');
        }
        
        // Get date range (default: last 30 days)
        $endDate = Carbon::now();
        $startDate = $request->has('start_date') 
            ? Carbon::parse($request->start_date) 
            : Carbon::now()->subDays(30);
            
        if ($request->has('end_date')) {
            $endDate = Carbon::parse($request->end_date);
        }
        
        // Overview Stats
        $overviewStats = [
            'total_calls' => Call::where('company_id', $user->company_id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'total_appointments' => Appointment::where('company_id', $user->company_id)
                ->whereBetween('starts_at', [$startDate, $endDate])
                ->count(),
            'new_customers' => Customer::where('company_id', $user->company_id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'conversion_rate' => $this->calculateConversionRate($user->company_id, $startDate, $endDate),
        ];
        
        // Appointment Status Distribution
        $appointmentStatus = Appointment::where('company_id', $user->company_id)
            ->whereBetween('starts_at', [$startDate, $endDate])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
            
        // Daily Call Volume (last 7 days)
        $dailyCalls = Call::where('company_id', $user->company_id)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('d.m'),
                    'count' => $item->count
                ];
            });
            
        // Top Services
        $topServices = DB::table('appointments')
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->where('appointments.company_id', $user->company_id)
            ->whereBetween('appointments.starts_at', [$startDate, $endDate])
            ->select('services.name', DB::raw('count(*) as count'))
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();
            
        // Staff Performance
        $staffPerformance = DB::table('appointments')
            ->join('staff', 'appointments.staff_id', '=', 'staff.id')
            ->where('appointments.company_id', $user->company_id)
            ->whereBetween('appointments.starts_at', [$startDate, $endDate])
            ->select('staff.name', 
                DB::raw('count(*) as total_appointments'),
                DB::raw('sum(case when appointments.status = "completed" then 1 else 0 end) as completed'),
                DB::raw('sum(case when appointments.status = "cancelled" then 1 else 0 end) as cancelled')
            )
            ->groupBy('staff.id', 'staff.name')
            ->orderByDesc('total_appointments')
            ->limit(10)
            ->get();
            
        // Branch Performance (if user has access to multiple branches)
        $branchPerformance = null;
        if (!$user->branch_id || $user->role === 'admin') {
            $branchPerformance = DB::table('appointments')
                ->join('branches', 'appointments.branch_id', '=', 'branches.id')
                ->where('appointments.company_id', $user->company_id)
                ->whereBetween('appointments.starts_at', [$startDate, $endDate])
                ->select('branches.name',
                    DB::raw('count(*) as total_appointments'),
                    DB::raw('count(distinct appointments.customer_id) as unique_customers')
                )
                ->groupBy('branches.id', 'branches.name')
                ->orderByDesc('total_appointments')
                ->get();
        }
        
        // Hourly Call Distribution
        $hourlyDistribution = Call::where('company_id', $user->company_id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(function($item) {
                return [
                    'hour' => sprintf('%02d:00', $item->hour),
                    'count' => $item->count
                ];
            });
        
        // Use unified layout for consistency
        return view('portal.analytics.index-unified', compact(
            'overviewStats',
            'appointmentStatus',
            'dailyCalls',
            'topServices',
            'staffPerformance',
            'branchPerformance',
            'hourlyDistribution',
            'startDate',
            'endDate'
        ));
    }
    
    /**
     * Calculate conversion rate (calls to appointments)
     */
    private function calculateConversionRate($companyId, $startDate, $endDate)
    {
        $totalCalls = Call::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
            
        if ($totalCalls == 0) {
            return 0;
        }
        
        // Count calls that resulted in appointments
        $callsWithAppointments = Call::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('customer', function($q) use ($startDate, $endDate) {
                $q->whereHas('appointments', function($q2) use ($startDate, $endDate) {
                    $q2->whereBetween('starts_at', [$startDate, $endDate]);
                });
            })
            ->count();
            
        return round(($callsWithAppointments / $totalCalls) * 100, 1);
    }
    
    /**
     * Export analytics data
     */
    public function export(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            abort(401, 'Unauthorized');
        }
        
        // This would typically generate a CSV or Excel file
        // For now, we'll redirect back with a message
        return redirect()->route('business.analytics.index')
            ->with('info', 'Export-Funktion wird in Kürze verfügbar sein.');
    }
}