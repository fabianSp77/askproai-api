<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SimpleBillingController extends Controller
{
    /**
     * Show billing overview with unified layout
     */
    public function index(Request $request)
    {
        // Mock data to avoid database queries
        $data = [
            'balance' => 150.00,
            'currency' => 'EUR',
            'auto_topup_enabled' => true,
            'auto_topup_threshold' => 50,
            'auto_topup_amount' => 100,
        ];
        
        // Mock transactions
        $transactions = collect([
            (object)[
                'id' => 1,
                'type' => 'topup',
                'amount' => 100,
                'description' => 'Guthaben Aufladung',
                'created_at' => now()->subDays(5),
                'status' => 'completed',
                'invoice_url' => null
            ],
            (object)[
                'id' => 2,
                'type' => 'usage',
                'amount' => -15.50,
                'description' => 'Anrufgebühren',
                'created_at' => now()->subDays(3),
                'status' => 'completed',
                'invoice_url' => null
            ],
            (object)[
                'id' => 3,
                'type' => 'usage',
                'amount' => -8.25,
                'description' => 'SMS Gebühren',
                'created_at' => now()->subDays(1),
                'status' => 'completed',
                'invoice_url' => null
            ]
        ]);
        
        // For now, return a simple view with the unified layout
        return view('portal.billing.simple-index', compact('data', 'transactions'));
    }
    
    /**
     * Show invoices
     */
    public function invoices(Request $request)
    {
        // Mock invoices data
        $invoices = collect([
            (object)[
                'id' => 'INV-2025-001',
                'amount' => 100,
                'created_at' => now()->subMonths(1),
                'payment_method' => 'Kreditkarte',
                'status' => 'paid'
            ],
            (object)[
                'id' => 'INV-2025-002',
                'amount' => 100,
                'created_at' => now()->subMonths(2),
                'payment_method' => 'SEPA',
                'status' => 'paid'
            ]
        ]);
        
        return view('portal.billing.invoices', compact('invoices'));
    }
    
    /**
     * Show topup page
     */
    public function topup(Request $request)
    {
        // Mock company data
        $company = (object)[
            'name' => 'Beispiel GmbH',
            'balance' => 150.00,
            'auto_topup_enabled' => true
        ];
        
        $suggestedAmount = $request->get('suggested', 100);
        
        return view('portal.billing.topup', compact('company', 'suggestedAmount'));
    }
}