<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\BalanceTopup;
use App\Services\StripeTopupService;
use App\Services\PrepaidBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;

class PublicTopupController extends Controller
{
    protected StripeTopupService $stripeService;
    protected PrepaidBillingService $billingService;

    public function __construct(
        StripeTopupService $stripeService,
        PrepaidBillingService $billingService
    ) {
        $this->stripeService = $stripeService;
        $this->billingService = $billingService;
    }

    /**
     * Show topup form for a specific company
     */
    public function showTopupForm(Request $request, $companyId)
    {
        $company = Company::findOrFail($companyId);
        
        // Get balance information
        $balance = $this->billingService->getOrCreateBalance($company);
        $currentBalance = $balance->getTotalBalance();
        
        // Get usage statistics for the last 30 days
        $startDate = now()->subDays(30);
        $endDate = now();
        $usageStats = $this->billingService->getUsageStatistics($company, $startDate, $endDate);
        
        // Calculate average daily usage and days remaining
        $avgDailyUsage = ($usageStats['total_charged'] ?? 0) / 30;
        $daysRemaining = $avgDailyUsage > 0 ? $currentBalance / $avgDailyUsage : 999;
        
        // Calculate call-based metrics
        $avgCallDuration = $usageStats['average_call_duration'] ?? 0;
        $avgCallCost = $usageStats['average_call_cost'] ?? 0;
        $totalCalls = $usageStats['total_calls'] ?? 0;
        $callsPerDay = $totalCalls > 0 ? $totalCalls / 30 : 0;
        
        // Calculate how many calls the current balance can support
        $callsRemaining = $avgCallCost > 0 ? floor($currentBalance / $avgCallCost) : 0;
        $avgCallMinutes = $avgCallDuration > 0 ? round($avgCallDuration / 60, 1) : 0;
        
        // Get last successful topup
        $lastTopup = \App\Models\BalanceTopup::where('company_id', $company->id)
            ->where('status', \App\Models\BalanceTopup::STATUS_SUCCEEDED)
            ->orderBy('created_at', 'desc')
            ->first();
        
        // Get bonus rules (filter out first-time only rules for display)
        $allBonusRules = $this->billingService->getApplicableBonusRules($company);
        $bonusRules = array_values(array_filter($allBonusRules, function($rule) {
            return !$rule['is_first_time_only'];
        }));
        
        // Calculate recommended amount based on usage
        $recommendedAmount = $this->calculateRecommendedAmount($avgDailyUsage);
        
        // Get suggested amounts with bonus calculations
        $suggestedAmounts = [50, 250, 500, 1000, 2000, 3000, 5000];
        $amountsWithBonus = [];
        
        foreach ($suggestedAmounts as $amount) {
            $bonusCalc = $this->billingService->calculateBonus($amount, $company);
            $amountsWithBonus[] = [
                'amount' => $amount,
                'bonus' => $bonusCalc['bonus_amount'] ?? 0,
                'bonus_percentage' => $bonusCalc['rule'] ? $bonusCalc['rule']->bonus_percentage : 0,
                'total' => $amount + ($bonusCalc['bonus_amount'] ?? 0),
                'is_recommended' => $amount === $recommendedAmount,
            ];
        }
        
        // Check if amount is preset via URL
        $presetAmount = $request->query('amount');
        if ($presetAmount) {
            $presetBonusCalc = $this->billingService->calculateBonus($presetAmount, $company);
            $presetBonus = $presetBonusCalc['bonus_amount'] ?? 0;
        }
        
        return view('public.topup-form-enhanced', [
            'company' => $company,
            'currentBalance' => $currentBalance,
            'avgDailyUsage' => round($avgDailyUsage, 2),
            'daysRemaining' => round($daysRemaining),
            'callsRemaining' => $callsRemaining,
            'avgCallMinutes' => $avgCallMinutes,
            'avgCallCost' => round($avgCallCost, 2),
            'callsPerDay' => round($callsPerDay, 1),
            'recommendedAmount' => $recommendedAmount,
            'suggestedAmounts' => $amountsWithBonus,
            'bonusRules' => $bonusRules,
            'presetAmount' => $presetAmount,
            'presetBonus' => $presetBonus ?? 0,
            'lowBalanceWarning' => $currentBalance < ($avgDailyUsage * 7), // Warning if less than 1 week
            'lastTopup' => $lastTopup,
        ]);
    }
    
    /**
     * Calculate recommended topup amount based on usage and optimal bonus
     */
    private function calculateRecommendedAmount(float $avgDailyUsage): int
    {
        // Calculate needs for different periods
        $thirtyDayAmount = $avgDailyUsage * 30;
        $sixtyDayAmount = $avgDailyUsage * 60;
        $ninetyDayAmount = $avgDailyUsage * 90;
        
        // Always recommend 500€ for maximum bonus (15%)
        // unless the user needs very little credit
        if ($thirtyDayAmount < 50) {
            // Very low usage - 100€ gives them 2+ months
            return 100;
        }
        
        // For everyone else, recommend 500€ for best value
        // They get 15% bonus = 575€ total credit
        return 500;
    }

    /**
     * Process public topup without authentication
     */
    public function processTopup(Request $request, $companyId)
    {
        $company = Company::findOrFail($companyId);
        
        // Validate request - email and name will be collected by Stripe
        $validated = $request->validate([
            'amount' => 'required|numeric|min:10|max:10000',
            'email' => 'nullable|email',
            'name' => 'nullable|string|max:255',
        ]);

        $amount = $validated['amount'];
        $email = $validated['email'] ?? null;  // Will be collected by Stripe
        $name = $validated['name'] ?? null;    // Will be collected by Stripe

        try {
            // Create a pending topup record
            $topup = BalanceTopup::create([
                'company_id' => $company->id,
                'amount' => $amount,
                'currency' => 'EUR',
                'status' => BalanceTopup::STATUS_PENDING,
                'initiated_by' => null, // No authenticated user
                'metadata' => [
                    'customer_email' => $email,
                    'customer_name' => $name,
                    'source' => 'public_link',
                ],
            ]);

            // Create Stripe Checkout Session
            Stripe::setApiKey(config('services.stripe.secret'));
            
            // Calculate bonus
            $bonusCalc = $this->billingService->calculateBonus($amount, $company);
            $totalCredit = $amount + $bonusCalc['bonus_amount'];
            
            // Get suggested amounts if not already set
            if (!isset($this->suggestedAmounts)) {
                $this->suggestedAmounts = $this->stripeService->getSuggestedAmounts($company);
            }
            
            $session = CheckoutSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Guthaben-Aufladung',
                            'description' => sprintf(
                                'Guthaben-Aufladung für %s%s', 
                                $company->name,
                                $bonusCalc['bonus_amount'] > 0 ? sprintf(' (inkl. %.2f€ Bonus)', $bonusCalc['bonus_amount']) : ''
                            ),
                        ],
                        'unit_amount' => $amount * 100, // Stripe uses cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('public.topup.success', ['company' => $companyId]) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('public.topup.cancel', ['company' => $companyId]),
                'client_reference_id' => $topup->id,
                'metadata' => [
                    'company_id' => $company->id,
                    'topup_id' => $topup->id,
                    'source' => 'public_link',
                ],
                'locale' => 'de',
                'billing_address_collection' => 'required',
                'customer_creation' => 'always',
            ]);

            // Update topup with session ID
            $topup->update([
                'stripe_checkout_session_id' => $session->id,
                'status' => BalanceTopup::STATUS_PROCESSING,
            ]);

            // Redirect to Stripe Checkout
            return redirect($session->url);

        } catch (\Exception $e) {
            Log::error('Public topup failed', [
                'company_id' => $company->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'class' => get_class($e),
            ]);

            if (isset($topup)) {
                $topup->markAsFailed($e->getMessage());
            }

            // Provide more detailed error message in debug mode
            $errorMessage = 'Es gab einen Fehler beim Erstellen der Zahlungssitzung. Bitte versuchen Sie es erneut.';
            if (config('app.debug')) {
                $errorMessage .= ' (Debug: ' . $e->getMessage() . ')';
            }

            return redirect()
                ->route('public.topup.form', ['company' => $companyId])
                ->with('error', $errorMessage);
        }
    }

    /**
     * Handle successful payment
     */
    public function success(Request $request, $companyId)
    {
        $company = Company::findOrFail($companyId);
        $sessionId = $request->query('session_id');
        
        if ($sessionId) {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));
                $session = CheckoutSession::retrieve($sessionId);
                
                $topup = BalanceTopup::where('stripe_checkout_session_id', $sessionId)
                    ->where('company_id', $company->id)
                    ->first();
                    
                if ($topup) {
                    $amount = $topup->amount;
                    $metadata = $topup->metadata ?? [];
                    $customerName = $metadata['customer_name'] ?? 'Kunde';
                }
            } catch (\Exception $e) {
                Log::error('Failed to retrieve checkout session', [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return view('public.topup-success', [
            'company' => $company,
            'amount' => $amount ?? null,
            'customerName' => $customerName ?? null,
        ]);
    }

    /**
     * Handle cancelled payment
     */
    public function cancel($companyId)
    {
        $company = Company::findOrFail($companyId);
        
        return view('public.topup-cancel', [
            'company' => $company,
        ]);
    }

    /**
     * Generate a topup link for sharing
     */
    public function generateLink(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'amount' => 'nullable|numeric|min:10|max:10000',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        
        $url = route('public.topup.form', ['company' => $company->id]);
        
        if (isset($validated['amount'])) {
            $url .= '?amount=' . $validated['amount'];
        }

        return response()->json([
            'url' => $url,
            'company' => $company->name,
            'amount' => $validated['amount'] ?? null,
        ]);
    }
}