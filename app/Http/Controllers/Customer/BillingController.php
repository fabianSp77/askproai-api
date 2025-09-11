<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\StripeCheckoutService;
use App\Services\InvoiceGenerator;
use App\Models\BalanceTopup;
use App\Models\Transaction;

class BillingController extends Controller
{
    private StripeCheckoutService $stripeService;
    private InvoiceGenerator $invoiceGenerator;
    
    public function __construct()
    {
        $this->stripeService = new StripeCheckoutService();
        $this->invoiceGenerator = new InvoiceGenerator();
    }
    
    /**
     * Display billing overview
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $tenant = $user->tenant;
        
        // Get recent topups
        $recentTopups = BalanceTopup::where('tenant_id', $tenant->id)
            ->where('status', 'succeeded')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get billing statistics
        $stats = $this->getBillingStatistics($tenant);
        
        // Get available topup amounts with bonuses
        $topupOptions = $this->getTopupOptions();
        
        // Check if auto-topup is enabled
        $autoTopupSettings = [
            'enabled' => $tenant->settings['auto_topup_enabled'] ?? false,
            'threshold' => ($tenant->settings['auto_topup_threshold'] ?? 1000) / 100,
            'amount' => ($tenant->settings['auto_topup_amount'] ?? 5000) / 100,
            'payment_method' => $tenant->settings['stripe_payment_method_id'] ?? null
        ];
        
        return view('customer.billing.index', compact(
            'tenant',
            'recentTopups',
            'stats',
            'topupOptions',
            'autoTopupSettings'
        ));
    }
    
    /**
     * Show topup form
     */
    public function topup(Request $request)
    {
        $user = $request->user();
        $tenant = $user->tenant;
        
        // Get predefined amounts with bonuses
        $amounts = [
            ['value' => 1000, 'label' => '10 €', 'bonus' => 0],
            ['value' => 2500, 'label' => '25 €', 'bonus' => 150],  // 1.50€ bonus
            ['value' => 5000, 'label' => '50 €', 'bonus' => 400],   // 4€ bonus
            ['value' => 10000, 'label' => '100 €', 'bonus' => 1000], // 10€ bonus
            ['value' => 25000, 'label' => '250 €', 'bonus' => 3000], // 30€ bonus
        ];
        
        return view('customer.billing.topup', compact('tenant', 'amounts'));
    }
    
    /**
     * Process topup request
     */
    public function processTopup(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:1000|max:100000', // 10€ - 1000€
            'idempotency_key' => 'sometimes|string|max:64'
        ]);
        
        $user = $request->user();
        $tenant = $user->tenant;
        $amountCents = $request->amount;
        
        // Generate or use provided idempotency key
        $idempotencyKey = $request->idempotency_key 
            ?? hash('sha256', $tenant->id . $amountCents . microtime());
        
        // Acquire lock to prevent race conditions
        $lock = Cache::lock("topup.{$tenant->id}", 30);
        
        if (!$lock->get()) {
            return response()->json([
                'error' => 'Eine andere Zahlung wird gerade verarbeitet. Bitte versuchen Sie es in einem Moment erneut.'
            ], 429);
        }
        
        try {
            // Check if this idempotency key was already processed
            $existingSession = Cache::get("stripe.checkout.{$idempotencyKey}");
            if ($existingSession) {
                return response()->json([
                    'checkout_url' => $existingSession['checkout_url'],
                    'session_id' => $existingSession['session_id']
                ]);
            }
            
            // Create Stripe checkout session
            $result = $this->stripeService->createTopupSession(
                $tenant,
                $amountCents,
                ['idempotency_key' => $idempotencyKey]
            );
            
            // Log topup initiation
            Log::info('Topup initiated', [
                'tenant_id' => $tenant->id,
                'amount' => $amountCents,
                'session_id' => $result['session_id']
            ]);
            
            return response()->json([
                'success' => true,
                'checkout_url' => $result['checkout_url'],
                'session_id' => $result['session_id'],
                'amount' => $result['amount'],
                'bonus' => $result['bonus'],
                'total_credit' => $result['total_credit']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Topup creation failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Die Zahlung konnte nicht initialisiert werden. Bitte versuchen Sie es später erneut.'
            ], 500);
            
        } finally {
            $lock->release();
        }
    }
    
    /**
     * Handle successful payment return
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        
        if (!$sessionId) {
            return redirect()->route('customer.billing.index')
                ->with('error', 'Ungültige Zahlungssitzung');
        }
        
        // Process the successful payment
        try {
            $this->stripeService->processSuccessfulPayment($sessionId);
            
            // Get topup details for display
            $topup = BalanceTopup::where('stripe_checkout_session_id', $sessionId)
                ->first();
            
            if ($topup) {
                // Generate invoice
                dispatch(function () use ($topup) {
                    $invoicePath = $this->invoiceGenerator->generateTopupInvoice($topup);
                    
                    // Send invoice via email
                    if ($topup->tenant->billing_email) {
                        $this->invoiceGenerator->sendInvoiceEmail(
                            $invoicePath,
                            $topup->tenant->billing_email
                        );
                    }
                })->afterResponse();
            }
            
            return view('customer.billing.success', [
                'topup' => $topup,
                'message' => 'Zahlung erfolgreich! Ihr Guthaben wurde aufgeladen.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Payment success handling failed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('customer.billing.index')
                ->with('warning', 'Die Zahlung wurde empfangen, aber es gab ein Problem bei der Verarbeitung. Unser Support wurde benachrichtigt.');
        }
    }
    
    /**
     * Handle cancelled payment
     */
    public function cancel(Request $request)
    {
        $sessionId = $request->query('session_id');
        
        if ($sessionId) {
            $this->stripeService->processCancelledPayment($sessionId);
        }
        
        return view('customer.billing.cancel', [
            'message' => 'Zahlung abgebrochen. Sie können es jederzeit erneut versuchen.'
        ]);
    }
    
    /**
     * Show auto-topup settings
     */
    public function autoTopupSettings(Request $request)
    {
        $user = $request->user();
        $tenant = $user->tenant;
        
        $settings = [
            'enabled' => $tenant->settings['auto_topup_enabled'] ?? false,
            'threshold' => ($tenant->settings['auto_topup_threshold'] ?? 1000) / 100,
            'amount' => ($tenant->settings['auto_topup_amount'] ?? 5000) / 100,
            'payment_method_id' => $tenant->settings['stripe_payment_method_id'] ?? null,
            'customer_id' => $tenant->stripe_customer_id
        ];
        
        return view('customer.billing.auto-topup', compact('settings', 'tenant'));
    }
    
    /**
     * Update auto-topup settings
     */
    public function updateAutoTopup(Request $request)
    {
        $request->validate([
            'enabled' => 'required|boolean',
            'threshold' => 'required_if:enabled,true|integer|min:5|max:100',
            'amount' => 'required_if:enabled,true|integer|min:10|max:500',
            'payment_method_id' => 'required_if:enabled,true|string'
        ]);
        
        $user = $request->user();
        $tenant = $user->tenant;
        
        DB::transaction(function () use ($request, $tenant) {
            $settings = $tenant->settings ?? [];
            
            $settings['auto_topup_enabled'] = $request->enabled;
            
            if ($request->enabled) {
                $settings['auto_topup_threshold'] = $request->threshold * 100; // Convert to cents
                $settings['auto_topup_amount'] = $request->amount * 100;
                $settings['stripe_payment_method_id'] = $request->payment_method_id;
                $settings['auto_topup_updated_at'] = now()->toIso8601String();
            } else {
                // Clear auto-topup settings when disabled
                unset(
                    $settings['auto_topup_threshold'],
                    $settings['auto_topup_amount'],
                    $settings['stripe_payment_method_id']
                );
            }
            
            $tenant->settings = $settings;
            $tenant->save();
            
            // Clear cache
            Cache::forget("tenant.settings.{$tenant->id}");
        });
        
        $message = $request->enabled 
            ? 'Automatische Aufladung wurde aktiviert'
            : 'Automatische Aufladung wurde deaktiviert';
        
        return redirect()->route('customer.billing.auto-topup')
            ->with('success', $message);
    }
    
    /**
     * Get billing statistics
     */
    private function getBillingStatistics($tenant)
    {
        $cacheKey = "billing.stats.{$tenant->id}";
        
        return Cache::remember($cacheKey, 300, function () use ($tenant) {
            $thirtyDaysAgo = now()->subDays(30);
            
            // Total topped up in last 30 days
            $totalToppedUp = BalanceTopup::where('tenant_id', $tenant->id)
                ->where('status', 'succeeded')
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->sum(DB::raw('amount + bonus_amount'));
            
            // Total spent in last 30 days
            $totalSpent = Transaction::where('tenant_id', $tenant->id)
                ->where('type', 'usage')
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->sum('amount_cents');
            
            // Average daily usage
            $avgDailyUsage = abs($totalSpent) / 30 / 100;
            
            // Days until balance depleted (at current rate)
            $daysRemaining = $avgDailyUsage > 0 
                ? floor($tenant->balance_cents / 100 / $avgDailyUsage)
                : 999;
            
            return [
                'total_topped_up' => $totalToppedUp,
                'total_spent' => abs($totalSpent) / 100,
                'avg_daily_usage' => $avgDailyUsage,
                'days_remaining' => min($daysRemaining, 999),
                'current_balance' => $tenant->balance_cents / 100
            ];
        });
    }
    
    /**
     * Get topup options with bonuses
     */
    private function getTopupOptions()
    {
        return [
            ['amount' => 10, 'bonus' => 0, 'popular' => false],
            ['amount' => 25, 'bonus' => 1.50, 'popular' => false],
            ['amount' => 50, 'bonus' => 4, 'popular' => true],
            ['amount' => 100, 'bonus' => 10, 'popular' => false],
            ['amount' => 250, 'bonus' => 30, 'popular' => false],
        ];
    }
}