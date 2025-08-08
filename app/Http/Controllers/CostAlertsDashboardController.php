<?php

namespace App\Http\Controllers;

use App\Models\BillingAlert;
use App\Models\Company;
use App\Services\CostTrackingAlertService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CostAlertsDashboardController extends Controller
{
    protected CostTrackingAlertService $costTrackingService;

    public function __construct(CostTrackingAlertService $costTrackingService)
    {
        $this->costTrackingService = $costTrackingService;
    }

    /**
     * Display the cost alerts dashboard
     */
    public function index(Request $request): View
    {
        $company = null;
        
        // If company filter is applied
        if ($request->has('company_id') && $request->company_id) {
            $company = Company::with('prepaidBalance')->find($request->company_id);
        }

        $dashboardData = $this->costTrackingService->getDashboardData($company);
        
        // Get all companies for filter dropdown
        $companies = Company::with('prepaidBalance')
            ->whereHas('prepaidBalance')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Use the standalone monitoring view to avoid telescope dependency
        return view('monitoring.cost-alerts', [
            'dashboardData' => $dashboardData,
            'companies' => $companies,
            'selectedCompany' => $company,
            'filters' => $request->only(['company_id', 'severity', 'status', 'alert_type']),
            // Add additional data for the view
            'alerts' => BillingAlert::with('company')
                ->whereIn('alert_type', ['low_balance', 'zero_balance', 'usage_spike', 'budget_exceeded', 'cost_anomaly'])
                ->orderBy('created_at', 'desc')
                ->take(50)
                ->get(),
            'totalBudget' => $dashboardData['metrics']['total_balance'] ?? 0,
            'currentSpend' => $dashboardData['metrics']['monthly_spend'] ?? 0,
            'budgetUsage' => $dashboardData['metrics']['budget_usage_percentage'] ?? 0
        ]);
    }

    /**
     * Get dashboard data as JSON for AJAX requests
     */
    public function data(Request $request): JsonResponse
    {
        $company = null;
        
        if ($request->has('company_id') && $request->company_id) {
            $company = Company::find($request->company_id);
        }

        $data = $this->costTrackingService->getDashboardData($company);
        
        // Add real-time metrics
        $data['real_time_metrics'] = $this->getRealTimeMetrics($company);
        
        return response()->json([
            'success' => true,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get alerts data with pagination and filters
     */
    public function alerts(Request $request): JsonResponse
    {
        $query = BillingAlert::with(['company', 'config', 'acknowledgedBy'])
            ->whereIn('alert_type', [
                CostTrackingAlertService::TYPE_LOW_BALANCE,
                CostTrackingAlertService::TYPE_USAGE_SPIKE,
                CostTrackingAlertService::TYPE_BUDGET_EXCEEDED,
                CostTrackingAlertService::TYPE_ZERO_BALANCE,
                CostTrackingAlertService::TYPE_COST_ANOMALY
            ]);

        // Apply filters
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('alert_type')) {
            $query->where('alert_type', $request->alert_type);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhereHas('company', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Pagination
        $perPage = min($request->get('per_page', 20), 100);
        $alerts = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $alerts->items(),
            'pagination' => [
                'current_page' => $alerts->currentPage(),
                'per_page' => $alerts->perPage(),
                'total' => $alerts->total(),
                'last_page' => $alerts->lastPage(),
                'has_more' => $alerts->hasMorePages()
            ]
        ]);
    }

    /**
     * Acknowledge an alert
     */
    public function acknowledge(Request $request, BillingAlert $alert): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $alert->acknowledge($user);

            return response()->json([
                'success' => true,
                'message' => 'Alert acknowledged successfully',
                'data' => [
                    'id' => $alert->id,
                    'status' => $alert->status,
                    'acknowledged_at' => $alert->acknowledged_at?->toISOString(),
                    'acknowledged_by' => $alert->acknowledgedBy?->name
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to acknowledge alert: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk acknowledge alerts
     */
    public function bulkAcknowledge(Request $request): JsonResponse
    {
        $request->validate([
            'alert_ids' => 'required|array|min:1',
            'alert_ids.*' => 'integer|exists:billing_alerts,id'
        ]);

        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $alertIds = $request->alert_ids;
            $acknowledgedCount = 0;

            foreach ($alertIds as $alertId) {
                $alert = BillingAlert::find($alertId);
                if ($alert && $alert->isActionable()) {
                    $alert->acknowledge($user);
                    $acknowledgedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully acknowledged {$acknowledgedCount} alerts",
                'data' => [
                    'acknowledged_count' => $acknowledgedCount,
                    'total_requested' => count($alertIds)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to acknowledge alerts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test alert creation (for development/testing)
     */
    public function testAlert(Request $request): JsonResponse
    {
        if (!app()->environment(['local', 'development'])) {
            return response()->json([
                'success' => false,
                'message' => 'Test alerts can only be created in development environment'
            ], 403);
        }

        $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'alert_type' => 'required|string|in:low_balance,zero_balance,usage_spike,budget_exceeded,cost_anomaly'
        ]);

        try {
            $company = Company::with(['prepaidBalance', 'billingAlertConfigs'])->find($request->company_id);
            
            if (!$company->prepaidBalance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company does not have a prepaid balance'
                ], 400);
            }

            // Force check alerts for this company
            $results = $this->costTrackingService->checkCompanyCostAlerts($company);

            return response()->json([
                'success' => true,
                'message' => 'Alert check completed',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to test alert: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get alert details
     */
    public function show(BillingAlert $alert): JsonResponse
    {
        $alert->load(['company', 'config', 'acknowledgedBy']);

        return response()->json([
            'success' => true,
            'data' => [
                'alert' => $alert,
                'company' => $alert->company,
                'config' => $alert->config,
                'acknowledged_by' => $alert->acknowledgedBy,
                'formatted_data' => $this->formatAlertData($alert)
            ]
        ]);
    }

    /**
     * Get cost tracking statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $companyId = $request->get('company_id');
        
        $cacheKey = "cost_alerts_stats:{$days}:" . ($companyId ?? 'all');
        
        $stats = Cache::remember($cacheKey, 300, function () use ($days, $companyId) {
            $startDate = now()->subDays($days);
            
            $query = BillingAlert::where('created_at', '>=', $startDate)
                ->whereIn('alert_type', [
                    CostTrackingAlertService::TYPE_LOW_BALANCE,
                    CostTrackingAlertService::TYPE_USAGE_SPIKE,
                    CostTrackingAlertService::TYPE_BUDGET_EXCEEDED,
                    CostTrackingAlertService::TYPE_ZERO_BALANCE,
                    CostTrackingAlertService::TYPE_COST_ANOMALY
                ]);
                
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
            
            $alerts = $query->get();
            
            return [
                'total_alerts' => $alerts->count(),
                'by_severity' => [
                    'critical' => $alerts->where('severity', 'critical')->count(),
                    'warning' => $alerts->where('severity', 'warning')->count(),
                    'info' => $alerts->where('severity', 'info')->count(),
                ],
                'by_type' => [
                    'low_balance' => $alerts->where('alert_type', CostTrackingAlertService::TYPE_LOW_BALANCE)->count(),
                    'zero_balance' => $alerts->where('alert_type', CostTrackingAlertService::TYPE_ZERO_BALANCE)->count(),
                    'usage_spike' => $alerts->where('alert_type', CostTrackingAlertService::TYPE_USAGE_SPIKE)->count(),
                    'budget_exceeded' => $alerts->where('alert_type', CostTrackingAlertService::TYPE_BUDGET_EXCEEDED)->count(),
                    'cost_anomaly' => $alerts->where('alert_type', CostTrackingAlertService::TYPE_COST_ANOMALY)->count(),
                ],
                'by_status' => [
                    'pending' => $alerts->where('status', 'pending')->count(),
                    'sent' => $alerts->where('status', 'sent')->count(),
                    'acknowledged' => $alerts->where('status', 'acknowledged')->count(),
                    'failed' => $alerts->where('status', 'failed')->count(),
                ],
                'daily_trend' => $this->getDailyTrend($alerts, $days),
                'response_times' => $this->getResponseTimeStats($alerts),
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $stats,
            'period' => [
                'days' => $days,
                'start_date' => now()->subDays($days)->toDateString(),
                'end_date' => now()->toDateString()
            ]
        ]);
    }

    /**
     * Get real-time metrics
     */
    protected function getRealTimeMetrics(?Company $company = null): array
    {
        $query = Company::with('prepaidBalance')
            ->whereHas('prepaidBalance')
            ->where('is_active', true);
            
        if ($company) {
            $query->where('id', $company->id);
        }
        
        $companies = $query->get();
        
        $metrics = [
            'total_companies' => $companies->count(),
            'low_balance_count' => 0,
            'zero_balance_count' => 0,
            'total_balance' => 0,
            'companies_with_auto_topup' => 0
        ];
        
        foreach ($companies as $comp) {
            if ($comp->prepaidBalance) {
                $balance = $comp->prepaidBalance->getEffectiveBalance();
                $metrics['total_balance'] += $balance;
                
                if ($balance <= 0) {
                    $metrics['zero_balance_count']++;
                } elseif ($comp->prepaidBalance->isLowBalance()) {
                    $metrics['low_balance_count']++;
                }
                
                if ($comp->prepaidBalance->auto_topup_enabled) {
                    $metrics['companies_with_auto_topup']++;
                }
            }
        }
        
        return $metrics;
    }

    /**
     * Format alert data for display
     */
    protected function formatAlertData(BillingAlert $alert): array
    {
        $data = $alert->data ?? [];
        
        $formatted = [
            'severity_badge' => $this->getSeverityBadge($alert->severity),
            'type_badge' => $this->getTypeBadge($alert->alert_type),
            'created_ago' => $alert->created_at->diffForHumans(),
        ];
        
        // Format specific data based on alert type
        switch ($alert->alert_type) {
            case CostTrackingAlertService::TYPE_LOW_BALANCE:
            case CostTrackingAlertService::TYPE_ZERO_BALANCE:
                $formatted['balance'] = isset($data['balance']) ? '€' . number_format($data['balance'], 2) : 'N/A';
                $formatted['threshold'] = isset($data['threshold']) ? '€' . number_format($data['threshold'], 2) : 'N/A';
                $formatted['percentage'] = isset($data['percentage']) ? round($data['percentage'], 1) . '%' : 'N/A';
                break;
                
            case CostTrackingAlertService::TYPE_BUDGET_EXCEEDED:
                $formatted['monthly_spend'] = isset($data['monthly_spend']) ? '€' . number_format($data['monthly_spend'], 2) : 'N/A';
                $formatted['monthly_budget'] = isset($data['monthly_budget']) ? '€' . number_format($data['monthly_budget'], 2) : 'N/A';
                $formatted['percentage'] = isset($data['percentage']) ? round($data['percentage'], 1) . '%' : 'N/A';
                break;
        }
        
        return $formatted;
    }

    /**
     * Get severity badge HTML
     */
    protected function getSeverityBadge(string $severity): string
    {
        $classes = match ($severity) {
            'critical' => 'bg-red-100 text-red-800',
            'warning' => 'bg-yellow-100 text-yellow-800',
            'info' => 'bg-blue-100 text-blue-800',
            default => 'bg-gray-100 text-gray-800'
        };
        
        $icon = match ($severity) {
            'critical' => '🚨',
            'warning' => '⚠️',
            'info' => '📊',
            default => '📈'
        };
        
        return "<span class=\"{$classes} px-2 py-1 rounded-full text-xs font-medium\">{$icon} " . ucfirst($severity) . "</span>";
    }

    /**
     * Get type badge HTML
     */
    protected function getTypeBadge(string $type): string
    {
        $label = ucwords(str_replace('_', ' ', $type));
        
        return "<span class=\"bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs font-medium\">{$label}</span>";
    }

    /**
     * Get daily trend data
     */
    protected function getDailyTrend($alerts, int $days): array
    {
        $trend = [];
        $startDate = now()->subDays($days);
        
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dayAlerts = $alerts->filter(function ($alert) use ($date) {
                return $alert->created_at->isSameDay($date);
            });
            
            $trend[] = [
                'date' => $date->toDateString(),
                'count' => $dayAlerts->count(),
                'critical' => $dayAlerts->where('severity', 'critical')->count(),
                'warning' => $dayAlerts->where('severity', 'warning')->count(),
                'info' => $dayAlerts->where('severity', 'info')->count(),
            ];
        }
        
        return $trend;
    }

    /**
     * Get response time statistics
     */
    protected function getResponseTimeStats($alerts): array
    {
        $acknowledgedAlerts = $alerts->filter(function ($alert) {
            return $alert->acknowledged_at !== null;
        });
        
        if ($acknowledgedAlerts->isEmpty()) {
            return [
                'average_response_time' => null,
                'median_response_time' => null,
                'fastest_response' => null,
                'slowest_response' => null
            ];
        }
        
        $responseTimes = $acknowledgedAlerts->map(function ($alert) {
            return $alert->acknowledged_at->diffInMinutes($alert->created_at);
        })->sort();
        
        return [
            'average_response_time' => round($responseTimes->avg(), 1),
            'median_response_time' => $responseTimes->median(),
            'fastest_response' => $responseTimes->min(),
            'slowest_response' => $responseTimes->max()
        ];
    }
}