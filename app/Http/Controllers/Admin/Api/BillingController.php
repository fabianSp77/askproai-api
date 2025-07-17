<?php

namespace App\Http\Controllers\Admin\Api;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\PrepaidBalance;
use App\Models\BalanceTopup;
use App\Models\BalanceTransaction;
use App\Models\CallCharge;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BillingController extends BaseAdminApiController
{
    /**
     * Get billing overview statistics
     */
    public function overview(Request $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        
        $query = Company::withoutGlobalScopes();
        if ($companyId) {
            $query->where('id', $companyId);
        }
        
        $companies = $query->with(['prepaidBalance', 'invoices' => function($q) {
            $q->orderBy('created_at', 'desc')->limit(5);
        }])->get();
        
        $stats = [
            'total_balance' => $companies->sum(fn($c) => $c->prepaidBalance->balance ?? 0),
            'total_bonus_balance' => $companies->sum(fn($c) => $c->prepaidBalance->bonus_balance ?? 0),
            'active_companies' => $companies->where('is_active', true)->count(),
            'low_balance_count' => $companies->filter(function($c) {
                $balance = $c->prepaidBalance;
                return $balance && $balance->balance < ($balance->low_balance_threshold ?? 10);
            })->count(),
            'auto_topup_enabled' => $companies->filter(fn($c) => $c->prepaidBalance && $c->prepaidBalance->auto_topup_enabled)->count(),
            'unpaid_invoices' => Invoice::withoutGlobalScopes()
                ->whereIn('company_id', $companies->pluck('id'))
                ->where('status', 'unpaid')
                ->count(),
            'revenue_mtd' => BalanceTopup::withoutGlobalScopes()
                ->whereIn('company_id', $companies->pluck('id'))
                ->where('status', 'succeeded')
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
            'revenue_today' => BalanceTopup::withoutGlobalScopes()
                ->whereIn('company_id', $companies->pluck('id'))
                ->where('status', 'succeeded')
                ->whereDate('created_at', today())
                ->sum('amount'),
        ];
        
        return response()->json($stats);
    }

    /**
     * Get company balances
     */
    public function balances(Request $request): JsonResponse
    {
        $query = PrepaidBalance::withoutGlobalScopes()
            ->with(['company' => function($q) {
                $q->withoutGlobalScopes();
            }]);
        
        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->whereHas('company', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Low balance filter
        if ($request->has('low_balance_only') && $request->get('low_balance_only')) {
            $query->whereRaw('balance < COALESCE(low_balance_threshold, 10)');
        }
        
        // Auto-topup filter
        if ($request->has('auto_topup')) {
            $query->where('auto_topup_enabled', $request->get('auto_topup'));
        }
        
        $balances = $query->paginate($request->get('per_page', 20));
        
        // Transform for frontend
        $balances->getCollection()->transform(function ($balance) {
            return [
                'id' => $balance->id,
                'company' => [
                    'id' => $balance->company->id,
                    'name' => $balance->company->name,
                    'email' => $balance->company->email,
                    'active' => $balance->company->is_active ?? true,
                ],
                'balance' => $balance->balance,
                'bonus_balance' => $balance->bonus_balance,
                'total_balance' => $balance->balance + $balance->bonus_balance,
                'reserved_balance' => $balance->reserved_balance,
                'available_balance' => $balance->balance + $balance->bonus_balance - $balance->reserved_balance,
                'low_balance_threshold' => $balance->low_balance_threshold,
                'is_low_balance' => $balance->balance < ($balance->low_balance_threshold ?? 10),
                'auto_topup_enabled' => $balance->auto_topup_enabled,
                'auto_topup_threshold' => $balance->auto_topup_threshold,
                'auto_topup_amount' => $balance->auto_topup_amount,
                'auto_topup_monthly_limit' => $balance->auto_topup_monthly_limit,
                'last_topup' => $balance->transactions()
                    ->where('type', 'topup')
                    ->latest()
                    ->first()?->created_at,
                'monthly_usage' => $balance->transactions()
                    ->where('type', 'charge')
                    ->whereMonth('created_at', now()->month)
                    ->sum('amount'),
            ];
        });
        
        return response()->json($balances);
    }

    /**
     * Get invoices
     */
    public function invoices(Request $request): JsonResponse
    {
        $query = Invoice::withoutGlobalScopes()
            ->with(['company' => function($q) {
                $q->withoutGlobalScopes();
            }, 'items']);
        
        // Company filter
        if ($request->has('company_id')) {
            $query->where('company_id', $request->get('company_id'));
        }
        
        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }
        
        // Date range filter
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('invoice_date', [$request->get('date_from'), $request->get('date_to')]);
        }
        
        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhereHas('company', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'invoice_date');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);
        
        $invoices = $query->paginate($request->get('per_page', 20));
        
        return response()->json($invoices);
    }

    /**
     * Get topups
     */
    public function topups(Request $request): JsonResponse
    {
        $query = BalanceTopup::withoutGlobalScopes()
            ->with(['company' => function($q) {
                $q->withoutGlobalScopes();
            }, 'initiatedBy' => function($q) {
                $q->withoutGlobalScopes();
            }]);
        
        // Company filter
        if ($request->has('company_id')) {
            $query->where('company_id', $request->get('company_id'));
        }
        
        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }
        
        // Date range filter
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('created_at', [$request->get('date_from'), $request->get('date_to')]);
        }
        
        $topups = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));
        
        return response()->json($topups);
    }

    /**
     * Get transactions
     */
    public function transactions(Request $request): JsonResponse
    {
        $query = BalanceTransaction::withoutGlobalScopes()
            ->with(['company' => function($q) {
                $q->withoutGlobalScopes();
            }]);
        
        // Company filter
        if ($request->has('company_id')) {
            $query->where('company_id', $request->get('company_id'));
        }
        
        // Type filter
        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }
        
        // Date range filter
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('created_at', [$request->get('date_from'), $request->get('date_to')]);
        }
        
        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));
        
        return response()->json($transactions);
    }

    /**
     * Update balance settings
     */
    public function updateBalanceSettings(Request $request, $id): JsonResponse
    {
        $balance = PrepaidBalance::withoutGlobalScopes()->findOrFail($id);
        
        $validated = $request->validate([
            'low_balance_threshold' => 'nullable|numeric|min:0',
            'auto_topup_enabled' => 'boolean',
            'auto_topup_threshold' => 'nullable|numeric|min:0',
            'auto_topup_amount' => 'nullable|numeric|min:0',
            'auto_topup_monthly_limit' => 'nullable|numeric|min:0',
            'stripe_payment_method_id' => 'nullable|string',
        ]);
        
        $balance->update($validated);
        
        return response()->json([
            'message' => 'Balance settings updated successfully',
            'balance' => $balance
        ]);
    }

    /**
     * Create manual topup
     */
    public function createTopup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string',
            'is_bonus' => 'boolean',
        ]);
        
        $company = Company::withoutGlobalScopes()->findOrFail($validated['company_id']);
        $prepaidBalance = $company->prepaidBalance;
        
        if (!$prepaidBalance) {
            $prepaidBalance = PrepaidBalance::create([
                'company_id' => $company->id,
                'balance' => 0,
                'bonus_balance' => 0,
                'reserved_balance' => 0,
            ]);
        }
        
        DB::beginTransaction();
        try {
            // Create topup record
            $topup = BalanceTopup::create([
                'company_id' => $company->id,
                'amount' => $validated['amount'],
                'currency' => 'EUR',
                'status' => 'succeeded',
                'initiated_by' => auth()->user()->id,
                'paid_at' => now(),
                'metadata' => [
                    'type' => 'manual',
                    'created_by' => auth()->user()->email,
                    'description' => $validated['description'],
                    'is_bonus' => $validated['is_bonus'] ?? false,
                ],
            ]);
            
            // Add balance
            if ($validated['is_bonus'] ?? false) {
                $prepaidBalance->addBonusBalance(
                    $validated['amount'],
                    $validated['description'],
                    'topup',
                    $topup->id
                );
            } else {
                $prepaidBalance->addBalance(
                    $validated['amount'],
                    $validated['description'],
                    'topup',
                    $topup->id
                );
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Topup created successfully',
                'topup' => $topup,
                'balance' => $prepaidBalance->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to create topup',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create manual charge
     */
    public function createCharge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string',
        ]);
        
        $company = Company::withoutGlobalScopes()->findOrFail($validated['company_id']);
        $prepaidBalance = $company->prepaidBalance;
        
        if (!$prepaidBalance) {
            return response()->json([
                'error' => 'Company has no prepaid balance'
            ], 422);
        }
        
        if ($prepaidBalance->balance + $prepaidBalance->bonus_balance < $validated['amount']) {
            return response()->json([
                'error' => 'Insufficient balance'
            ], 422);
        }
        
        DB::beginTransaction();
        try {
            $prepaidBalance->chargeBalance(
                $validated['amount'],
                $validated['description'],
                'manual',
                null
            );
            
            DB::commit();
            
            return response()->json([
                'message' => 'Charge created successfully',
                'balance' => $prepaidBalance->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to create charge',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get call charges
     */
    public function callCharges(Request $request): JsonResponse
    {
        $query = CallCharge::withoutGlobalScopes()
            ->with(['call' => function($q) {
                $q->withoutGlobalScopes()->with('customer');
            }, 'company' => function($q) {
                $q->withoutGlobalScopes();
            }]);
        
        // Company filter
        if ($request->has('company_id')) {
            $query->where('company_id', $request->get('company_id'));
        }
        
        // Date range filter
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('created_at', [$request->get('date_from'), $request->get('date_to')]);
        }
        
        // Billable filter
        if ($request->has('billable_only') && $request->get('billable_only')) {
            $query->where('is_billable', true);
        }
        
        $charges = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));
        
        return response()->json($charges);
    }

    /**
     * Mark invoice as paid
     */
    public function markInvoiceAsPaid($id): JsonResponse
    {
        $invoice = Invoice::withoutGlobalScopes()->findOrFail($id);
        $invoice->markAsPaid();
        
        return response()->json([
            'message' => 'Invoice marked as paid',
            'invoice' => $invoice
        ]);
    }

    /**
     * Resend invoice
     */
    public function resendInvoice($id): JsonResponse
    {
        $invoice = Invoice::withoutGlobalScopes()->findOrFail($id);
        
        try {
            // Dispatch job to send invoice email
            \App\Jobs\SendInvoiceEmailJob::dispatch($invoice);
            
            $invoice->update(['sent_at' => now()]);
            
            return response()->json([
                'message' => 'Invoice resent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to resend invoice',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export transactions
     */
    public function exportTransactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'type' => 'nullable|string',
        ]);
        
        $query = BalanceTransaction::withoutGlobalScopes()
            ->with(['company' => function($q) {
                $q->withoutGlobalScopes();
            }])
            ->whereBetween('created_at', [$validated['date_from'], $validated['date_to']]);
        
        if ($validated['company_id'] ?? null) {
            $query->where('company_id', $validated['company_id']);
        }
        
        if ($validated['type'] ?? null) {
            $query->where('type', $validated['type']);
        }
        
        $transactions = $query->orderBy('created_at', 'desc')->get();
        
        $csvData = [];
        $csvData[] = ['Date', 'Company', 'Type', 'Description', 'Amount', 'Balance Before', 'Balance After'];
        
        foreach ($transactions as $transaction) {
            $csvData[] = [
                $transaction->created_at->format('d.m.Y H:i'),
                $transaction->company->name ?? '',
                ucfirst($transaction->type),
                $transaction->description,
                number_format($transaction->amount, 2),
                number_format($transaction->balance_before, 2),
                number_format($transaction->balance_after, 2),
            ];
        }
        
        return response()->json([
            'headers' => $csvData[0],
            'rows' => array_slice($csvData, 1),
            'filename' => 'transactions_' . now()->format('Y-m-d') . '.csv'
        ]);
    }
}