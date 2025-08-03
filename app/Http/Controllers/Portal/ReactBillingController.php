<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\BalanceTransaction;
use App\Models\BalanceTopup;
use App\Services\StripeTopupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReactBillingController extends Controller
{
    protected StripeTopupService $stripeService;
    
    public function __construct(StripeTopupService $stripeService)
    {
        $this->stripeService = $stripeService;
    }
    
    /**
     * Show billing overview with React app
     */
    public function index(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            return redirect()->route('business.login');
        }
        
        $company = Company::find($user->company_id);
        
        // Get current balance and recent transactions
        $currentBalance = $company->current_balance ?? 0;
        $transactions = BalanceTransaction::where('company_id', $user->company_id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        $topups = BalanceTopup::where('company_id', $user->company_id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Use unified layout for consistency
        return view('portal.billing.index-unified', compact('currentBalance', 'transactions', 'topups', 'company'));
    }
    
    /**
     * API endpoint for billing data
     */
    public function getBillingData(Request $request)
    {
        try {
            $user = Auth::guard('portal')->user();
            
            if (!$user || !$user->company_id) {
                Log::error('ReactBillingController: No authenticated user or company_id', [
                    'user' => $user ? $user->id : 'null',
                    'company_id' => $user ? $user->company_id : 'null',
                    'guard_check' => Auth::guard('portal')->check(),
                ]);
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        
        $company = Company::find($user->company_id);
        
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }
        
        // Get prepaid balance data
        $prepaidBalance = [
            'current_balance' => $company->balance ?? 0,
            'bonus_balance' => $company->bonus_balance ?? 0,
            'reserved_balance' => $company->reserved_balance ?? 0,
            'available_balance' => ($company->balance ?? 0) - ($company->reserved_balance ?? 0),
        ];
        
        // Get auto-topup settings
        $autoTopup = [
            'enabled' => $company->auto_topup_enabled ?? false,
            'threshold' => $company->auto_topup_threshold ?? 50,
            'amount' => $company->auto_topup_amount ?? 100,
            'last_topup' => null, // Will be populated from topups
        ];
        
        // Get spending limits
        $spendingLimits = [
            'daily_limit' => $company->daily_spending_limit ?? 0,
            'monthly_limit' => $company->monthly_spending_limit ?? 0,
            'daily_spent' => 0, // TODO: Calculate from transactions
            'monthly_spent' => 0, // TODO: Calculate from transactions
        ];
        
        // Get billing rate
        $billingRate = [
            'rate_per_minute' => 0.39, // Default rate
            'rate_per_sms' => 0.09,
            'currency' => 'EUR',
        ];
        
        // Get recent topups
        $recentTopups = BalanceTopup::where('company_id', $company->id)
            ->where('status', BalanceTopup::STATUS_SUCCEEDED)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($topup) {
                return [
                    'id' => $topup->id,
                    'amount' => $topup->amount,
                    'bonus' => $topup->bonus_amount ?? 0,
                    'created_at' => $topup->created_at->toIso8601String(),
                    'payment_method' => $topup->stripe_payment_method_type ?? 'card',
                ];
            });
            
        if ($recentTopups->isNotEmpty()) {
            $autoTopup['last_topup'] = $recentTopups->first()['created_at'];
        }
        
        // Get monthly usage stats
        $monthlyUsage = [
            'total_minutes' => 0,
            'total_calls' => 0,
            'total_sms' => 0,
            'total_cost' => 0,
        ];
        
        // TODO: Calculate actual usage from call logs
        
        return response()->json([
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'prepaid_balance' => $prepaidBalance,
            'auto_topup' => $autoTopup,
            'spending_limits' => $spendingLimits,
            'billing_rate' => $billingRate,
            'recent_topups' => $recentTopups,
            'monthly_usage' => $monthlyUsage,
        ]);
        
        } catch (\Exception $e) {
            Log::error('ReactBillingController::getBillingData error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Ein Fehler ist aufgetreten beim Laden der Abrechnungsdaten',
                'message' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * API endpoint for transactions
     */
    public function getTransactions(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $perPage = $request->get('per_page', 20);
        $type = $request->get('type');
        
        $query = BalanceTransaction::where('company_id', $user->company_id)
            ->orderBy('created_at', 'desc');
            
        if ($type) {
            $query->where('type', $type);
        }
        
        $transactions = $query->paginate($perPage);
        
        return response()->json([
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'last_page' => $transactions->lastPage(),
            ]
        ]);
    }
    
    /**
     * Update auto-topup settings
     */
    public function updateAutoTopup(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $request->validate([
            'enabled' => 'required|boolean',
            'threshold' => 'required_if:enabled,true|numeric|min:10|max:500',
            'amount' => 'required_if:enabled,true|numeric|min:50|max:5000',
        ]);
        
        $company = Company::find($user->company_id);
        
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }
        
        $company->update([
            'auto_topup_enabled' => $request->enabled,
            'auto_topup_threshold' => $request->threshold,
            'auto_topup_amount' => $request->amount,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Auto-Topup Einstellungen wurden aktualisiert',
            'data' => [
                'enabled' => $company->auto_topup_enabled,
                'threshold' => $company->auto_topup_threshold,
                'amount' => $company->auto_topup_amount,
            ]
        ]);
    }
    
    /**
     * Initiate topup process
     */
    public function initiateTopup(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $request->validate([
            'amount' => 'required|numeric|min:10|max:5000',
        ]);
        
        $company = Company::find($user->company_id);
        
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }
        
        try {
            // Create Stripe Checkout Session
            $checkoutSession = $this->stripeService->createCheckoutSession(
                $company,
                $request->amount,
                $user
            );
            
            if ($checkoutSession) {
                return response()->json([
                    'success' => true,
                    'checkout_url' => $checkoutSession->url,
                    'session_id' => $checkoutSession->id,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Fehler beim Erstellen der Zahlungssitzung'
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('Topup initiation failed', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Fehler bei der Zahlung: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * API endpoint for usage data
     */
    public function getUsageData(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Return usage data
        // TODO: Calculate actual usage from database
        return response()->json([
            'today_calls' => 12,
            'today_minutes' => 45,
            'weekly_calls' => 85,
            'weekly_minutes' => 312,
            'monthly_calls' => 342,
            'monthly_minutes' => 1245,
        ]);
    }
}