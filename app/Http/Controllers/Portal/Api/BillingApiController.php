<?php

namespace App\Http\Controllers\Portal\Api;

use App\Models\BalanceTransaction;
use App\Models\Invoice;
use App\Models\PortalUser;
use App\Services\BalanceMonitoringService;
use App\Services\CallRefundService;
use App\Services\PrepaidBillingService;
use App\Services\SpendingLimitService;
use App\Services\StripeTopupService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingApiController extends BaseApiController
{
    protected PrepaidBillingService $billingService;

    protected BalanceMonitoringService $monitoringService;

    protected StripeTopupService $stripeService;

    protected SpendingLimitService $spendingLimitService;

    protected CallRefundService $refundService;

    public function __construct(
        PrepaidBillingService $billingService,
        BalanceMonitoringService $monitoringService,
        StripeTopupService $stripeService,
        SpendingLimitService $spendingLimitService,
        CallRefundService $refundService
    ) {
        $this->billingService = $billingService;
        $this->monitoringService = $monitoringService;
        $this->stripeService = $stripeService;
        $this->spendingLimitService = $spendingLimitService;
        $this->refundService = $refundService;
    }

    public function index(Request $request)
    {
        $company = $this->getCompany();

        if (! $company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get prepaid balance
        $prepaidBalance = $this->billingService->getOrCreateBalance($company);

        // Get spending limits
        $spendingLimits = $this->spendingLimitService->getOrCreateSpendingLimits($company);

        // Get recent transactions (last 30 days)
        $recentTransactions = BalanceTransaction::where('company_id', $company->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'date' => $transaction->created_at->format('d.m.Y H:i'),
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'balance_after' => $transaction->balance_after,
                    'description' => $transaction->description,
                    'metadata' => $transaction->metadata,
                ];
            });

        // Calculate usage stats for current month
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $monthlyUsage = DB::table('call_charges')
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->select(
                DB::raw('SUM(amount_charged) as total_charges'),
                DB::raw('COUNT(DISTINCT call_id) as total_calls'),
                DB::raw('SUM(duration_seconds) as total_duration')
            )
            ->first();

        // Get bonus rules
        $bonusRules = $this->billingService->getApplicableBonusRules($company);

        // Get billing rate
        $billingRate = $this->billingService->getCompanyBillingRate($company);

        return response()->json([
            'prepaid_balance' => [
                'id' => $prepaidBalance->id,
                'current_balance' => (float) $prepaidBalance->balance,
                'bonus_balance' => (float) ($prepaidBalance->bonus_balance ?? 0),
                'total_balance' => (float) $prepaidBalance->getTotalBalance(),
                'balance' => (float) $prepaidBalance->balance,
                'critical_threshold' => (float) ($prepaidBalance->critical_threshold ?? 10),
                'warning_threshold' => (float) ($prepaidBalance->warning_threshold ?? 20),
                'is_critical' => (float) $prepaidBalance->balance <= 10,
                'is_warning' => (float) $prepaidBalance->balance <= 20,
                'auto_topup_enabled' => $prepaidBalance->auto_topup_enabled,
                'auto_topup_threshold' => (float) ($prepaidBalance->auto_topup_threshold ?? 0),
                'auto_topup_amount' => (float) ($prepaidBalance->auto_topup_amount ?? 0),
                'currency' => 'EUR',
            ],
            'billing_rate' => [
                'rate_per_minute' => (float) ($billingRate->rate_per_minute ?? 0.39),
                'minimum_charge_seconds' => (int) ($billingRate->minimum_charge_seconds ?? 60),
                'rounding_seconds' => (int) ($billingRate->rounding_seconds ?? 1),
            ],
            'balance' => [
                'current_balance' => (float) $prepaidBalance->balance,
                'critical_threshold' => (float) ($prepaidBalance->critical_threshold ?? 10),
                'warning_threshold' => (float) ($prepaidBalance->warning_threshold ?? 20),
                'is_critical' => (float) $prepaidBalance->balance <= 10,
                'is_warning' => (float) $prepaidBalance->balance <= 20,
                'auto_topup_enabled' => $prepaidBalance->auto_topup_enabled,
                'currency' => 'EUR',
            ],
            'spending_limits' => $spendingLimits,
            'monthly_usage' => [
                'total_charges' => (float) ($monthlyUsage->total_charges ?? 0),
                'total_charged' => (float) ($monthlyUsage->total_charges ?? 0),
                'total_calls' => (int) ($monthlyUsage->total_calls ?? 0),
                'total_duration_minutes' => (float) round(($monthlyUsage->total_duration ?? 0) / 60, 2),
                'month' => Carbon::now()->format('F Y'),
            ],
            'recent_transactions' => $recentTransactions,
            'transactions' => $recentTransactions,
            'bonus_rules' => $bonusRules,
            'suggested_topup_amounts' => [50, 250, 500, 1000, 2000, 5000],
            'subscription' => [
                'plan' => $company->subscription_plan ?? 'prepaid',
                'plan_name' => $this->getPlanName($company->subscription_plan ?? 'prepaid'),
                'status' => 'active',
            ],
        ]);
    }

    /**
     * Get transactions history.
     */
    public function getTransactions(Request $request)
    {
        $company = $this->getCompany();

        if (! $company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $query = BalanceTransaction::where('company_id', $company->id)
            ->orderBy('created_at', 'desc');

        // Date filter
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Type filter
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        $transactions = $query->paginate(50);

        $transactions->getCollection()->transform(function ($transaction) {
            return [
                'id' => $transaction->id,
                'date' => $transaction->created_at->format('d.m.Y H:i'),
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'balance_after' => $transaction->balance_after,
                'description' => $transaction->description,
                'metadata' => $transaction->metadata,
                'invoice_available' => in_array($transaction->type, ['topup', 'bonus']),
            ];
        });

        return response()->json([
            'transactions' => $transactions,
            'summary' => [
                'total_topups' => $query->clone()->where('type', 'topup')->sum('amount'),
                'total_charges' => abs($query->clone()->where('type', 'charge')->sum('amount')),
                'total_bonuses' => $query->clone()->where('type', 'bonus')->sum('amount'),
                'total_refunds' => $query->clone()->where('type', 'refund')->sum('amount'),
            ],
        ]);
    }

    /**
     * Get usage statistics.
     */
    public function getUsage(Request $request)
    {
        $company = $this->getCompany();

        if (! $company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $period = $request->get('period', 'month'); // month, week, day

        // Calculate date range
        switch ($period) {
            case 'week':
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();

                break;
            case 'day':
                $startDate = Carbon::today();
                $endDate = Carbon::today()->endOfDay();

                break;
            default: // month
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
        }

        // Get usage data
        $usage = DB::table('call_charges')
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(DISTINCT call_id) as calls'),
                DB::raw('SUM(duration_seconds) as duration'),
                DB::raw('SUM(amount_charged) as charges')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get usage by service type (temporarily disabled - service_type column not available)
        $usageByService = collect([]);

        // Get top numbers by usage
        $topNumbers = DB::table('calls')
            ->join('call_charges', 'calls.id', '=', 'call_charges.call_id')
            ->where('calls.company_id', $company->id)
            ->whereBetween('calls.created_at', [$startDate, $endDate])
            ->select(
                'calls.from_number',
                DB::raw('COUNT(DISTINCT calls.id) as call_count'),
                DB::raw('SUM(call_charges.amount_charged) as total_charges')
            )
            ->groupBy('calls.from_number')
            ->orderBy('total_charges', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'label' => $this->getPeriodLabel($period),
            ],
            'daily_usage' => $usage->map(function ($day) {
                return [
                    'date' => $day->date,
                    'calls' => $day->calls,
                    'duration_minutes' => round($day->duration / 60, 2),
                    'charges' => round($day->charges, 2),
                ];
            }),
            'usage_by_service' => $usageByService->map(function ($service) {
                return [
                    'service' => $service->service_type ?? 'Standard',
                    'calls' => $service->calls,
                    'duration_minutes' => round($service->duration / 60, 2),
                    'charges' => round($service->charges, 2),
                ];
            }),
            'top_numbers' => $topNumbers->map(function ($number) {
                return [
                    'number' => $number->from_number,
                    'calls' => $number->call_count,
                    'charges' => round($number->total_charges, 2),
                ];
            }),
            'totals' => [
                'total_calls' => $usage->sum('calls'),
                'total_duration_minutes' => round($usage->sum('duration') / 60, 2),
                'total_charges' => round($usage->sum('charges'), 2),
            ],
        ]);
    }

    /**
     * Process topup.
     */
    public function topup(Request $request)
    {
        $company = $this->getCompany();

        if (! $company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'amount' => 'required|numeric|min:50|max:5000',
        ]);

        try {
            $user = $this->getCurrentUser();

            // Handle different user types
            if ($user instanceof \App\Models\User && session('is_admin_viewing')) {
                // Admin is viewing - create a temporary PortalUser-like object or use the company's primary user
                // For now, we'll get the first portal user of the company
                $portalUser = PortalUser::where('company_id', $company->id)
                    ->where('is_active', true)
                    ->first();

                if (! $portalUser) {
                    throw new \Exception('No active portal user found for this company');
                }

                $user = $portalUser;
            } elseif (! ($user instanceof PortalUser)) {
                throw new \Exception('Invalid user type for topup');
            }

            // Create Stripe Checkout Session
            $session = $this->stripeService->createCheckoutSession($company, $request->amount, $user);

            if (! $session) {
                throw new \Exception('Could not create checkout session');
            }

            return response()->json([
                'success' => true,
                'checkout_url' => $session->url,
                'session_id' => $session->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Topup checkout session failed', [
                'user_id' => isset($user) && $user ? $user->id : null,
                'user_type' => isset($user) ? get_class($user) : 'null',
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Checkout-Session konnte nicht erstellt werden: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get auto-topup settings.
     */
    public function getAutoTopupSettings(Request $request)
    {
        $company = $this->getCompany();

        if (! $company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $prepaidBalance = $this->billingService->getOrCreateBalance($company);

        // Get saved payment methods
        $savedPaymentMethods = collect();
        if ($company->stripe_customer_id) {
            try {
                $savedPaymentMethods = $this->stripeService->getSavedPaymentMethods($company)
                    ->map(function ($pm) use ($prepaidBalance) {
                        return [
                            'id' => $pm->id,
                            'type' => 'card',
                            'brand' => $pm->card->brand,
                            'last4' => $pm->card->last4,
                            'exp_month' => $pm->card->exp_month,
                            'exp_year' => $pm->card->exp_year,
                            'is_selected' => $pm->id === $prepaidBalance->auto_topup_payment_method_id,
                        ];
                    });
            } catch (\Exception $e) {
                \Log::error('Failed to fetch payment methods', ['error' => $e->getMessage()]);
            }
        }

        // Calculate bonus for current auto-topup amount
        $applicableBonus = null;
        if ($prepaidBalance->auto_topup_amount) {
            $bonusCalc = $this->billingService->calculateBonus($prepaidBalance->auto_topup_amount, $company);
            if ($bonusCalc['rule']) {
                $applicableBonus = [
                    'bonus_percentage' => $bonusCalc['rule']->bonus_percentage,
                    'bonus_amount' => $bonusCalc['bonus_amount'],
                ];
            }
        }

        return response()->json([
            'auto_topup_enabled' => $prepaidBalance->auto_topup_enabled,
            'auto_topup_threshold' => $prepaidBalance->auto_topup_threshold,
            'auto_topup_amount' => $prepaidBalance->auto_topup_amount,
            'auto_topup_payment_method_id' => $prepaidBalance->auto_topup_payment_method_id,
            'auto_topup_daily_limit' => $prepaidBalance->auto_topup_daily_limit,
            'auto_topup_monthly_limit' => $prepaidBalance->auto_topup_monthly_limit,
            'payment_methods' => $savedPaymentMethods,
            'applicable_bonus' => $applicableBonus,
            'bonus_rules' => $this->billingService->getApplicableBonusRules($company),
        ]);
    }

    /**
     * Update auto-topup settings.
     */
    public function updateAutoTopupSettings(Request $request)
    {
        $company = $this->getCompany();

        if (! $company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = $this->getCurrentUser();

        // Check permission
        if ($user instanceof PortalUser && ! $user->canManageBilling()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // If admin is viewing, get a portal user for the activity log
        $activityUser = $user;
        if ($user instanceof \App\Models\User && session('is_admin_viewing')) {
            // Use the first active portal user or create a system user reference
            $portalUser = PortalUser::where('company_id', $company->id)
                ->where('is_active', true)
                ->first();
            if ($portalUser) {
                $activityUser = $portalUser;
            }
        }

        // Validate request
        $validated = $request->validate([
            'auto_topup_enabled' => 'required|boolean',
            'auto_topup_threshold' => 'required_if:auto_topup_enabled,true|numeric|min:10|max:500',
            'auto_topup_amount' => 'required_if:auto_topup_enabled,true|numeric|min:50|max:5000',
            'auto_topup_daily_limit' => 'required_if:auto_topup_enabled,true|integer|min:1|max:5',
            'auto_topup_monthly_limit' => 'required_if:auto_topup_enabled,true|integer|min:5|max:30',
            'payment_method_id' => 'required_if:auto_topup_enabled,true|string',
        ]);

        // Get prepaid balance
        $prepaidBalance = $this->billingService->getOrCreateBalance($company);

        // Update settings
        $prepaidBalance->auto_topup_enabled = $validated['auto_topup_enabled'];

        if ($prepaidBalance->auto_topup_enabled) {
            // Verify payment method
            if ($request->payment_method_id) {
                try {
                    $paymentMethod = $this->stripeService->getPaymentMethod($request->payment_method_id);
                    if ($paymentMethod->customer !== $company->stripe_customer_id) {
                        return response()->json(['error' => 'Invalid payment method'], 400);
                    }
                } catch (\Exception $e) {
                    return response()->json(['error' => 'Invalid payment method'], 400);
                }
            }

            $prepaidBalance->auto_topup_threshold = $validated['auto_topup_threshold'];
            $prepaidBalance->auto_topup_amount = $validated['auto_topup_amount'];
            $prepaidBalance->auto_topup_payment_method_id = $validated['payment_method_id'];
            $prepaidBalance->auto_topup_daily_limit = $validated['auto_topup_daily_limit'];
            $prepaidBalance->auto_topup_monthly_limit = $validated['auto_topup_monthly_limit'];
        }

        $prepaidBalance->save();

        // Log the change
        activity()
            ->performedOn($prepaidBalance)
            ->causedBy($activityUser)
            ->withProperties([
                'auto_topup_enabled' => $prepaidBalance->auto_topup_enabled,
                'settings' => $prepaidBalance->auto_topup_enabled ? [
                    'threshold' => $prepaidBalance->auto_topup_threshold,
                    'amount' => $prepaidBalance->auto_topup_amount,
                    'daily_limit' => $prepaidBalance->auto_topup_daily_limit,
                    'monthly_limit' => $prepaidBalance->auto_topup_monthly_limit,
                ] : null,
            ])
            ->log('Auto-Topup-Einstellungen aktualisiert');

        return response()->json([
            'success' => true,
            'message' => 'Auto-Topup-Einstellungen erfolgreich aktualisiert',
            'settings' => [
                'auto_topup_enabled' => $prepaidBalance->auto_topup_enabled,
                'auto_topup_threshold' => $prepaidBalance->auto_topup_threshold,
                'auto_topup_amount' => $prepaidBalance->auto_topup_amount,
                'auto_topup_daily_limit' => $prepaidBalance->auto_topup_daily_limit,
                'auto_topup_monthly_limit' => $prepaidBalance->auto_topup_monthly_limit,
            ],
        ]);
    }

    /**
     * Get saved payment methods.
     */
    public function paymentMethods(Request $request)
    {
        $company = $this->getCompany();

        if (! $company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (! $company->stripe_customer_id) {
            return response()->json(['payment_methods' => []]);
        }

        try {
            $paymentMethods = $this->stripeService->getSavedPaymentMethods($company)
                ->map(function ($pm) {
                    return [
                        'id' => $pm->id,
                        'type' => $pm->type,
                        'card' => $pm->type === 'card' ? [
                            'brand' => $pm->card->brand,
                            'last4' => $pm->card->last4,
                            'exp_month' => $pm->card->exp_month,
                            'exp_year' => $pm->card->exp_year,
                        ] : null,
                        'created_at' => Carbon::createFromTimestamp($pm->created)->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json(['payment_methods' => $paymentMethods]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch payment methods', ['error' => $e->getMessage()]);

            return response()->json(['payment_methods' => []]);
        }
    }

    /**
     * Download invoice.
     */
    public function downloadInvoice(Request $request, $invoiceId)
    {
        $company = $this->getCompany();

        if (! $company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // For now, just return success
        // TODO: Implement actual invoice generation/download
        return response()->json([
            'download_url' => url("/api/invoices/{$invoiceId}/download"),
            'filename' => "invoice-{$invoiceId}.pdf",
        ]);
    }

    /**
     * Get invoices.
     */
    public function invoices(Request $request)
    {
        $company = $this->getCompany();

        if (! $company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // For now, return empty invoices
        // TODO: Implement actual invoice retrieval
        return response()->json([
            'invoices' => [],
            'pagination' => [
                'total' => 0,
                'per_page' => 20,
                'current_page' => 1,
                'last_page' => 1,
            ],
        ]);
    }

    /**
     * Change subscription plan.
     */
    public function changePlan(Request $request)
    {
        $company = $this->getCompany();

        if (! $company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = $this->getCurrentUser();

        // Check permission
        if ($user instanceof PortalUser && ! $user->hasPermissionTo('billing.manage')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'plan_id' => 'required|in:starter,professional,enterprise',
        ]);
        $company->subscription_plan = $request->plan_id;
        $company->save();

        return response()->json([
            'success' => true,
            'message' => 'Plan wird zum nächsten Abrechnungszeitraum geändert',
        ]);
    }

    private function getPlanName($planId)
    {
        $plans = [
            'prepaid' => 'Prepaid',
            'starter' => 'Starter',
            'professional' => 'Professional',
            'enterprise' => 'Enterprise',
        ];

        return $plans[$planId] ?? 'Prepaid';
    }

    private function getPlanPrice($planId)
    {
        $prices = [
            'starter' => 49,
            'professional' => 149,
            'enterprise' => 399,
        ];

        return $prices[$planId] ?? 0;
    }

    private function getPlanMinutes($planId)
    {
        $minutes = [
            'starter' => 500,
            'professional' => 2000,
            'enterprise' => 10000,
        ];

        return $minutes[$planId] ?? 0;
    }

    private function getPeriodLabel($period)
    {
        $labels = [
            'day' => 'Heute',
            'week' => 'Diese Woche',
            'month' => 'Dieser Monat',
        ];

        return $labels[$period] ?? 'Dieser Monat';
    }
}
