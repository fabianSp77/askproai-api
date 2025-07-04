<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\BalanceTransaction;
use App\Services\PrepaidBillingService;
use App\Services\BalanceMonitoringService;
use App\Services\StripeTopupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BillingController extends Controller
{
    protected PrepaidBillingService $billingService;
    protected BalanceMonitoringService $monitoringService;
    protected StripeTopupService $stripeService;

    public function __construct(
        PrepaidBillingService $billingService,
        BalanceMonitoringService $monitoringService,
        StripeTopupService $stripeService
    ) {
        $this->billingService = $billingService;
        $this->monitoringService = $monitoringService;
        $this->stripeService = $stripeService;
    }

    /**
     * Show billing overview
     */
    public function index(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // Handle admin viewing
        if (session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
            $company = Company::findOrFail($companyId);
        } else {
            $company = $user->company;
        }

        // Check permissions (skip for admin viewing)
        if (!session('is_admin_viewing') && !$user->canViewBilling()) {
            abort(403, 'Sie haben keine Berechtigung, die Abrechnung einzusehen.');
        }

        // Get balance status
        $balanceStatus = $this->monitoringService->getBalanceStatus($company);
        
        // Get recent transactions
        $transactions = BalanceTransaction::where('company_id', $company->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get usage statistics for current month
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $monthlyStats = $this->billingService->getUsageStatistics($company, $startOfMonth, $endOfMonth);

        // Get billing rate
        $billingRate = $this->billingService->getCompanyBillingRate($company);

        // Get suggested topup amounts
        $suggestedAmounts = $this->stripeService->getSuggestedAmounts($company);
        
        // Extract values from balanceStatus for the view
        $effectiveBalance = $balanceStatus['effective_balance'] ?? 0;
        $reservedBalance = $balanceStatus['reserved_balance'] ?? 0;
        $availableMinutes = $balanceStatus['available_minutes'] ?? 0;

        return view('portal.billing.index', compact(
            'company',
            'balanceStatus',
            'effectiveBalance',
            'reservedBalance',
            'availableMinutes',
            'transactions',
            'monthlyStats',
            'billingRate',
            'suggestedAmounts'
        ));
    }

    /**
     * Show topup page
     */
    public function topup(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // Handle admin viewing
        if (session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
            $company = Company::findOrFail($companyId);
        } else {
            $company = $user->company;
        }

        // Check permissions (skip for admin viewing)
        if (!session('is_admin_viewing') && !$user->hasPermission('billing.pay')) {
            abort(403, 'Sie haben keine Berechtigung, Guthaben aufzuladen.');
        }

        // Get suggested amounts
        $suggestedAmounts = $this->stripeService->getSuggestedAmounts($company);
        
        // Pre-select amount if provided
        $selectedAmount = $request->get('suggested', $suggestedAmounts[1] ?? 100);

        return view('portal.billing.topup', compact(
            'suggestedAmounts',
            'selectedAmount',
            'company'
        ));
    }

    /**
     * Process topup request
     */
    public function processTopup(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // Handle admin viewing
        if (session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
            $company = Company::findOrFail($companyId);
            
            // For admin viewing, we can't process payments
            return back()->with('error', 'Als Administrator können Sie keine Zahlungen für Kunden durchführen. Bitte informieren Sie den Kunden, sich selbst einzuloggen.');
        } else {
            $company = $user->company;
        }

        // Validate request
        $request->validate([
            'amount' => 'required|numeric|min:10|max:10000',
        ]);

        $amount = $request->input('amount');

        // Create Stripe Checkout Session
        $session = $this->stripeService->createCheckoutSession($company, $amount, $user);

        if (!$session) {
            return back()->with('error', 'Es gab einen Fehler beim Erstellen der Zahlungssitzung.');
        }

        // Redirect to Stripe Checkout
        return redirect($session->url);
    }

    /**
     * Handle successful topup
     */
    public function topupSuccess(Request $request)
    {
        $sessionId = $request->get('session_id');
        
        if ($sessionId) {
            // Process the successful payment
            $this->stripeService->handleCheckoutSessionCompleted($sessionId);
        }

        return redirect()->route('business.billing.index')
            ->with('success', 'Ihr Guthaben wurde erfolgreich aufgeladen.');
    }

    /**
     * Handle cancelled topup
     */
    public function topupCancel()
    {
        return redirect()->route('business.billing.index')
            ->with('info', 'Die Aufladung wurde abgebrochen.');
    }

    /**
     * Show transaction history
     */
    public function transactions(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // Handle admin viewing
        if (session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
            $company = Company::findOrFail($companyId);
        } else {
            $company = $user->company;
        }

        // Check permissions (skip for admin viewing)
        if (!session('is_admin_viewing') && !$user->canViewBilling()) {
            abort(403, 'Sie haben keine Berechtigung, die Transaktionen einzusehen.');
        }

        // Build query
        $query = BalanceTransaction::where('company_id', $company->id)
            ->with(['createdBy']);

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        // Get transactions
        $transactions = $query->orderBy('created_at', 'desc')
                             ->paginate(50);

        return view('portal.billing.transactions', compact('transactions'));
    }

    /**
     * Download invoice
     */
    public function downloadInvoice($transactionId)
    {
        $user = Auth::guard('portal')->user();
        
        // Handle admin viewing
        if (session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
        } else {
            if (!$user) {
                abort(403);
            }
            $companyId = $user->company_id;
        }
        
        $transaction = BalanceTransaction::where('company_id', $companyId)
                                        ->findOrFail($transactionId);

        // Check if this is a topup transaction
        if ($transaction->type !== 'topup' || !$transaction->reference_id) {
            abort(404, 'Keine Rechnung verfügbar.');
        }

        // TODO: Generate and download PDF invoice
        // For now, redirect back with message
        return back()->with('info', 'Die Rechnungsfunktion wird in Kürze verfügbar sein.');
    }

    /**
     * Show usage statistics
     */
    public function usage(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // Handle admin viewing
        if (session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
            $company = Company::findOrFail($companyId);
        } else {
            if (!$user) {
                abort(403);
            }
            $company = $user->company;
            
            // Check permissions
            if (!$user->canViewBilling()) {
                abort(403, 'Sie haben keine Berechtigung, die Nutzungsstatistiken einzusehen.');
            }
        }

        // Handle period selection
        $period = $request->get('period', 'this_month');
        $customFrom = $request->get('date_from');
        $customTo = $request->get('date_to');
        
        // Determine date range based on period
        switch ($period) {
            case 'last_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'last_7_days':
                $startDate = Carbon::now()->subDays(7)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                break;
            case 'last_30_days':
                $startDate = Carbon::now()->subDays(30)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                break;
            case 'custom':
                $startDate = $customFrom ? Carbon::parse($customFrom)->startOfDay() : Carbon::now()->startOfMonth();
                $endDate = $customTo ? Carbon::parse($customTo)->endOfDay() : Carbon::now()->endOfDay();
                break;
            default: // this_month
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
        }

        // Get usage statistics
        $stats = $this->billingService->getUsageStatistics($company, $startDate, $endDate);
        
        // Get billing rate
        $billingRate = $this->billingService->getCompanyBillingRate($company)->rate_per_minute;
        
        // Get daily usage for charts
        $dailyUsage = DB::table('calls')
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('duration_sec')
            ->where('duration_sec', '>', 0)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as call_count'),
                DB::raw('SUM(duration_sec) / 60 as total_minutes'),
                DB::raw('SUM(duration_sec) / 60 * ' . $billingRate . ' as total_cost')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Get top usage days
        $topDays = DB::table('calls')
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('duration_sec')
            ->where('duration_sec', '>', 0)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as call_count'),
                DB::raw('SUM(duration_sec) / 60 as total_minutes'),
                DB::raw('SUM(duration_sec) / 60 * ' . $billingRate . ' as total_cost')
            )
            ->groupBy('date')
            ->orderBy('total_cost', 'desc')
            ->limit(10)
            ->get();
        
        // Prepare chart data
        $chartData = $this->prepareChartData($dailyUsage, $startDate, $endDate);
        
        // Handle export requests
        if ($request->has('export')) {
            return $this->exportUsageData($request, $dailyUsage, $stats);
        }

        return view('portal.billing.usage', compact(
            'company',
            'stats',
            'topDays',
            'chartData',
            'billingRate',
            'period',
            'startDate',
            'endDate'
        ));
    }
    
    /**
     * Prepare chart data for usage statistics
     */
    protected function prepareChartData($dailyUsage, Carbon $startDate, Carbon $endDate)
    {
        // Create array with all dates in range
        $dates = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dates[$currentDate->format('Y-m-d')] = [
                'calls' => 0,
                'minutes' => 0,
            ];
            $currentDate->addDay();
        }
        
        // Fill in actual data
        foreach ($dailyUsage as $day) {
            $dates[$day->date] = [
                'calls' => $day->call_count,
                'minutes' => round($day->total_minutes, 0),
            ];
        }
        
        // Prepare data for charts
        $labels = [];
        $calls = [];
        $minutes = [];
        
        foreach ($dates as $date => $data) {
            $labels[] = Carbon::parse($date)->format('d.m.');
            $calls[] = $data['calls'];
            $minutes[] = $data['minutes'];
        }
        
        // Get company ID based on admin viewing or portal user
        if (session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
        } else {
            $user = Auth::guard('portal')->user();
            $companyId = $user ? $user->company_id : null;
        }
        
        // Duration distribution (for bar chart)
        $durationRanges = [
            '0-1 Min' => [0, 60],
            '1-3 Min' => [60, 180],
            '3-5 Min' => [180, 300],
            '5-10 Min' => [300, 600],
            '10-20 Min' => [600, 1200],
            '20+ Min' => [1200, PHP_INT_MAX],
        ];
        
        $durationCounts = [];
        if ($companyId) {
            foreach ($durationRanges as $label => $range) {
                $count = DB::table('calls')
                    ->where('company_id', $companyId)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->whereBetween('duration_sec', $range)
                    ->count();
                $durationCounts[] = $count;
            }
        } else {
            // Fill with zeros if no company ID
            $durationCounts = array_fill(0, count($durationRanges), 0);
        }
        
        return [
            'daily_labels' => $labels,
            'daily_calls' => $calls,
            'daily_minutes' => $minutes,
            'duration_labels' => array_keys($durationRanges),
            'duration_counts' => $durationCounts,
        ];
    }
    
    /**
     * Export usage data in different formats
     */
    protected function exportUsageData(Request $request, $data, array $stats)
    {
        $format = $request->get('export');
        
        if ($format === 'csv') {
            return $this->exportCsv($data, $stats);
        } elseif ($format === 'pdf') {
            return $this->exportPdf($data, $stats);
        }
        
        return redirect()->back();
    }
    
    /**
     * Export usage data as CSV
     */
    protected function exportCsv($data, array $stats)
    {
        $filename = 'nutzungsbericht_' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($data, $stats) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Summary
            fputcsv($file, ['Nutzungsbericht'], ';');
            fputcsv($file, ['Erstellt am:', now()->format('d.m.Y H:i')], ';');
            fputcsv($file, [], ';');
            fputcsv($file, ['Zusammenfassung'], ';');
            fputcsv($file, ['Anrufe gesamt:', $stats['total_calls']], ';');
            fputcsv($file, ['Minuten gesamt:', $stats['total_minutes']], ';');
            fputcsv($file, ['Kosten gesamt:', number_format($stats['total_cost'], 2, ',', '.') . ' €'], ';');
            fputcsv($file, [], ';');
            
            // Daily data
            fputcsv($file, ['Datum', 'Anrufe', 'Minuten', 'Kosten'], ';');
            foreach ($data as $day) {
                fputcsv($file, [
                    Carbon::parse($day->date)->format('d.m.Y'),
                    $day->call_count,
                    round($day->total_minutes, 0),
                    number_format($day->total_cost, 2, ',', '.') . ' €'
                ], ';');
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Export usage data as PDF
     */
    protected function exportPdf($data, array $stats)
    {
        // TODO: Implement PDF export with a library like DomPDF or TCPDF
        session()->flash('info', 'PDF-Export wird in Kürze verfügbar sein.');
        return redirect()->back();
    }
}