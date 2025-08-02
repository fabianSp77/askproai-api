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

class WorkingBillingController extends Controller
{
    protected StripeTopupService $stripeService;
    
    public function __construct(StripeTopupService $stripeService)
    {
        $this->stripeService = $stripeService;
    }
    
    /**
     * Show billing overview with real data
     */
    public function index(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            return redirect()->route('business.login');
        }
        
        $company = Company::find($user->company_id);
        
        if (!$company) {
            return redirect()->route('business.login');
        }
        
        // Get real billing data
        $data = [
            'balance' => $company->balance ?? 0,
            'currency' => 'EUR',
            'auto_topup_enabled' => $company->auto_topup_enabled ?? false,
            'auto_topup_threshold' => $company->auto_topup_threshold ?? 50,
            'auto_topup_amount' => $company->auto_topup_amount ?? 100,
        ];
        
        // Get real transactions
        $transactions = BalanceTransaction::where('company_id', $company->id)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->map(function($transaction) {
                return (object)[
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'description' => $transaction->description ?? 'Transaktion',
                    'created_at' => $transaction->created_at,
                    'status' => $transaction->status ?? 'completed',
                    'invoice_url' => $transaction->invoice_id ? route('business.billing.invoice.download', $transaction->invoice_id) : null
                ];
            });
        
        return view('portal.billing.simple-index', compact('data', 'transactions', 'company'));
    }
    
    /**
     * Show topup page
     */
    public function topup(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            return redirect()->route('business.login');
        }
        
        $company = Company::find($user->company_id);
        
        if (!$company) {
            return redirect()->route('business.login');
        }
        
        $suggestedAmount = $request->get('suggested', 100);
        
        return view('portal.billing.topup', compact('company', 'suggestedAmount'));
    }
    
    /**
     * Process topup with Stripe
     */
    public function processTopup(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            return redirect()->route('business.login');
        }
        
        $request->validate([
            'amount' => 'required|numeric|min:10|max:1000',
        ]);
        
        $company = Company::find($user->company_id);
        
        if (!$company) {
            return redirect()->route('business.login');
        }
        
        try {
            // Create Stripe Checkout Session
            $checkoutSession = $this->stripeService->createCheckoutSession(
                $company,
                $request->amount,
                $user
            );
            
            if ($checkoutSession) {
                // Redirect to Stripe Checkout
                return redirect($checkoutSession->url);
            } else {
                return back()->with('error', 'Fehler beim Erstellen der Zahlungssitzung.');
            }
            
        } catch (\Exception $e) {
            Log::error('Topup failed', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Fehler bei der Zahlung: ' . $e->getMessage());
        }
    }
    
    /**
     * Show invoices
     */
    public function invoices(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            return redirect()->route('business.login');
        }
        
        // Get invoices from balance topups
        $invoices = BalanceTopup::where('company_id', $user->company_id)
            ->where('status', BalanceTopup::STATUS_SUCCEEDED)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($topup) {
                return (object)[
                    'id' => $topup->stripe_invoice_id ?? 'TOP-' . $topup->id,
                    'amount' => $topup->amount,
                    'created_at' => $topup->created_at,
                    'payment_method' => $topup->stripe_payment_method_type ?? 'card',
                    'status' => 'paid'
                ];
            });
        
        return view('portal.billing.invoices', compact('invoices'));
    }
    
    /**
     * Download invoice
     */
    public function downloadInvoice($invoiceId)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            return redirect()->route('business.login');
        }
        
        // For now, redirect back with a message
        return back()->with('info', 'Rechnungsdownload wird implementiert.');
    }
}