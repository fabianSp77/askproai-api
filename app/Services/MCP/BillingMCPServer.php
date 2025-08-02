<?php

namespace App\Services\MCP;

use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Company;
use App\Models\Call;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Customer as StripeCustomer;
use Stripe\Subscription;
use Stripe\Invoice as StripeInvoice;
use Stripe\PaymentMethod;

class BillingMCPServer
{
    protected string $name = 'Billing Management MCP Server';
    protected string $version = '1.0.0';
    
    protected ?StripeMCPServer $stripeMCP = null;

    public function __construct()
    {
        try {
            $this->stripeMCP = app(StripeMCPServer::class);
        } catch (\Exception $e) {
            Log::warning('StripeMCPServer not available', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get available tools for this MCP server.
     */
    public function getTools(): array
    {
        return [
            [
                'name' => 'getBillingOverview',
                'description' => 'Get billing overview including balance, usage, and limits',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer'],
                        'include_usage_details' => ['type' => 'boolean', 'default' => true]
                    ]
                ]
            ],
            [
                'name' => 'getBalance',
                'description' => 'Get current balance and credit information',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer']
                    ]
                ]
            ],
            [
                'name' => 'topupBalance',
                'description' => 'Add credit to account balance',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'amount' => ['type' => 'number', 'required' => true, 'minimum' => 10],
                        'payment_method' => ['type' => 'string', 'enum' => ['stripe', 'invoice', 'bank_transfer']],
                        'payment_method_id' => ['type' => 'string']
                    ],
                    'required' => ['amount']
                ]
            ],
            [
                'name' => 'getTransactions',
                'description' => 'Get transaction history',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'page' => ['type' => 'integer', 'default' => 1],
                        'per_page' => ['type' => 'integer', 'default' => 20],
                        'type' => ['type' => 'string', 'enum' => ['credit', 'debit', 'all']],
                        'date_from' => ['type' => 'string', 'format' => 'date'],
                        'date_to' => ['type' => 'string', 'format' => 'date']
                    ]
                ]
            ],
            [
                'name' => 'getInvoices',
                'description' => 'Get invoice list',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'page' => ['type' => 'integer', 'default' => 1],
                        'per_page' => ['type' => 'integer', 'default' => 20],
                        'status' => ['type' => 'string', 'enum' => ['paid', 'open', 'overdue', 'all']],
                        'year' => ['type' => 'integer'],
                        'month' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 12]
                    ]
                ]
            ],
            [
                'name' => 'getInvoice',
                'description' => 'Get detailed invoice information',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'invoice_id' => ['type' => 'string', 'required' => true]
                    ],
                    'required' => ['invoice_id']
                ]
            ],
            [
                'name' => 'getUsageReport',
                'description' => 'Get detailed usage report',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'period' => ['type' => 'string', 'enum' => ['today', 'week', 'month', 'custom'], 'default' => 'month'],
                        'date_from' => ['type' => 'string', 'format' => 'date'],
                        'date_to' => ['type' => 'string', 'format' => 'date'],
                        'group_by' => ['type' => 'string', 'enum' => ['day', 'week', 'month'], 'default' => 'day']
                    ]
                ]
            ],
            [
                'name' => 'getAutoTopupSettings',
                'description' => 'Get auto-topup configuration',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => []
                ]
            ],
            [
                'name' => 'updateAutoTopupSettings',
                'description' => 'Update auto-topup configuration',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'enabled' => ['type' => 'boolean', 'required' => true],
                        'threshold_amount' => ['type' => 'number', 'minimum' => 0],
                        'topup_amount' => ['type' => 'number', 'minimum' => 10],
                        'max_monthly_topups' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 10]
                    ],
                    'required' => ['enabled']
                ]
            ],
            [
                'name' => 'estimateUsage',
                'description' => 'Estimate remaining usage based on current balance and consumption',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => []
                ]
            ]
        ];
    }

    /**
     * Handle tool execution.
     */
    public function executeTool(string $name, array $arguments): array
    {
        try {
            switch ($name) {
                case 'getBillingOverview':
                    return $this->getBillingOverview($arguments);
                case 'getBalance':
                    return $this->getBalance($arguments);
                case 'topupBalance':
                    return $this->topupBalance($arguments);
                case 'getTransactions':
                    return $this->getTransactions($arguments);
                case 'getInvoices':
                    return $this->getInvoices($arguments);
                case 'getInvoice':
                    return $this->getInvoice($arguments);
                case 'getUsageReport':
                    return $this->getUsageReport($arguments);
                case 'getAutoTopupSettings':
                    return $this->getAutoTopupSettings($arguments);
                case 'updateAutoTopupSettings':
                    return $this->updateAutoTopupSettings($arguments);
                case 'estimateUsage':
                    return $this->estimateUsage($arguments);
                default:
                    throw new \Exception("Unknown tool: {$name}");
            }
        } catch (\Exception $e) {
            Log::error("BillingMCPServer error in {$name}", [
                'error' => $e->getMessage(),
                'arguments' => $arguments
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get billing overview.
     */
    protected function getBillingOverview(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $companyId = $params['company_id'] ?? $user->company_id;
        
        // Get company
        $company = Company::find($companyId);
        if (!$company) {
            throw new \Exception('Company not found');
        }

        // Get current balance
        $currentBalance = $company->credit_balance ?? 0;
        $bonusBalance = $company->bonus_balance ?? 0;
        $totalBalance = $currentBalance + $bonusBalance;

        // Get usage this month
        $monthStart = now()->startOfMonth();
        $monthlyUsage = Transaction::where('company_id', $companyId)
            ->where('type', 'debit')
            ->where('created_at', '>=', $monthStart)
            ->sum('amount');

        // Get call costs
        $callCosts = $this->calculateCallCosts($companyId, $monthStart);

        // Get limits and warnings
        $lowBalanceThreshold = $company->low_balance_threshold ?? 10;
        $isLowBalance = $totalBalance < $lowBalanceThreshold;

        // Estimate remaining usage
        $avgDailyUsage = $monthlyUsage / now()->day;
        $estimatedDaysRemaining = $avgDailyUsage > 0 ? round($totalBalance / $avgDailyUsage) : 999;

        $overview = [
            'current_balance' => round($currentBalance, 2),
            'bonus_balance' => round($bonusBalance, 2),
            'total_balance' => round($totalBalance, 2),
            'monthly_usage' => round($monthlyUsage, 2),
            'call_costs_this_month' => round($callCosts, 2),
            'estimated_days_remaining' => $estimatedDaysRemaining,
            'is_low_balance' => $isLowBalance,
            'low_balance_threshold' => $lowBalanceThreshold,
            'currency' => 'EUR',
            'auto_topup_enabled' => $company->auto_topup_enabled ?? false
        ];

        if ($params['include_usage_details'] ?? true) {
            $overview['usage_breakdown'] = $this->getUsageBreakdown($companyId, $monthStart);
        }

        return [
            'success' => true,
            'data' => $overview
        ];
    }

    /**
     * Get current balance.
     */
    protected function getBalance(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $companyId = $params['company_id'] ?? $user->company_id;
        
        $company = Company::find($companyId);
        if (!$company) {
            throw new \Exception('Company not found');
        }

        return [
            'success' => true,
            'data' => [
                'current_balance' => round($company->credit_balance ?? 0, 2),
                'bonus_balance' => round($company->bonus_balance ?? 0, 2),
                'total_balance' => round(($company->credit_balance ?? 0) + ($company->bonus_balance ?? 0), 2),
                'currency' => 'EUR',
                'last_updated' => $company->updated_at->toIso8601String()
            ]
        ];
    }

    /**
     * Top up balance.
     */
    protected function topupBalance(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $amount = $params['amount'];
        
        if ($amount < 10) {
            throw new \Exception('Minimum topup amount is €10');
        }

        DB::beginTransaction();
        try {
            $company = Company::lockForUpdate()->find($user->company_id);
            
            // Create transaction record
            $transaction = new Transaction();
            $transaction->company_id = $company->id;
            $transaction->type = 'credit';
            $transaction->amount = $amount;
            $transaction->balance_before = $company->credit_balance;
            $transaction->balance_after = $company->credit_balance + $amount;
            $transaction->description = 'Balance top-up';
            $transaction->reference = 'TOPUP-' . strtoupper(uniqid());
            
            // Handle payment method
            $paymentMethod = $params['payment_method'] ?? 'stripe';
            
            if ($paymentMethod === 'stripe' && $this->stripeMCP) {
                // Process Stripe payment
                $stripeResult = $this->stripeMCP->executeTool('createPaymentIntent', [
                    'amount' => $amount * 100, // Convert to cents
                    'currency' => 'eur',
                    'metadata' => [
                        'company_id' => $company->id,
                        'transaction_type' => 'topup'
                    ]
                ]);
                
                if (!$stripeResult['success']) {
                    throw new \Exception('Payment processing failed: ' . ($stripeResult['error'] ?? 'Unknown error'));
                }
                
                $transaction->payment_method = 'stripe';
                $transaction->payment_reference = $stripeResult['data']['payment_intent_id'];
                $transaction->status = 'pending';
            } else {
                // Manual payment methods
                $transaction->payment_method = $paymentMethod;
                $transaction->status = 'completed';
                
                // Update balance immediately for manual methods
                $company->credit_balance += $amount;
                $company->save();
            }
            
            $transaction->save();
            
            // Create invoice
            $invoice = new Invoice();
            $invoice->company_id = $company->id;
            $invoice->invoice_number = 'INV-' . date('Y') . '-' . str_pad(Invoice::whereYear('created_at', date('Y'))->count() + 1, 5, '0', STR_PAD_LEFT);
            $invoice->type = 'topup';
            $invoice->status = $transaction->status === 'completed' ? 'paid' : 'open';
            $invoice->subtotal = $amount;
            $invoice->tax_rate = 19; // German VAT
            $invoice->tax_amount = $amount * 0.19;
            $invoice->total = $amount * 1.19;
            $invoice->transaction_id = $transaction->id;
            $invoice->due_date = now()->addDays(14);
            $invoice->save();
            
            DB::commit();
            
            return [
                'success' => true,
                'data' => [
                    'transaction_id' => $transaction->id,
                    'reference' => $transaction->reference,
                    'amount' => $amount,
                    'new_balance' => $company->credit_balance,
                    'status' => $transaction->status,
                    'invoice_id' => $invoice->id,
                    'payment_instructions' => $this->getPaymentInstructions($paymentMethod, $transaction)
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get transactions.
     */
    protected function getTransactions(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $query = Transaction::where('company_id', $user->company_id);

        // Apply filters
        if (!empty($params['type']) && $params['type'] !== 'all') {
            $query->where('type', $params['type']);
        }

        if (!empty($params['date_from'])) {
            $query->whereDate('created_at', '>=', $params['date_from']);
        }

        if (!empty($params['date_to'])) {
            $query->whereDate('created_at', '<=', $params['date_to']);
        }

        // Paginate
        $perPage = $params['per_page'] ?? 20;
        $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Transform data
        $transformedTransactions = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => round($transaction->amount, 2),
                'balance_before' => round($transaction->balance_before, 2),
                'balance_after' => round($transaction->balance_after, 2),
                'description' => $transaction->description,
                'reference' => $transaction->reference,
                'payment_method' => $transaction->payment_method,
                'status' => $transaction->status,
                'created_at' => $transaction->created_at->toIso8601String()
            ];
        });

        return [
            'success' => true,
            'data' => $transformedTransactions,
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem()
            ]
        ];
    }

    /**
     * Get invoices.
     */
    protected function getInvoices(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $query = Invoice::where('company_id', $user->company_id);

        // Apply filters
        if (!empty($params['status']) && $params['status'] !== 'all') {
            if ($params['status'] === 'overdue') {
                $query->where('status', 'open')
                      ->where('due_date', '<', now());
            } else {
                $query->where('status', $params['status']);
            }
        }

        if (!empty($params['year'])) {
            $query->whereYear('created_at', $params['year']);
        }

        if (!empty($params['month'])) {
            $query->whereMonth('created_at', $params['month']);
        }

        // Paginate
        $perPage = $params['per_page'] ?? 20;
        $invoices = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Transform data
        $transformedInvoices = $invoices->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'type' => $invoice->type,
                'status' => $invoice->status,
                'subtotal' => round($invoice->subtotal, 2),
                'tax_amount' => round($invoice->tax_amount, 2),
                'total' => round($invoice->total, 2),
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'is_overdue' => $invoice->status === 'open' && $invoice->due_date < now(),
                'created_at' => $invoice->created_at->toIso8601String(),
                'download_url' => route('business.billing.invoice.download', $invoice->id)
            ];
        });

        return [
            'success' => true,
            'data' => $transformedInvoices,
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total()
            ]
        ];
    }

    /**
     * Get detailed invoice.
     */
    protected function getInvoice(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $invoice = Invoice::where('company_id', $user->company_id)
                         ->where('id', $params['invoice_id'])
                         ->with(['transaction', 'items'])
                         ->firstOrFail();

        return [
            'success' => true,
            'data' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'type' => $invoice->type,
                'status' => $invoice->status,
                'subtotal' => round($invoice->subtotal, 2),
                'tax_rate' => $invoice->tax_rate,
                'tax_amount' => round($invoice->tax_amount, 2),
                'total' => round($invoice->total, 2),
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'is_overdue' => $invoice->status === 'open' && $invoice->due_date < now(),
                'payment_method' => $invoice->transaction->payment_method ?? null,
                'items' => $invoice->items->map(function ($item) {
                    return [
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'unit_price' => round($item->unit_price, 2),
                        'total' => round($item->total, 2)
                    ];
                }),
                'created_at' => $invoice->created_at->toIso8601String(),
                'download_url' => route('business.billing.invoice.download', $invoice->id)
            ]
        ];
    }

    /**
     * Get usage report.
     */
    protected function getUsageReport(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $period = $params['period'] ?? 'month';
        $groupBy = $params['group_by'] ?? 'day';
        
        // Determine date range
        switch ($period) {
            case 'today':
                $dateFrom = now()->startOfDay();
                $dateTo = now()->endOfDay();
                break;
            case 'week':
                $dateFrom = now()->startOfWeek();
                $dateTo = now()->endOfWeek();
                break;
            case 'month':
                $dateFrom = now()->startOfMonth();
                $dateTo = now()->endOfMonth();
                break;
            case 'custom':
                $dateFrom = Carbon::parse($params['date_from'] ?? now()->startOfMonth());
                $dateTo = Carbon::parse($params['date_to'] ?? now());
                break;
        }

        // Get usage data
        $usage = $this->calculateUsageReport($user->company_id, $dateFrom, $dateTo, $groupBy);

        return [
            'success' => true,
            'data' => [
                'period' => $period,
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
                'total_calls' => $usage['total_calls'],
                'total_minutes' => $usage['total_minutes'],
                'total_cost' => round($usage['total_cost'], 2),
                'average_call_duration' => $usage['average_call_duration'],
                'average_call_cost' => round($usage['average_call_cost'], 2),
                'usage_by_period' => $usage['usage_by_period'],
                'usage_by_branch' => $usage['usage_by_branch'] ?? []
            ]
        ];
    }

    /**
     * Get auto-topup settings.
     */
    protected function getAutoTopupSettings(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $company = Company::find($user->company_id);

        return [
            'success' => true,
            'data' => [
                'enabled' => $company->auto_topup_enabled ?? false,
                'threshold_amount' => $company->auto_topup_threshold ?? 10,
                'topup_amount' => $company->auto_topup_amount ?? 50,
                'max_monthly_topups' => $company->max_monthly_topups ?? 3,
                'current_monthly_topups' => $this->getCurrentMonthlyTopups($company->id),
                'last_topup_date' => $company->last_auto_topup_at ?? null,
                'payment_method_configured' => !empty($company->stripe_customer_id)
            ]
        ];
    }

    /**
     * Update auto-topup settings.
     */
    protected function updateAutoTopupSettings(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $company = Company::find($user->company_id);

        // Update settings
        $company->auto_topup_enabled = $params['enabled'];
        
        if ($params['enabled']) {
            if (!empty($params['threshold_amount'])) {
                $company->auto_topup_threshold = $params['threshold_amount'];
            }
            if (!empty($params['topup_amount'])) {
                $company->auto_topup_amount = $params['topup_amount'];
            }
            if (!empty($params['max_monthly_topups'])) {
                $company->max_monthly_topups = $params['max_monthly_topups'];
            }
            
            // Validate payment method is configured
            if (empty($company->stripe_customer_id)) {
                throw new \Exception('Please configure a payment method before enabling auto-topup');
            }
        }
        
        $company->save();

        // Clear cache
        Cache::forget("company.{$company->id}.auto_topup_settings");

        return [
            'success' => true,
            'data' => [
                'enabled' => $company->auto_topup_enabled,
                'threshold_amount' => $company->auto_topup_threshold,
                'topup_amount' => $company->auto_topup_amount,
                'max_monthly_topups' => $company->max_monthly_topups
            ]
        ];
    }

    /**
     * Estimate remaining usage.
     */
    protected function estimateUsage(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $company = Company::find($user->company_id);
        
        // Get current balance
        $totalBalance = ($company->credit_balance ?? 0) + ($company->bonus_balance ?? 0);
        
        // Calculate average daily usage over last 30 days
        $thirtyDaysAgo = now()->subDays(30);
        $recentUsage = Transaction::where('company_id', $company->id)
            ->where('type', 'debit')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->sum('amount');
        
        $avgDailyUsage = $recentUsage / 30;
        
        // Calculate average per-call cost
        $recentCalls = Call::where('company_id', $company->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();
        
        $avgCallCost = $recentCalls > 0 ? $recentUsage / $recentCalls : 0.10; // Default 10 cents per call
        
        // Estimates
        $estimatedDaysRemaining = $avgDailyUsage > 0 ? round($totalBalance / $avgDailyUsage) : 999;
        $estimatedCallsRemaining = $avgCallCost > 0 ? round($totalBalance / $avgCallCost) : round($totalBalance / 0.10);
        
        return [
            'success' => true,
            'data' => [
                'current_balance' => round($totalBalance, 2),
                'average_daily_usage' => round($avgDailyUsage, 2),
                'average_call_cost' => round($avgCallCost, 2),
                'estimated_days_remaining' => $estimatedDaysRemaining,
                'estimated_calls_remaining' => $estimatedCallsRemaining,
                'usage_trend' => $this->calculateUsageTrend($company->id),
                'recommendations' => $this->getUsageRecommendations($totalBalance, $avgDailyUsage, $estimatedDaysRemaining)
            ]
        ];
    }

    /**
     * Calculate call costs for a period.
     */
    protected function calculateCallCosts(int $companyId, Carbon $from): float
    {
        // Get call count and total duration
        $calls = Call::where('company_id', $companyId)
            ->where('created_at', '>=', $from)
            ->selectRaw('COUNT(*) as count, SUM(duration_sec) as total_duration')
            ->first();
        
        // Simple cost calculation: €0.05 per call + €0.01 per minute
        $callCost = $calls->count * 0.05;
        $durationCost = ($calls->total_duration / 60) * 0.01;
        
        return $callCost + $durationCost;
    }

    /**
     * Get usage breakdown.
     */
    protected function getUsageBreakdown(int $companyId, Carbon $from): array
    {
        $breakdown = [];
        
        // Calls breakdown
        $calls = Call::where('company_id', $companyId)
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(duration_sec) as duration')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(7)
            ->get();
        
        $breakdown['daily_calls'] = $calls->map(function ($day) {
            return [
                'date' => $day->date,
                'calls' => $day->count,
                'minutes' => round($day->duration / 60, 1),
                'cost' => round(($day->count * 0.05) + (($day->duration / 60) * 0.01), 2)
            ];
        })->toArray();
        
        return $breakdown;
    }

    /**
     * Calculate usage report.
     */
    protected function calculateUsageReport(int $companyId, Carbon $from, Carbon $to, string $groupBy): array
    {
        $calls = Call::where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to])
            ->get();
        
        $totalCalls = $calls->count();
        $totalSeconds = $calls->sum('duration_sec');
        $totalMinutes = round($totalSeconds / 60, 1);
        $totalCost = ($totalCalls * 0.05) + ($totalMinutes * 0.01);
        
        $avgDuration = $totalCalls > 0 ? round($totalSeconds / $totalCalls) : 0;
        $avgCost = $totalCalls > 0 ? $totalCost / $totalCalls : 0;
        
        // Group by period
        $format = match($groupBy) {
            'day' => 'Y-m-d',
            'week' => 'Y-W',
            'month' => 'Y-m'
        };
        
        $grouped = $calls->groupBy(function ($call) use ($format) {
            return $call->created_at->format($format);
        })->map(function ($group) {
            $count = $group->count();
            $duration = $group->sum('duration_sec');
            $cost = ($count * 0.05) + (($duration / 60) * 0.01);
            
            return [
                'calls' => $count,
                'minutes' => round($duration / 60, 1),
                'cost' => round($cost, 2)
            ];
        });
        
        return [
            'total_calls' => $totalCalls,
            'total_minutes' => $totalMinutes,
            'total_cost' => $totalCost,
            'average_call_duration' => $avgDuration,
            'average_call_cost' => $avgCost,
            'usage_by_period' => $grouped->toArray()
        ];
    }

    /**
     * Get current monthly topups count.
     */
    protected function getCurrentMonthlyTopups(int $companyId): int
    {
        return Transaction::where('company_id', $companyId)
            ->where('type', 'credit')
            ->where('description', 'like', '%Auto-topup%')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    /**
     * Calculate usage trend.
     */
    protected function calculateUsageTrend(int $companyId): string
    {
        $thisWeek = Transaction::where('company_id', $companyId)
            ->where('type', 'debit')
            ->where('created_at', '>=', now()->startOfWeek())
            ->sum('amount');
        
        $lastWeek = Transaction::where('company_id', $companyId)
            ->where('type', 'debit')
            ->whereBetween('created_at', [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek()
            ])
            ->sum('amount');
        
        if ($lastWeek == 0) return 'stable';
        
        $change = (($thisWeek - $lastWeek) / $lastWeek) * 100;
        
        if ($change > 20) return 'increasing';
        if ($change < -20) return 'decreasing';
        return 'stable';
    }

    /**
     * Get usage recommendations.
     */
    protected function getUsageRecommendations(float $balance, float $dailyUsage, int $daysRemaining): array
    {
        $recommendations = [];
        
        if ($daysRemaining < 7) {
            $recommendations[] = [
                'type' => 'urgent',
                'message' => 'Your balance will run out in less than a week. Consider topping up soon.',
                'action' => 'topup'
            ];
        }
        
        if ($balance < 10) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Low balance warning. Enable auto-topup to avoid service interruption.',
                'action' => 'enable_auto_topup'
            ];
        }
        
        if ($dailyUsage > 10) {
            $recommendations[] = [
                'type' => 'info',
                'message' => 'High usage detected. Consider reviewing your call volume and optimizing where possible.',
                'action' => 'view_usage_report'
            ];
        }
        
        return $recommendations;
    }

    /**
     * Get payment instructions.
     */
    protected function getPaymentInstructions(string $method, Transaction $transaction): ?array
    {
        switch ($method) {
            case 'bank_transfer':
                return [
                    'method' => 'bank_transfer',
                    'bank_name' => 'Deutsche Bank',
                    'iban' => 'DE89 3704 0044 0532 0130 00',
                    'bic' => 'DEUTDEFF',
                    'reference' => $transaction->reference,
                    'amount' => $transaction->amount
                ];
            case 'invoice':
                return [
                    'method' => 'invoice',
                    'due_date' => now()->addDays(14)->format('Y-m-d'),
                    'reference' => $transaction->reference
                ];
            default:
                return null;
        }
    }
}