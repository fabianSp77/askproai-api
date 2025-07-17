<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\ReactDashboardController;
use App\Models\Company;
use App\Models\BalanceTransaction;
use App\Models\Invoice;
use App\Models\BalanceTopup;
use App\Services\PrepaidBillingService;
use App\Services\BalanceMonitoringService;
use App\Services\StripeTopupService;
use App\Services\SpendingLimitService;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BillingController extends Controller
{
    protected PrepaidBillingService $billingService;
    protected BalanceMonitoringService $monitoringService;
    protected StripeTopupService $stripeService;
    protected SpendingLimitService $spendingLimitService;
    protected InvoicePdfService $invoicePdfService;

    public function __construct(
        PrepaidBillingService $billingService,
        BalanceMonitoringService $monitoringService,
        StripeTopupService $stripeService,
        SpendingLimitService $spendingLimitService,
        InvoicePdfService $invoicePdfService
    ) {
        $this->billingService = $billingService;
        $this->monitoringService = $monitoringService;
        $this->stripeService = $stripeService;
        $this->spendingLimitService = $spendingLimitService;
        $this->invoicePdfService = $invoicePdfService;
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
        
        // Get prepaid balance with bonus
        $prepaidBalance = $this->billingService->getOrCreateBalance($company);
        
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
        
        // Get applicable bonus rules
        $bonusRules = $this->billingService->getApplicableBonusRules($company);
        
        // Get spending summary
        $spendingSummary = $this->spendingLimitService->getSpendingSummary($company);
        
        // Extract values from balanceStatus for the view
        $effectiveBalance = $balanceStatus['effective_balance'] ?? 0;
        $reservedBalance = $balanceStatus['reserved_balance'] ?? 0;
        $availableMinutes = $balanceStatus['available_minutes'] ?? 0;
        $bonusBalance = $prepaidBalance->bonus_balance;
        $totalBalance = $prepaidBalance->getTotalBalance();

        // Load React SPA directly
        return app(ReactDashboardController::class)->index();
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
        if (!session('is_admin_viewing') && (!$user || !$user->hasPermission('billing.pay'))) {
            abort(403, 'Sie haben keine Berechtigung, Guthaben aufzuladen.');
        }

        // Get suggested amounts
        $suggestedAmounts = $this->stripeService->getSuggestedAmounts($company);
        
        // Pre-select amount if provided
        $selectedAmount = $request->get('suggested', $suggestedAmounts[1] ?? 100);
        
        // Get applicable bonus rules
        $bonusRules = $this->billingService->getApplicableBonusRules($company);
        
        // Get prepaid balance
        $prepaidBalance = $this->billingService->getOrCreateBalance($company);

        // Load React SPA directly
        return app(ReactDashboardController::class)->index();
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

        // Store message in session for React to display
        session()->flash('success', 'Ihr Guthaben wurde erfolgreich aufgeladen.');
        
        // Return React SPA
        return app(ReactDashboardController::class)->index();
    }

    /**
     * Handle cancelled topup
     */
    public function topupCancel()
    {
        // Store message in session for React to display
        session()->flash('info', 'Die Aufladung wurde abgebrochen.');
        
        // Return React SPA
        return app(ReactDashboardController::class)->index();
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

        // Redirect to React billing page
        return app(ReactDashboardController::class)->index();
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

        // Try to find the topup record
        $topup = BalanceTopup::find($transaction->reference_id);
        
        if (!$topup) {
            abort(404, 'Aufladung nicht gefunden.');
        }
        
        // Option 1: Check if we have a local invoice
        if ($topup->invoice_id) {
            $invoice = Invoice::findOrFail($topup->invoice_id);
            
            // Generate or get PDF
            try {
                $pdfPath = $this->invoicePdfService->getPdf($invoice);
                $filename = $this->invoicePdfService->getDownloadFilename($invoice);
                
                return response()->download($pdfPath, $filename, [
                    'Content-Type' => 'application/pdf',
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to generate invoice PDF', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
                return back()->with('error', 'Fehler beim Erstellen der Rechnung.');
            }
        }
        
        // Option 2: Use Stripe invoice URL if available
        if ($topup->stripe_invoice_id) {
            try {
                $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
                $stripeInvoice = $stripe->invoices->retrieve($topup->stripe_invoice_id);
                
                if ($stripeInvoice->invoice_pdf) {
                    // Redirect to Stripe's PDF
                    return redirect($stripeInvoice->invoice_pdf);
                }
            } catch (\Exception $e) {
                Log::error('Failed to retrieve Stripe invoice', [
                    'stripe_invoice_id' => $topup->stripe_invoice_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // No invoice available
        return back()->with('info', 'Für diese Transaktion ist noch keine Rechnung verfügbar. Bitte kontaktieren Sie den Support.');
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

        // Redirect to React billing page
        return app(ReactDashboardController::class)->index();
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
    
    /**
     * Show auto-topup settings page
     */
    public function autoTopup(Request $request)
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
        if (!session('is_admin_viewing') && !$user->canManageBilling()) {
            abort(403, 'Sie haben keine Berechtigung, die Auto-Aufladung zu verwalten.');
        }

        // Get prepaid balance
        $prepaidBalance = $this->billingService->getOrCreateBalance($company);
        
        // Get saved payment methods from Stripe
        $savedPaymentMethods = collect();
        if ($company->stripe_customer_id) {
            try {
                $savedPaymentMethods = $this->stripeService->getSavedPaymentMethods($company);
            } catch (\Exception $e) {
                \Log::error('Failed to fetch saved payment methods', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Get bonus rules for display
        $bonusRules = $this->billingService->getApplicableBonusRules($company);
        
        // Calculate applicable bonus for current auto-topup amount
        $applicableBonus = null;
        if ($prepaidBalance->auto_topup_amount) {
            $bonusCalculation = $this->billingService->calculateBonus($prepaidBalance->auto_topup_amount, $company);
            if ($bonusCalculation['rule']) {
                $applicableBonus = [
                    'bonus_percentage' => $bonusCalculation['rule']->bonus_percentage,
                    'bonus_amount' => $bonusCalculation['bonus_amount']
                ];
            }
        }

        // Redirect to React billing page
        return app(ReactDashboardController::class)->index();
    }
    
    /**
     * Update auto-topup settings
     */
    public function updateAutoTopup(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // Handle admin viewing
        if (session('is_admin_viewing')) {
            return back()->with('error', 'Als Administrator können Sie die Auto-Aufladung nicht für Kunden aktivieren.');
        }
        
        $company = $user->company;
        
        // Check permissions
        if (!$user->canManageBilling()) {
            abort(403, 'Sie haben keine Berechtigung, die Auto-Aufladung zu verwalten.');
        }
        
        // Validate request
        $validated = $request->validate([
            'auto_topup_enabled' => 'nullable|boolean',
            'auto_topup_threshold' => 'required_if:auto_topup_enabled,1|numeric|min:10|max:500',
            'auto_topup_amount' => 'required_if:auto_topup_enabled,1|numeric|min:50|max:5000',
            'auto_topup_daily_limit' => 'required_if:auto_topup_enabled,1|integer|min:1|max:5',
            'auto_topup_monthly_limit' => 'required_if:auto_topup_enabled,1|integer|min:5|max:30',
            'payment_method_id' => 'required_if:auto_topup_enabled,1|string',
        ]);
        
        // Get prepaid balance
        $prepaidBalance = $this->billingService->getOrCreateBalance($company);
        
        // Update settings
        $prepaidBalance->auto_topup_enabled = $request->boolean('auto_topup_enabled');
        
        if ($prepaidBalance->auto_topup_enabled) {
            // Verify payment method exists and belongs to company
            if ($request->payment_method_id) {
                try {
                    $paymentMethod = $this->stripeService->getPaymentMethod($request->payment_method_id);
                    if ($paymentMethod->customer !== $company->stripe_customer_id) {
                        return back()->with('error', 'Die ausgewählte Zahlungsmethode ist ungültig.');
                    }
                    $prepaidBalance->auto_topup_payment_method_id = $request->payment_method_id;
                } catch (\Exception $e) {
                    return back()->with('error', 'Die ausgewählte Zahlungsmethode konnte nicht verifiziert werden.');
                }
            }
            
            $prepaidBalance->auto_topup_threshold = $validated['auto_topup_threshold'];
            $prepaidBalance->auto_topup_amount = $validated['auto_topup_amount'];
            $prepaidBalance->auto_topup_daily_limit = $validated['auto_topup_daily_limit'];
            $prepaidBalance->auto_topup_monthly_limit = $validated['auto_topup_monthly_limit'];
        } else {
            // Clear payment method when disabling
            $prepaidBalance->auto_topup_payment_method_id = null;
        }
        
        $prepaidBalance->save();
        
        // Log the change
        activity()
            ->performedOn($prepaidBalance)
            ->causedBy($user)
            ->withProperties([
                'auto_topup_enabled' => $prepaidBalance->auto_topup_enabled,
                'threshold' => $prepaidBalance->auto_topup_threshold,
                'amount' => $prepaidBalance->auto_topup_amount,
            ])
            ->log('Auto-topup settings updated');
        
        return redirect()->route('business.billing.auto-topup')
            ->with('success', 'Die Auto-Aufladung Einstellungen wurden erfolgreich gespeichert.');
    }
    
    /**
     * Show payment methods page
     */
    public function paymentMethods(Request $request)
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
        if (!session('is_admin_viewing') && !$user->canManageBilling()) {
            abort(403, 'Sie haben keine Berechtigung, die Zahlungsmethoden zu verwalten.');
        }

        // Get saved payment methods from Stripe
        $savedPaymentMethods = collect();
        if ($company->stripe_customer_id) {
            try {
                $savedPaymentMethods = $this->stripeService->getSavedPaymentMethods($company);
            } catch (\Exception $e) {
                \Log::error('Failed to fetch saved payment methods', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Redirect to React billing page
        return app(ReactDashboardController::class)->index();
    }
    
    /**
     * Add payment method page
     */
    public function addPaymentMethod(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // Handle admin viewing
        if (session('is_admin_viewing')) {
            return back()->with('error', 'Als Administrator können Sie keine Zahlungsmethoden für Kunden hinzufügen.');
        }
        
        $company = $user->company;
        
        // Check permissions
        if (!$user->canManageBilling()) {
            abort(403, 'Sie haben keine Berechtigung, Zahlungsmethoden hinzuzufügen.');
        }
        
        // Create setup intent for adding payment method
        $setupIntent = $this->stripeService->createSetupIntent($company);
        
        // Redirect to React billing page
        return app(ReactDashboardController::class)->index();
    }
    
    /**
     * Store payment method
     */
    public function storePaymentMethod(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (session('is_admin_viewing')) {
            return back()->with('error', 'Als Administrator können Sie keine Zahlungsmethoden für Kunden hinzufügen.');
        }
        
        $company = $user->company;
        
        // Check permissions
        if (!$user->canManageBilling()) {
            abort(403, 'Sie haben keine Berechtigung, Zahlungsmethoden hinzuzufügen.');
        }
        
        $request->validate([
            'payment_method_id' => 'required|string',
        ]);
        
        try {
            // Attach payment method to customer
            $this->stripeService->attachPaymentMethod($company, $request->payment_method_id);
            
            return redirect()->route('business.billing.payment-methods')
                ->with('success', 'Zahlungsmethode wurde erfolgreich hinzugefügt.');
        } catch (\Exception $e) {
            return back()->with('error', 'Die Zahlungsmethode konnte nicht hinzugefügt werden: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete payment method
     */
    public function deletePaymentMethod(Request $request, $paymentMethodId)
    {
        $user = Auth::guard('portal')->user();
        
        if (session('is_admin_viewing')) {
            return back()->with('error', 'Als Administrator können Sie keine Zahlungsmethoden für Kunden löschen.');
        }
        
        $company = $user->company;
        
        // Check permissions
        if (!$user->canManageBilling()) {
            abort(403, 'Sie haben keine Berechtigung, Zahlungsmethoden zu löschen.');
        }
        
        try {
            // Check if this payment method is used for auto-topup
            $prepaidBalance = $this->billingService->getOrCreateBalance($company);
            if ($prepaidBalance->auto_topup_payment_method_id === $paymentMethodId) {
                return back()->with('error', 'Diese Zahlungsmethode wird für Auto-Aufladung verwendet und kann nicht gelöscht werden.');
            }
            
            // Detach payment method
            $this->stripeService->detachPaymentMethod($paymentMethodId);
            
            return redirect()->route('business.billing.payment-methods')
                ->with('success', 'Zahlungsmethode wurde erfolgreich entfernt.');
        } catch (\Exception $e) {
            return back()->with('error', 'Die Zahlungsmethode konnte nicht entfernt werden: ' . $e->getMessage());
        }
    }
}