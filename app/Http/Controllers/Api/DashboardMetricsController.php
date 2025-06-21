<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Services\Dashboard\DashboardMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardMetricsController extends Controller
{
    public function __construct(
        private DashboardMetricsService $metricsService
    ) {}

    /**
     * Get operational metrics
     * 
     * @authenticated
     * @group Dashboard
     * @queryParam branch_id integer Branch ID (optional)
     * @response {
     *   "timestamp": "2025-06-18T10:00:00Z",
     *   "active_calls": 5,
     *   "queue": {
     *     "depth": 3,
     *     "average_wait_time": 45,
     *     "longest_wait_time": 120,
     *     "abandoned_rate": 0.05
     *   },
     *   "today": {
     *     "calls": {
     *       "total": 150,
     *       "booked": 75,
     *       "conversion_rate": 50.0
     *     },
     *     "appointments": {
     *       "total": 80,
     *       "completed": 65,
     *       "completion_rate": 81.3
     *     }
     *   },
     *   "system_health": {
     *     "status": "operational",
     *     "services": {
     *       "calcom": {
     *         "status": "operational",
     *         "uptime": 99.9,
     *         "response_time": 45
     *       }
     *     }
     *   },
     *   "conversion_funnel": {
     *     "stages": [...],
     *     "overall_conversion": 50.0
     *   }
     * }
     */
    public function operational(Request $request): JsonResponse
    {
        $user = Auth::user();
        $company = $user->company;
        
        if (!$company) {
            return response()->json(['error' => 'No company associated with user'], 403);
        }
        
        $branch = null;
        if ($request->has('branch_id')) {
            $branch = Branch::where('company_id', $company->id)
                ->where('id', $request->branch_id)
                ->first();
                
            if (!$branch) {
                return response()->json(['error' => 'Branch not found'], 404);
            }
        }
        
        $metrics = $this->metricsService->getOperationalMetrics($company, $branch);
        
        return response()->json($metrics);
    }

    /**
     * Get financial metrics
     * 
     * @authenticated
     * @group Dashboard
     * @queryParam branch_id integer Branch ID (optional)
     * @queryParam period string Period: day, week, month, quarter, year (default: month)
     * @response {
     *   "period": "month",
     *   "date_range": {
     *     "start": "2025-06-01",
     *     "end": "2025-06-18"
     *   },
     *   "acquisition": {
     *     "new_customers": 150,
     *     "marketing_spend": 5000,
     *     "cac": 33.33,
     *     "channels": {
     *       "phone": 105,
     *       "web": 30,
     *       "referral": 15
     *     }
     *   },
     *   "revenue": {
     *     "total_revenue": 45000,
     *     "appointment_count": 900,
     *     "average_booking_value": 50,
     *     "mrr": 45000
     *   },
     *   "unit_economics": {
     *     "ltv": 500,
     *     "cac": 33.33,
     *     "ltv_cac_ratio": 15.0,
     *     "payback_months": 0.8,
     *     "health_score": "excellent"
     *   },
     *   "trends": [...]
     * }
     */
    public function financial(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'nullable|integer',
            'period' => 'nullable|in:day,week,month,quarter,year',
        ]);
        
        $user = Auth::user();
        $company = $user->company;
        
        if (!$company) {
            return response()->json(['error' => 'No company associated with user'], 403);
        }
        
        $branch = null;
        if ($request->has('branch_id')) {
            $branch = Branch::where('company_id', $company->id)
                ->where('id', $request->branch_id)
                ->first();
                
            if (!$branch) {
                return response()->json(['error' => 'Branch not found'], 404);
            }
        }
        
        $period = $request->get('period', 'month');
        $metrics = $this->metricsService->getFinancialMetrics($company, $branch, $period);
        
        return response()->json($metrics);
    }

    /**
     * Get branch comparison
     * 
     * @authenticated
     * @group Dashboard
     * @queryParam period string Period: day, week, month (default: week)
     * @response {
     *   "period": "week",
     *   "date_range": {
     *     "start": "2025-06-12",
     *     "end": "2025-06-18"
     *   },
     *   "branches": [
     *     {
     *       "branch": {
     *         "id": 1,
     *         "name": "Berlin Mitte",
     *         "location": "Berlin"
     *       },
     *       "metrics": {
     *         "calls": 234,
     *         "bookings": 156,
     *         "conversion_rate": 66.7,
     *         "revenue": 7020,
     *         "revenue_change": 12
     *       },
     *       "rank": 1
     *     }
     *   ]
     * }
     */
    public function branchComparison(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:day,week,month',
        ]);
        
        $user = Auth::user();
        $company = $user->company;
        
        if (!$company) {
            return response()->json(['error' => 'No company associated with user'], 403);
        }
        
        $period = $request->get('period', 'week');
        $comparison = $this->metricsService->getBranchComparison($company, $period);
        
        return response()->json($comparison);
    }

    /**
     * Get anomalies
     * 
     * @authenticated
     * @group Dashboard
     * @queryParam branch_id integer Branch ID (optional)
     * @response {
     *   "count": 2,
     *   "alerts": [
     *     {
     *       "type": "conversion_rate",
     *       "severity": "warning",
     *       "message": "Conversion rate is 25.5% (normal: 50.0% ± 5.0%)",
     *       "current_value": 25.5,
     *       "expected_range": {
     *         "min": 40.0,
     *         "max": 60.0
     *       },
     *       "detected_at": "2025-06-18T10:00:00Z"
     *     }
     *   ],
     *   "last_check": "2025-06-18T10:00:00Z"
     * }
     */
    public function anomalies(Request $request): JsonResponse
    {
        $user = Auth::user();
        $company = $user->company;
        
        if (!$company) {
            return response()->json(['error' => 'No company associated with user'], 403);
        }
        
        $branch = null;
        if ($request->has('branch_id')) {
            $branch = Branch::where('company_id', $company->id)
                ->where('id', $request->branch_id)
                ->first();
                
            if (!$branch) {
                return response()->json(['error' => 'Branch not found'], 404);
            }
        }
        
        $anomalies = $this->metricsService->getAnomalies($company, $branch);
        
        return response()->json($anomalies);
    }

    /**
     * Get all dashboard metrics in one call
     * 
     * @authenticated
     * @group Dashboard
     * @queryParam branch_id integer Branch ID (optional)
     * @queryParam period string Period for financial metrics (default: month)
     * @response {
     *   "operational": {...},
     *   "financial": {...},
     *   "branch_comparison": {...},
     *   "anomalies": {...}
     * }
     */
    public function all(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'nullable|integer',
            'period' => 'nullable|in:day,week,month,quarter,year',
        ]);
        
        $user = Auth::user();
        $company = $user->company;
        
        if (!$company) {
            return response()->json(['error' => 'No company associated with user'], 403);
        }
        
        $branch = null;
        if ($request->has('branch_id')) {
            $branch = Branch::where('company_id', $company->id)
                ->where('id', $request->branch_id)
                ->first();
                
            if (!$branch) {
                return response()->json(['error' => 'Branch not found'], 404);
            }
        }
        
        $period = $request->get('period', 'month');
        
        // Use cache for combined requests
        $cacheKey = "dashboard_all_{$company->id}" . ($branch ? "_{$branch->id}" : '') . "_{$period}";
        
        $data = Cache::remember($cacheKey, 60, function () use ($company, $branch, $period) {
            return [
                'operational' => $this->metricsService->getOperationalMetrics($company, $branch),
                'financial' => $this->metricsService->getFinancialMetrics($company, $branch, $period),
                'branch_comparison' => $this->metricsService->getBranchComparison($company, 'week'),
                'anomalies' => $this->metricsService->getAnomalies($company, $branch),
            ];
        });
        
        return response()->json($data);
    }
}