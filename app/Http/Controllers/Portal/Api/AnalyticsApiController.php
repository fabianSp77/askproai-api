<?php

namespace App\Http\Controllers\Portal\Api;

use Illuminate\Http\Request;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\PortalUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Get date range
        $startDate = Carbon::parse($request->get('start_date', Carbon::now()->subDays(7)));
        $endDate = Carbon::parse($request->get('end_date', Carbon::now()));
        $branchId = $request->get('branch_id');

        // Get previous period for comparison
        $daysDiff = $startDate->diffInDays($endDate);
        $prevStartDate = $startDate->copy()->subDays($daysDiff);
        $prevEndDate = $startDate->copy()->subDay();

        // Overview statistics
        $overview = [
            'calls' => $this->getCallStats($company->id, $startDate, $endDate, $prevStartDate, $prevEndDate, $branchId),
            'appointments' => $this->getAppointmentStats($company->id, $startDate, $endDate, $prevStartDate, $prevEndDate, $branchId),
            'customers' => $this->getCustomerStats($company->id, $startDate, $endDate, $prevStartDate, $prevEndDate),
            'revenue' => $this->getRevenueStats($company->id, $startDate, $endDate, $prevStartDate, $prevEndDate, $branchId),
        ];

        // Trends
        $callsTrend = $this->getCallsTrend($company->id, $startDate, $endDate, $branchId);
        $appointmentsTrend = $this->getAppointmentsTrend($company->id, $startDate, $endDate, $branchId);

        // Services data
        $servicesData = $this->getServicesData($company->id, $startDate, $endDate, $branchId);

        // Staff performance
        $staffPerformance = $this->getStaffPerformance($company->id, $startDate, $endDate, $branchId);

        // Peak hours
        $peakHours = $this->getPeakHours($company->id, $startDate, $endDate, $branchId);

        // Conversion funnel
        $conversion = $this->getConversionData($company->id, $startDate, $endDate, $branchId);

        return response()->json([
            'overview' => $overview,
            'calls_trend' => $callsTrend,
            'appointments_trend' => $appointmentsTrend,
            'services' => $servicesData,
            'staff_performance' => $staffPerformance,
            'peak_hours' => $peakHours,
            'conversion' => $conversion,
        ]);
    }

    public function filters(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $company = $user->company;

        return response()->json([
            'branches' => $company->branches()->select('id', 'name')->get(),
        ]);
    }

    public function export(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permission
        if ($user instanceof PortalUser && !$user->hasPermissionTo('analytics.export')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $format = $request->get('format', 'csv');
        
        // TODO: Implement export functionality
        
        return response()->json(['message' => 'Export functionality will be implemented']);
    }

    private function getCallStats($companyId, $startDate, $endDate, $prevStartDate, $prevEndDate, $branchId = null)
    {
        $query = Call::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate]);
            
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $current = $query->count();
        
        $prevQuery = Call::where('company_id', $companyId)
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate]);
            
        if ($branchId) {
            $prevQuery->where('branch_id', $branchId);
        }
        
        $previous = $prevQuery->count();
        
        $trend = $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : 0;

        return [
            'total' => $current,
            'trend' => $trend,
        ];
    }

    private function getAppointmentStats($companyId, $startDate, $endDate, $prevStartDate, $prevEndDate, $branchId = null)
    {
        $query = Appointment::where('company_id', $companyId)
            ->whereBetween('starts_at', [$startDate, $endDate]);
            
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $current = $query->count();
        
        $prevQuery = Appointment::where('company_id', $companyId)
            ->whereBetween('starts_at', [$prevStartDate, $prevEndDate]);
            
        if ($branchId) {
            $prevQuery->where('branch_id', $branchId);
        }
        
        $previous = $prevQuery->count();
        
        $trend = $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : 0;

        return [
            'total' => $current,
            'trend' => $trend,
        ];
    }

    private function getCustomerStats($companyId, $startDate, $endDate, $prevStartDate, $prevEndDate)
    {
        $current = Customer::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
            
        $previous = Customer::where('company_id', $companyId)
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate])
            ->count();
        
        $trend = $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : 0;

        return [
            'total' => $current,
            'trend' => $trend,
        ];
    }

    private function getRevenueStats($companyId, $startDate, $endDate, $prevStartDate, $prevEndDate, $branchId = null)
    {
        // TODO: Implement actual revenue calculation based on completed appointments and services
        return [
            'total' => rand(5000, 15000),
            'trend' => rand(-10, 20),
        ];
    }

    private function getCallsTrend($companyId, $startDate, $endDate, $branchId = null)
    {
        $query = Call::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date');
            
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $data = $query->get();
        
        // Fill missing dates with 0
        $result = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $count = $data->firstWhere('date', $dateStr)?->count ?? 0;
            
            $result[] = [
                'date' => $currentDate->format('d.m'),
                'count' => $count,
            ];
            
            $currentDate->addDay();
        }
        
        return $result;
    }

    private function getAppointmentsTrend($companyId, $startDate, $endDate, $branchId = null)
    {
        $query = Appointment::where('company_id', $companyId)
            ->whereBetween('starts_at', [$startDate, $endDate])
            ->selectRaw('DATE(starts_at) as date, 
                        SUM(CASE WHEN status = "scheduled" THEN 1 ELSE 0 END) as scheduled,
                        SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled')
            ->groupBy('date')
            ->orderBy('date');
            
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $data = $query->get();
        
        // Fill missing dates
        $result = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayData = $data->firstWhere('date', $dateStr);
            
            $result[] = [
                'date' => $currentDate->format('d.m'),
                'scheduled' => $dayData?->scheduled ?? 0,
                'completed' => $dayData?->completed ?? 0,
                'cancelled' => $dayData?->cancelled ?? 0,
            ];
            
            $currentDate->addDay();
        }
        
        return $result;
    }

    private function getServicesData($companyId, $startDate, $endDate, $branchId = null)
    {
        $query = Appointment::where('appointments.company_id', $companyId)
            ->whereBetween('starts_at', [$startDate, $endDate])
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->selectRaw('services.name, COUNT(*) as count')
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('count')
            ->limit(6);
            
        if ($branchId) {
            $query->where('appointments.branch_id', $branchId);
        }
        
        return $query->get()->toArray();
    }

    private function getStaffPerformance($companyId, $startDate, $endDate, $branchId = null)
    {
        $staff = Staff::where('company_id', $companyId);
        
        if ($branchId) {
            $staff->whereHas('branches', function($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            });
        }
        
        $staff = $staff->get();
        
        $performance = [];
        
        foreach ($staff as $member) {
            $callsQuery = Call::where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $endDate]);
                
            $appointmentsQuery = Appointment::where('company_id', $companyId)
                ->where('staff_id', $member->id)
                ->whereBetween('starts_at', [$startDate, $endDate]);
                
            if ($branchId) {
                $callsQuery->where('branch_id', $branchId);
                $appointmentsQuery->where('branch_id', $branchId);
            }
            
            $callsHandled = $callsQuery->count();
            $appointmentsCompleted = $appointmentsQuery->where('status', 'completed')->count();
            $appointmentsTotal = $appointmentsQuery->count();
            
            $performance[] = [
                'id' => $member->id,
                'name' => $member->name,
                'calls_handled' => $callsHandled,
                'appointments_completed' => $appointmentsCompleted,
                'conversion_rate' => $callsHandled > 0 ? round(($appointmentsTotal / $callsHandled) * 100, 1) : 0,
                'avg_call_duration' => rand(45, 180), // TODO: Calculate actual duration
                'satisfaction_score' => round(rand(35, 50) / 10, 1),
            ];
        }
        
        return $performance;
    }

    private function getPeakHours($companyId, $startDate, $endDate, $branchId = null)
    {
        $query = Call::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as calls')
            ->groupBy('hour')
            ->orderBy('hour');
            
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $data = $query->get();
        
        // Fill all 24 hours
        $result = [];
        for ($i = 0; $i < 24; $i++) {
            $hourData = $data->firstWhere('hour', $i);
            $result[] = [
                'hour' => sprintf('%02d:00', $i),
                'calls' => $hourData?->calls ?? 0,
            ];
        }
        
        return $result;
    }

    private function getConversionData($companyId, $startDate, $endDate, $branchId = null)
    {
        $callsQuery = Call::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate]);
            
        $appointmentsQuery = Appointment::where('company_id', $companyId)
            ->whereBetween('starts_at', [$startDate, $endDate]);
            
        if ($branchId) {
            $callsQuery->where('branch_id', $branchId);
            $appointmentsQuery->where('branch_id', $branchId);
        }
        
        $totalCalls = $callsQuery->count();
        $callsWithCustomer = $callsQuery->whereNotNull('customer_id')->count();
        $appointments = $appointmentsQuery->count();
        $completedAppointments = $appointmentsQuery->where('status', 'completed')->count();
        
        $funnel = [
            [
                'name' => 'Anrufe eingegangen',
                'count' => $totalCalls,
                'percentage' => 100,
            ],
            [
                'name' => 'Kunde identifiziert',
                'count' => $callsWithCustomer,
                'percentage' => $totalCalls > 0 ? round(($callsWithCustomer / $totalCalls) * 100, 1) : 0,
            ],
            [
                'name' => 'Termin vereinbart',
                'count' => $appointments,
                'percentage' => $totalCalls > 0 ? round(($appointments / $totalCalls) * 100, 1) : 0,
            ],
            [
                'name' => 'Termin durchgeführt',
                'count' => $completedAppointments,
                'percentage' => $totalCalls > 0 ? round(($completedAppointments / $totalCalls) * 100, 1) : 0,
            ],
        ];
        
        return [
            'rate' => $totalCalls > 0 ? round(($completedAppointments / $totalCalls) * 100, 1) : 0,
            'funnel' => $funnel,
        ];
    }
}