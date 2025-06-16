<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\QueryCache;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Call;
use App\Models\Staff;
use Illuminate\Http\Request;

class OptimizedDashboardController extends Controller
{
    private $queryCache;
    
    public function __construct(QueryCache $queryCache)
    {
        $this->queryCache = $queryCache;
    }
    
    /**
     * Get dashboard data with optimized queries
     */
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $dateRange = $request->get('range', 'month');
        
        // Get cached statistics
        $stats = [
            'appointments' => $this->queryCache->getAppointmentStats($companyId, $dateRange),
            'customers' => $this->queryCache->getCustomerMetrics($companyId),
            'calls' => $this->queryCache->getCallStats($companyId),
            'staff_performance' => $this->queryCache->getStaffPerformance($companyId),
        ];
        
        // Get recent appointments with optimized query
        $recentAppointments = Appointment::forCompany($companyId)
            ->upcoming()
            ->withRelations()
            ->forceIndex('idx_appointments_company_status_date')
            ->limit(10)
            ->get();
            
        // Get top customers with optimized query
        $topCustomers = Customer::forCompany($companyId)
            ->withAppointmentCount()
            ->hint('big_result')
            ->orderBy('appointments_count', 'desc')
            ->limit(5)
            ->get();
            
        // Get recent calls with optimized query
        $recentCalls = Call::forCompany($companyId)
            ->recent(7)
            ->withRelations()
            ->forceIndex('idx_calls_company_date')
            ->limit(20)
            ->get();
            
        return response()->json([
            'stats' => $stats,
            'recent_appointments' => $recentAppointments,
            'top_customers' => $topCustomers,
            'recent_calls' => $recentCalls,
        ]);
    }
    
    /**
     * Get appointment calendar data
     */
    public function calendar(Request $request)
    {
        $companyId = $request->user()->company_id;
        $startDate = $request->get('start', now()->startOfMonth());
        $endDate = $request->get('end', now()->endOfMonth());
        
        // Use optimized query with proper indexes
        $appointments = Appointment::forCompany($companyId)
            ->dateRange($startDate, $endDate)
            ->withRelations()
            ->forceIndex('idx_appointments_dates')
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'title' => $appointment->customer->name . ' - ' . $appointment->service->name,
                    'start' => $appointment->starts_at->toIso8601String(),
                    'end' => $appointment->ends_at->toIso8601String(),
                    'staff' => $appointment->staff->name,
                    'branch' => $appointment->branch->name,
                    'status' => $appointment->status,
                ];
            });
            
        return response()->json($appointments);
    }
    
    /**
     * Search across entities with optimized queries
     */
    public function search(Request $request)
    {
        $companyId = $request->user()->company_id;
        $search = $request->get('q', '');
        
        if (strlen($search) < 3) {
            return response()->json([]);
        }
        
        // Search customers with optimized query
        $customers = Customer::forCompany($companyId)
            ->search($search)
            ->hint('no_cache') // Don't cache search results
            ->limit(10)
            ->get(['id', 'name', 'email', 'phone']);
            
        // Search staff with optimized query
        $staff = Staff::forCompany($companyId)
            ->where('name', 'LIKE', "%{$search}%")
            ->active()
            ->limit(10)
            ->get(['id', 'name', 'email']);
            
        return response()->json([
            'customers' => $customers,
            'staff' => $staff,
        ]);
    }
    
    /**
     * Get performance metrics
     */
    public function performance(Request $request)
    {
        $companyId = $request->user()->company_id;
        $period = $request->get('period', 30);
        
        // Get staff performance with caching
        $staffPerformance = $this->queryCache->getStaffPerformance($companyId, null, $period);
        
        // Get branch comparison with caching
        $branchComparison = $this->queryCache->getBranchComparison($companyId, 'month');
        
        return response()->json([
            'staff' => $staffPerformance,
            'branches' => $branchComparison,
        ]);
    }
    
    /**
     * Clear cache for current company
     */
    public function clearCache(Request $request)
    {
        $companyId = $request->user()->company_id;
        
        $this->queryCache->clearCompanyCache($companyId);
        
        return response()->json([
            'message' => 'Cache cleared successfully'
        ]);
    }
}