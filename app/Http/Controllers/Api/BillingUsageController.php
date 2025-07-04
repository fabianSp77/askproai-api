<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Billing\UsageCalculationService;
use App\Models\BillingPeriod;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BillingUsageController extends Controller
{
    protected UsageCalculationService $usageService;
    
    public function __construct(UsageCalculationService $usageService)
    {
        $this->usageService = $usageService;
    }
    
    /**
     * Get current period usage
     */
    public function currentUsage(Request $request): JsonResponse
    {
        $company = $request->user()->company;
        
        if (!$company) {
            return response()->json([
                'error' => 'No company associated with user'
            ], 403);
        }
        
        $usage = $this->usageService->getCurrentPeriodUsage($company);
        
        return response()->json([
            'success' => true,
            'data' => $usage
        ]);
    }
    
    /**
     * Get usage projection for current period
     */
    public function projection(Request $request): JsonResponse
    {
        $company = $request->user()->company;
        
        if (!$company) {
            return response()->json([
                'error' => 'No company associated with user'
            ], 403);
        }
        
        $projection = $this->usageService->projectMonthEndUsage($company);
        
        return response()->json([
            'success' => true,
            'data' => $projection
        ]);
    }
    
    /**
     * Get billing history
     */
    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'integer|min:1|max:100',
            'offset' => 'integer|min:0'
        ]);
        
        $company = $request->user()->company;
        
        if (!$company) {
            return response()->json([
                'error' => 'No company associated with user'
            ], 403);
        }
        
        $limit = $request->input('limit', 12);
        $offset = $request->input('offset', 0);
        
        $periods = BillingPeriod::where('company_id', $company->id)
            ->with(['invoice' => function ($query) {
                $query->select('id', 'number', 'status', 'total', 'paid_at', 'pdf_url');
            }])
            ->orderBy('start_date', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function ($period) {
                return [
                    'id' => $period->id,
                    'period' => [
                        'start' => $period->start_date->format('Y-m-d'),
                        'end' => $period->end_date->format('Y-m-d')
                    ],
                    'usage' => [
                        'minutes' => $period->used_minutes,
                        'included' => $period->included_minutes,
                        'overage' => $period->overage_minutes
                    ],
                    'costs' => [
                        'base' => $period->base_fee,
                        'overage' => $period->overage_cost,
                        'total' => $period->total_cost
                    ],
                    'invoice' => $period->invoice ? [
                        'id' => $period->invoice->id,
                        'number' => $period->invoice->number,
                        'status' => $period->invoice->status,
                        'total' => $period->invoice->total,
                        'paid_at' => $period->invoice->paid_at?->format('Y-m-d'),
                        'download_url' => $period->invoice->pdf_url
                    ] : null
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $periods,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => BillingPeriod::where('company_id', $company->id)->count()
            ]
        ]);
    }
    
    /**
     * Get specific billing period details
     */
    public function periodDetails(Request $request, $periodId): JsonResponse
    {
        $company = $request->user()->company;
        
        if (!$company) {
            return response()->json([
                'error' => 'No company associated with user'
            ], 403);
        }
        
        $period = BillingPeriod::where('company_id', $company->id)
            ->where('id', $periodId)
            ->first();
        
        if (!$period) {
            return response()->json([
                'error' => 'Billing period not found'
            ], 404);
        }
        
        $usage = $this->usageService->calculatePeriodUsage($period);
        
        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'id' => $period->id,
                    'start' => $period->start_date->format('Y-m-d'),
                    'end' => $period->end_date->format('Y-m-d'),
                    'status' => $period->status
                ],
                'usage' => $usage
            ]
        ]);
    }
    
    /**
     * Download usage report
     */
    public function downloadReport(Request $request, $periodId)
    {
        $company = $request->user()->company;
        
        if (!$company) {
            abort(403, 'No company associated with user');
        }
        
        $period = BillingPeriod::where('company_id', $company->id)
            ->where('id', $periodId)
            ->first();
        
        if (!$period) {
            abort(404, 'Billing period not found');
        }
        
        $usage = $this->usageService->calculatePeriodUsage($period);
        
        // Generate CSV report
        $csv = $this->generateUsageReportCsv($period, $usage);
        
        $filename = sprintf(
            'usage_report_%s_%s.csv',
            $company->slug,
            $period->start_date->format('Y-m')
        );
        
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }
    
    /**
     * Generate CSV report
     */
    protected function generateUsageReportCsv(BillingPeriod $period, array $usage): string
    {
        $csv = "Usage Report\n";
        $csv .= "Company: {$period->company->name}\n";
        $csv .= "Period: {$period->start_date->format('Y-m-d')} to {$period->end_date->format('Y-m-d')}\n";
        $csv .= "\n";
        
        // Call usage
        $csv .= "Call Usage\n";
        $csv .= "Metric,Value\n";
        $csv .= "Total Calls,{$usage['calls']['total_calls']}\n";
        $csv .= "Total Minutes,{$usage['calls']['total_minutes']}\n";
        $csv .= "Average Duration,{$usage['calls']['avg_duration_minutes']} minutes\n";
        $csv .= "Unique Callers,{$usage['calls']['unique_callers']}\n";
        $csv .= "Conversion Rate,{$usage['calls']['conversion_rate']}%\n";
        $csv .= "\n";
        
        // Appointment usage
        $csv .= "Appointment Usage\n";
        $csv .= "Metric,Value\n";
        $csv .= "Total Appointments,{$usage['appointments']['total_appointments']}\n";
        $csv .= "AI Booked,{$usage['appointments']['ai_booked']}\n";
        $csv .= "Manual Booked,{$usage['appointments']['manual_booked']}\n";
        $csv .= "Completion Rate,{$usage['appointments']['completion_rate']}%\n";
        $csv .= "\n";
        
        // Cost breakdown
        $csv .= "Cost Breakdown\n";
        $csv .= "Item,Amount\n";
        $csv .= "Base Fee,€{$usage['calculations']['base_fee']}\n";
        $csv .= "Minutes Cost,€{$usage['calculations']['minutes_cost']}\n";
        $csv .= "Appointments Cost,€{$usage['calculations']['appointments_cost']}\n";
        $csv .= "Total Cost,€{$usage['calculations']['total_cost']}\n";
        $csv .= "\n";
        
        // Daily breakdown
        $csv .= "Daily Breakdown\n";
        $csv .= "Date,Calls,Minutes\n";
        foreach ($usage['calls']['daily_distribution'] as $day) {
            $csv .= "{$day['date']},{$day['calls']},{$day['minutes']}\n";
        }
        
        return $csv;
    }
}