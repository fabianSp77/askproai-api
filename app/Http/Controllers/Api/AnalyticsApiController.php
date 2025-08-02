<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MCP\AnalyticsMCPServer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnalyticsApiController extends Controller
{
    protected AnalyticsMCPServer $analyticsMCP;
    
    public function __construct(AnalyticsMCPServer $analyticsMCP)
    {
        $this->analyticsMCP = $analyticsMCP;
    }
    
    /**
     * Get business metrics
     */
    public function metrics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'branch_id' => 'nullable|integer',
            'metrics' => 'nullable|array',
            'metrics.*' => 'string|in:revenue,appointments,calls,customers,conversion,utilization',
            'comparison_period' => 'nullable|string|in:previous_period,previous_year,custom'
        ]);
        
        $result = $this->analyticsMCP->executeTool('getBusinessMetrics', [
            'company_id' => auth()->user()->company_id,
            'branch_id' => $validated['branch_id'] ?? null,
            'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'],
            'metrics' => $validated['metrics'] ?? ['revenue', 'appointments', 'calls', 'customers'],
            'comparison_period' => $validated['comparison_period'] ?? null
        ]);
        
        return response()->json($result);
    }
    
    /**
     * Predict revenue
     */
    public function predictRevenue(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prediction_days' => 'required|integer|min:7|max:90',
            'branch_id' => 'nullable|integer',
            'confidence_level' => 'nullable|numeric|min:0.8|max:0.99',
            'include_seasonality' => 'nullable|boolean',
            'include_growth_trend' => 'nullable|boolean'
        ]);
        
        $result = $this->analyticsMCP->executeTool('predictRevenue', [
            'company_id' => auth()->user()->company_id,
            'branch_id' => $validated['branch_id'] ?? null,
            'prediction_days' => $validated['prediction_days'],
            'confidence_level' => $validated['confidence_level'] ?? 0.9,
            'include_seasonality' => $validated['include_seasonality'] ?? true,
            'include_growth_trend' => $validated['include_growth_trend'] ?? true
        ]);
        
        return response()->json($result);
    }
    
    /**
     * Predict appointment demand
     */
    public function predictDemand(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'branch_id' => 'nullable|integer',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer',
            'granularity' => 'nullable|string|in:hourly,daily,weekly'
        ]);
        
        $result = $this->analyticsMCP->executeTool('predictAppointmentDemand', [
            'company_id' => auth()->user()->company_id,
            'branch_id' => $validated['branch_id'] ?? null,
            'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'],
            'service_ids' => $validated['service_ids'] ?? [],
            'granularity' => $validated['granularity'] ?? 'daily'
        ]);
        
        return response()->json($result);
    }
    
    /**
     * Analyze customer behavior
     */
    public function customerBehavior(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'analysis_type' => 'required|string|in:churn_risk,lifetime_value,segmentation,preferences',
            'include_predictions' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:1000'
        ]);
        
        $result = $this->analyticsMCP->executeTool('analyzeCustomerBehavior', [
            'company_id' => auth()->user()->company_id,
            'analysis_type' => $validated['analysis_type'],
            'include_predictions' => $validated['include_predictions'] ?? true,
            'limit' => $validated['limit'] ?? 100
        ]);
        
        return response()->json($result);
    }
    
    /**
     * Get performance insights
     */
    public function insights(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|integer',
            'focus_areas' => 'nullable|array',
            'focus_areas.*' => 'string|in:staff_productivity,service_efficiency,revenue_optimization,customer_satisfaction',
            'include_recommendations' => 'nullable|boolean'
        ]);
        
        $result = $this->analyticsMCP->executeTool('getPerformanceInsights', [
            'company_id' => auth()->user()->company_id,
            'branch_id' => $validated['branch_id'] ?? null,
            'focus_areas' => $validated['focus_areas'] ?? ['staff_productivity', 'revenue_optimization'],
            'include_recommendations' => $validated['include_recommendations'] ?? true
        ]);
        
        return response()->json($result);
    }
    
    /**
     * Detect anomalies
     */
    public function anomalies(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'metric_type' => 'required|string|in:revenue,appointments,calls,cancellations',
            'sensitivity' => 'nullable|string|in:low,medium,high',
            'lookback_days' => 'nullable|integer|min:7|max:90'
        ]);
        
        $result = $this->analyticsMCP->executeTool('detectAnomalies', [
            'company_id' => auth()->user()->company_id,
            'metric_type' => $validated['metric_type'],
            'sensitivity' => $validated['sensitivity'] ?? 'medium',
            'lookback_days' => $validated['lookback_days'] ?? 30
        ]);
        
        return response()->json($result);
    }
    
    /**
     * Optimize scheduling
     */
    public function optimizeSchedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|integer',
            'optimization_goal' => 'required|string|in:maximize_revenue,minimize_wait_time,balance_workload,reduce_overtime',
            'constraints' => 'nullable|array'
        ]);
        
        $result = $this->analyticsMCP->executeTool('optimizeScheduling', [
            'company_id' => auth()->user()->company_id,
            'branch_id' => $validated['branch_id'] ?? null,
            'optimization_goal' => $validated['optimization_goal'],
            'constraints' => $validated['constraints'] ?? []
        ]);
        
        return response()->json($result);
    }
    
    /**
     * Generate analytics report
     */
    public function generateReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_type' => 'required|string|in:executive_summary,detailed_analytics,predictive_insights,custom',
            'date_range' => 'nullable|string',
            'format' => 'nullable|string|in:json,pdf,excel',
            'sections' => 'nullable|array',
            'sections.*' => 'string'
        ]);
        
        $result = $this->analyticsMCP->executeTool('generateReport', [
            'company_id' => auth()->user()->company_id,
            'report_type' => $validated['report_type'],
            'date_range' => $validated['date_range'] ?? 'this_month',
            'format' => $validated['format'] ?? 'json',
            'sections' => $validated['sections'] ?? []
        ]);
        
        return response()->json($result);
    }
    
    /**
     * Real-time analytics dashboard data
     */
    public function realtime(Request $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $branchId = $request->get('branch_id');
        
        // Get real-time metrics
        $realtime = [
            'current_time' => now()->toIso8601String(),
            'active_appointments' => $this->getActiveAppointments($companyId, $branchId),
            'ongoing_calls' => $this->getOngoingCalls($companyId, $branchId),
            'staff_availability' => $this->getStaffAvailability($companyId, $branchId),
            'today_metrics' => $this->getTodayMetrics($companyId, $branchId),
            'alerts' => $this->getActiveAlerts($companyId, $branchId)
        ];
        
        return response()->json($realtime);
    }
    
    /**
     * Export analytics data
     */
    public function export(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'export_type' => 'required|string|in:raw_data,processed_analytics,predictions',
            'date_from' => 'required|date',
            'date_to' => 'required|date',
            'format' => 'required|string|in:csv,json,excel',
            'include_metadata' => 'nullable|boolean'
        ]);
        
        // Generate export based on type
        $exportUrl = $this->generateExport($validated);
        
        return response()->json([
            'export_url' => $exportUrl,
            'expires_at' => now()->addHours(24)->toIso8601String(),
            'format' => $validated['format'],
            'size_estimate' => $this->estimateExportSize($validated)
        ]);
    }
    
    // Helper methods
    
    protected function getActiveAppointments(int $companyId, ?int $branchId): array
    {
        $query = \App\Models\Appointment::where('company_id', $companyId)
            ->where('scheduled_at', '<=', now())
            ->where('scheduled_at', '>=', now()->subHours(2))
            ->where('status', 'in_progress');
            
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return [
            'count' => $query->count(),
            'list' => $query->with(['customer', 'staff', 'service'])->limit(10)->get()
        ];
    }
    
    protected function getOngoingCalls(int $companyId, ?int $branchId): array
    {
        $query = \App\Models\Call::where('company_id', $companyId)
            ->where('status', 'in_progress')
            ->where('created_at', '>=', now()->subMinutes(30));
            
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return [
            'count' => $query->count(),
            'average_duration' => $query->avg('duration_seconds') ?? 0
        ];
    }
    
    protected function getStaffAvailability(int $companyId, ?int $branchId): array
    {
        $query = \App\Models\Staff::where('company_id', $companyId)
            ->where('is_active', true);
            
        if ($branchId) {
            $query->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }
        
        $totalStaff = $query->count();
        $availableStaff = $query->whereDoesntHave('appointments', function ($q) {
            $q->where('status', 'in_progress')
              ->where('scheduled_at', '<=', now())
              ->where('scheduled_at', '>=', now()->subHours(2));
        })->count();
        
        return [
            'total' => $totalStaff,
            'available' => $availableStaff,
            'busy' => $totalStaff - $availableStaff,
            'availability_rate' => $totalStaff > 0 ? round($availableStaff / $totalStaff * 100, 2) : 0
        ];
    }
    
    protected function getTodayMetrics(int $companyId, ?int $branchId): array
    {
        $today = now()->startOfDay();
        $tomorrow = now()->endOfDay();
        
        $appointmentsQuery = \App\Models\Appointment::where('company_id', $companyId)
            ->whereBetween('scheduled_at', [$today, $tomorrow]);
            
        $callsQuery = \App\Models\Call::where('company_id', $companyId)
            ->whereBetween('created_at', [$today, $tomorrow]);
            
        if ($branchId) {
            $appointmentsQuery->where('branch_id', $branchId);
            $callsQuery->where('branch_id', $branchId);
        }
        
        return [
            'appointments' => [
                'total' => $appointmentsQuery->count(),
                'completed' => (clone $appointmentsQuery)->where('status', 'completed')->count(),
                'cancelled' => (clone $appointmentsQuery)->where('status', 'cancelled')->count(),
                'no_show' => (clone $appointmentsQuery)->where('status', 'no_show')->count()
            ],
            'calls' => [
                'total' => $callsQuery->count(),
                'answered' => (clone $callsQuery)->where('status', 'completed')->count(),
                'missed' => (clone $callsQuery)->where('status', 'missed')->count(),
                'average_duration' => (clone $callsQuery)->where('status', 'completed')->avg('duration_seconds') ?? 0
            ],
            'revenue' => [
                'total' => \App\Models\Invoice::where('company_id', $companyId)
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->whereBetween('created_at', [$today, $tomorrow])
                    ->where('status', 'paid')
                    ->sum('total_amount')
            ]
        ];
    }
    
    protected function getActiveAlerts(int $companyId, ?int $branchId): array
    {
        // Check for various alert conditions
        $alerts = [];
        
        // High cancellation rate alert
        $cancellationRate = $this->calculateTodayCancellationRate($companyId, $branchId);
        if ($cancellationRate > 20) {
            $alerts[] = [
                'type' => 'warning',
                'category' => 'operations',
                'message' => "High cancellation rate today: {$cancellationRate}%",
                'metric' => 'cancellation_rate',
                'value' => $cancellationRate,
                'threshold' => 20
            ];
        }
        
        // Low staff availability
        $availability = $this->getStaffAvailability($companyId, $branchId);
        if ($availability['availability_rate'] < 30) {
            $alerts[] = [
                'type' => 'critical',
                'category' => 'staffing',
                'message' => "Low staff availability: {$availability['availability_rate']}%",
                'metric' => 'staff_availability',
                'value' => $availability['availability_rate'],
                'threshold' => 30
            ];
        }
        
        return $alerts;
    }
    
    protected function calculateTodayCancellationRate(int $companyId, ?int $branchId): float
    {
        $today = now()->startOfDay();
        $query = \App\Models\Appointment::where('company_id', $companyId)
            ->whereBetween('scheduled_at', [$today, now()->endOfDay()]);
            
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $total = $query->count();
        $cancelled = (clone $query)->where('status', 'cancelled')->count();
        
        return $total > 0 ? round($cancelled / $total * 100, 2) : 0;
    }
    
    protected function generateExport(array $params): string
    {
        // In a real implementation, this would generate and store the export file
        // For now, return a dummy URL
        $exportId = uniqid('export_');
        return url("/api/analytics/download/{$exportId}");
    }
    
    protected function estimateExportSize(array $params): string
    {
        // Estimate based on date range and export type
        $days = \Carbon\Carbon::parse($params['date_from'])->diffInDays(\Carbon\Carbon::parse($params['date_to']));
        $baseSize = match($params['export_type']) {
            'raw_data' => 1000,
            'processed_analytics' => 500,
            'predictions' => 200,
            default => 100
        };
        
        $estimatedKb = $baseSize * $days;
        
        if ($estimatedKb < 1024) {
            return $estimatedKb . ' KB';
        } elseif ($estimatedKb < 1024 * 1024) {
            return round($estimatedKb / 1024, 2) . ' MB';
        } else {
            return round($estimatedKb / (1024 * 1024), 2) . ' GB';
        }
    }
}