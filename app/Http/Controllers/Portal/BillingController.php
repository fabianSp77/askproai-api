<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\ReactDashboardController;
use App\Traits\UsesMCPServers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BillingController extends Controller
{
    use UsesMCPServers;

    public function __construct()
    {
        $this->setMCPPreferences([
            'billing' => true,
            'stripe' => true,
            'company' => true
        ]);
    }

    /**
     * Show billing overview
     */
    public function index(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // Get company context
        $companyId = $this->getCompanyId($user);
        
        // Check permissions
        if (!$this->canViewBilling($user)) {
            abort(403, 'Sie haben keine Berechtigung, die Abrechnung einzusehen.');
        }

        // Get billing overview via MCP
        $data = $this->executeMCPTask('getBillingOverview', [
            'company_id' => $companyId
        ]);

        // Store data in session for React
        session()->flash('billing_data', $data['result']);

        // Load React SPA
        return app(ReactDashboardController::class)->index();
    }

    /**
     * Show topup page
     */
    public function topup(Request $request)
    {
        $user = Auth::guard('portal')->user();
        $companyId = $this->getCompanyId($user);
        
        // Check permissions
        if (!$this->canManageBilling($user)) {
            abort(403, 'Sie haben keine Berechtigung, Guthaben aufzuladen.');
        }

        // Get topup options via MCP
        $data = $this->executeMCPTask('getTopupOptions', [
            'company_id' => $companyId,
            'selected_amount' => $request->get('suggested')
        ]);

        // Store data for React
        session()->flash('topup_data', $data['result']);

        // Load React SPA
        return app(ReactDashboardController::class)->index();
    }

    /**
     * Process topup request
     */
    public function processTopup(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // Prevent admin actions
        if (session('is_admin_viewing')) {
            return back()->with('error', 'Als Administrator können Sie keine Zahlungen für Kunden durchführen.');
        }

        // Validate request
        $request->validate([
            'amount' => 'required|numeric|min:10|max:10000',
        ]);

        // Process topup via MCP
        $result = $this->executeMCPTask('topupBalance', [
            'user_id' => $user->id,
            'amount' => $request->input('amount'),
            'method' => 'stripe_checkout'
        ]);

        if ($result['result']['success'] ?? false) {
            // Redirect to Stripe Checkout
            return redirect($result['result']['checkout_url']);
        }

        return back()->with('error', $result['result']['error'] ?? 'Fehler beim Erstellen der Zahlungssitzung.');
    }

    /**
     * Handle successful topup
     */
    public function topupSuccess(Request $request)
    {
        $sessionId = $request->get('session_id');
        
        if ($sessionId) {
            // Process via MCP
            $this->executeMCPTask('handlePaymentSuccess', [
                'session_id' => $sessionId,
                'provider' => 'stripe'
            ]);
        }

        session()->flash('success', 'Ihr Guthaben wurde erfolgreich aufgeladen.');
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
        $companyId = $this->getCompanyId($user);
        
        // Check permissions
        if (!$this->canViewBilling($user)) {
            abort(403, 'Sie haben keine Berechtigung, die Transaktionen einzusehen.');
        }

        // Get transactions via MCP
        $data = $this->executeMCPTask('listTransactions', [
            'company_id' => $companyId,
            'filters' => [
                'type' => $request->input('type'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to')
            ],
            'page' => $request->input('page', 1),
            'per_page' => 50
        ]);

        // Store data for React
        session()->flash('transactions_data', $data['result']);

        return app(ReactDashboardController::class)->index();
    }

    /**
     * Download invoice
     */
    public function downloadInvoice($transactionId)
    {
        $user = Auth::guard('portal')->user();
        $companyId = $this->getCompanyId($user);
        
        // Download invoice via MCP
        $result = $this->executeMCPTask('downloadInvoice', [
            'company_id' => $companyId,
            'transaction_id' => $transactionId
        ]);

        if ($result['result']['success'] ?? false) {
            if ($result['result']['type'] === 'redirect') {
                return redirect($result['result']['url']);
            } elseif ($result['result']['type'] === 'download') {
                return response()->download(
                    $result['result']['path'],
                    $result['result']['filename'],
                    ['Content-Type' => 'application/pdf']
                );
            }
        }

        return back()->with(
            $result['result']['level'] ?? 'error',
            $result['result']['message'] ?? 'Fehler beim Abrufen der Rechnung.'
        );
    }

    /**
     * Show usage statistics
     */
    public function usage(Request $request)
    {
        $user = Auth::guard('portal')->user();
        $companyId = $this->getCompanyId($user);
        
        // Check permissions
        if (!$this->canViewBilling($user)) {
            abort(403, 'Sie haben keine Berechtigung, die Nutzungsstatistiken einzusehen.');
        }

        // Handle export requests first
        if ($request->has('export')) {
            return $this->exportUsage($request);
        }

        // Get usage report via MCP
        $data = $this->executeMCPTask('getUsageReport', [
            'company_id' => $companyId,
            'period' => $request->get('period', 'this_month'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to')
        ]);

        // Store data for React
        session()->flash('usage_data', $data['result']);

        return app(ReactDashboardController::class)->index();
    }
    
    /**
     * Export usage data
     */
    protected function exportUsage(Request $request)
    {
        $user = Auth::guard('portal')->user();
        $companyId = $this->getCompanyId($user);
        
        // Export via MCP
        $result = $this->executeMCPTask('exportUsageReport', [
            'company_id' => $companyId,
            'format' => $request->get('export', 'csv'),
            'period' => $request->get('period', 'this_month'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to')
        ]);

        if ($result['result']['success'] ?? false) {
            $export = $result['result']['export'];
            
            if ($export['type'] === 'stream') {
                return response()->stream(
                    function() use ($export) {
                        echo $export['content'];
                    },
                    200,
                    $export['headers']
                );
            } elseif ($export['type'] === 'download') {
                return response()->download(
                    $export['path'],
                    $export['filename'],
                    $export['headers']
                );
            }
        }

        return back()->with('error', $result['result']['message'] ?? 'Export fehlgeschlagen.');
    }
    
    /**
     * Show auto-topup settings page
     */
    public function autoTopup(Request $request)
    {
        $user = Auth::guard('portal')->user();
        $companyId = $this->getCompanyId($user);
        
        // Check permissions
        if (!$this->canManageBilling($user)) {
            abort(403, 'Sie haben keine Berechtigung, die Auto-Aufladung zu verwalten.');
        }

        // Get auto-topup settings via MCP
        $data = $this->executeMCPTask('getAutoTopupSettings', [
            'company_id' => $companyId
        ]);

        // Store data for React
        session()->flash('auto_topup_data', $data['result']);

        return app(ReactDashboardController::class)->index();
    }
    
    /**
     * Update auto-topup settings
     */
    public function updateAutoTopup(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // Prevent admin actions
        if (session('is_admin_viewing')) {
            return back()->with('error', 'Als Administrator können Sie die Auto-Aufladung nicht für Kunden aktivieren.');
        }
        
        // Check permissions
        if (!$this->canManageBilling($user)) {
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
        
        // Update via MCP
        $result = $this->executeMCPTask('updateAutoTopupSettings', [
            'user_id' => $user->id,
            'settings' => $validated
        ]);

        if ($result['result']['success'] ?? false) {
            return redirect()->route('business.billing.auto-topup')
                ->with('success', 'Die Auto-Aufladung Einstellungen wurden erfolgreich gespeichert.');
        }

        return back()->with('error', $result['result']['message'] ?? 'Fehler beim Speichern der Einstellungen.');
    }
    
    /**
     * Show payment methods page
     */
    public function paymentMethods(Request $request)
    {
        $user = Auth::guard('portal')->user();
        $companyId = $this->getCompanyId($user);
        
        // Check permissions
        if (!$this->canManageBilling($user)) {
            abort(403, 'Sie haben keine Berechtigung, die Zahlungsmethoden zu verwalten.');
        }

        // Get payment methods via MCP
        $data = $this->executeMCPTask('listPaymentMethods', [
            'company_id' => $companyId
        ]);

        // Store data for React
        session()->flash('payment_methods_data', $data['result']);

        return app(ReactDashboardController::class)->index();
    }
    
    /**
     * Add payment method page
     */
    public function addPaymentMethod(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // Prevent admin actions
        if (session('is_admin_viewing')) {
            return back()->with('error', 'Als Administrator können Sie keine Zahlungsmethoden für Kunden hinzufügen.');
        }
        
        // Check permissions
        if (!$this->canManageBilling($user)) {
            abort(403, 'Sie haben keine Berechtigung, Zahlungsmethoden hinzuzufügen.');
        }
        
        // Create setup intent via MCP
        $data = $this->executeMCPTask('createPaymentMethodSetup', [
            'user_id' => $user->id
        ]);
        
        // Store setup intent for React
        session()->flash('setup_intent_data', $data['result']);
        
        return app(ReactDashboardController::class)->index();
    }
    
    /**
     * Store payment method
     */
    public function storePaymentMethod(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // Prevent admin actions
        if (session('is_admin_viewing')) {
            return back()->with('error', 'Als Administrator können Sie keine Zahlungsmethoden für Kunden hinzufügen.');
        }
        
        // Check permissions
        if (!$this->canManageBilling($user)) {
            abort(403, 'Sie haben keine Berechtigung, Zahlungsmethoden hinzuzufügen.');
        }
        
        $request->validate([
            'payment_method_id' => 'required|string',
        ]);
        
        // Add payment method via MCP
        $result = $this->executeMCPTask('addPaymentMethod', [
            'user_id' => $user->id,
            'payment_method_id' => $request->payment_method_id
        ]);

        if ($result['result']['success'] ?? false) {
            return redirect()->route('business.billing.payment-methods')
                ->with('success', 'Zahlungsmethode wurde erfolgreich hinzugefügt.');
        }

        return back()->with('error', $result['result']['message'] ?? 'Die Zahlungsmethode konnte nicht hinzugefügt werden.');
    }
    
    /**
     * Delete payment method
     */
    public function deletePaymentMethod(Request $request, $paymentMethodId)
    {
        $user = Auth::guard('portal')->user();
        
        // Prevent admin actions
        if (session('is_admin_viewing')) {
            return back()->with('error', 'Als Administrator können Sie keine Zahlungsmethoden für Kunden löschen.');
        }
        
        // Check permissions
        if (!$this->canManageBilling($user)) {
            abort(403, 'Sie haben keine Berechtigung, Zahlungsmethoden zu löschen.');
        }
        
        // Delete via MCP
        $result = $this->executeMCPTask('removePaymentMethod', [
            'user_id' => $user->id,
            'payment_method_id' => $paymentMethodId
        ]);

        if ($result['result']['success'] ?? false) {
            return redirect()->route('business.billing.payment-methods')
                ->with('success', 'Zahlungsmethode wurde erfolgreich entfernt.');
        }

        return back()->with('error', $result['result']['message'] ?? 'Die Zahlungsmethode konnte nicht entfernt werden.');
    }

    /**
     * Get company ID for current context
     */
    protected function getCompanyId($user): ?int
    {
        if (session('is_admin_viewing')) {
            return session('admin_impersonation.company_id');
        }
        
        return $user ? $user->company_id : null;
    }

    /**
     * Check if user can view billing
     */
    protected function canViewBilling($user): bool
    {
        if (session('is_admin_viewing')) {
            return true;
        }
        
        return $user && $user->canViewBilling();
    }

    /**
     * Check if user can manage billing
     */
    protected function canManageBilling($user): bool
    {
        if (session('is_admin_viewing')) {
            return false; // Admins can view but not modify
        }
        
        return $user && $user->canManageBilling();
    }
}